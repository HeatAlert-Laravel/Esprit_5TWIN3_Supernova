<?php

test('the application returns a successful response', function () {
    $this->artisan('migrate', ['--force' => true]);
    $response = $this->get('/');

    $response->assertStatus(200);
});

test('the admin area sends guests to the login page', function () {
    $this->get('/admin')->assertRedirect(route('login'));
});
