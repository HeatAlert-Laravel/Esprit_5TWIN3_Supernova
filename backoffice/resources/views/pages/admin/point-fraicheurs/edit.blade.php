@extends('layouts.app')
@section('title', 'Edit cooling point')
@section('content')
<x-ha.page-header :title="'Edit: ' . $point->nom" description="Update location information, hours and accessibility." :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Cooling points', route('admin.point-fraicheurs.index')], [$point->nom, route('admin.point-fraicheurs.show', $point)], ['Edit', null]]" />
<x-ha.error-summary />
<form method="POST" action="{{ route('admin.point-fraicheurs.update', $point) }}" class="ha-form">
    <div class="ha-card">@include('pages.admin.point-fraicheurs._form', ['point' => $point])</div>
</form>
@endsection
