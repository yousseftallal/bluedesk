<?php
/** Shared validation and presentation helpers for the private company module. */
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

function company_money($value): string
{
    return number_format((float) $value, 2, '.', ',') . ' ج.م';
}

function company_input(string $key, int $max = 1000): string
{
    $value = trim((string) input($key));
    return mb_substr($value, 0, $max, 'UTF-8');
}

function company_optional_id(string $key): ?int
{
    $value = trim((string) input($key));
    return $value !== '' && ctype_digit($value) && (int) $value > 0 ? (int) $value : null;
}

function company_valid_date(string $value): string|false|null
{
    if ($value === '') return null;
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : false;
}

function company_decimal(string $key, float $default = 0): float|false
{
    $raw = trim((string) input($key));
    if ($raw === '') return $default;
    if (!preg_match('/^\d{1,11}(?:\.\d{1,2})?$/D', $raw)) return false;
    return (float) $raw;
}

function company_enum(string $value, array $allowed, string $default): string
{
    return in_array($value, $allowed, true) ? $value : $default;
}

function company_flash_error(string $message): void
{
    flash('error', $message);
}

function company_activity(string $action, string $details): void
{
    log_activity($action, mb_substr($details, 0, 500, 'UTF-8'));
}

function company_period(): array
{
    $defaultFrom = date('Y-m-01');
    try {
        $opening = db_fetch("SELECT entry_date FROM company_capital_entries WHERE entry_type='opening' AND voided_at IS NULL ORDER BY id LIMIT 1");
        if ($opening && $opening['entry_date'] <= date('Y-m-d')) {
            $defaultFrom = $opening['entry_date'];
        }
    } catch (Throwable $ex) {
        // Keep the normal current-month default until the capital migration is applied.
    }
    $from = trim((string) ($_GET['from'] ?? $defaultFrom));
    $to = trim((string) ($_GET['to'] ?? date('Y-m-d')));
    $validFrom = company_valid_date($from);
    $validTo = company_valid_date($to);
    if ($validFrom === false || $validTo === false || $validFrom > $validTo) {
        return [date('Y-m-01'), date('Y-m-d')];
    }
    return [$validFrom ?? date('Y-m-01'), $validTo ?? date('Y-m-d')];
}
