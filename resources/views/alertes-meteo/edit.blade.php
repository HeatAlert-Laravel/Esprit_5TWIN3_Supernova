@extends('layouts.admin')
@section('title', 'Edit weather alert')
@section('content')
<x-ha.page-header title="Edit weather alert" :description="'Update '.$alerte->titre.'.'" :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Weather alerts', route('admin.alertes-meteo.index')], [$alerte->titre, route('admin.alertes-meteo.show', $alerte)], ['Edit', null]]" />
@if($alerte->generee_auto)<div class="ha-auto-notice" role="note"><x-ha.icon name="zap" /><div><strong>Generated automatically</strong><p>Saving your changes turns this alert into a manual one: the automatic generation will no longer update or withdraw it.</p></div></div>@endif
<form method="POST" action="{{ route('admin.alertes-meteo.update', $alerte) }}" class="ha-form"><div class="ha-card">@include('alertes-meteo._form')</div></form>
@endsection