@extends('layouts.app')
@section('title', 'Edit weather alert')
@section('content')
<x-ha.page-header title="Edit weather alert" :description="'Update '.$alerte->titre.'.'" :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Weather alerts', route('admin.alertes-meteo.index')], [$alerte->titre, route('admin.alertes-meteo.show', $alerte)], ['Edit', null]]" />
<x-ha.error-summary />
<form novalidate method="POST" action="{{ route('admin.alertes-meteo.update', $alerte) }}" class="ha-form"><div class="ha-card">@include('pages.admin.alertes-meteo._form')</div></form>
@endsection