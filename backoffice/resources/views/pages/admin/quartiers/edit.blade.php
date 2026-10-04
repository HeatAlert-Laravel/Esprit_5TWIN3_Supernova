@extends('layouts.app')
@section('title', 'Edit neighborhood')
@section('content')
<x-ha.page-header title="Edit neighborhood" :description="'Update '.$quartier->nom.'.'" :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Neighborhoods', route('admin.quartiers.index')], [$quartier->nom, null]]" />
<x-ha.error-summary />
<form novalidate method="POST" action="{{ route('admin.quartiers.update', $quartier) }}" class="ha-form">
    <div class="ha-card">@include('pages.admin.quartiers._form')</div>
</form>
@endsection
