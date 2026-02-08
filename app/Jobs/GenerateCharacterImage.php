<?php

namespace App\Jobs;

use App\Models\Mediafile;
use App\Models\PartyCharacter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateCharacterImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(public int $characterId)
    {
    }

    public function handle(): void
    {
        $character = PartyCharacter::query()
            ->with(['party', 'user'])
            ->findOrFail($this->characterId);

        $prompt = $this->buildPrompt($character);
        $workflow = $this->loadWorkflow($prompt);

        $baseUrl = rtrim(config('comfyui.url'), '/');
        $response = Http::post($baseUrl.'/prompt', [
            'prompt' => $workflow,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('ComfyUI prompt failed: '.$response->body());
        }

        $promptId = $response->json('prompt_id');
        if (! $promptId) {
            throw new \RuntimeException('ComfyUI returned no prompt_id.');
        }

        $imageInfo = $this->waitForImage($baseUrl, $promptId);
        $binary = Http::get($baseUrl.'/view', [
            'filename' => $imageInfo['filename'],
            'subfolder' => $imageInfo['subfolder'] ?? '',
            'type' => $imageInfo['type'] ?? 'output',
        ])->body();

        $filename = 'character_'.$character->id.'_'.Str::random(8).'.png';
        $path = 'characters/party_'.$character->party_id.'/'.$filename;
        Storage::disk('public')->put($path, $binary);

        $media = Mediafile::create([
            'disk' => 'public',
            'path' => $path,
            'filename' => $filename,
            'mime' => 'image/png',
            'size' => strlen($binary),
            'created_by' => $character->user_id,
            'metadata' => [
                'prompt_id' => $promptId,
            ],
        ]);

        $character->mediafiles()->syncWithoutDetaching([
            $media->id => ['role' => 'character'],
        ]);
    }

    private function buildPrompt(PartyCharacter $character): string
    {
        $style = config('comfyui.style_prompt');
        $traits = implode(', ', $character->traits ?? []);

        return trim(sprintf(
            '%s, character portrait, %s, race %s, class %s, %s, age %s, height %scm, weight %skg, traits: %s',
            $style,
            $character->name,
            $character->race,
            $character->class_name,
            $character->gender,
            $character->age,
            $character->height_cm,
            $character->weight_kg,
            $traits
        ));
    }

    private function loadWorkflow(string $prompt): array
    {
        $path = base_path(config('comfyui.workflow_path'));
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new \RuntimeException('ComfyUI workflow not found.');
        }

        $raw = str_replace('{{PROMPT}}', $prompt, $raw);
        $raw = str_replace('{{NEGATIVE_PROMPT}}', config('comfyui.negative_prompt'), $raw);

        $json = json_decode($raw, true);
        if (! is_array($json)) {
            throw new \RuntimeException('ComfyUI workflow JSON invalid.');
        }

        return $json;
    }

    private function waitForImage(string $baseUrl, string $promptId): array
    {
        $maxAttempts = 30;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $history = Http::get($baseUrl.'/history/'.$promptId);
            if ($history->successful()) {
                $payload = $history->json($promptId);
                if (isset($payload['outputs'])) {
                    foreach ($payload['outputs'] as $output) {
                        if (! empty($output['images'][0])) {
                            return $output['images'][0];
                        }
                    }
                }
            }
            usleep(500000);
        }

        throw new \RuntimeException('ComfyUI image timeout.');
    }
}
