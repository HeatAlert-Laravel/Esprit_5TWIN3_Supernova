@extends('layouts.app')
@section('title', 'Add equipment type')
@section('content')
<x-ha.page-header title="Add equipment type" description="Define a kind of equipment, its risk level and what it is sensitive to." :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Equipment types', route('admin.type-equipements.index')], ['Add type', null]]" />
<x-ha.error-summary />
<form novalidate method="POST" action="{{ route('admin.type-equipements.store') }}" class="ha-form">
    <div class="ha-card">@include('pages.admin.type-equipements._form', ['type' => null])</div>
</form>
@endsection
