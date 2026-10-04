@extends('layouts.app')
@section('title', 'Add point type')
@section('content')
<x-ha.page-header title="Add point type" description="Define a new category of cooling location for residents." :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Point types', route('admin.type-points.index')], ['Add type', null]]" />
<x-ha.error-summary />
<form method="POST" action="{{ route('admin.type-points.store') }}" class="ha-form">
    <div class="ha-card">@include('pages.admin.type-points._form', ['type' => null])</div>
</form>
@endsection
