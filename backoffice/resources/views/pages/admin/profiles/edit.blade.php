@extends('layouts.app')
@section('title', 'Edit profile')
@section('content')
<h1 class="mb-6 text-3xl font-bold dark:text-white">Edit profile</h1><form method="POST" action="{{ route('admin.profiles.update', $profile) }}" class="max-w-2xl rounded-xl bg-white p-6 shadow-sm dark:bg-gray-900 dark:text-white">@include('pages.admin.profiles._form')</form>
@endsection
