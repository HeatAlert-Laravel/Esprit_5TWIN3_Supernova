<?php

namespace App\Http\Controllers;

use App\Models\Conseil;
use App\Services\AdviceAudio;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdviceAudioController extends Controller
{
    public function store(Conseil $conseil, string $voice, AdviceAudio $audio): JsonResponse
    {
        abort_unless($conseil->actif, 404);
        $audio->generate($conseil, $voice);

        return response()->json(['url' => route('advice.audio.show', [
            'conseil' => $conseil, 'voice' => $voice, 'version' => $audio->version($conseil, $voice),
        ])])->header('Cache-Control', 'no-store');
    }

    public function show(Conseil $conseil, string $voice, string $version, AdviceAudio $audio): BinaryFileResponse
    {
        // Recheck publication even for previously generated recordings.
        abort_unless($conseil->actif && hash_equals($audio->version($conseil, $voice), $version), 404);
        $disk = Storage::disk('local');
        $path = $audio->path($conseil, $voice);
        abort_unless($disk->exists($path), 404);

        return response()->file($disk->path($path), [
            'Content-Type' => 'audio/wav',
            'Cache-Control' => 'private, no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
