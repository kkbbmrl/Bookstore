<?php

namespace Bookstore\Services;

// Ensure Composer autoloader is included
require_once __DIR__ . '/../vendor/autoload.php';

use Chargily\ChargilyPay\Auth\Credentials;
use Chargily\ChargilyPay\ChargilyPay;

class ChargilyPayService
{
    private ChargilyPay $chargilyPay;
    
    public function __construct()
    {
        // Load configuration
        $config = require __DIR__ . '/../config/chargily.php';
        
        // Initialize credentials
        $credentials = new Credentials([
            'mode' => $config['mode'],
            'public' => $config['public_key'],
            'secret' => $config['secret_key'],
        ]);
        
        // Initialize Chargily Pay
        $this->chargilyPay = new ChargilyPay($credentials);
    }
    
    public function getBalance()
    {
        return $this->chargilyPay->balance()->get();
    }
    
    public function getAllCheckouts()
    {
        return $this->chargilyPay->checkouts()->all();
    }
    
    public function getAllCustomers()
    {
        return $this->chargilyPay->customers()->all();
    }
    
    public function getAllPaymentLinks()
    {
        return $this->chargilyPay->payment_links()->all();
    }
    
    public function getAllPrices()
    {
        return $this->chargilyPay->prices()->all();
    }
    
    public function getAllProducts()
    {
        return $this->chargilyPay->products()->all();
    }
    
    public function getWebhookDetails()
    {
        return $this->chargilyPay->webhook()->get();
    }
    
    // Add more methods as needed
    
    // Direct access to the Chargily Pay instance
    public function getClient()
    {
        return $this->chargilyPay;
    }
}