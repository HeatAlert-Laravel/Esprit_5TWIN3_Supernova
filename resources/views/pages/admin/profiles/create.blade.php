@extends('layouts.app')
@section('title', 'Add profile')
@section('content')
<x-ha.page-header title="Add profile" description="Link a household profile to an existing resident account." :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Profiles', route('admin.profiles.index')], ['Add profile', null]]" />
<x-ha.error-summary />
<form novalidate method="POST" action="{{ route('admin.profiles.store') }}" class="ha-form">
    <div class="ha-card">@include('pages.admin.profiles._form', ['profile' => null])</div>
</form>
@endsection
