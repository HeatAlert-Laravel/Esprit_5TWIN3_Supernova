@extends('layouts.app')
@section('title', __('Add category'))
@section('content')
<x-ha.page-header :title="__('Add category')" :description="__('Group advice into clear topics that residents can browse.')" :breadcrumbs="[[__('Dashboard'), route('admin.dashboard')], [__('Advice categories'), route('admin.categorie-conseils.index')], [__('Add category'), null]]" />
<x-ha.error-summary />
<form method="POST" action="{{ route('admin.categorie-conseils.store') }}" class="ha-form">
    <div class="ha-card">@include('pages.admin.categorie-conseils._form')</div>
</form>
@endsection
