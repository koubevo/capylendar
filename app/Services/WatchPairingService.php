<?php

namespace App\Services;

use App\Models\User;
use App\Models\WatchDevice;
use App\Models\WatchPairing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WatchPairingService
{
    private const string USER_CODE_ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    /**
     * @return array{pairing: WatchPairing, device_code: string, user_code: string}
     */
    public function create(string $deviceName): array
    {
        WatchPairing::query()
            ->where('expires_at', '<', now()->subDay())
            ->delete();

        $deviceCode = 'capy_pair_'.Str::random(64);
        $userCode = $this->generateUserCode();

        $pairing = WatchPairing::query()->create([
            'device_name' => $deviceName,
            'device_code_hash' => hash('sha256', $deviceCode),
            'user_code_hash' => $this->hashUserCode($userCode),
            'expires_at' => now()->addMinutes(10),
        ]);

        return [
            'pairing' => $pairing,
            'device_code' => $deviceCode,
            'user_code' => $userCode,
        ];
    }

    public function approve(User $user, string $userCode): ?WatchPairing
    {
        return DB::transaction(function () use ($user, $userCode): ?WatchPairing {
            $pairing = WatchPairing::query()
                ->where('user_code_hash', $this->hashUserCode($userCode))
                ->where('expires_at', '>', now())
                ->whereNull('approved_at')
                ->whereNull('claimed_at')
                ->lockForUpdate()
                ->first();

            if (! $pairing) {
                return null;
            }

            $pairing->update([
                'user_id' => $user->id,
                'approved_at' => now(),
            ]);

            return $pairing;
        });
    }

    /**
     * @return array{status: 'invalid'}|array{status: 'expired'}|array{status: 'pending'}|array{status: 'claimed'}|array{status: 'authorized', token: string, device: WatchDevice}
     */
    public function claim(string $deviceCode): array
    {
        $deviceCodeHash = hash('sha256', $deviceCode);

        return DB::transaction(function () use ($deviceCodeHash): array {
            $pairing = WatchPairing::query()
                ->where('device_code_hash', $deviceCodeHash)
                ->lockForUpdate()
                ->first();

            if (! $pairing) {
                return ['status' => 'invalid'];
            }

            if ($pairing->expires_at->isPast()) {
                return ['status' => 'expired'];
            }

            if ($pairing->claimed_at) {
                $plainTextToken = $pairing->claimed_token;

                if (! is_string($plainTextToken)) {
                    return ['status' => 'claimed'];
                }

                $device = WatchDevice::query()
                    ->where('token_hash', hash('sha256', $plainTextToken))
                    ->first();

                if (! $device) {
                    return ['status' => 'claimed'];
                }

                return $this->authorizedResult($plainTextToken, $device);
            }

            if (! $pairing->approved_at || ! $pairing->user_id) {
                return ['status' => 'pending'];
            }

            $plainTextToken = 'capy_watch_'.Str::random(64);

            $device = WatchDevice::query()->create([
                'user_id' => $pairing->user_id,
                'name' => $pairing->device_name,
                'token_hash' => hash('sha256', $plainTextToken),
                'last_used_at' => now(),
            ]);

            $pairing->update([
                'claimed_at' => now(),
                'claimed_token' => $plainTextToken,
            ]);

            return $this->authorizedResult($plainTextToken, $device);
        });
    }

    /**
     * @return array{status: 'authorized', token: string, device: WatchDevice}
     */
    private function authorizedResult(string $plainTextToken, WatchDevice $device): array
    {
        return ['status' => 'authorized', 'token' => $plainTextToken, 'device' => $device];
    }

    public function hashUserCode(string $userCode): string
    {
        $normalizedCode = preg_replace('/[^A-Z0-9]/', '', Str::upper($userCode)) ?? '';
        $appKey = config('app.key');

        if (! is_string($appKey) || $appKey === '') {
            throw new \LogicException('The application key must be configured before pairing a watch.');
        }

        return hash_hmac('sha256', $normalizedCode, $appKey);
    }

    private function generateUserCode(): string
    {
        $characters = '';
        $maxIndex = strlen(self::USER_CODE_ALPHABET) - 1;

        for ($index = 0; $index < 8; $index++) {
            $characters .= self::USER_CODE_ALPHABET[random_int(0, $maxIndex)];
        }

        return substr($characters, 0, 4).'-'.substr($characters, 4);
    }
}
