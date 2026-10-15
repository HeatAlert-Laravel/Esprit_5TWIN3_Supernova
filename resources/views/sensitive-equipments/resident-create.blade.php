@extends('layouts.app')
@section('title', 'Add equipment')
@section('content')
<div class="ha-form">
    <x-ha.page-header title="Add sensitive equipment" description="This equipment will be linked to your household profile." :breadcrumbs="[['My Profile', route('my-profile')], ['Add equipment', null]]" />
    <x-ha.error-summary />
    <form novalidate method="POST" action="{{ route('profile.equipment.store') }}" class="ha-card">
        @include('sensitive-equipments._resident-form', ['equipment' => null])
    </form>
</div>
@endsection
