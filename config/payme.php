<?php

return [
    'merchant_id' => env('PAYME_MERCHANT_ID'),
    'login'       => env('PAYME_LOGIN', 'Paycom'),
    'key'     => env('PAYME_KEY'),
    'test_key' => env('PAYME_TEST_KEY'),
];