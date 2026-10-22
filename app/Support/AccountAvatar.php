<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class AccountAvatar
{
    public static function delete(?string $path): void
    {
        // Only remove files generated in this feature's flat avatar directory.
        if ($path && preg_match('~\Aavatars/[a-zA-Z0-9]+\.(?:jpe?g|png|webp)\z~D', $path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
