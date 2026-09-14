<?php

namespace App\Http\Controllers;

use App\Services\CheckoutPhoneVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CheckoutPhoneVerificationController extends Controller
{
    public function send(Request $request, CheckoutPhoneVerificationService $verification): JsonResponse
    {
        $data = $request->validate(['phone' => ['required', 'string', 'max:30']]);
        $this->ensureValidPhone($verification, $data['phone']);

        try {
            $sent = $verification->send($request, $data['phone']);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages(['phone' => 'OTP could not be sent. Please try again.']);
        }

        return response()->json([
            'sent' => $sent,
            'verified' => ! $sent,
            'message' => $sent ? 'OTP sent to your phone.' : 'This phone number is already verified.',
        ]);
    }

    public function verify(Request $request, CheckoutPhoneVerificationService $verification): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'code' => ['required', 'digits:6'],
        ]);
        $this->ensureValidPhone($verification, $data['phone']);
        $verification->verify($request, $data['phone'], $data['code']);

        return response()->json(['verified' => true, 'message' => 'Phone number verified successfully.']);
    }

    private function ensureValidPhone(CheckoutPhoneVerificationService $verification, string $phone): void
    {
        if (! preg_match('/^01[3-9]\d{8}$/', $verification->normalize($phone))) {
            throw ValidationException::withMessages(['phone' => 'Enter a valid Bangladesh phone number.']);
        }
    }
}
