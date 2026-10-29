<?php

namespace App\Console\Commands;

use App\Models\AlerteMeteo;
use App\Services\AlertGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateWeatherAlerts extends Command
{
    protected $signature = 'alerts:generate
        {--dry-run : Show what would be created without writing anything}
        {--draft : Create the alerts as drafts instead of publishing them}
        {--days=7 : Number of forecast days to read (1 to 16)}
        {--quartier= : Only generate for this neighborhood id}
        {--simulate-temperature= : Demo only: pretend every day reaches this maximum temperature (°C)}';

    protected $description = 'Create or update weather alerts from the Open-Meteo forecast of every neighborhood with coordinates';

    public function handle(AlertGenerator $generator): int
    {
        $days = (int) $this->option('days');
        $simulate = $this->option('simulate-temperature');
        $quartier = $this->option('quartier');
        $dryRun = (bool) $this->option('dry-run');

        if ($days < 1 || $days > 16) {
            $this->components->error('--days must be between 1 and 16.');

            return self::INVALID;
        }

        if ($simulate !== null && (! is_numeric($simulate) || $simulate < -50 || $simulate > 60)) {
            $this->components->error('--simulate-temperature must be a number between -50 and 60.');

            return self::INVALID;
        }

        if ($quartier !== null && ! ctype_digit((string) $quartier)) {
            $this->components->error('--quartier must be a neighborhood id.');

            return self::INVALID;
        }

        if ($simulate !== null && ! $dryRun) {
            $this->components->warn('Simulation mode writes real alerts marked "'.AlertGenerator::SIMULATION_SOURCE.'". Use --dry-run to preview only.');
        }

        $result = $generator->run(
            dryRun: $dryRun,
            publish: ! $this->option('draft'),
            days: $days,
            simulate: $simulate !== null ? (float) $simulate : null,
            quartierId: $quartier !== null ? (int) $quartier : null,
        );

        $this->components->info(($dryRun ? '[dry run] ' : '').'Weather alerts for '.$result['quartiers'].' neighborhood(s), '.$days.' day(s)');

        if ($result['rows'] !== []) {
            $this->table(
                ['Neighborhood', 'Date', 'Level', 'Max °C', 'Action', 'Note'],
                array_map(fn (array $row) => [$row['quartier'], $row['date'], AlerteMeteo::labelFor($row['niveau']), $row['temperature'], $row['action'], $row['note']], $result['rows']),
            );
        } else {
            $this->line('No alert needed: every forecast day stays at the green level.');
        }

        foreach ($result['errors'] as $error) {
            $this->components->error($error);
        }

        $summary = collect($result['counts'])->map(fn (int $count, string $name) => "{$count} {$name}")->implode(', ');
        $this->line($summary);

        if (! $dryRun) {
            Log::info('alerts:generate finished', ['counts' => $result['counts'], 'errors' => $result['errors'], 'quartiers' => $result['quartiers']]);
        }

        // Fail only when nothing could be read at all, so the scheduler reports a real outage.
        return $result['quartiers'] > 0 && count($result['errors']) === $result['quartiers'] ? self::FAILURE : self::SUCCESS;
    }
}
