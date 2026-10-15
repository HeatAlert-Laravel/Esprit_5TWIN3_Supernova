@extends('layouts.app')
@section('title', 'Add cooling point')
@section('content')
<x-ha.page-header title="Add cooling point" description="Record a new cooling location for residents." :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Cooling points', route('admin.point-fraicheurs.index')], ['Add point', null]]" />
<x-ha.error-summary />
<form method="POST" action="{{ route('admin.point-fraicheurs.store') }}" class="ha-form">
    <div class="ha-card">@include('pages.admin.point-fraicheurs._form', ['point' => null])</div>
</form>
@endsection
