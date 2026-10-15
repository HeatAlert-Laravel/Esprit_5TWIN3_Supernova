@extends('layouts.admin')
@section('title', 'Edit neighborhood')
@section('content')
<x-ha.page-header title="Edit neighborhood" :description="'Update '.$quartier->nom.'.'" :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Neighborhoods', route('admin.quartiers.index')], [$quartier->nom, null]]" />
<form method="POST" action="{{ route('admin.quartiers.update', $quartier) }}" class="ha-form">
    <div class="ha-card">@include('quartiers._form')</div>
</form>
@endsection
