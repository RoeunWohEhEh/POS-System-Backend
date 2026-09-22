<?php

namespace App\Services;

use Piseth\BakongKhqr\BakongKHQR;
use Piseth\BakongKhqr\Models\MerchantInfo;

class KHQRService
{
    public function generate(
        string $accountId,
        string $merchantName,
        string $merchantCity,
        float $amount,
        int $currencyCode = 840 // Default to USD
    ): array {
        // For POS Systems, banking apps (like ABA Mobile) strictly require a
        // Corporate Merchant KHQR (Tag 30). Individual KHQR (Tag 29) is often rejected
        // during POS scans. We will use the package's generateMerchant method.
        
        $merchantInfo = new \Piseth\BakongKhqr\Models\MerchantInfo(
            $accountId,      // bakongAccountID (SubTag 00)
            $merchantName,   // merchantName
            $merchantCity,   // merchantCity
            $accountId,      // merchantID (SubTag 01)
            'Bakong'         // acquiringBank (SubTag 02)
        );
        
        $merchantInfo->amount = (float) $amount;
        $merchantInfo->currency = (int) $currencyCode;
        
        $response = \Piseth\BakongKhqr\BakongKHQR::generateMerchant($merchantInfo);
        
        // The package returns a KHQRResponse object containing 'data'
        $data = (array) $response->data;

        return [
            'status' => ['code' => 0, 'message' => 'Success'],
            'data'   => [
                'qr'  => $data['qr'] ?? null,
                'md5' => $data['md5'] ?? null,
            ],
        ];
    }

    public function verify(string $md5): array
    {
        $token = config('bakong.token');

        if (!$token) {
            throw new \InvalidArgumentException(
                'Bakong token is not configured.'
            );
        }

        $bakong = new BakongKHQR($token);

        return $bakong->checkTransactionByMD5($md5);
    }
}