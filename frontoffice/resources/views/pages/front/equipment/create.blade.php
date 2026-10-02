@extends('layouts.front')
@section('title', 'Add equipment')
@section('content')
<div class="ha-form">
    <x-ha.page-header title="Add sensitive equipment" description="This equipment will be linked to your household profile." :breadcrumbs="[['My Profile', route('my-profile')], ['Add equipment', null]]" />
    <x-ha.error-summary />
    <form method="POST" action="{{ route('profile.equipment.store') }}" class="ha-card">
        @include('pages.front.equipment._form', ['equipment' => null])
    </form>
</div>
@endsection
