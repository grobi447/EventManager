<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MfaSecret;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;

class MfaController extends Controller
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA;
    }

    public function setup(Request $request): JsonResponse
    {
        $user = auth()->user();
        $secret = $this->google2fa->generateSecretKey();

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        return response()->json([
            'success' => true,
            'data' => [
                'secret' => $secret,
                'qr_code_url' => $qrCodeUrl,
            ],
            'message' => 'Scan the QR code with your authenticator app, then confirm with your OTP.',
        ]);
    }

    public function enable(Request $request): JsonResponse
    {
        $request->validate([
            'secret' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        $user = auth()->user();
        $valid = $this->google2fa->verifyKey($request->secret, $request->otp);

        if (! $valid) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP. Please try again.',
            ], 422);
        }

        $backupCodes = collect(range(1, 8))->map(fn () => strtoupper(bin2hex(random_bytes(4))))->toArray();

        MfaSecret::updateOrCreate(
            ['user_id' => $user->id],
            [
                'secret' => $request->secret,
                'backup_codes' => $backupCodes,
            ]
        );

        $user->update(['mfa_enabled' => true]);

        return response()->json([
            'success' => true,
            'data' => ['backup_codes' => $backupCodes],
            'message' => 'MFA enabled successfully. Save your backup codes.',
        ]);
    }

    public function disable(Request $request): JsonResponse
    {
        $request->validate([
            'otp' => 'required|string',
        ]);

        $user = auth()->user();
        $mfaSecret = $user->mfaSecret;

        if (! $mfaSecret) {
            return response()->json(['success' => false, 'message' => 'MFA not enabled.'], 400);
        }

        $valid = $this->google2fa->verifyKey($mfaSecret->secret, $request->otp);

        if (! $valid) {
            return response()->json(['success' => false, 'message' => 'Invalid OTP.'], 422);
        }

        $mfaSecret->delete();
        $user->update(['mfa_enabled' => false]);

        return response()->json([
            'success' => true,
            'message' => 'MFA disabled successfully.',
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'otp' => 'required|string',
        ]);

        $user = auth()->user();
        $mfaSecret = $user->mfaSecret;

        if (! $mfaSecret) {
            return response()->json(['success' => false, 'message' => 'MFA not enabled.'], 400);
        }

        // Check backup codes first
        if (strlen($request->otp) === 8) {
            $backupCodes = $mfaSecret->backup_codes;
            $index = array_search(strtoupper($request->otp), $backupCodes);

            if ($index !== false) {
                unset($backupCodes[$index]);
                $mfaSecret->update(['backup_codes' => array_values($backupCodes)]);

                return response()->json(['success' => true, 'message' => 'Backup code accepted.']);
            }
        }

        $valid = $this->google2fa->verifyKey($mfaSecret->secret, $request->otp);

        if (! $valid) {
            return response()->json(['success' => false, 'message' => 'Invalid OTP.'], 422);
        }

        return response()->json(['success' => true, 'message' => 'OTP verified successfully.']);
    }
}
