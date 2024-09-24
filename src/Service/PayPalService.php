<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class PayPalService
{
    private $client;
    private $clientId;
    private $secret;
    private $mode;

    public function __construct(string $clientId, string $secret, string $mode)
    {
        $this->clientId = $clientId;
        $this->secret = $secret;
        $this->mode = $mode;

        $this->client = new Client([
            'base_uri' => $this->mode === 'live' ? 'https://api.paypal.com' : 'https://api.sandbox.paypal.com',
        ]);
    }

    public function getAccessToken()
    {
        try {
            $response = $this->client->post('/v1/oauth2/token', [
                'auth' => [$this->clientId, $this->secret],
                'form_params' => [
                    'grant_type' => 'client_credentials',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['access_token'];
        } catch (RequestException $e) {
            return null;
        }
    }

    public function createOrder($amount)
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            throw new \Exception('Impossible d’obtenir le jeton d’accès PayPal.');
        }

        try {
            $response = $this->client->post('/v2/checkout/orders', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [
                        [
                            'amount' => [
                                'currency_code' => 'EUR',
                                'value' => number_format($amount, 2, '.', ''),
                            ],
                        ],
                    ],
                    'application_context' => [
                        'cancel_url' => 'https://gagnetesplaces.fr/payment/cancel',
                        'return_url' => 'https://gagnetesplaces.fr/payment/success',
                    ],
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            return null;
        }
    }

    public function captureOrder($orderId)
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            throw new \Exception('Impossible d’obtenir le jeton d’accès PayPal.');
        }

        try {
            $response = $this->client->post("/v2/checkout/orders/{$orderId}/capture", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            return null;
        }
    }
}

