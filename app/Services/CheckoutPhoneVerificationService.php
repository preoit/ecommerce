<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutPhoneVerificationService
{
    private const SESSION_KEY = 'checkout_phone_verification';

    public function __construct(private readonly MramSmsService $sms) {}

    public function send(Request $request, string $phone): bool
    {
        $phone = $this->normalize($phone);

        if ($this->isVerified($request, $phone)) {
            $this->markVerified($request, $phone);
            return false;
        }

        $current = $request->session()->get(self::SESSION_KEY, []);
        if (($current['phone'] ?? null) === $phone && (int) ($current['sent_at'] ?? 0) > now()->subMinute()->timestamp) {
            throw ValidationException::withMessages(['phone' => 'Please wait one minute before requesting another OTP.']);
        }

        $code = (string) random_int(1000, 9999);
        $this->sms->send($phone, "Your iTTiBA verification code is {$code}. It expires in 10 minutes.");
        $request->session()->put(self::SESSION_KEY, [
            'phone' => $phone,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(10)->timestamp,
            'sent_at' => now()->timestamp,
            'verified_at' => null,
        ]);

        return true;
    }

    public function verify(Request $request, string $phone, string $code): void
    {
        $phone = $this->normalize($phone);
        $current = $request->session()->get(self::SESSION_KEY, []);

        if (($current['phone'] ?? null) !== $phone || blank($current['code'] ?? null) ||
            (int) ($current['expires_at'] ?? 0) < now()->timestamp || ! Hash::check($code, $current['code'])) {
            throw ValidationException::withMessages(['code' => 'The verification code is invalid or expired.']);
        }

        $this->markVerified($request, $phone);
    }

    public function isVerified(Request $request, ?string $phone): bool
    {
        if (blank($phone)) return false;
        $phone = $this->normalize($phone);
        if (DB::table('verified_phone_numbers')->where('phone', $phone)->exists()) return true;
        if ($this->verifiedByAccount($request, $phone)) return true;
        $current = $request->session()->get(self::SESSION_KEY, []);

        return ($current['phone'] ?? null) === $phone && filled($current['verified_at'] ?? null);
    }

    public function clear(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    public function normalize(string $phone): string
    {
        $number = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($number, '880')) $number = '0'.substr($number, 3);
        return $number;
    }

    private function verifiedByAccount(Request $request, string $phone): bool
    {
        $user = $request->user();
        return $user && $user->phone_verified_at && $this->normalize((string) $user->phone) === $phone;
    }

    private function markVerified(Request $request, string $phone): void
    {
        DB::table('verified_phone_numbers')->updateOrInsert(
            ['phone' => $phone],
            ['verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        );
        $request->session()->put(self::SESSION_KEY, [
            'phone' => $phone,
            'code' => null,
            'expires_at' => null,
            'sent_at' => now()->timestamp,
            'verified_at' => now()->toISOString(),
        ]);
    }
}
