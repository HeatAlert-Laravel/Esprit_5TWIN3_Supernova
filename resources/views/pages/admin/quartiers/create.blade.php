@extends('layouts.app')
@section('title', 'Add neighborhood')
@section('content')
<x-ha.page-header title="Add neighborhood" description="Create a neighborhood that can be used by weather alerts and other HeatAlert modules." :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Neighborhoods', route('admin.quartiers.index')], ['Add neighborhood', null]]" />
<form method="POST" action="{{ route('admin.quartiers.store') }}" class="ha-form">
    <div class="ha-card">@include('pages.admin.quartiers._form', ['quartier' => null])</div>
</form>
@endsection
