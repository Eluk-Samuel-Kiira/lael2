<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;

class CountryService
{
    /**
     * Get all countries
     */
    public static function getAllCountries()
    {
        return Config::get('countries.countries', []);
    }

    /**
     * Get country by code
     */
    public static function getCountryByCode($code)
    {
        $countries = self::getAllCountries();
        foreach ($countries as $country) {
            if ($country['code'] === strtoupper($code)) {
                return $country;
            }
        }
        return null;
    }

    /**
     * Get country by phone code
     */
    public static function getCountryByPhoneCode($phoneCode)
    {
        $countries = self::getAllCountries();
        $phoneCode = (string)$phoneCode;
        
        // First try exact match
        foreach ($countries as $country) {
            if ((string)$country['phone'] === $phoneCode) {
                return $country;
            }
        }
        
        // Then try prefix match (for cases where the code might be part of a longer code)
        foreach ($countries as $country) {
            if (strpos($phoneCode, (string)$country['phone']) === 0) {
                return $country;
            }
        }
        
        return null;
    }

    /**
     * Extract phone country code from phone number
     */
    public static function extractPhoneCode($phone)
    {
        // Remove any non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // If doesn't start with +, return null
        if (substr($phone, 0, 1) !== '+') {
            return null;
        }
        
        // Remove the + and get the number
        $number = substr($phone, 1);
        $countries = self::getAllCountries();
        
        // Get all phone codes and sort by length descending (longest first)
        $phoneCodes = array_map(function($country) {
            return (string)$country['phone'];
        }, $countries);
        
        // Sort by length descending
        usort($phoneCodes, function($a, $b) {
            return strlen($b) - strlen($a);
        });
        
        // Try to match the beginning of the number with a phone code
        foreach ($phoneCodes as $code) {
            if (strpos($number, $code) === 0) {
                return $code;
            }
        }
        
        return null;
    }

    /**
     * Format phone number
     */
    public static function formatPhone($phone)
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // If doesn't start with +, add it
        if (substr($phone, 0, 1) !== '+') {
            $phone = '+' . $phone;
        }
        
        $phoneCode = self::extractPhoneCode($phone);
        if (!$phoneCode) {
            return $phone;
        }
        
        $number = substr($phone, strlen($phoneCode) + 1);
        
        // Format: +XXX XXX XXX XXX
        $chunks = str_split($number, 3);
        return '+' . $phoneCode . ' ' . implode(' ', $chunks);
    }

    /**
     * Validate phone number
     */
    public static function validatePhone($phone)
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Must start with +
        if (substr($phone, 0, 1) !== '+') {
            return [
                'valid' => false,
                'message' => 'Phone number must start with a country code (e.g., +256)'
            ];
        }
        
        $number = substr($phone, 1);
        
        // Must be numeric
        if (!ctype_digit($number)) {
            return [
                'valid' => false,
                'message' => 'Phone number contains invalid characters'
            ];
        }
        
        // Must be between 8 and 15 digits
        if (strlen($number) < 8 || strlen($number) > 15) {
            return [
                'valid' => false,
                'message' => 'Phone number must be between 8 and 15 digits'
            ];
        }
        
        // Check if country code exists
        $phoneCode = self::extractPhoneCode($phone);
        if (!$phoneCode) {
            return [
                'valid' => false,
                'message' => 'Invalid or unsupported country code'
            ];
        }
        
        $country = self::getCountryByPhoneCode($phoneCode);
        
        return [
            'valid' => true,
            'country_code' => $phoneCode,
            'country_name' => $country ? $country['name'] : 'Unknown',
            'country_flag' => $country ? $country['flag'] : '',
            'formatted' => self::formatPhone($phone),
        ];
    }

    /**
     * Get country options for dropdown
     */
    public static function getCountryOptions()
    {
        $countries = self::getAllCountries();
        $options = [];
        
        foreach ($countries as $country) {
            $options[] = [
                'value' => '+' . $country['phone'],
                'label' => $country['flag'] . ' ' . $country['name'] . ' (+' . $country['phone'] . ')',
                'phone' => $country['phone'],
                'code' => $country['code'],
                'flag' => $country['flag'],
                'name' => $country['name'],
            ];
        }
        
        // Sort by name
        usort($options, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        return $options;
    }

    /**
     * Get default country (Uganda)
     */
    public static function getDefaultCountry()
    {
        return self::getCountryByCode('UG');
    }
}