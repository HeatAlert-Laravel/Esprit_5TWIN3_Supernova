<?php

use App\Http\Middleware\NormalizeAuthEmail;
use App\Http\Middleware\PrivateAccountResponse;
use Laravel\Fortify\Features;

return [
    'guard' => 'web',
    'passwords' => 'users',
    'username' => 'email',
    'email' => 'email',
    'lowercase_usernames' => true,
    'home' => '/my-profile',
    'prefix' => '',
    'domain' => null,
    'middleware' => ['web', NormalizeAuthEmail::class, PrivateAccountResponse::class],
    // Fortify's login pipeline limits failed email + IP attempts to 5/minute
    // and clears its LoginRateLimiter after a successful authentication.
    'limiters' => ['login' => null, 'two-factor' => 'two-factor', 'verification' => '6,1'],
    'views' => true,
    'features' => [
        Features::registration(),
        Features::resetPasswords(),
        Features::emailVerification(),
        Features::updateProfileInformation(),
        Features::updatePasswords(),
        Features::twoFactorAuthentication(['confirm' => true, 'confirmPassword' => true]),
    ],
];
