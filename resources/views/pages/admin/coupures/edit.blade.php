@extends('layouts.app')
@section('title', __('Edit outage'))
@section('content')
<x-ha.page-header :title="__('Edit outage')" />
<x-ha.error-summary />
<form novalidate method="POST" action="{{ route('admin.coupures.update', $coupure) }}" class="ha-form"><div class="ha-card">@include('pages.admin.coupures._form')</div></form>
@endsection

