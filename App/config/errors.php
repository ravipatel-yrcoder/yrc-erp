<?php
return [
    'validation' => [
        // global & generic errors
        'required' => ':field is required',
        'one_item_required' => 'At least one :field is required',
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
        'does_not_exist' => ':field does not exist',

        // Feature specific errors

        // Inventory
        'can_not_change_stock_exist' => 'Stock record exist, can not change :field',

        // Inventory
        'no_stock_adjusted' => 'No stock available to adjust',

        // purchase orders
        'po_line_item_duplicate' => 'Duplicate line items detected',

        // Registration
        'password_mismatch'   => 'Passwords do not match',
        'password_too_short'  => 'Password must be at least 8 characters',
        'email_already_registered' => 'This email is already registered. Please log in or use a different email',
        'invalid_activation_token' => 'This activation link is invalid or has already been used',
        'expired_activation_token' => 'This activation link has expired. Please sign up again to receive a new one',

        // Password reset
        'invalid_reset_token' => 'This password reset link is invalid or has already been used',
        'expired_reset_token' => 'This password reset link has expired. Please request a new one',
    ]
];