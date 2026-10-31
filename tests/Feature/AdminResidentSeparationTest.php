<?php

use App\Models\Profile;
use App\Models\SensitiveEquipment;
use App\Models\User;

beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
});

test('an admin is redirected from every resident page to the admin dashboard', function (string $method, string $routeName) {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $equipment = SensitiveEquipment::factory()->create();

    $this->actingAs($admin)->{$method}(route($routeName, ['equipment' => $equipment]))
        ->assertRedirect(route('admin.dashboard'));
})->with([
    ['get', 'my-profile'],
    ['put', 'my-profile.update'],
    ['get', 'profile.equipment.create'],
    ['post', 'profile.equipment.store'],
    ['get', 'profile.equipment.edit'],
    ['put', 'profile.equipment.update'],
    ['delete', 'profile.equipment.destroy'],
    ['post', 'profile.ai-risk.analyze'],
]);

test('a resident can reach every household page', function (string $routeName) {
    $resident = User::factory()->create(['role' => 'USER']);
    $profile = Profile::factory()->for($resident)->create();
    SensitiveEquipment::factory()->for($profile)->create();

    $this->actingAs($resident)->get(route($routeName))->assertOk();
})->with(['my-profile', 'profile.equipment.create']);

test('the admin navigation never links back to the public or resident area', function () {
    $this->actingAs(User::factory()->create(['role' => 'ADMIN']))
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('Public site')
        ->assertDontSee('href="'.route('home').'"', false)
        ->assertDontSee('href="'.route('home').'/"', false)
        ->assertDontSee('href="'.route('my-profile').'"', false)
        ->assertSee('href="'.route('admin.dashboard').'"', false);
});

test('the public navigation offers the household profile to residents only', function () {
    $this->actingAs(User::factory()->create(['role' => 'USER']))
        ->get(route('home'))
        ->assertOk()
        ->assertSee('My Profile');

    $this->actingAs(User::factory()->create(['role' => 'ADMIN']))
        ->get(route('home'))
        ->assertOk()
        ->assertDontSee('My Profile');
});

test('the resident profile page never links to the admin area', function () {
    $this->actingAs(User::factory()->create(['role' => 'USER']))
        ->get(route('my-profile'))
        ->assertOk()
        ->assertDontSee(route('admin.dashboard'), false);
});

test('the account page adapts its links to the role', function () {
    $this->actingAs(User::factory()->create(['role' => 'ADMIN']))
        ->get(route('account.edit'))
        ->assertOk()
        ->assertSee('Compte et sécurité')
        ->assertSee('Compte administrateur')
        ->assertDontSee('Retour au profil du foyer');

    $this->actingAs(User::factory()->create(['role' => 'USER']))
        ->get(route('account.edit'))
        ->assertOk()
        ->assertSee('Retour au profil du foyer')
        ->assertSee('profil du foyer');
});

test('the profile and account pages use the coherent French titles and cross-links', function () {
    $resident = User::factory()->create(['role' => 'USER', 'name' => 'Résidente Test']);

    $this->actingAs($resident)->get(route('my-profile'))
        ->assertOk()
        ->assertSee('Profil du foyer')
        ->assertSee('Espace résident')
        ->assertSee('Résidente Test')
        ->assertSee('Gérer mon compte et ma sécurité')
        ->assertSee(route('account.edit'), false);

    $this->actingAs($resident)->get(route('account.edit'))
        ->assertOk()
        ->assertSee('Compte et sécurité')
        ->assertSee(route('my-profile'), false);
});
