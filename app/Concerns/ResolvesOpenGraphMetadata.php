<?php

namespace App\Concerns;

use DOMDocument;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

trait ResolvesOpenGraphMetadata
{
    private const int MAP_PREVIEW_MAX_RESPONSE_BYTES = 1048576;

    private const int MAP_PREVIEW_MAX_REDIRECTS = 3;

    private const int MAP_PREVIEW_TIMEOUT_SECONDS = 5;

    /** @var list<string> */
    private const array MAP_PREVIEW_ALLOWED_HOSTS = [
        'google.com',
        'www.google.com',
        'google.cz',
        'www.google.cz',
        'maps.google.com',
        'maps.app.goo.gl',
    ];

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
        $pattern = '/https:\/\/(?:www\.)?(?:google\.(?:com|cz)\/maps\/[^\s<>"\']+|maps\.app\.goo\.gl\/[^\s<>"\']+)/';

        if (! preg_match($pattern, $description, $matches)) {
            return null;
        }

        $mapUrl = $matches[0];

        try {
            return $this->fetchMapPreview($mapUrl);
        } catch (Throwable $exception) {
            Log::error('Failed to fetch OpenGraph data for map preview.', [
                'exception' => $exception,
            ]);

            return null;
        }
    }

    /**
     * @return array{title: string, image: string, url: string}|null
     */
    private function fetchMapPreview(string $mapUrl): ?array
    {
        $currentUrl = $mapUrl;

        for ($redirectCount = 0; $redirectCount <= self::MAP_PREVIEW_MAX_REDIRECTS; $redirectCount++) {
            if (! $this->isAllowedMapUrl($currentUrl)) {
                return null;
            }

            $responseDeadline = hrtime(true) + (self::MAP_PREVIEW_TIMEOUT_SECONDS * 1_000_000_000);

            $response = Http::accept('text/html')
                ->withUserAgent('Capylendar map preview')
                ->connectTimeout(2)
                ->timeout(self::MAP_PREVIEW_TIMEOUT_SECONDS)
                ->withoutRedirecting()
                ->withOptions(['stream' => true])
                ->get($currentUrl);

            if ($response->status() >= 300 && $response->status() < 400) {
                $location = $response->header('Location');

                if ($location === '' || $redirectCount === self::MAP_PREVIEW_MAX_REDIRECTS) {
                    return null;
                }

                $currentUrl = (string) UriResolver::resolve(
                    new Uri($currentUrl),
                    new Uri($location),
                );

                continue;
            }

            if (! $response->successful()) {
                return null;
            }

            $contentType = strtolower($response->header('Content-Type'));
            if ($contentType !== '' && ! str_contains($contentType, 'text/html')) {
                return null;
            }

            $html = $this->readLimitedResponseBody($response, $responseDeadline);
            if ($html === null) {
                return null;
            }

            $metadata = $this->parseOpenGraphMetadata($html, $currentUrl);
            if ($metadata === null) {
                return null;
            }

            return [
                ...$metadata,
                'url' => $mapUrl,
            ];
        }

        return null;
    }

    private function isAllowedMapUrl(string $url): bool
    {
        $components = parse_url($url);

        if (
            ! is_array($components)
            || strtolower((string) ($components['scheme'] ?? '')) !== 'https'
            || ! isset($components['host'])
            || isset($components['user'])
            || isset($components['pass'])
        ) {
            return false;
        }

        $host = strtolower(rtrim($components['host'], '.'));

        return in_array($host, self::MAP_PREVIEW_ALLOWED_HOSTS, true);
    }

    private function readLimitedResponseBody(Response $response, int $deadline): ?string
    {
        $body = $response->toPsrResponse()->getBody();
        $size = $body->getSize();

        if ($size !== null && $size > self::MAP_PREVIEW_MAX_RESPONSE_BYTES) {
            return null;
        }

        if ($body->isSeekable()) {
            $body->rewind();
        }

        $contents = '';

        while (! $body->eof()) {
            if (hrtime(true) >= $deadline) {
                return null;
            }

            $chunk = $body->read(8192);

            if ($chunk === '') {
                break;
            }

            $contents .= $chunk;

            if (
                strlen($contents) > self::MAP_PREVIEW_MAX_RESPONSE_BYTES
                || hrtime(true) >= $deadline
            ) {
                return null;
            }
        }

        return $contents;
    }

    /**
     * @return array{title: string, image: string}|null
     */
    private function parseOpenGraphMetadata(string $html, string $pageUrl): ?array
    {
        $previousInternalErrors = libxml_use_internal_errors(true);

        try {
            $document = new DOMDocument;

            if (! $document->loadHTML(
                '<?xml encoding="UTF-8">'.$html,
                LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING,
            )) {
                return null;
            }

            $metadata = [];

            foreach ($document->getElementsByTagName('meta') as $meta) {
                $property = strtolower($meta->getAttribute('property'));

                if (in_array($property, ['og:title', 'og:image'], true)) {
                    $metadata[$property] = trim($meta->getAttribute('content'));
                }
            }

            $title = $metadata['og:title'] ?? '';
            $image = $metadata['og:image'] ?? '';

            if ($title === '' || $image === '' || strlen($title) > 500 || strlen($image) > 2048) {
                return null;
            }

            $imageUrl = (string) UriResolver::resolve(new Uri($pageUrl), new Uri($image));
            $imageComponents = parse_url($imageUrl);

            if (
                ! is_array($imageComponents)
                || strtolower((string) ($imageComponents['scheme'] ?? '')) !== 'https'
                || ! isset($imageComponents['host'])
                || isset($imageComponents['user'])
                || isset($imageComponents['pass'])
            ) {
                return null;
            }

            return [
                'title' => $title,
                'image' => $imageUrl,
            ];
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousInternalErrors);
        }
    }
}
