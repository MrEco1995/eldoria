<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicMediaController extends Controller
{
    public function show(string $path): Response
    {
        $disk = Storage::disk('public');
        $resolvedPath = $this->resolvePublicPath($disk, $path);

        abort_unless($resolvedPath !== null, 404);

        $mimeType = $disk->mimeType($resolvedPath) ?: 'application/octet-stream';

        return response($disk->get($resolvedPath), 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function resolvePublicPath($disk, string $path): ?string
    {
        // Block path traversal attempts.
        if (Str::contains($path, ['..', '\\'])) {
            return null;
        }

        if ($disk->exists($path)) {
            return $path;
        }

        // Case-insensitive fallback for Linux servers.
        $directory = trim(dirname($path), '.');
        $filename = basename($path);
        $target = mb_strtolower($filename);

        $files = $disk->files($directory === '' ? null : $directory);
        foreach ($files as $candidate) {
            if (mb_strtolower(basename($candidate)) === $target) {
                return $candidate;
            }
        }

        return null;
    }
}
