@extends('layouts.app')
@section('title', __('Add outage'))
@section('content')
<x-ha.page-header :title="__('Add outage')" />
<x-ha.error-summary />
<form novalidate method="POST" action="{{ route('admin.coupures.store') }}" class="ha-form"><div class="ha-card">@include('pages.admin.coupures._form', ['coupure' => null])</div></form>
@endsection

