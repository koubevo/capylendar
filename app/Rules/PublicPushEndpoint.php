<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class PublicPushEndpoint implements ValidationRule
{
    private const array ALLOWED_HOSTS = [
        'fcm.googleapis.com',
        'push.services.mozilla.com',
        'updates.push.services.mozilla.com',
        'web.push.apple.com',
    ];

    private const array ALLOWED_HOST_SUFFIXES = ['.notify.windows.com'];

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Push endpoint musí být platná HTTPS URL.');

            return;
        }

        $components = parse_url($value);

        if (
            ! is_array($components)
            || strtolower((string) ($components['scheme'] ?? '')) !== 'https'
            || ! isset($components['host'])
            || isset($components['user'])
            || isset($components['pass'])
        ) {
            $fail('Push endpoint musí být platná HTTPS URL.');

            return;
        }

        $host = strtolower(rtrim($components['host'], '.'));

        if (! $this->isAllowedHost($host)) {
            $fail('Push endpoint nepatří podporované push službě.');
        }
    }

    private function isAllowedHost(string $host): bool
    {
        if (in_array($host, self::ALLOWED_HOSTS, true)) {
            return true;
        }

        foreach (self::ALLOWED_HOST_SUFFIXES as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return true;
            }
        }

        return false;
    }
}
