<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPhoneNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Skip validation if value is empty (let required rule handle it)
        if (empty($value)) {
            $fail('The phone number is required.');
            return;
        }

        // Remove spaces, dashes, parentheses
        $cleaned = preg_replace('/[\s\-\(\)]/', '', $value);
        
        // Check if starts with +
        if (substr($cleaned, 0, 1) !== '+') {
            $fail('The phone number must start with a country code (e.g., +256 for Uganda).');
            return;
        }
        
        // Remove + and check if it's numeric
        $numberPart = substr($cleaned, 1);
        
        if (!ctype_digit($numberPart)) {
            $fail('The phone number contains invalid characters.');
            return;
        }
        
        // Check length (8-15 digits)
        if (strlen($numberPart) < 8 || strlen($numberPart) > 15) {
            $fail('The phone number must be between 8 and 15 digits.');
            return;
        }
        
        // List of valid country codes (from your config)
        $validCountryCodes = ['1', '7', '20', '27', '30', '31', '32', '33', '34', '36', '39', '40', '41', '43', '44', '45', '46', '47', '48', '49', '51', '52', '53', '54', '55', '56', '57', '58', '60', '61', '62', '63', '64', '65', '66', '81', '82', '84', '86', '90', '91', '92', '93', '94', '95', '98', '211', '212', '213', '216', '218', '220', '221', '222', '223', '224', '225', '226', '227', '228', '229', '230', '231', '232', '233', '234', '235', '236', '237', '238', '239', '240', '241', '242', '243', '244', '245', '246', '247', '248', '249', '250', '251', '252', '253', '254', '255', '256', '257', '258', '260', '261', '262', '263', '264', '265', '266', '267', '268', '269', '290', '291', '297', '298', '299', '350', '351', '352', '353', '354', '355', '356', '357', '358', '359', '370', '371', '372', '373', '374', '375', '376', '377', '378', '379', '380', '381', '382', '383', '385', '386', '387', '389', '420', '421', '423', '500', '501', '502', '503', '504', '505', '506', '507', '508', '509', '590', '591', '592', '593', '594', '595', '596', '597', '598', '599', '670', '672', '673', '674', '675', '676', '677', '678', '679', '680', '681', '682', '683', '684', '685', '686', '687', '688', '689', '690', '691', '692', '850', '852', '853', '855', '856', '880', '886', '960', '961', '962', '963', '964', '965', '966', '967', '968', '970', '971', '972', '973', '974', '975', '976', '977', '992', '993', '994', '995', '996', '998'];
        
        // Check if the number starts with a valid country code
        $hasValidCode = false;
        foreach ($validCountryCodes as $code) {
            if (strpos($numberPart, $code) === 0) {
                $hasValidCode = true;
                break;
            }
        }
        
        if (!$hasValidCode) {
            $fail('Invalid or unsupported country code. Please use a valid country code (e.g., 256 for Uganda).');
            return;
        }
        
        // Phone is valid
        return;
    }
}