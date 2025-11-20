<?php

namespace SePay\Builders;

class CheckoutBuilder
{
    private $data = [];

    public static function make()
    {
        return new self();
    }

    public function paymentMethod($method)
    {
        $this->data['paymentMethod'] = $method;
        return $this;
    }

    public function currency($currency)
    {
        $this->data['currency'] = $currency;
        return $this;
    }

    public function orderInvoiceNumber($invoiceNumber)
    {
        $this->data['orderInvoiceNumber'] = $invoiceNumber;
        return $this;
    }

    public function orderAmount($amount)
    {
        $this->data['orderAmount'] = $amount;
        return $this;
    }

    public function operation($operation)
    {
        $this->data['operation'] = $operation;
        return $this;
    }

    public function orderDescription($description)
    {
        $this->data['orderDescription'] = $description;
        return $this;
    }

    public function customerName($name)
    {
        $this->data['customerName'] = $name;
        return $this;
    }

    public function customerEmail($email)
    {
        $this->data['customerEmail'] = $email;
        return $this;
    }

    public function customerPhone($phone)
    {
        $this->data['customerPhone'] = $phone;
        return $this;
    }

    public function callbackUrl($url)
    {
        $this->data['callbackUrl'] = $url;
        return $this;
    }

    public function cancelUrl($url)
    {
        $this->data['cancelUrl'] = $url;
        return $this;
    }

    public function returnUrl($url)
    {
        $this->data['returnUrl'] = $url;
        return $this;
    }

    public function successUrl($url)
    {
        $this->data['successUrl'] = $url;
        return $this;
    }

    public function errorUrl($url)
    {
        $this->data['errorUrl'] = $url;
        return $this;
    }

    public function build()
    {
        return $this->data;
    }
}
