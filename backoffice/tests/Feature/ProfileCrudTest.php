<?php

use App\Models\Profile;
use App\Models\SensitiveEquipment;
use App\Models\User;

// Back Office Profile management (supporting functionality of Module 5). In-memory SQLite.
beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
    $this->admin = User::factory()->create(['role' => 'ADMIN']);
});

function profilePayload(User $user, array $overrides = []): array
{
    return array_merge([
        'user_id' => $user->id, 'phone' => '+216 20 123 456', 'address' => '10 Main Street',
        'neighborhood' => 'Carthage', 'has_fragile_person' => '0',
    ], $overrides);
}

test('guest and USER cannot manage profiles', function () {
    $profile = Profile::factory()->create();
    $user = User::factory()->create(['role' => 'USER']);

    $this->get(route('admin.profiles.index'))->assertRedirect(route('login'));
    $this->actingAs($user);
    $this->get(route('admin.profiles.index'))->assertForbidden();
    $this->get(route('admin.profiles.create'))->assertForbidden();
    $this->post(route('admin.profiles.store'), profilePayload($user))->assertForbidden();
    $this->get(route('admin.profiles.show', $profile))->assertForbidden();
    $this->get(route('admin.profiles.edit', $profile))->assertForbidden();
    $this->put(route('admin.profiles.update', $profile), profilePayload($profile->user))->assertForbidden();
    $this->delete(route('admin.profiles.destroy', $profile))->assertForbidden();
    $this->assertDatabaseHas('profiles', ['id' => $profile->id]);
});

test('index lists residents readably with equipment counts and a delete action', function () {
    $profile = Profile::factory()->create(['neighborhood' => 'Carthage']);
    SensitiveEquipment::factory()->count(2)->for($profile)->create();

    $response = $this->actingAs($this->admin)->get(route('admin.profiles.index'))->assertOk()
        ->assertSeeInOrder(['Resident', 'Neighborhood', 'Details', 'Equipment', 'Updated'])
        ->assertSee($profile->user->name)->assertSee($profile->user->email)->assertSee('Carthage')
        ->assertSee('Delete '.$profile->user->name);

    expect($response->viewData('profiles')->first()->sensitive_equipments_count)->toBe(2);
    $response->assertDontSee('Profile ID')->assertDontSee('User ID');
});

test('create form only offers residents without a profile', function () {
    $free = User::factory()->create(['role' => 'USER', 'name' => 'Free Resident']);
    Profile::factory()->create(['user_id' => User::factory()->create(['name' => 'Taken Resident'])->id]);

    $this->actingAs($this->admin)->get(route('admin.profiles.create'))->assertOk()
        ->assertSee('Free Resident')->assertDontSee('Taken Resident')->assertSee('Save profile');
});

test('store creates a profile, shows it, and update edits it', function () {
    $resident = User::factory()->create(['role' => 'USER']);
    $this->actingAs($this->admin);

    $this->post(route('admin.profiles.store'), profilePayload($resident, ['has_fragile_person' => '1']))->assertRedirect()->assertSessionHas('status', 'Profile created.');
    $profile = $resident->fresh()->profile;
    expect($profile->address)->toBe('10 Main Street')->and($profile->has_fragile_person)->toBeTrue();

    $this->get(route('admin.profiles.show', $profile))->assertOk()
        ->assertSee($resident->name)->assertSee($resident->email)->assertSee('10 Main Street')->assertSee('+216 20 123 456')->assertSee('Carthage');
    $this->get(route('admin.profiles.edit', $profile))->assertOk()->assertSee('value="10 Main Street"', false)->assertSee($resident->email);

    $this->put(route('admin.profiles.update', $profile), profilePayload($resident, ['address' => '22 New Road', 'has_fragile_person' => '0']))
        ->assertRedirect(route('admin.profiles.show', $profile))->assertSessionHas('status', 'Profile updated.');
    expect($profile->fresh()->address)->toBe('22 New Road')->and($profile->fresh()->has_fragile_person)->toBeFalse();
});

test('profile validation covers required fields, phone format, lengths and uniqueness', function () {
    $resident = User::factory()->create(['role' => 'USER']);
    $taken = Profile::factory()->create();
    $this->actingAs($this->admin);

    $this->post(route('admin.profiles.store'), [])->assertSessionHasErrors(['user_id', 'phone', 'address', 'neighborhood']);
    $this->post(route('admin.profiles.store'), profilePayload($resident, ['user_id' => 987654]))->assertSessionHasErrors('user_id');
    $this->post(route('admin.profiles.store'), profilePayload($taken->user))->assertSessionHasErrors('user_id'); // one profile per account
    $this->post(route('admin.profiles.store'), profilePayload($resident, ['phone' => 'call me']))->assertSessionHasErrors('phone');
    $this->post(route('admin.profiles.store'), profilePayload($resident, ['phone' => '123']))->assertSessionHasErrors('phone');
    $this->post(route('admin.profiles.store'), profilePayload($resident, ['address' => str_repeat('a', 256)]))->assertSessionHasErrors('address');
    $this->post(route('admin.profiles.store'), profilePayload($resident, ['neighborhood' => str_repeat('a', 101)]))->assertSessionHasErrors('neighborhood');
    $this->post(route('admin.profiles.store'), profilePayload($resident, ['has_fragile_person' => 'maybe']))->assertSessionHasErrors('has_fragile_person');
    $this->assertDatabaseMissing('profiles', ['user_id' => $resident->id]);

    // Accepted phone formats.
    foreach (['12345678', '+216 20 123 456', '(71) 123-456'] as $phone) {
        $other = User::factory()->create(['role' => 'USER']);
        $this->post(route('admin.profiles.store'), profilePayload($other, ['phone' => $phone]))->assertSessionHasNoErrors();
    }

    // An update may keep its own account.
    $this->put(route('admin.profiles.update', $taken), profilePayload($taken->user))->assertSessionHasNoErrors();
});

test('profile errors are shown with the typed values preserved', function () {
    $resident = User::factory()->create(['role' => 'USER']);

    $this->actingAs($this->admin)->from(route('admin.profiles.create'))
        ->post(route('admin.profiles.store'), profilePayload($resident, ['phone' => 'abc', 'neighborhood' => 'Le Bardo']))
        ->assertRedirect(route('admin.profiles.create'))->assertSessionHasInput('neighborhood', 'Le Bardo');
    $this->get(route('admin.profiles.create'))->assertOk()->assertSee('Le Bardo')->assertSee('The phone field format is invalid.');
});

test('a profile that still owns equipment cannot be deleted', function () {
    $profile = Profile::factory()->create();
    SensitiveEquipment::factory()->count(2)->for($profile)->create();

    $this->actingAs($this->admin)->delete(route('admin.profiles.destroy', $profile))
        ->assertRedirect(route('admin.profiles.show', $profile))
        ->assertSessionHas('error', 'This profile still has 2 sensitive equipment records. Delete or reassign them before deleting the profile.');
    $this->assertDatabaseHas('profiles', ['id' => $profile->id]);
    $this->assertDatabaseCount('sensitive_equipments', 2);

    $this->get(route('admin.profiles.show', $profile))->assertOk()->assertSee('This profile still has 2 sensitive equipment records.');
});

test('a profile without equipment can be deleted and its resident account is kept', function () {
    $profile = Profile::factory()->create();

    $this->actingAs($this->admin)->delete(route('admin.profiles.destroy', $profile))
        ->assertRedirect(route('admin.profiles.index'))->assertSessionHas('status', 'Profile deleted.');
    $this->assertDatabaseMissing('profiles', ['id' => $profile->id]);
    $this->assertDatabaseHas('users', ['id' => $profile->user_id]);
});

test('profile detail is readable and never shows technical identifiers', function () {
    $profile = Profile::factory()->create();
    SensitiveEquipment::factory()->for($profile)->create();

    $this->actingAs($this->admin)->get(route('admin.profiles.show', $profile))->assertOk()
        ->assertSee($profile->user->name)->assertSee($profile->user->email)
        ->assertDontSee('Profile ID')->assertDontSee('User ID')->assertSee('Created')->assertSee('Last updated');
});

test('profile list filters automatically and keeps filters in pagination', function () {
    Profile::factory()->count(12)->create(['neighborhood' => 'Carthage']);
    Profile::factory()->create(['neighborhood' => 'Le Bardo']);

    $this->actingAs($this->admin);
    $this->get(route('admin.profiles.index', ['neighborhood' => 'Carthage']))->assertOk()
        ->assertSee('data-auto-filter', false)->assertSee('neighborhood=Carthage&amp;page=2', false)->assertSee('Reset filters');
    $this->get(route('admin.profiles.index'))->assertOk()->assertDontSee('Reset filters');
});
