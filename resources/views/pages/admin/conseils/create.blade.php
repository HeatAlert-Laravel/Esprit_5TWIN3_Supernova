@extends('layouts.app')
@section('title', __('Add advice'))
@section('content')
<x-ha.page-header :title="__('Add advice')" :description="__('Help residents prepare with clear, practical advice.')" :breadcrumbs="[[__('Dashboard'), route('admin.dashboard')], [__('Advice'), route('admin.conseils.index')], [__('Add advice'), null]]" />
<x-ha.error-summary />
@if($categories->isEmpty())
    <div class="ha-card"><x-ha.empty-state icon="lightbulb" :title="__('Start with a category')" :description="__('Create a category first, then add your advice article.')"><a class="ha-btn ha-btn--primary" href="{{ route('admin.categorie-conseils.create') }}">{{ __('Add category') }}</a></x-ha.empty-state></div>
@else
<form method="POST" action="{{ route('admin.conseils.store') }}" class="ha-form">
    <div class="ha-card">@include('pages.admin.conseils._form')</div>
</form>
@endif
@endsection
