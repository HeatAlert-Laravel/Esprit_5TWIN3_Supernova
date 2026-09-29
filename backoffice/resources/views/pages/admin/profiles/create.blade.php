@extends('layouts.app')
@section('title', 'Add profile')
@section('content')
<h1 class="mb-6 text-3xl font-bold dark:text-white">Add profile</h1><form method="POST" action="{{ route('admin.profiles.store') }}" class="max-w-2xl rounded-xl bg-white p-6 shadow-sm dark:bg-gray-900 dark:text-white">@include('pages.admin.profiles._form', ['profile' => null])</form>
@endsection
