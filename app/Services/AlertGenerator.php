<?php

namespace App\Services;

use App\Models\AlerteMeteo;
use App\Models\Quartier;
use App\Support\HeatLevel;
use RuntimeException;

/**
 * Creates, updates and withdraws weather alerts from the Open-Meteo forecast.
 *
 * Rules:
 *  - one automatic alert per neighborhood and per day, only for days above the green level;
 *  - a day already covered by a manual alert is skipped, and a manual edit never gets overwritten
 *    (editing an automatic alert makes it manual);
 *  - an automatic alert whose day turns green again is withdrawn (unpublished), never deleted;
 *  - an automatic alert an administrator unpublished is not published again.
 */
class AlertGenerator
{
    public const SOURCE = 'Open-Meteo (auto)';

    public const SIMULATION_SOURCE = 'Simulation (auto)';

    public function __construct(private OpenMeteoForecast $forecast) {}

    /**
     * @return array{
     *     rows: list<array{quartier: string, date: string, niveau: string, temperature: float, action: string, note: string}>,
     *     counts: array<string, int>,
     *     errors: list<string>,
     *     quartiers: int
     * }
     */
    public function run(bool $dryRun = false, bool $publish = true, int $days = 7, ?float $simulate = null, ?int $quartierId = null): array
    {
        $result = [
            'rows' => [],
            'counts' => ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'withdrawn' => 0, 'skipped' => 0],
            'errors' => [],
            'quartiers' => 0,
        ];

        $quartiers = Quartier::query()
            ->whereNotNull('latitude')->whereNotNull('longitude')
            ->when($quartierId !== null, fn ($query) => $query->whereKey($quartierId))
            ->orderBy('ville')->orderBy('nom')->get();

        $result['quartiers'] = $quartiers->count();

        foreach ($quartiers as $quartier) {
            try {
                $forecast = $this->forecast->forQuartier($quartier, $days);
            } catch (RuntimeException $exception) {
                $result['errors'][] = $quartier->nom.': '.$exception->getMessage();

                continue;
            }

            foreach ($forecast as $day) {
                $this->handleDay($result, $quartier, $day, $dryRun, $publish, $simulate);
            }
        }

        return $result;
    }

    private function handleDay(array &$result, Quartier $quartier, array $day, bool $dryRun, bool $publish, ?float $simulate): void
    {
        $max = $simulate ?? $day['temperature_max'];
        $level = HeatLevel::forTemperature($max);
        $date = $day['date'];

        $automatic = AlerteMeteo::where('quartier_id', $quartier->id)->where('generee_auto', true)->whereDate('date_debut', $date)->first();

        if ($level === 'vert') {
            // The forecast calmed down: withdraw a still-published automatic alert for today or a future day.
            if ($automatic && $automatic->publiee && $date >= today()->toDateString()) {
                if (! $dryRun) {
                    $automatic->update(['publiee' => false]);
                }
                $this->record($result, 'withdrawn', $quartier, $date, $automatic->niveau, $max, 'forecast is back to green');
            }

            return;
        }

        $covered = AlerteMeteo::where('quartier_id', $quartier->id)->where('generee_auto', false)
            ->whereDate('date_debut', '<=', $date)->whereDate('date_fin', '>=', $date)->exists();

        if ($covered) {
            $this->record($result, 'skipped', $quartier, $date, $level, $max, 'a manual alert already covers this day');

            return;
        }

        $attributes = $this->attributes($quartier, $day, $level, $max, $simulate !== null);

        if ($automatic) {
            $automatic->fill($attributes);

            if (! $automatic->isDirty()) {
                $this->record($result, 'unchanged', $quartier, $date, $level, $max, '');

                return;
            }

            if (! $dryRun) {
                $automatic->save();
            }
            $this->record($result, 'updated', $quartier, $date, $level, $max, '');

            return;
        }

        if (! $dryRun) {
            AlerteMeteo::create($attributes + [
                'quartier_id' => $quartier->id,
                'date_debut' => $date,
                'date_fin' => $date,
                'publiee' => $publish,
                'generee_auto' => true,
            ]);
        }
        $this->record($result, 'created', $quartier, $date, $level, $max, $publish ? 'published' : 'draft');
    }

    private function attributes(Quartier $quartier, array $day, string $level, float $max, bool $simulated): array
    {
        return [
            'titre' => AlerteMeteo::labelFor($level).' heat alert - '.$quartier->nom,
            'type' => $level === 'rouge' ? 'canicule' : 'vague_de_chaleur',
            'niveau' => $level,
            'temperature_max' => $max,
            'temperature_min' => $simulated ? $max - 9 : $day['temperature_min'],
            'temperature_ressentie' => $simulated ? $max + 3 : $day['temperature_ressentie'],
            'humidite' => $day['humidite'],
            'indice_uv' => $day['indice_uv'],
            'vitesse_vent' => $day['vitesse_vent'],
            'risque_coupure' => HeatLevel::risksOutage($level),
            'source' => $simulated ? self::SIMULATION_SOURCE : self::SOURCE,
        ];
    }

    private function record(array &$result, string $action, Quartier $quartier, string $date, string $level, float $temperature, string $note): void
    {
        $result['counts'][$action]++;
        $result['rows'][] = ['quartier' => $quartier->nom, 'date' => $date, 'niveau' => $level, 'temperature' => $temperature, 'action' => $action, 'note' => $note];
    }
}
