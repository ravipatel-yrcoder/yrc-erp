<?php

/**
 * Get or set the hydrated TenantContext for the current request.
 *
 * Called with no argument by controllers/services to read the context.
 * Called with a context argument by Middleware_AppAuth to store it once.
 *
 * Returns null when called before middleware has set it (e.g. on public pages).
 */
function tenantContext(?Service_TenantContext $set = null): ?Service_TenantContext
{
    static $instance = null;
    if ($set !== null) {
        $instance = $set;
    }
    return $instance;
}

function isValidPrice($value): bool {
    
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

function isValidEmail($value): bool {
    
    if (!is_string($value)) {
        return false;
    }

    $value = trim($value);

    if ($value === '') {
        return false;
    }

    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
}

function validationErrMsg($key, $field): String {

    $validationErrMsgs = config("errors.validation", []);
    $msg = $validationErrMsgs[$key] ?? "$field is invalid";
    return str_replace(':field', $field, $msg);
}

function getCountries() {
    return config("countries");
}

function getCurrencies() {
    return config("currencies");
}

function getTimezones() {
    return config("timezones");
}


function formatMySqlDate(?string $date, ?string $format = null, string $fallback = '-'): string {
    
    if (!$date) {
        return $fallback;
    }

    try {
        
        // MySQL DATETIME: YYYY-MM-DD HH:MM:SS
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $date)) {
            $dt = DateTime::createFromFormat('Y-m-d H:i:s', $date);
            $outputFormat = $format ?? config('sys_default.dateTimeFormat');
        }
        // MySQL DATE: YYYY-MM-DD
        elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $dt = DateTime::createFromFormat('Y-m-d', $date);
            $outputFormat = $format ?? config('sys_default.dateFormat');
        } else {
            return $fallback;
        }

        if (!$dt) {
            return $fallback;
        }

        return $dt->format($outputFormat);

    } catch (Throwable) {
        return $fallback;
    }
}


function unformatNumber($value): float {
    
    if ($value === null || $value === '') {
        return 0;
    }

    return (float) preg_replace('/[^0-9\.\-]/', '', (string) $value);
}

function formatCurrency($value, array $options = []): string {
    
    $locale = $options['locale'] ?? config('sys_default.locale');
    $currency = $options['currency'] ?? config('sys_default.currency');
    $min = $options['minimumFractionDigits'] ?? 2;
    $max = $options['maximumFractionDigits'] ?? 4;

    $amount = is_numeric($value) ? (float) $value : 0;

    $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
    $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $min);
    $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $max);

    return $formatter->formatCurrency($amount, $currency);
}


function formatPrice($value, array $options = []): string {

    $locale = $options['locale'] ?? config('sys_default.locale');
    $min = $options['minimumFractionDigits'] ?? 2;
    $max = $options['maximumFractionDigits'] ?? 4;

    $amount = is_numeric($value) ? (float) $value : 0;

    $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
    $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $min);
    $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $max);

    return $formatter->format($amount);
}

function formatQty($qty): string {
    
    return number_format((float) ($qty ?? 0), 2, '.', '');
}

function normalizeIndianPhone($mobile) {
    
    $mobile = preg_replace('/\D/', '', $mobile); // remove non-digits

    if( !$mobile ) {
        return null;
    }

    // Remove leading 0
    if (str_starts_with($mobile, '0')) {
        $mobile = substr($mobile, 1);
    }

    // Remove leading 91 if present
    if (str_starts_with($mobile, '91') && strlen($mobile) === 12) {
        $mobile = substr($mobile, 2);
    }

    // Remove leading 0
    if (str_starts_with($mobile, '0')) {
        $mobile = substr($mobile, 1);
    }

    return $mobile ?: null;
}

function methodNotAllowed() {
    return response([], "Method not allowed", 405)->sendJson();
}