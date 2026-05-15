<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;
use App\Models\muser;
use Google\Client;

class DeviceTokenController extends Controller
{
    /**
     * Simpan atau update device token dari Android
     */
    public function store(Request $request)
    {
        DeviceToken::updateOrCreate(
            [
                'nuserid' => $request->user_id
            ],
            [
                'fcm_token' => $request->fcm_token,
                'last_used_at' => now()
            ]
        );

        return response()->json([
            'status' => 'success'
        ]);
    }


    /**
     * Ambil OAuth2 Access Token dari Firebase Service Account
     */
    private function getAccessToken()
    {
        $client = new Client();

        $client->setAuthConfig(storage_path('app/firebase-service-account.json'));

        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $token = $client->fetchAccessTokenWithAssertion();

        return $token['access_token'];
    }


    /**
     * Kirim Notifikasi ke user berdasarkan token
     */
    public function sendNotif(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'title'   => 'required',
            'body'    => 'required'
        ]);

        // cek apakah user mengaktifkan notif
        $user = muser::where('nid', $request->user_id)->first();

        if (!$user || !$user->fnotif) {
            return response()->json([
                'status' => 'skipped',
                'message' => 'User mematikan notifikasi'
            ]);
        }

        // ambil token device
        $tokens = DeviceToken::where('nuserid', $request->user_id)
            ->pluck('fcm_token')
            ->toArray();

        if (empty($tokens)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token tidak ditemukan'
            ]);
        }

        $accessToken = $this->getAccessToken();

        $responses = [];

        foreach ($tokens as $token) {

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type'  => 'application/json',
            ])->post(
                'https://fcm.googleapis.com/v1/projects/absensi-2b01e/messages:send',
                [
                    "message" => [
                        "token" => $token,
                        "notification" => [
                            "title" => $request->title,
                            "body"  => $request->body
                        ],
                        "android" => [
                            "priority" => "high"
                        ]
                    ]
                ]
            );

            $responses[] = $response->json();
        }

        return response()->json([
            'status' => 'sent',
            'firebase_response' => $responses
        ]);
    }

}
