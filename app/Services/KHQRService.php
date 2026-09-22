<?php

namespace App\Services;

use Exception;
use InvalidArgumentException;
use Piseth\BakongKhqr\BakongKHQR;
use Piseth\BakongKhqr\Models\IndividualInfo;

class KHQRService
{
    /**
     * Generate dynamic Individual KHQR string and MD5.
     */
    public function generate(
        string $accountId,
        string $merchantName,
        string $merchantCity,
        float $amount,
        int $currencyCode = 840 // 840 = USD, 116 = KHR
    ): array {
        $individualInfo = new IndividualInfo(
            $accountId,      // e.g. name@bkrt
            $merchantName,   // Display name
            $merchantCity    // e.g. Phnom Penh
        );

        $individualInfo->amount = (float) $amount;
        $individualInfo->currency = (int) $currencyCode;

        // Generate KHQR
        $response = BakongKHQR::generateIndividual($individualInfo);

        // Package uses getData() to retrieve the associative array
        $data = (is_object($response) && method_exists($response, 'getData')) ? $response->getData() : (array) ($response->data ?? (is_array($response) ? $response : []));
        $qrString = $data['qr'] ?? null;

        if (!$qrString) {
            throw new Exception('Failed to generate KHQR string.');
        }

        // The MD5 used to check transactions is the MD5 hash of the raw QR string
        $md5 = $data['md5'] ?? md5($qrString);

        return [
            'status' => ['code' => 0, 'message' => 'Success'],
            'data'   => [
                'qr'           => $qrString,
                'md5'          => $md5,
                'qr_image_url' => $data['qrImageUrl'] ?? null,
            ],
        ];
    }

    /**
     * Check transaction settlement status by MD5.
     */
    public function verify(string $md5): array
    {
        // Check config/services.php first, then fallback to config/bakong.php
        $token = config('services.bakong.token') ?? config('bakong.token');

        if (!$token) {
            throw new InvalidArgumentException('Bakong API token is not configured.');
        }

        $bakong = new BakongKHQR($token);

        try {
            $response = $bakong->checkTransactionByMD5($md5);

            if (is_object($response) && method_exists($response, 'getData')) {
                return $response->getData();
            }

            return (array) $response;
        } catch (Exception $e) {
            return [
                'responseCode'    => 1,
                'responseMessage' => $e->getMessage(),
                'data'            => null,
            ];
        }
    }
}
