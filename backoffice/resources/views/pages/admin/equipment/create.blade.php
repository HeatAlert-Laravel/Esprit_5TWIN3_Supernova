@extends('layouts.app')
@section('title', 'Add equipment')
@section('content')
<x-ha.page-header title="Add sensitive equipment" description="Record a device that needs power or cooling during hot weather." :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Sensitive equipment', route('admin.equipment.index')], ['Add equipment', null]]" />
<x-ha.error-summary />
<form novalidate method="POST" action="{{ route('admin.equipment.store') }}" class="ha-form">
    <div class="ha-card">@include('pages.admin.equipment._form', ['equipment' => null])</div>
</form>
@endsection
