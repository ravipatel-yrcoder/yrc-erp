<?php
function isValidPrice($value): bool
{
    // Must be numeric and non-negative
    if (!is_numeric($value) || $value < 0) {
        return false;
    }

    // Match up to 2 decimal places
    return preg_match('/^\d+(\.\d{1,2})?$/', (string)$value) === 1;
}

function isNonNegativeNumeric($value): bool {
    return is_numeric($value) && $value >= 0;
}

function isPositiveNumeric($value): bool {
    return is_numeric($value) && $value > 0;
}

function validationErrMsg($key, $field): String {

    $validationErrMsgs = config("errors.validation", []);
    $msg = $validationErrMsgs[$key] ?? "$field is invalid";
    return str_replace(':field', $field, $msg);
}