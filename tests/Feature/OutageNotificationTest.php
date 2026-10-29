<?php

use App\Models\Coupure;
use App\Models\Quartier;
use App\Models\Signalement;
use App\Models\User;
use App\Notifications\OutageStatusNotification;
use App\Services\OutageNotifications;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->withoutVite();
    $this->artisan('migrate', ['--force' => true]);
});

function notificationOutagePayload(array $overrides = []): array
{
    return array_replace([
        'quartier_id' => Quartier::factory()->create()->id, 'type' => 'en cours', 'statut' => 'active',
        'date_debut' => '2026-10-29 10:00:00', 'date_fin_estimee' => null, 'description' => 'Power supply interrupted.',
    ], $overrides);
}

function storedOutageNotification(User $user, Coupure $outage, string $event = 'active')
{
    $user->notify(new OutageStatusNotification([
        'outage_id' => $outage->id, 'event' => $event,
        'title' => $event === 'resolved' ? 'Outage Resolved' : 'New Outage Alert',
        'message' => 'Outage status update.', 'area' => 'Example neighborhood',
        'outage_type' => 'Ongoing', 'status' => $event === 'resolved' ? 'Resolved' : 'Active',
        'starts_at' => '2026-10-29T10:00:00+00:00',
    ]));

    return $user->notifications()->latest('created_at')->firstOrFail();
}

test('admin creation atomically stores one database alert for every existing USER but no admin', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $resident = User::factory()->create(['role' => 'USER']);
    $unverified = User::factory()->unverified()->create(['role' => 'USER']);
    $payload = notificationOutagePayload();
    $this->actingAs($admin)->post(route('admin.coupures.store'), $payload)->assertRedirect();
    $outage = Coupure::firstOrFail();
    foreach ([$resident, $unverified] as $user) {
        $notification = $user->notifications()->sole();
        expect($notification->type)->toBe(OutageStatusNotification::class)
            ->and($notification->read_at)->toBeNull()
            ->and($notification->data['outage_id'])->toBe($outage->id)
            ->and($notification->data['event'])->toBe('active')
            ->and($notification->data['title'])->toBe('New Outage Alert')
            ->and($notification->data['area'])->toContain($outage->quartier->nom)
            ->and($notification->data['outage_type'])->toBe('Ongoing');
    }
    expect($admin->notifications()->count())->toBe(0);
    $this->assertDatabaseCount('notifications', 2);
});

test('creating a resolved outage does not send an active alert', function () {
    User::factory()->create(['role' => 'USER']);
    $this->actingAs(User::factory()->create(['role' => 'ADMIN']))
        ->post(route('admin.coupures.store'), notificationOutagePayload(['statut' => 'résolue']))->assertRedirect();
    $this->assertDatabaseCount('notifications', 0);
});

test('activation and resolution send one alert per transition while repeated edits do not duplicate', function () {
    $user = User::factory()->create(['role' => 'USER']);
    $outage = Coupure::factory()->create(['statut' => 'résolue']);
    $payload = notificationOutagePayload(['quartier_id' => $outage->quartier_id]);
    $this->actingAs(User::factory()->create(['role' => 'ADMIN']));
    $this->put(route('admin.coupures.update', $outage), $payload)->assertRedirect();
    expect($user->notifications()->count())->toBe(1);
    $payload['description'] = 'Updated outage information.';
    $this->put(route('admin.coupures.update', $outage), $payload)->assertRedirect();
    expect($user->notifications()->count())->toBe(1);
    $payload['statut'] = 'résolue';
    $this->put(route('admin.coupures.update', $outage), $payload)->assertRedirect();
    $this->put(route('admin.coupures.update', $outage), $payload)->assertRedirect();
    expect($user->notifications()->count())->toBe(2);
    $resolution = $user->notifications()->get()->first(fn ($n) => $n->data['event'] === 'resolved');
    expect($resolution->data['title'])->toBe('Outage Resolved')
        ->and($resolution->data['message'])->toBe('The reported outage has been resolved.');
});

test('a failed delivery rolls back both the outage and already inserted notifications', function () {
    User::factory()->create(['role' => 'USER']);
    $this->app->instance(OutageNotifications::class, new class extends OutageNotifications
    {
        public function send(Coupure $coupure): void
        {
            parent::send($coupure);
            throw new RuntimeException('Simulated database delivery failure');
        }
    });
    $this->actingAs(User::factory()->create(['role' => 'ADMIN']))->withoutExceptionHandling();
    try {
        $this->post(route('admin.coupures.store'), notificationOutagePayload());
        $this->fail('The delivery failure must propagate.');
    } catch (RuntimeException $failure) {
        expect($failure->getMessage())->toBe('Simulated database delivery failure');
    }
    $this->assertDatabaseCount('coupures', 0);
    $this->assertDatabaseCount('notifications', 0);
});

test('USER inbox and bell show only owned notifications and their real unread count', function () {
    $user = User::factory()->create(['role' => 'USER']);
    $other = User::factory()->create(['role' => 'USER']);
    $outage = Coupure::factory()->create(['statut' => 'active']);
    storedOutageNotification($user, $outage);
    $otherItem = storedOutageNotification($other, $outage);
    $this->actingAs($user)->getJson(route('outage-notifications.index'))->assertOk()
        ->assertJsonPath('unread_count', 1)->assertJson(fn ($json) => $json->has('html')->etc())
        ->assertDontSee($otherItem->id);
    $this->get(route('my-reports'))->assertOk()->assertSee('data-outage-notifications', false)->assertSee('New Outage Alert');
    $this->actingAs(User::factory()->create(['role' => 'ADMIN']))->get(route('outages'))->assertOk()->assertDontSee('data-outage-notifications', false);
});

test('individual reads are idempotent and cannot update another USER notification', function () {
    $user = User::factory()->create(['role' => 'USER']);
    $other = User::factory()->create(['role' => 'USER']);
    $outage = Coupure::factory()->create();
    $own = storedOutageNotification($user, $outage);
    $foreign = storedOutageNotification($other, $outage);
    $this->actingAs($user)->patchJson(route('outage-notifications.read', $own->id))->assertOk()->assertJsonPath('unread_count', 0);
    $readAt = $own->fresh()->read_at;
    $this->patchJson(route('outage-notifications.read', $own->id))->assertOk();
    expect($own->fresh()->read_at->equalTo($readAt))->toBeTrue();
    $this->patchJson(route('outage-notifications.read', $foreign->id))->assertNotFound();
    $this->postJson(route('outage-notifications.open', $foreign->id))->assertNotFound();
    expect($foreign->fresh()->read_at)->toBeNull();
});

test('mark all reads only the current USER outage notifications', function () {
    $user = User::factory()->create(['role' => 'USER']);
    $other = User::factory()->create(['role' => 'USER']);
    $outage = Coupure::factory()->create();
    $own = storedOutageNotification($user, $outage);
    $foreign = storedOutageNotification($other, $outage);
    $otherFeature = $user->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'AnotherFeature', 'data' => []]);
    $this->actingAs($user)->patchJson(route('outage-notifications.read-all'))->assertOk()->assertJsonPath('unread_count', 0);
    expect($own->fresh()->read_at)->not->toBeNull()
        ->and($foreign->fresh()->read_at)->toBeNull()
        ->and($otherFeature->fresh()->read_at)->toBeNull();
});

test('opening a resolution notification marks it read and leads to safe resident details', function () {
    $user = User::factory()->create(['role' => 'USER']);
    $outage = Coupure::factory()->create(['statut' => 'résolue']);
    $item = storedOutageNotification($user, $outage, 'resolved');
    Signalement::factory()->create(['coupure_id' => $outage->id, 'description' => 'PRIVATE_REPORT_DO_NOT_SHOW']);
    $this->actingAs($user)->post(route('outage-notifications.open', $item->id))->assertRedirect(route('outages.show', $outage));
    expect($item->fresh()->read_at)->not->toBeNull();
    $this->get(route('outages.show', $outage))->assertOk()->assertSee('Resolved')->assertDontSee('PRIVATE_REPORT_DO_NOT_SHOW');
    $this->actingAs(User::factory()->create(['role' => 'USER']))->get(route('outages.show', $outage))->assertNotFound();
});

test('deleted outages are handled without a broken notification link', function () {
    $user = User::factory()->create(['role' => 'USER']);
    $outage = Coupure::factory()->create();
    $item = storedOutageNotification($user, $outage);
    $outage->delete();
    $this->actingAs($user)->post(route('outage-notifications.open', $item->id))->assertRedirect(route('outages'))->assertSessionHas('status', 'This outage is no longer available.');
    expect($item->fresh()->read_at)->not->toBeNull();
});

test('empty inboxes and limited recent lists work with complete unread counts', function () {
    $user = User::factory()->create(['role' => 'USER']);
    $this->actingAs($user)->getJson(route('outage-notifications.index'))->assertOk()->assertJsonPath('unread_count', 0)->assertSee('No notifications yet');
    $outage = Coupure::factory()->create();
    for ($i = 0; $i < 12; $i++) {
        storedOutageNotification($user, $outage);
    }
    $this->getJson(route('outage-notifications.index'))->assertOk()->assertJsonPath('unread_count', 12);
    expect(\App\Support\OutageNotificationInbox::for($user)['recentNotifications'])->toHaveCount(10);
});

test('notification endpoints require authentication USER role and verified email', function ($method, $route) {
    $id = (string) Str::uuid();
    $params = in_array($route, ['outage-notifications.read', 'outage-notifications.open']) ? [$id] : [];
    $url = route($route, $params);
    $this->json($method, $url)->assertUnauthorized();
    $this->actingAs(User::factory()->create(['role' => 'ADMIN']))->json($method, $url)->assertForbidden();
    $this->actingAs(User::factory()->unverified()->create(['role' => 'USER']))->json($method, $url)->assertForbidden();
})->with([
    ['GET', 'outage-notifications.index'],
    ['PATCH', 'outage-notifications.read'],
    ['PATCH', 'outage-notifications.read-all'],
    ['POST', 'outage-notifications.open'],
]);

test('USER cannot create an outage to trigger alerts and invalid admin creation writes nothing', function () {
    $this->actingAs(User::factory()->create(['role' => 'USER']))->postJson(route('admin.coupures.store'), notificationOutagePayload())->assertForbidden();
    $this->actingAs(User::factory()->create(['role' => 'ADMIN']))->postJson(route('admin.coupures.store'), notificationOutagePayload(['statut' => 'invalid']))->assertUnprocessable();
    $this->assertDatabaseCount('coupures', 0);
    $this->assertDatabaseCount('notifications', 0);
});
