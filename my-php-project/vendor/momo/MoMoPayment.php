<?php
/**
 * MoMo Payment SDK
 * Based on https://github.com/momo-wallet/payment
 * 
 * @version 1.0.0
 * @author Event Management System
 */

class MoMoPayment {
    private $partnerCode;
    private $accessKey;
    private $secretKey;
    private $endpoint;
    private $returnUrl;
    private $notifyUrl;
    
    public function __construct($config) {
        $this->partnerCode = $config['partner_code'];
        $this->accessKey = $config['access_key'];
        $this->secretKey = $config['secret_key'];
        $this->endpoint = $config['endpoint'];
        $this->returnUrl = $config['return_url'];
        $this->notifyUrl = $config['notify_url'];
    }
    
    /**
     * Create payment request
     */
    public function createPayment($orderId, $amount, $orderInfo, $extraData = '') {
        $requestId = time() . '';
        $orderId = $orderId . '_' . time();
        
        // Before sign HMAC SHA256 signature
        $rawHash = "accessKey=" . $this->accessKey . 
                   "&amount=" . $amount . 
                   "&extraData=" . $extraData . 
                   "&ipnUrl=" . $this->notifyUrl . 
                   "&orderId=" . $orderId . 
                   "&orderInfo=" . $orderInfo . 
                   "&partnerCode=" . $this->partnerCode . 
                   "&redirectUrl=" . $this->returnUrl . 
                   "&requestId=" . $requestId . 
                   "&requestType=captureWallet";
        
        $signature = hash_hmac('sha256', $rawHash, $this->secretKey);
        
        $data = [
            'partnerCode' => $this->partnerCode,
            'accessKey' => $this->accessKey,
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $this->returnUrl,
            'ipnUrl' => $this->notifyUrl,
            'extraData' => $extraData,
            'requestType' => 'captureWallet',
            'signature' => $signature,
            'lang' => 'vi'
        ];
        
        $result = $this->execPostRequest($this->endpoint, json_encode($data));
        $jsonResult = json_decode($result, true);
        
        return $jsonResult;
    }
    
    /**
     * Verify payment result
     */
    public function verifyPayment($data) {
        $signature = $data['signature'];
        unset($data['signature']);
        
        $rawHash = '';
        foreach ($data as $key => $value) {
            $rawHash .= $key . '=' . $value . '&';
        }
        $rawHash = rtrim($rawHash, '&');
        
        $calculatedSignature = hash_hmac('sha256', $rawHash, $this->secretKey);
        
        return $signature === $calculatedSignature;
    }
    
    /**
     * Generate QR Code for offline payment
     */
    public function generateQRCode($phone, $amount, $note = '') {
        $qrString = "momo://transfer?phone={$phone}&amount={$amount}&note=" . urlencode($note);
        
        return [
            'qr_string' => $qrString,
            'qr_data' => [
                'type' => 'momo',
                'phone' => $phone,
                'amount' => $amount,
                'note' => $note,
                'qr_url' => $qrString
            ]
        ];
    }
    
    /**
     * Execute POST request
     */
    private function execPostRequest($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $result = curl_exec($ch);
        curl_close($ch);
        
        return $result;
    }
    
    /**
     * Get payment status
     */
    public function getPaymentStatus($orderId) {
        $requestId = time() . '';
        
        $rawHash = "accessKey=" . $this->accessKey . 
                   "&orderId=" . $orderId . 
                   "&partnerCode=" . $this->partnerCode . 
                   "&requestId=" . $requestId;
        
        $signature = hash_hmac('sha256', $rawHash, $this->secretKey);
        
        $data = [
            'partnerCode' => $this->partnerCode,
            'accessKey' => $this->accessKey,
            'requestId' => $requestId,
            'orderId' => $orderId,
            'signature' => $signature,
            'lang' => 'vi'
        ];
        
        $result = $this->execPostRequest($this->endpoint . '/query', json_encode($data));
        $jsonResult = json_decode($result, true);
        
        return $jsonResult;
    }
    
    /**
     * Refund payment
     */
    public function refundPayment($orderId, $amount, $description = '') {
        $requestId = time() . '';
        $transId = $orderId; // Assuming transId is same as orderId for simplicity
        
        $rawHash = "accessKey=" . $this->accessKey . 
                   "&amount=" . $amount . 
                   "&description=" . $description . 
                   "&orderId=" . $orderId . 
                   "&partnerCode=" . $this->partnerCode . 
                   "&requestId=" . $requestId . 
                   "&transId=" . $transId;
        
        $signature = hash_hmac('sha256', $rawHash, $this->secretKey);
        
        $data = [
            'partnerCode' => $this->partnerCode,
            'accessKey' => $this->accessKey,
            'requestId' => $requestId,
            'orderId' => $orderId,
            'transId' => $transId,
            'amount' => $amount,
            'description' => $description,
            'signature' => $signature,
            'lang' => 'vi'
        ];
        
        $result = $this->execPostRequest($this->endpoint . '/refund', json_encode($data));
        $jsonResult = json_decode($result, true);
        
        return $jsonResult;
    }
}
