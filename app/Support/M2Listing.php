<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class M2Listing
{
    public static function paginate(Builder $query, array $filters, bool $outages, bool $public = false): LengthAwarePaginator
    {
        if (filled($filters['q'] ?? null)) {
            $search = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($filters['q'])).'%';
            // Group OR clauses so visibility and ownership restrictions remain effective.
            $query->where(function (Builder $searchQuery) use ($search, $outages) {
                $searchQuery->whereRaw("description LIKE ? ESCAPE '!'", [$search]);
                if ($outages) {
                    $searchQuery->orWhereHas('quartier', fn ($q) => $q
                        ->whereRaw("nom LIKE ? ESCAPE '!'", [$search])
                        ->orWhereRaw("ville LIKE ? ESCAPE '!'", [$search]));
                } else {
                    $searchQuery->orWhereRaw("adresse LIKE ? ESCAPE '!'", [$search]);
                }
            });
        }
        if (filled($filters['statut'] ?? null)) {
            $query->where('statut', $filters['statut']);
        }
        if ($public && filled($filters['type'] ?? null)) {
            $query->where('type', $filters['type']);
        }
        $direction = ($filters['sort'] ?? 'recent') === 'oldest' ? 'asc' : 'desc';
        // Only these fixed columns can be sorted; no column comes from the request.
        $query->orderBy($public ? 'date_debut' : 'created_at', $direction)->orderBy('id', $direction);
        $paginator = $query->paginate(10)->withQueryString();
        if ($paginator->currentPage() > $paginator->lastPage()) {
            $paginator = $query->paginate(10, ['*'], 'page', $paginator->lastPage())->withQueryString();
        }

        return $paginator;
    }
}
