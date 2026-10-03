@extends('layouts.app')
@section('title', 'Edit equipment type')
@section('content')
<x-ha.page-header title="Edit equipment type" :description="'Update '.$type->name.'. Changes apply to every equipment of this type.'" :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Equipment types', route('admin.type-equipements.index')], [$type->name, route('admin.type-equipements.show', $type)], ['Edit', null]]" />
<x-ha.error-summary />
<form method="POST" action="{{ route('admin.type-equipements.update', $type) }}" class="ha-form">
    <div class="ha-card">@include('pages.admin.type-equipements._form')</div>
</form>
@endsection
