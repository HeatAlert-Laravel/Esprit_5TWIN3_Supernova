<?php

use App\Models\Profile;
use App\Models\SensitiveEquipment;
use App\Models\TypeEquipement;
use App\Models\User;
use App\Services\HouseholdRiskService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
    config([
        'services.mistral.key' => 'test-mistral-key',
        'services.mistral.model' => 'ministral-8b-latest',
    ]);
    $this->type = fn (string $name): TypeEquipement => TypeEquipement::where('name', $name)->firstOrFail();
});

function householdAiResponse(array $overrides = []): array
{
    $content = array_replace([
        'summary' => 'Votre foyer présente une exposition modérée pendant les fortes chaleurs.',
        'risk_factors' => ['Un équipement médical dépend de l’électricité.'],
        'priority_actions' => [
            ['priority' => 1, 'action' => 'Préparez une solution de secours pour l’appareil médical.'],
        ],
    ], $overrides);

    return [
        'choices' => [[
            'finish_reason' => 'stop',
            'message' => ['role' => 'assistant', 'content' => json_encode($content, JSON_UNESCAPED_UNICODE)],
        ]],
    ];
}

function householdResident(array $profile = []): User
{
    $user = User::factory()->create(['name' => 'Resident Secret Name']);
    Profile::factory()->for($user)->create(array_replace([
        'phone' => '+216 55 123 456',
        'address' => '42 Secret Street',
        'neighborhood' => 'Secret Neighborhood',
        'has_fragile_person' => false,
    ], $profile));

    return $user;
}

test('guest is refused and unverified residents are redirected to verification', function () {
    $this->postJson(route('profile.ai-risk.analyze'))->assertUnauthorized();

    $unverified = User::factory()->unverified()->create();
    $this->actingAs($unverified)->post(route('profile.ai-risk.analyze'))
        ->assertRedirect(route('verification.notice'));

    Http::assertNothingSent();
});

test('a resident without a household profile is told to complete it and no AI call is made', function () {
    Http::fake();

    $response = $this->actingAs(User::factory()->create())
        ->postJson(route('profile.ai-risk.analyze'))
        ->assertStatus(422)
        ->assertJson(['ok' => false, 'reason' => 'missing_profile']);

    expect($response->json('message'))->toContain('profil de foyer');
    Http::assertNothingSent();
});

test('a profile without equipment still produces a local LOW assessment', function () {
    Http::fake(['api.mistral.ai/*' => Http::response(householdAiResponse())]);

    $user = householdResident();

    $this->actingAs($user)->postJson(route('profile.ai-risk.analyze'))
        ->assertOk()
        ->assertJson([
            'ok' => true,
            'score' => 0,
            'level' => 'LOW',
            'equipment_count' => 0,
            'has_fragile_person' => false,
            'ai_available' => true,
        ])
        ->assertJsonPath('factors.0.label', 'Aucun équipement sensible enregistré');
});

test('a profile with equipment returns the equipment/type business data and a HIGH level', function () {
    Http::fake(['api.mistral.ai/*' => Http::response(householdAiResponse())]);

    $user = householdResident(['has_fragile_person' => true]);
    SensitiveEquipment::factory()->for($user->profile)->ofType(($this->type)('Medical equipment'))->create(['name' => 'Home oxygen unit']);

    $this->actingAs($user)->postJson(route('profile.ai-risk.analyze'))
        ->assertOk()
        ->assertJson([
            'ok' => true,
            'score' => 62,
            'level' => 'HIGH',
            'equipment_count' => 1,
            'has_fragile_person' => true,
        ]);
});

test('personal data is never sent to the AI provider', function () {
    Http::fake(['api.mistral.ai/*' => Http::response(householdAiResponse())]);

    $user = householdResident();
    SensitiveEquipment::factory()->for($user->profile)->ofType(($this->type)('Refrigerator'))->create([
        'name' => 'Private fridge label',
        'description' => 'Private medical note about the resident.',
    ]);

    $this->actingAs($user)->postJson(route('profile.ai-risk.analyze'))->assertOk();

    Http::assertSent(function (Request $request) use ($user) {
        $body = $request->body();
        foreach ([
            'Resident Secret Name', $user->email, '+216 55 123 456',
            '42 Secret Street', 'Secret Neighborhood', 'Private fridge label',
            'Private medical note about the resident.',
        ] as $private) {
            expect($body)->not->toContain($private);
        }

        $payload = json_decode($request->data()['messages'][1]['content'], true);
        expect($payload['equipment'][0])->toHaveKeys([
            'type', 'risk_level', 'sensitive_to_heat', 'sensitive_to_outage', 'has_notes',
        ])->not->toHaveKey('name')->not->toHaveKey('description');

        return true;
    });
});

test('the local score is deterministic and reproducible without the AI', function () {
    $user = householdResident(['has_fragile_person' => true]);
    SensitiveEquipment::factory()->for($user->profile)->ofType(($this->type)('Refrigerator'))->create();
    $service = app(HouseholdRiskService::class);
    $profile = $user->profile;

    expect($service->assess($profile))->toBe($service->assess($profile))
        ->and($service->assess($profile)['score'])->toBe(57);
});

test('explicit thresholds produce LOW MEDIUM and HIGH levels', function () {
    $service = app(HouseholdRiskService::class);

    $low = householdResident(['has_fragile_person' => false]);
    SensitiveEquipment::factory()->for($low->profile)->ofType(($this->type)('Other'))->create();

    $medium = householdResident(['has_fragile_person' => false]);
    SensitiveEquipment::factory()->for($medium->profile)->ofType(($this->type)('Refrigerator'))->create();

    $high = householdResident(['has_fragile_person' => true]);
    SensitiveEquipment::factory()->for($high->profile)->ofType(($this->type)('Medical equipment'))->create();

    expect($service->assess($low->profile)['level'])->toBe('LOW')
        ->and($service->assess($low->profile)['score'])->toBe(5)
        ->and($service->assess($medium->profile)['level'])->toBe('MEDIUM')
        ->and($service->assess($medium->profile)['score'])->toBe(27)
        ->and($service->assess($high->profile)['level'])->toBe('HIGH')
        ->and($service->assess($high->profile)['score'])->toBe(62);
});

test('a valid Mistral JSON answer is exposed without changing the local score or level', function () {
    Http::fake(['api.mistral.ai/*' => Http::response(householdAiResponse())]);

    $user = householdResident(['has_fragile_person' => false]);
    SensitiveEquipment::factory()->for($user->profile)->ofType(($this->type)('Refrigerator'))->create();

    $this->actingAs($user)->postJson(route('profile.ai-risk.analyze'))
        ->assertOk()
        ->assertJson([
            'score' => 27,
            'level' => 'MEDIUM',
            'ai_available' => true,
            'ai' => [
                'summary' => 'Votre foyer présente une exposition modérée pendant les fortes chaleurs.',
                'risk_factors' => ['Un équipement médical dépend de l’électricité.'],
                'priority_actions' => [
                    ['priority' => 1, 'action' => 'Préparez une solution de secours pour l’appareil médical.'],
                ],
            ],
        ]);
});

test('invalid JSON from Mistral falls back to the local deterministic analysis', function () {
    Http::fake(['api.mistral.ai/*' => Http::response([
        'choices' => [['finish_reason' => 'stop', 'message' => ['role' => 'assistant', 'content' => '{not json']]],
    ])]);

    $user = householdResident();

    $this->actingAs($user)->postJson(route('profile.ai-risk.analyze'))
        ->assertOk()
        ->assertJson([
            'ok' => true,
            'score' => 0,
            'level' => 'LOW',
            'ai' => null,
            'ai_available' => false,
        ])
        ->assertJsonPath('message', 'L’analyse IA est temporairement indisponible.');
});

test('an unexpected AI structure is rejected instead of trusted', function () {
    Http::fake(['api.mistral.ai/*' => Http::response(householdAiResponse([
        'priority_actions' => [['priority' => 'first', 'action' => 'Do something']],
    ]))]);

    $this->actingAs(householdResident())->postJson(route('profile.ai-risk.analyze'))
        ->assertOk()
        ->assertJson(['ai' => null, 'ai_available' => false]);
});

test('a missing API key keeps the feature working with the local fallback', function () {
    config(['services.mistral.key' => null]);
    Http::fake();

    $user = householdResident(['has_fragile_person' => true]);

    $this->actingAs($user)->postJson(route('profile.ai-risk.analyze'))
        ->assertOk()
        ->assertJson(['score' => 30, 'level' => 'MEDIUM', 'ai' => null, 'ai_available' => false])
        ->assertJsonCount(3, 'local_actions');

    Http::assertNothingSent();
});

test('a provider timeout never returns a server error', function () {
    Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));

    $this->actingAs(householdResident())->postJson(route('profile.ai-risk.analyze'))
        ->assertOk()
        ->assertJson(['ok' => true, 'ai' => null, 'ai_available' => false]);
});

test('provider authentication quota and server errors fall back locally', function (int $status) {
    Http::fake(['api.mistral.ai/*' => Http::response(['error' => 'provider failure'], $status)]);

    $this->actingAs(householdResident())->postJson(route('profile.ai-risk.analyze'))
        ->assertOk()
        ->assertJson(['ok' => true, 'ai' => null, 'ai_available' => false, 'score' => 0]);
})->with([401, 429, 500, 503]);

test('the AI endpoint is rate limited per resident', function () {
    Http::fake(['api.mistral.ai/*' => Http::response(householdAiResponse())]);

    $user = householdResident();
    $this->actingAs($user);

    for ($i = 0; $i < 5; $i++) {
        $this->postJson(route('profile.ai-risk.analyze'))->assertOk();
    }

    $this->postJson(route('profile.ai-risk.analyze'))->assertStatus(429);
});

test('a resident cannot analyse another household by sending profile or user identifiers', function () {
    Http::fake(['api.mistral.ai/*' => Http::response(householdAiResponse())]);

    $owner = householdResident();
    SensitiveEquipment::factory()->for($owner->profile)->ofType(($this->type)('Refrigerator'))->create();

    $other = householdResident();
    SensitiveEquipment::factory()->for($other->profile)->ofType(($this->type)('Medical equipment'))->count(2)->create();

    $this->actingAs($other)->postJson(route('profile.ai-risk.analyze'), [
        'profile_id' => $owner->profile->id,
        'user_id' => $owner->id,
    ])->assertOk()
        ->assertJson(['equipment_count' => 2, 'score' => 64, 'level' => 'HIGH']);

    $withoutProfile = User::factory()->create();
    $this->actingAs($withoutProfile)->postJson(route('profile.ai-risk.analyze'), [
        'profile_id' => $owner->profile->id,
        'user_id' => $owner->id,
    ])->assertStatus(422)->assertJson(['reason' => 'missing_profile']);

    Http::assertSent(function (Request $request) {
        $payload = json_decode($request->data()['messages'][1]['content'], true);
        expect($payload['household']['sensitive_equipment_count'])->toBe(2)
            ->and(collect($payload['equipment'])->pluck('type')->unique()->all())->toBe(['Medical equipment']);

        return true;
    });
});

test('HTML or script returned by the AI is stripped and never rendered raw', function () {
    Http::fake(['api.mistral.ai/*' => Http::response(householdAiResponse([
        'summary' => '<script>alert(1)</script>Résumé sûr',
        'risk_factors' => ['<b>Facteur</b> important'],
        'priority_actions' => [['priority' => 1, 'action' => '<img src=x onerror=alert(1)>Buvez de l’eau']],
    ]))]);

    $user = householdResident();
    $response = $this->actingAs($user)->postJson(route('profile.ai-risk.analyze'))->assertOk();

    expect($response->json('ai.summary'))->toBe('alert(1)Résumé sûr')
        ->and($response->json('ai.risk_factors.0'))->toBe('Facteur important')
        ->and($response->json('ai.priority_actions.0.action'))->toBe('Buvez de l’eau')
        ->and($response->getContent())->not->toContain('<script')
        ->and($response->getContent())->not->toContain('onerror');

    $this->get(route('my-profile'))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
});

test('the household profile page exposes the AI card and the local indicators', function () {
    $user = householdResident(['has_fragile_person' => true]);
    SensitiveEquipment::factory()->for($user->profile)->ofType(($this->type)('Refrigerator'))->create();

    $this->actingAs($user)->get(route('my-profile'))
        ->assertOk()
        ->assertSee('Analyse intelligente du risque du foyer')
        ->assertSee('Analyser mon foyer')
        ->assertSee(route('profile.ai-risk.analyze'), false)
        ->assertSee('Les recommandations IA sont fournies à titre préventif');
});

test('the AI card explains that the household profile is required first', function () {
    $this->actingAs(User::factory()->create())->get(route('my-profile'))
        ->assertOk()
        ->assertSee('Complétez d’abord votre profil de foyer')
        ->assertDontSee('Analyser mon foyer');
});
