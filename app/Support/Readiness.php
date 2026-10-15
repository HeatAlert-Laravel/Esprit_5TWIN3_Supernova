<?php

namespace App\Support;

use App\Models\User;

/**
 * Display-only "household readiness" checklist built from data that already exists
 * (user account, Profile, SensitiveEquipment). Nothing is stored.
 *
 * Rule: three steps, each worth one third of the progress bar.
 *   1. account   — done when the visitor is signed in
 *   2. profile   — done when the user has a Profile whose required details are all filled
 *                  (Profile::isComplete(): phone, address, neighborhood)
 *   3. equipment — done when the profile has at least one SensitiveEquipment record
 */
class Readiness
{
    /**
     * @return array{authenticated: bool, percent: int, doneCount: int, steps: list<array{key: string, title: string, description: string, done: bool, href: string, cta: string}>}
     */
    public static function for(?User $user): array
    {
        $profile = $user?->profile;
        $hasEquipment = $profile?->sensitiveEquipments()->exists() ?? false;

        $steps = [
            [
                'key' => 'account',
                'title' => 'Create your account',
                'description' => 'A resident account keeps your household information in one place.',
                'done' => $user !== null,
                'href' => route('register'),
                'cta' => 'Create account',
            ],
            [
                'key' => 'profile',
                'title' => 'Complete your household profile',
                'description' => 'Phone, address and neighborhood, plus whether a fragile person lives with you.',
                'done' => $profile?->isComplete() ?? false,
                'href' => $user ? route('my-profile') : route('login'),
                'cta' => $profile ? 'Update profile' : 'Add details',
            ],
            [
                'key' => 'equipment',
                'title' => 'Record sensitive equipment',
                'description' => 'Optional: devices that need power or cooling, such as a fan or a medication fridge.',
                'done' => $hasEquipment,
                'href' => $profile ? route('profile.equipment.create') : ($user ? route('my-profile') : route('login')),
                'cta' => 'Add equipment',
            ],
        ];

        $doneCount = count(array_filter($steps, fn (array $step): bool => $step['done']));

        return [
            'authenticated' => $user !== null,
            'percent' => (int) round($doneCount / count($steps) * 100),
            'doneCount' => $doneCount,
            'steps' => $steps,
        ];
    }
}
