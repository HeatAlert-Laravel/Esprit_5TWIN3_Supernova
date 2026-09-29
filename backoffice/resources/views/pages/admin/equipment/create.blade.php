@extends('layouts.app')
@section('title', 'Add equipment')
@section('content')
<h1 class="mb-6 text-3xl font-bold dark:text-white">Add sensitive equipment</h1><form method="POST" action="{{ route('admin.equipment.store') }}" class="max-w-2xl rounded-xl bg-white p-6 shadow-sm dark:bg-gray-900 dark:text-white">@include('pages.admin.equipment._form', ['equipment' => null])</form>
@endsection
