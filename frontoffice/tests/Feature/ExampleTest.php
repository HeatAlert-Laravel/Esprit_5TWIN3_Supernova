<?php

test('the application returns a successful response', function () {
    $this->artisan('migrate', ['--force' => true]);
    $response = $this->get('/');

    $response->assertStatus(200);
});
