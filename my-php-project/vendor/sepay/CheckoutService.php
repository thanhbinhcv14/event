<?php

namespace SePay;

class CheckoutService
{
    private $client;

    public function __construct(SePayClient $client)
    {
        $this->client = $client;
    }

    public function generateFormHtml($checkoutData)
    {
        $partnerCode = $this->client->getPartnerCode();
        $secretKey = $this->client->getSecretKey();
        $baseUrl = $this->client->getBaseUrl();
        
        // Add callback URLs if not already set
        if (!isset($checkoutData['callbackUrl'])) {
            $checkoutData['callbackUrl'] = SEPAY_CALLBACK_URL;
        }
        if (!isset($checkoutData['returnUrl'])) {
            $checkoutData['returnUrl'] = SEPAY_CALLBACK_URL;
        }
        
        // Generate signature
        $signature = $this->generateSignature($checkoutData, $secretKey);
        
        // Add signature to data
        $checkoutData['signature'] = $signature;
        $checkoutData['partnerCode'] = $partnerCode;
        
        // Generate HTML form
        $html = '<form id="sepay-checkout-form" method="POST" action="' . $baseUrl . '/checkout" target="_blank">';
        
        foreach ($checkoutData as $key => $value) {
            $html .= '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
        }
        
        $html .= '<button type="submit" class="btn btn-primary">';
        $html .= '<i class="fas fa-university"></i> Thanh toán qua ngân hàng';
        $html .= '</button>';
        $html .= '</form>';
        
        return $html;
    }

    private function generateSignature($data, $secretKey)
    {
        // Sort data by key
        ksort($data);
        
        // Create query string
        $queryString = http_build_query($data);
        
        // Generate HMAC SHA256 signature
        return hash_hmac('sha256', $queryString, $secretKey);
    }

    public function createPayment($checkoutData)
    {
        $partnerCode = $this->client->getPartnerCode();
        $secretKey = $this->client->getSecretKey();
        $baseUrl = $this->client->getBaseUrl();
        
        // Generate signature
        $signature = $this->generateSignature($checkoutData, $secretKey);
        
        // Prepare request data
        $requestData = array_merge($checkoutData, [
            'partnerCode' => $partnerCode,
            'signature' => $signature
        ]);
        
        // Send request to SePay API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseUrl . '/v1/payment/create');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        // Add API token if available
        if ($this->client->getApiToken()) {
            $headers[] = 'Authorization: Bearer ' . $this->client->getApiToken();
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        } else {
            throw new \Exception('SePay API Error: ' . $response);
        }
    }
}
