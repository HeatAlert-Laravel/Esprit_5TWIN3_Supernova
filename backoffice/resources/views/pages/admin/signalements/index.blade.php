@extends('layouts.app')
@section('title', __('Reports'))
@section('content')
<x-ha.page-header :title="__('Reports')"><x-slot:actions><a class="ha-btn ha-btn--primary" href="{{ route('admin.signalements.create') }}">{{ __('Add report') }}</a></x-slot:actions></x-ha.page-header>
<x-ha.error-summary />
<div class="ha-table-wrap"><table class="ha-table"><thead><tr><th>{{ __('Resident') }}</th><th>{{ __('Associated outage') }}</th><th>{{ __('Address') }}</th><th>{{ __('Description') }}</th><th>{{ __('Status') }}</th><th>{{ __('Actions') }}</th></tr></thead><tbody>
@forelse($signalements as $signalement)<tr><td>{{ $signalement->user->name }}</td><td>{{ $signalement->coupure ? '#'.$signalement->coupure->id.' — '.$signalement->coupure->quartier->nom : __('None') }}</td><td>{{ $signalement->adresse }}</td><td>{{ $signalement->description }}</td><td>{{ $signalement->statut }}
<form novalidate method="POST" action="{{ route('admin.signalements.statut', $signalement) }}">@csrf @method('PATCH')
<label class="sr-only" for="statut-{{ $signalement->id }}">{{ __('Status') }}</label><select class="ha-select" id="statut-{{ $signalement->id }}" name="statut">@foreach(\App\Models\Signalement::STATUTS as $statut)<option @selected($signalement->statut === $statut)>{{ $statut }}</option>@endforeach</select><button class="ha-btn ha-btn--secondary ha-btn--sm">{{ __('Update status') }}</button></form></td><td>
<a class="ha-btn ha-btn--ghost ha-btn--sm" href="{{ route('admin.signalements.show', $signalement) }}">{{ __('View') }}</a>
<a class="ha-btn ha-btn--ghost ha-btn--sm" href="{{ route('admin.signalements.edit', $signalement) }}">{{ __('Edit') }}</a>
<form novalidate method="POST" action="{{ route('admin.signalements.destroy', $signalement) }}" class="inline">@csrf @method('DELETE')<button class="ha-btn ha-btn--danger-soft ha-btn--sm">{{ __('Delete') }}</button></form>
</td></tr>@empty<tr><td colspan="6">{{ __('No records') }}</td></tr>@endforelse
</tbody></table></div><div class="ha-pagination">{{ $signalements->links() }}</div>
@endsection

