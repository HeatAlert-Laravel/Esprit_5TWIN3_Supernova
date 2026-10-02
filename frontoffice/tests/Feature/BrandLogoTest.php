<?php

use Illuminate\Support\Facades\Blade;

// Shelter logo: x-logo component, favicon/app-icon declarations and the replaced warning-triangle mark.
test('x-logo renders a unique mask id per instance and links each sun to its own mask', function () {
    $html = Blade::render('<x-logo /><x-logo variant="dark" :size="96" :wordmark="false" /><x-logo variant="mono" :size="28" />');

    preg_match_all('/<mask id="(ha-cut-[a-z0-9]+)"/', $html, $masks);
    preg_match_all('/mask="url\(#(ha-cut-[a-z0-9]+)\)"/', $html, $refs);

    expect($masks[1])->toHaveCount(3)->and(array_unique($masks[1]))->toHaveCount(3)
        ->and($refs[1])->toBe($masks[1])
        ->and($html)->not->toContain('id="ha-cut"')->and($html)->not->toContain('<img');
});

test('x-logo variants use the approved colors and wordmark rules', function () {
    $light = Blade::render('<x-logo />');
    expect($light)->toContain('fill="#E8590C"')->toContain('fill="#0F3D3E"')->toContain('color: #0F3D3E')
        ->toContain('HeatAlert')->toContain('--ha-logo-size: 36px')->toContain('aria-hidden="true"');

    $dark = Blade::render('<x-logo variant="dark" :size="32" />');
    expect($dark)->toContain('fill="#DCEFEC"')->toContain('color: #FFFFFF')->toContain('--ha-logo-size: 32px');

    $mono = Blade::render('<x-logo variant="mono" />');
    expect($mono)->toContain('fill="currentColor"')->not->toContain('#E8590C');

    $iconOnly = Blade::render('<x-logo :wordmark="false" />');
    expect($iconOnly)->not->toContain('ha-logo__word')->toContain('aria-label="HeatAlert"');

    // The icon never shrinks below the 16px brand minimum.
    expect(Blade::render('<x-logo :size="4" />'))->toContain('--ha-logo-size: 16px');
});

test('home navbar and auth pages show the Shelter logo, not the old warning triangle', function () {
    $home = $this->get('/')->assertOk();
    $home->assertSee('aria-label="HeatAlert home"', false)->assertSee('href="'.route('home').'"', false)
        ->assertSee('rel="icon" href="/favicon.svg"', false)->assertSee('href="/favicon-32.png"', false)
        ->assertSee('rel="apple-touch-icon" href="/apple-touch-icon.png"', false)
        ->assertSee('name="theme-color" content="#0F3D3E"', false)
        ->assertDontSee('heat-alert-mark', false)->assertDontSee('#f97316', false);
    expect(substr_count($home->getContent(), 'rel="icon"'))->toBe(2);

    foreach (['login', 'register'] as $page) {
        $html = $this->get(route($page))->assertOk()->getContent();
        // light lockup above the form + dark mark-only on the Shade panel
        expect(substr_count($html, 'class="ha-logo'))->toBeGreaterThanOrEqual(3)->and($html)->toContain('--ha-logo-size: 96px');
    }
    $this->get(route('password.request'))->assertOk()->assertSee('--ha-logo-size: 28px', false);
    $this->get(route('password.reset', ['token' => 'x', 'email' => 'a@b.c']))->assertOk()->assertSee('--ha-logo-size: 28px', false);
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
