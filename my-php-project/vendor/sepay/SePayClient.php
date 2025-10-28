<?php

namespace SePay;

class SePayClient
{
    private $partnerCode;
    private $secretKey;
    private $apiToken;
    private $environment;
    private $baseUrl;

    public function __construct($partnerCode, $secretKey, $environment = 'sandbox', $apiToken = null)
    {
        $this->partnerCode = $partnerCode;
        $this->secretKey = $secretKey;
        $this->apiToken = $apiToken;
        $this->environment = $environment;
        
        // Set base URL based on environment
        if ($environment === 'sandbox') {
            $this->baseUrl = 'https://my.sepay.vn/userapi';
        } else {
            $this->baseUrl = 'https://my.sepay.vn/userapi';
        }
    }

    public function checkout()
    {
        return new CheckoutService($this);
    }

    public function getPartnerCode()
    {
        return $this->partnerCode;
    }

    public function getSecretKey()
    {
        return $this->secretKey;
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function getApiToken()
    {
        return $this->apiToken;
    }

    public function getEnvironment()
    {
        return $this->environment;
    }
}
