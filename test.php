<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "PHP is working!\n";
echo "Current directory: " . getcwd() . "\n";

if (file_exists('artisan')) {
    echo "artisan file exists\n";
} else {
    echo "artisan file NOT found!\n";
}

if (is_readable('artisan')) {
    echo "artisan is readable\n";
} else {
    echo "artisan is NOT readable\n";
}