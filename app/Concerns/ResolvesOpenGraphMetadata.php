<?php

namespace App\Concerns;

use Exception;
use Illuminate\Support\Facades\Log;
use shweshi\OpenGraph\OpenGraph;

trait ResolvesOpenGraphMetadata
{
    /**
     * @return array<string, mixed>|null
     */
    private function resolveMetadata(?string $description): ?array
    {
        if (! $description) {
            return null;
        }

        if ($mapPreview = $this->resolveMapPreview($description)) {
            return ['map_preview' => $mapPreview];
        }

        return null;
    }

    /**
     * @return array{title: string, image: string, url: string}|null
     */
    private function resolveMapPreview(string $description): ?array
    {
        $pattern = '/https?:\/\/(?:www\.)?(?:google\.(?:com|cz)\/maps\/[^\s]+|maps\.app\.goo\.gl\/[^\s]+)/';

        if (! preg_match($pattern, $description, $matches)) {
            return null;
        }

        $mapUrl = $matches[0];

        try {
            /** @var OpenGraph $openGraph */
            $openGraph = app(OpenGraph::class);
            $data = $openGraph->fetch($mapUrl);

            $title = $data['title'] ?? null;
            $image = $data['image'] ?? null;

            if (! $title || ! $image) {
                return null;
            }

            return [
                'title' => $title,
                'image' => $image,
                'url' => $mapUrl,
            ];
        } catch (Exception $e) {
            Log::error('Failed to fetch OpenGraph data for map preview.', ['exception' => $e]);

            return null;
        }
    }
}
