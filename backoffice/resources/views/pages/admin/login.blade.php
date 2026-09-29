<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin login | HeatAlert</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-orange-50 text-gray-900 dark:bg-gray-950 dark:text-white">
    <main class="mx-auto flex min-h-screen max-w-md items-center px-5 py-10">
        <div class="w-full rounded-2xl bg-white p-8 shadow-sm dark:bg-gray-900">
            <div class="mb-6 flex items-center gap-3">
                <img src="{{ Vite::asset('resources/images/heat-alert-mark.svg') }}" alt="" class="h-10 w-10">
                <div><p class="text-sm font-semibold text-orange-700 dark:text-orange-300">HeatAlert</p><h1 class="text-2xl font-bold">Admin login</h1></div>
            </div>
            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf
                <div><label for="email" class="block text-sm font-medium">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="mt-2 w-full rounded-lg border border-gray-300 px-4 py-3 dark:border-gray-700 dark:bg-gray-800">@error('email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label for="password" class="block text-sm font-medium">Password</label><input id="password" name="password" type="password" required class="mt-2 w-full rounded-lg border border-gray-300 px-4 py-3 dark:border-gray-700 dark:bg-gray-800">@error('password')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <button class="w-full rounded-lg bg-orange-600 px-5 py-3 font-semibold text-white hover:bg-orange-700">Log in</button>
            </form>
            <a href="{{ config('app.frontoffice_url') }}" class="mt-6 inline-block text-sm font-medium text-orange-700 dark:text-orange-300">← Public site</a>
        </div>
    </main>
</body>
</html>
