@extends('layouts.app')
@section('title', 'Add weather alert')
@section('content')
<x-ha.page-header title="Add weather alert" description="Create a heat alert and attach it to a neighborhood." :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Weather alerts', route('admin.alertes-meteo.index')], ['Add alert', null]]" />
<x-ha.error-summary />
<form novalidate method="POST" action="{{ route('admin.alertes-meteo.store') }}" class="ha-form"><div class="ha-card">@include('pages.admin.alertes-meteo._form', ['alerte' => null])</div></form>
@endsection