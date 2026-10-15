@extends('layouts.app')
@section('title', 'Edit equipment')
@section('content')
<div class="ha-form">
    <x-ha.page-header title="Edit sensitive equipment" :description="'Update the details for '.$equipment->name.'.'" :breadcrumbs="[['My Profile', route('my-profile')], [$equipment->name, null]]" />
    <x-ha.error-summary />
    <form novalidate method="POST" action="{{ route('profile.equipment.update', $equipment) }}" class="ha-card">
        @include('sensitive-equipments._resident-form')
    </form>
</div>
@endsection
