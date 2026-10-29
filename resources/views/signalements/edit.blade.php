@extends('layouts.admin')
@section('title', __('Edit report'))
@section('content')
<x-ha.page-header :title="__('Edit report')" />
<x-ha.error-summary />
<form novalidate method="POST" action="{{ route('admin.signalements.update', $signalement) }}" class="ha-form"><div class="ha-card">@include('signalements._form')</div></form>
@endsection

