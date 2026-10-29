<?php

use App\Models\Conseil;
use App\Models\Profile;
use App\Models\SensitiveEquipment;
use App\Models\Signalement;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
    Notification::fake();
    Storage::fake('public');
});

// A real PNG fixture avoids requiring the optional GD extension just to create uploads.
function accountAvatarUpload(string $name = 'picture.png'): \Illuminate\Http\Testing\File
{
    return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aX1sAAAAASUVORK5CYII='));
}

test('account settings are private contain all sections and use initials without an avatar', function () {
    $this->get(route('account.edit'))->assertRedirect(route('login'));
    $user = User::factory()->create(['name' => 'Nadia Resident']);
    $this->actingAs($user)->get(route('account.edit'))->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertSee('Account information')->assertSee('Profile picture')->assertSee('Password')
        ->assertSee('Email verification')->assertSee('Two-factor authentication')->assertSee('Danger zone')
        ->assertSee('Verified')->assertSee('NR');
});

test('account name updates leave household profile and verification unchanged and ignore forged role', function () {
    $user = User::factory()->create(['role' => 'USER']);
    $profile = Profile::factory()->for($user)->create();
    $this->actingAs($user)->put(route('user-profile-information.update'), [
        'name' => 'New Name', 'email' => $user->email, 'role' => 'ADMIN', 'phone' => 'forged',
    ])->assertRedirect(route('account.edit'));
    expect($user->fresh()->name)->toBe('New Name')->and($user->fresh()->role)->toBe('USER')
        ->and($user->fresh()->hasVerifiedEmail())->toBeTrue()
        ->and($profile->fresh()->phone)->toBe($profile->phone);
    Notification::assertNothingSent();
});

test('email changes require current password invalidate verification remove old reset tokens and send a new verification', function () {
    $user = User::factory()->create(['password' => 'safe-password-123']);
    $oldEmail = $user->email;
    Password::broker()->createToken($user);
    $this->actingAs($user)->put(route('user-profile-information.update'), [
        'name' => $user->name, 'email' => 'NEW@example.test', 'current_password' => 'safe-password-123',
        'email_verified_at' => now(),
    ])->assertRedirect(route('verification.notice'));
    expect($user->fresh()->email)->toBe('new@example.test')->and($user->fresh()->email_verified_at)->toBeNull();
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $oldEmail]);
    Notification::assertSentTo($user, VerifyEmail::class);
    $this->get(route('my-profile'))->assertRedirect(route('verification.notice'));
});

test('account information rejects invalid or duplicate email and incorrect current password', function (array $data, string $field) {
    User::factory()->create(['email' => 'taken@example.test']);
    $user = User::factory()->create(['password' => 'safe-password-123']);
    $this->actingAs($user)->put(route('user-profile-information.update'), [
        'name' => 'Changed', 'email' => 'changed@example.test', 'current_password' => 'safe-password-123', ...$data,
    ])->assertSessionHasErrorsIn('updateProfileInformation', $field);
    expect($user->fresh()->email)->toBe($user->email)->and($user->fresh()->name)->toBe($user->name);
    Notification::assertNothingSent();
})->with([
    [['email' => 'invalid'], 'email'], [['email' => 'taken@example.test'], 'email'],
    [['name' => ''], 'name'], [['current_password' => 'wrong'], 'current_password'],
    [['current_password' => null], 'current_password'],
]);

test('password update validates current password strength and confirmation', function (array $invalid, string $field) {
    $user = User::factory()->create(['password' => 'safe-password-123']);
    $this->actingAs($user)->put(route('user-password.update'), array_replace([
        'current_password' => 'safe-password-123', 'password' => 'new-password-456', 'password_confirmation' => 'new-password-456',
    ], $invalid))->assertSessionHasErrorsIn('updatePassword', $field);
    expect(Hash::check('safe-password-123', $user->fresh()->password))->toBeTrue();
})->with([
    [['current_password' => 'wrong'], 'current_password'], [['current_password' => ''], 'current_password'],
    [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
    [['password_confirmation' => 'mismatch'], 'password'],
]);

test('password update rotates remember token and session and revokes other sessions and reset tokens', function () {
    $user = User::factory()->create(['password' => 'safe-password-123']);
    $other = User::factory()->create();
    Password::broker()->createToken($user);
    foreach (['own-other-session' => $user->id, 'unrelated-session' => $other->id] as $id => $userId) {
        DB::table('sessions')->insert(['id' => $id, 'user_id' => $userId, 'payload' => '', 'last_activity' => time()]);
    }
    $remember = $user->remember_token;
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])->get(route('account.edit'));
    $session = session()->getId();
    $this->put(route('user-password.update'), [
        'current_password' => 'safe-password-123', 'password' => 'new-password-456', 'password_confirmation' => 'new-password-456',
    ])->assertSessionHas('status', 'password-updated')->assertSessionMissing('auth.password_confirmed_at');
    expect(Hash::check('new-password-456', $user->fresh()->password))->toBeTrue()
        ->and($user->fresh()->remember_token)->not->toBe($remember)->and(session()->getId())->not->toBe($session);
    $this->assertDatabaseMissing('sessions', ['id' => 'own-other-session']);
    $this->assertDatabaseHas('sessions', ['id' => 'unrelated-session']);
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    $this->post(route('logout'));
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'safe-password-123'])->assertSessionHasErrors('email');
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'new-password-456'])->assertRedirect(route('my-profile'));
});

test('avatar upload replacement and removal use generated paths and clean old files', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('account.avatar.update'), [
        'avatar' => accountAvatarUpload('my-picture.png'),
    ])->assertRedirect(route('account.edit'));
    $first = $user->fresh()->avatar_path;
    expect($first)->toStartWith('avatars/')->not->toContain('my-picture');
    Storage::disk('public')->assertExists($first);
    $this->get(route('account.edit'))->assertSee(Storage::disk('public')->url($first));
    $this->post(route('account.avatar.update'), ['avatar' => accountAvatarUpload('replacement.png')])->assertRedirect();
    $second = $user->fresh()->avatar_path;
    expect($second)->not->toBe($first);
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);
    $this->delete(route('account.avatar.destroy'))->assertRedirect(route('account.edit'));
    expect($user->fresh()->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing($second);
});

test('avatar validation rejects non images svg and oversized uploads', function (string $kind) {
    $user = User::factory()->create();
    $file = match ($kind) {
        'text' => UploadedFile::fake()->create('script.php', 1, 'text/plain'),
        'svg' => UploadedFile::fake()->createWithContent('image.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
        'large' => accountAvatarUpload('large.png')->size(2049),
    };
    $this->actingAs($user)->post(route('account.avatar.update'), ['avatar' => $file])->assertSessionHasErrors('avatar');
    expect($user->fresh()->avatar_path)->toBeNull();
    expect(Storage::disk('public')->allFiles())->toBe([]);
})->with(['text', 'svg', 'large']);

test('avatar removal never deletes a file outside the generated avatars directory', function () {
    Storage::disk('public')->put('unrelated/important.png', 'keep');
    $user = User::factory()->create();
    $user->forceFill(['avatar_path' => 'unrelated/important.png'])->save();
    $this->actingAs($user)->delete(route('account.avatar.destroy'))->assertRedirect();
    Storage::disk('public')->assertExists('unrelated/important.png');
});

test('a failed avatar write preserves the previous database path', function () {
    $user = User::factory()->create();
    $user->forceFill(['avatar_path' => 'avatars/previous.png'])->save();
    $disk = Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
    $disk->shouldReceive('putFileAs')->once()->andReturn(false);
    Storage::shouldReceive('disk')->with('public')->andReturn($disk);
    $this->actingAs($user)->post(route('account.avatar.update'), ['avatar' => accountAvatarUpload()])
        ->assertSessionHasErrors('avatar');
    expect($user->fresh()->avatar_path)->toBe('avatars/previous.png');
});

test('account deletion requires recent password confirmation current password and explicit agreement', function () {
    $user = User::factory()->create(['password' => 'safe-password-123']);
    $this->actingAs($user)->delete(route('account.destroy'), ['password' => 'safe-password-123', 'confirmation' => '1'])
        ->assertRedirect(route('password.confirm'));
    $this->withSession(['auth.password_confirmed_at' => time()])->delete(route('account.destroy'), ['password' => 'wrong', 'confirmation' => '1'])
        ->assertSessionHasErrorsIn('deleteAccount', 'password');
    $this->delete(route('account.destroy'), ['password' => 'safe-password-123'])
        ->assertSessionHasErrorsIn('deleteAccount', 'confirmation');
    $this->assertDatabaseHas('users', ['id' => $user->id]);
});

test('account deletion follows existing FK cascades without deleting other users or shared business data', function () {
    $user = User::factory()->create(['password' => 'safe-password-123']);
    $other = User::factory()->create();
    $profile = Profile::factory()->for($user)->create();
    $equipment = SensitiveEquipment::factory()->for($profile)->create();
    $otherProfile = Profile::factory()->for($other)->create();
    $otherEquipment = SensitiveEquipment::factory()->for($otherProfile)->create();
    $article = Conseil::factory()->create();
    foreach ([$user, $other] as $resident) {
        $resident->savedConseils()->attach($article);
        $resident->conseilVotes()->create(['conseil_id' => $article->id, 'value' => 1]);
    }
    $report = Signalement::factory()->for($user)->create();
    $otherReport = Signalement::factory()->for($other)->create();
    DB::table('sessions')->insert(['id' => 'account-to-delete', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
    Password::broker()->createToken($user);
    Storage::disk('public')->put('avatars/account.png', 'image');
    $user->forceFill(['avatar_path' => 'avatars/account.png'])->save();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time(), 'private_marker' => 'remove'])->get(route('account.security'));
    $sessionId = session()->getId();
    $csrf = session()->token();
    $this->delete(route('account.destroy'), ['password' => 'safe-password-123', 'confirmation' => '1'])
        ->assertRedirect(route('home'))->assertSessionMissing('private_marker');
    $this->assertGuest();
    expect(session()->getId())->not->toBe($sessionId)->and(session()->token())->not->toBe($csrf);
    foreach (['users' => $user, 'profiles' => $profile, 'sensitive_equipments' => $equipment, 'signalements' => $report] as $table => $model) {
        $this->assertDatabaseMissing($table, ['id' => $model->id]);
    }
    foreach (['conseil_user', 'conseil_votes', 'sessions'] as $table) {
        $this->assertDatabaseMissing($table, ['user_id' => $user->id]);
    }
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    Storage::disk('public')->assertMissing('avatars/account.png');
    foreach (['users' => $other, 'profiles' => $otherProfile, 'sensitive_equipments' => $otherEquipment, 'signalements' => $otherReport, 'conseils' => $article] as $table => $model) {
        $this->assertDatabaseHas($table, ['id' => $model->id]);
    }
    $this->assertDatabaseHas('conseil_user', ['user_id' => $other->id]);
    $this->assertDatabaseHas('conseil_votes', ['user_id' => $other->id]);
    expect(DB::select('PRAGMA foreign_key_check'))->toBe([]);
});
