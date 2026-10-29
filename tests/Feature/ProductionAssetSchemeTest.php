<?php

use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

afterEach(function () {
    URL::forceScheme(null);
    URL::forceRootUrl(null);
});

it('keeps asset URLs on HTTP for the private production URL', function () {
    config()->set('app.env', 'production');
    config()->set('app.url', 'http://192.168.124.133:8088');
    URL::setRequest(Request::create('http://127.0.0.1:8088/'));

    (new AppServiceProvider(app()))->boot();

    expect(asset('build/assets/app-test.css'))
        ->toBe('http://127.0.0.1:8088/build/assets/app-test.css');
});

it('uses HTTPS assets when the production URL is HTTPS', function () {
    config()->set('app.env', 'production');
    config()->set('app.url', 'https://heatalert.example.test');
    URL::setRequest(Request::create('http://127.0.0.1:8088/'));

    (new AppServiceProvider(app()))->boot();

    expect(asset('build/assets/app-test.css'))
        ->toBe('https://127.0.0.1:8088/build/assets/app-test.css');
});
