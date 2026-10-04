@extends('layouts.app')
@section('title', 'Edit profile')
@section('content')
<x-ha.page-header title="Edit profile" :description="'Update the household details for '.$profile->user->name.'.'" :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Profiles', route('admin.profiles.index')], [$profile->user->name, route('admin.profiles.show', $profile)], ['Edit', null]]" />
<x-ha.error-summary />
<form novalidate method="POST" action="{{ route('admin.profiles.update', $profile) }}" class="ha-form">
    <div class="ha-card">@include('pages.admin.profiles._form')</div>
</form>
@endsection
