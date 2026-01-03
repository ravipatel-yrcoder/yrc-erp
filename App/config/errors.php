<?php
return [
    'validation' => [
        'required' => ':field is required',
        'invalid' => ':field is invalid',
        'missing_or_invalid' => ':field is missing or invalid',
        'duplicate' => ':field already exists',
        'non_negative' => ':field must be zero or greater',
        'invalid_price' => ':field must be a valid price',
        'positive_number' => ':field must be a positive number',
        'number' => ':field must be a number',
        'boolean' => ':field must be true or false',
        'max_length' => ':field exceeds the allowed length',
        'not_supported_lot_or_serial' => ':field tracking method does not support serial or lot numbers',
        'does_not_match_qty' => ':field count does not match quantity',
        'can_not_adjusted' => ':field can not adjusted',
        'no_stock_adjusted' => 'No stock available to adjust',
        'does_not_exist' => ':field does not exist',
    ]
];