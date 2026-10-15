<?php

use App\Models\User;
use Illuminate\Support\Facades\Blade;

// Shelter logo: x-logo component, favicon/app-icon declarations and the replaced warning-triangle mark.
beforeEach(fn () => $this->artisan('migrate', ['--force' => true]));

test('x-logo renders a unique mask id per instance and links each sun to its own mask', function () {
    $html = Blade::render('<x-logo variant="dark" /><x-logo variant="dark" :wordmark="false" /><x-logo />');

    preg_match_all('/<mask id="(ha-cut-[a-z0-9]+)"/', $html, $masks);
    preg_match_all('/mask="url\(#(ha-cut-[a-z0-9]+)\)"/', $html, $refs);

    expect($masks[1])->toHaveCount(3)->and(array_unique($masks[1]))->toHaveCount(3)
        ->and($refs[1])->toBe($masks[1])
        ->and($html)->not->toContain('id="ha-cut"')->and($html)->not->toContain('<img');
});

test('x-logo variants use the approved colors and wordmark rules', function () {
    $dark = Blade::render('<x-logo variant="dark" :size="32" />');
    expect($dark)->toContain('fill="#E8590C"')->toContain('fill="#DCEFEC"')->toContain('color: #FFFFFF')
        ->toContain('--ha-logo-size: 32px')->toContain('aria-hidden="true"');

    expect(Blade::render('<x-logo />'))->toContain('fill="#0F3D3E"')->toContain('color: #0F3D3E');
    expect(Blade::render('<x-logo variant="mono" />'))->toContain('fill="currentColor"')->not->toContain('#E8590C');
    expect(Blade::render('<x-logo :size="4" />'))->toContain('--ha-logo-size: 16px');
});

test('admin sidebar logo links to the dashboard and the layout declares the favicons', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);

    foreach (['admin.dashboard', 'admin.profiles.index', 'admin.equipment.index'] as $route) {
        $this->actingAs($admin)->get(route($route))->assertOk()
            ->assertSee('aria-label="HeatAlert administration"', false)
            ->assertSee('href="'.route('admin.dashboard').'"', false)
            ->assertSee('rel="icon" href="/favicon.svg"', false)->assertSee('href="/favicon-32.png"', false)
            ->assertSee('rel="apple-touch-icon" href="/apple-touch-icon.png"', false)
            ->assertSee('name="theme-color" content="#0F3D3E"', false)
            ->assertDontSee('heat-alert-mark', false);
    }
    $this->post(route('logout'));

    $this->get(route('login'))->assertOk()->assertSee('aria-label="HeatAlert administration"', false)
        ->assertSee('rel="icon" href="/favicon.svg"', false)->assertSee('Admin login');
});

test('brand and favicon files exist and are valid in public', function () {
    foreach (['heatalert-mark', 'heatalert-mark-dark', 'heatalert-mark-mono'] as $svg) {
        expect(file_get_contents(public_path("images/brand/{$svg}.svg")))->toContain('<svg')->toContain('id="ha-cut"');
    }
    foreach (['favicon-16.png' => 16, 'favicon-32.png' => 32, 'apple-touch-icon.png' => 180, 'icon-192.png' => 192, 'icon-512.png' => 512] as $file => $px) {
        $size = getimagesize(public_path($file));
        expect([$size[0], $size[1], $size['mime']])->toBe([$px, $px, 'image/png']);
    }
    expect(public_path('favicon.svg'))->toBeFile();
});
