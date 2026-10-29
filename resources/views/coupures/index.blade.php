@extends('layouts.admin')
@section('title', __('Outages'))
@section('content')
<x-ha.page-header :title="__('Outages')"><x-slot:actions><a class="ha-btn ha-btn--primary" href="{{ route('admin.coupures.create') }}">{{ __('Add outage') }}</a></x-slot:actions></x-ha.page-header>
<x-ha.error-summary />
@include('coupures._filters')
<p class="mb-4">{{ $coupures->total() }} results — page {{ $coupures->currentPage() }} / {{ $coupures->lastPage() }}</p>
<div class="ha-table-wrap"><table class="ha-table"><thead><tr><th>ID</th><th>{{ __('Neighborhood') }}</th><th>{{ __('Type') }}</th><th>{{ __('Status') }}</th><th>{{ __('Start date') }}</th><th>Created</th><th>{{ __('Estimated end') }}</th><th>{{ __('Description') }}</th><th>{{ __('Reports') }}</th><th>{{ __('Actions') }}</th></tr></thead><tbody>
@forelse($coupures as $coupure)<tr><td>#{{ $coupure->id }}</td><td>{{ $coupure->quartier->nom }}</td><td>{{ __($coupure->type) }}</td><td>{{ __($coupure->statut) }}</td><td>{{ $coupure->date_debut->format('d/m/Y H:i') }}</td><td>{{ $coupure->created_at?->format('d/m/Y H:i') }}</td><td>{{ $coupure->date_fin_estimee?->format('d/m/Y H:i') ?? __('Unknown') }}</td><td>{{ $coupure->description }}</td><td>{{ $coupure->signalements_count }}</td><td>
<a class="ha-btn ha-btn--ghost ha-btn--sm" href="{{ route('admin.coupures.show', $coupure) }}">{{ __('View') }}</a>
<a class="ha-btn ha-btn--ghost ha-btn--sm" href="{{ route('admin.coupures.edit', $coupure) }}">{{ __('Edit') }}</a>
<form novalidate method="POST" action="{{ route('admin.coupures.destroy', $coupure) }}" class="inline">@csrf @method('DELETE')<button class="ha-btn ha-btn--danger-soft ha-btn--sm">{{ __('Delete') }}</button></form>
</td></tr>@empty<tr><td colspan="10">{{ __('No records') }}</td></tr>@endforelse
</tbody></table></div><div class="ha-pagination">{{ $coupures->links('coupures._pagination') }}</div>
@endsection

