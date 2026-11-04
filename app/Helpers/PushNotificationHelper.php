<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Log;

class PushNotificationHelper
{
    private static function getAccessToken()
    {
        try {
            $jsonKeyFilePath = env('FIREBASE_JSON_FILE');

            if (!file_exists($jsonKeyFilePath)) {
                throw new \Exception('Service account JSON file not found at ' . $jsonKeyFilePath);
            }

            $client = new GoogleClient();
            $client->setAuthConfig($jsonKeyFilePath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            $client->useApplicationDefaultCredentials();
            $token = $client->fetchAccessTokenWithAssertion();

            if (!isset($token['access_token'])) {
                throw new \Exception('Failed to fetch access token');
            }

            return $token['access_token'];
        } catch (\Exception $e) {
            Log::error('Failed to fetch access token: ' . $e->getMessage());
            throw $e;
        }
    }

    private static function sendMessage($accessToken, $url, $message)
    {
        try {
            $client = new Client();
            $headers = [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ];

            // Log::info('Sending message to FCM', ['url' => $url, 'headers' => $headers, 'message' => $message]);


            $response = $client->post($url, [
                'headers' => $headers,
                'json' => ['message' => $message],
            ]);

            // Log::info('FCM response', ['status_code' => $response->getStatusCode(), 'body' => $response->getBody()->getContents()]);


            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Failed to send message, status code: ' . $response->getStatusCode());
            }

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Failed to send message: ' . $e->getMessage());
            throw $e;
        }
    }

    public static function send($token, $payload)
    {

        try {
            $jsonKeyFilePath = env('FIREBASE_JSON_FILE');
            $credential_file = json_decode(file_get_contents($jsonKeyFilePath), true);
            $url = 'https://fcm.googleapis.com/v1/projects/' . $credential_file['project_id'] . '/messages:send';
            $body = $payload['notification']['body'] ?? '';
            $message = [
                'token' => $token,
                'notification' => [
                    'title' => $payload['notification']['title'] ?? '',
                    'body' => strip_tags($body),
                ],
                'data' => $payload['data'] ?? new \stdClass(),  // Use an empty object if data is not provided
            ];

            $accessToken = self::getAccessToken();
            //dd($accessToken);
            return self::sendMessage($accessToken, $url, $message);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            Log::error('Error thrown while sending the sending Push Notification | Token: ' . $token);
            return false;
        }
    }

    public static function sendToTopic($topic, $payload)
    {
        $jsonKeyFilePath = env('FIREBASE_JSON_FILE');
        $credential_file = json_decode(file_get_contents($jsonKeyFilePath), true);
        $url = 'https://fcm.googleapis.com/v1/projects/' . $credential_file['project_id'] . '/messages:send';

        $message = [
            'topic' => $topic,
            'notification' => $payload['notification'],
            'data' => $payload['data'],
        ];

        $accessToken = self::getAccessToken();
        return self::sendMessage($accessToken, $url, $message);
    }

    public static function subscribe($token, $topic)
    {
        $jsonKeyFilePath = env('FIREBASE_JSON_FILE');
        $credential_file = json_decode(file_get_contents($jsonKeyFilePath), true);
        $url = 'https://iid.googleapis.com/v1/' . $token . '/rel/topics/' . $topic;

        $accessToken = self::getAccessToken();
        $client = new Client();
        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ];

        $response = $client->post($url, [
            'headers' => $headers,
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Failed to subscribe, status code: ' . $response->getStatusCode());
        }

        return json_decode($response->getBody(), true);
    }

    /*
    public static function send($token, $payload)
    {
        $client = new Client();
        $url = "https://fcm.googleapis.com/fcm/send";

        $response = $client->request("POST", $url, [
            'headers' => [
                'Authorization' => 'key=' . env("FCM_SERVER_KEY"),
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'to' => $token,
                'notification' => $payload['notification'],
                'data' => $payload['data']
            ]
        ]);

        // store in app notification
        //self::storeInAppNotification($token, $payload);

        return json_decode($response->getBody(), true);
    }

    public static function sendToTopic($topic, $payload)
    {
        $client = new Client();
        $url = "https://fcm.googleapis.com/fcm/send";

        $response = $client->request("POST", $url, [
            'headers' => [
                'Authorization' => 'key=' . env("FCM_SERVER_KEY"),
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'to' => "/topics/" . $topic,
                'notification' => $payload['notification'],
                'data' => $payload['data']
            ]
        ]);

        //todo: need to create in-app notification for all users

        return json_decode($response->getBody(), true);
    }

    public static function subscribe($token, $topic)
    {
        $client = new Client();
        $url = "https://iid.googleapis.com/iid/v1/" . $token . "/rel/topics/" . $topic;

        $response = $client->request("POST", $url, [
            'headers' => [
                'Authorization' => 'key=' . env("FCM_SERVER_KEY"),
                'Content-Type' => 'application/json'
            ]
        ]);

        return json_decode($response->getBody(), true);
    }
    */
}
