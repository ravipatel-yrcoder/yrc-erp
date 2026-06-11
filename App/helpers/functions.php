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

function isWholeNumber(float $value): bool {
    return $value == floor($value);
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


/**
 * Returns today's date string (Y-m-d) in the resolved timezone.
 *
 * Timezone resolution order:
 *   1. Explicit $timezone argument if provided.
 *   2. The authenticated company's timezone from TenantContext (set during request hydration).
 *   3. The server-level default from config('app.timezone') as the final fallback.
 *
 * Use this instead of date('Y-m-d') whenever "today" needs to reflect the
 * company's local calendar day rather than the server's UTC clock — e.g. for
 * date filter presets (today, this week, overdue) in list controllers.
 */
function dateNow(string $format, string $modifier = 'now', ?string $timezone = null): string {
    $tz = $timezone ?? (tenantContext()?->timezone ?? config('app.timezone'));
    return (new DateTime($modifier, new DateTimeZone($tz)))->format($format);
}


function localToUtc(string $localDatetime, ?string $timezone = null): string {
    $tz = new DateTimeZone($timezone ?? (tenantContext()?->timezone ?? config('app.timezone')));
    return (new DateTime($localDatetime, $tz))
        ->setTimezone(new DateTimeZone('UTC'))
        ->format('Y-m-d H:i:s');
}


/**
 * Format a MySQL date or datetime string for display.
 *
 * For DATETIME values the timezone resolution order is:
 *   1. Explicit $displayTz argument if provided.
 *   2. The authenticated company's timezone from TenantContext.
 *   3. 'UTC' as the final fallback.
 *
 * The source is always treated as UTC. DATE-only values are formatted
 * as-is with no timezone conversion.
 */
function formatMySqlDate(?string $date, ?string $format = null, string $fallback = '-', ?string $displayTz = null): string {

    if (!$date) {
        return $fallback;
    }

    try {

        // MySQL DATETIME: YYYY-MM-DD HH:MM:SS
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $date)) {
            $tz = $displayTz ?? (tenantContext()?->timezone ?? 'UTC');
            $dt = DateTime::createFromFormat('Y-m-d H:i:s', $date, new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone($tz));
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


function formatIndian($value): string {
    $amount = is_numeric($value) ? (float) $value : 0;
    $sign   = $amount < 0 ? '-' : '';
    $abs    = abs($amount);

    if ($abs >= 1_00_00_000) {
        $num    = $abs / 1_00_00_000;
        $suffix = 'Cr';
    } elseif ($abs >= 1_00_000) {
        $num    = $abs / 1_00_000;
        $suffix = 'L';
    } elseif ($abs >= 1_000) {
        $num    = $abs / 1_000;
        $suffix = 'K';
    } else {
        return $sign . '₹' . number_format($abs, 0);
    }

    $formatted = (fmod($num, 1) < 0.05) ? number_format($num, 0) : number_format($num, 1);
    return $sign . '₹' . $formatted . $suffix;
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