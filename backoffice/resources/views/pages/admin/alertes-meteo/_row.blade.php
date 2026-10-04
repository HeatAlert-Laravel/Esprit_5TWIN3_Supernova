<tr>
    <td><a class="ha-cell-person__name" href="{{ route('admin.alertes-meteo.show', $alerte) }}">{{ $alerte->titre }}</a></td>
    <td>{{ $alerte->quartier->nom }}</td>
    <td><x-ha.badge :variant="$alerte->levelBadgeVariant()">{{ ucfirst($alerte->niveau) }}</x-ha.badge></td>
    <td>{{ number_format($alerte->temperature_max, 1) }} °C</td>
    <td>{{ $alerte->date_debut->format('d/m/Y') }} - {{ $alerte->date_fin->format('d/m/Y') }}</td>
    <td><x-ha.badge :variant="$alerte->publiee ? 'success' : 'neutral'">{{ $alerte->publiee ? 'Published' : 'Draft' }}</x-ha.badge></td>
    <td><x-ha.badge :variant="$alerte->temporalStatusBadgeVariant()">{{ ucfirst($alerte->temporalStatus()) }}</x-ha.badge></td>
    <td class="ha-actions-cell">
        <a href="{{ route('admin.alertes-meteo.edit', $alerte) }}" class="ha-btn ha-btn--ghost ha-btn--sm"><x-ha.icon name="pencil" size="sm" />Edit</a>
        <form method="POST" action="{{ route('admin.alertes-meteo.destroy', $alerte) }}" data-confirm-subject="{{ $alerte->titre }}" data-confirm="Delete this weather alert? This cannot be undone." class="inline">
            @csrf @method('DELETE')
            <button class="ha-btn ha-btn--danger-soft ha-btn--sm"><x-ha.icon name="trash" size="sm" />Delete</button>
        </form>
    </td>
</tr>
