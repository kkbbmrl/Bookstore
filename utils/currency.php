<?php
/**
 * Currency utility functions for Bookstore
 */

/**
 * Convert USD to DZD
 * 
 * @param float $usd_amount Amount in US Dollars
 * @return float Amount in Algerian Dinars
 */
function usd_to_dzd($usd_amount) {
    // Exchange rate: 1 USD = approximately 135 DZD (as of May 2025)
    // You can update this rate or implement an API call to get live rates
    $exchange_rate = 135;
    return round($usd_amount * $exchange_rate, 2);
}

/**
 * Format currency with the appropriate symbol
 * 
 * @param float $amount The amount to format
 * @param string $currency The currency code (DZD, USD, etc)
 * @return string Formatted currency string
 */
function format_currency($amount, $currency = 'DZD') {
    if ($currency === 'DZD') {
        return number_format($amount, 2) . ' ' . $currency;
    } elseif ($currency === 'USD') {
        return '$' . number_format($amount, 2);
    } else {
        return number_format($amount, 2) . ' ' . $currency;
    }
}