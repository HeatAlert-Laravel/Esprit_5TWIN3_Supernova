@extends('layouts.app')
@section('title', 'Edit point type')
@section('content')
<x-ha.page-header :title="'Edit: ' . $type->nom" description="Update information for this cooling place category." :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Point types', route('admin.type-points.index')], [$type->nom, route('admin.type-points.show', $type)], ['Edit', null]]" />
<x-ha.error-summary />
<form method="POST" action="{{ route('admin.type-points.update', $type) }}" class="ha-form">
    <div class="ha-card">@include('pages.admin.type-points._form', ['type' => $type])</div>
</form>
@endsection
