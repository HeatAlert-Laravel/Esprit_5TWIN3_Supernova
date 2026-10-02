@extends('layouts.front')
@section('title', $title)
@section('content')
<div class="ha-form">
    <x-ha.page-header :title="$title" eyebrow="HeatAlert" :breadcrumbs="[['Home', route('home')], [$title, null]]" />
    <div class="ha-card">
        <x-ha.empty-state icon="clock" :title="$title.' is coming soon'" :description="'This section is reserved for the team member building the '.strtolower($title).' module. No live information is available here yet.'">
            <a class="ha-btn ha-btn--outline" href="{{ route('home') }}"><x-ha.icon name="arrow-left" size="sm" />Back home</a>
        </x-ha.empty-state>
    </div>
</div>
@endsection
