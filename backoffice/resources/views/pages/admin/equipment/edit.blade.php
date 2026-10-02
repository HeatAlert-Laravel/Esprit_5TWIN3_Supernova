@extends('layouts.app')
@section('title', 'Edit equipment')
@section('content')
<x-ha.page-header title="Edit sensitive equipment" :description="'Update the details for '.$equipment->name.'.'" :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Sensitive equipment', route('admin.equipment.index')], [$equipment->name, route('admin.equipment.show', $equipment)], ['Edit', null]]" />
<x-ha.error-summary />
<form method="POST" action="{{ route('admin.equipment.update', $equipment) }}" class="ha-form">
    <div class="ha-card">@include('pages.admin.equipment._form')</div>
</form>
@endsection
