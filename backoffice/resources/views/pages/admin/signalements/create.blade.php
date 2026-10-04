@extends('layouts.app')
@section('title', __('Add report'))
@section('content')
<x-ha.page-header :title="__('Add report')" />
<x-ha.error-summary />
<form novalidate method="POST" action="{{ route('admin.signalements.store') }}" class="ha-form"><div class="ha-card">@include('pages.admin.signalements._form', ['signalement' => null])</div></form>
@endsection

