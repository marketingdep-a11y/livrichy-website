<?php

namespace App\Services\ListingImport\Support;

use InvalidArgumentException;

class Normalizer
{
    /**
     * Collapse whitespace and trim strings. Returns null for empty values.
     */
    public static function sanitizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (! is_scalar($value)) {
            throw new InvalidArgumentException('Expected scalar value for string sanitization.');
        }

        $string = trim((string) $value);
        $string = preg_replace('/\s+/u', ' ', $string) ?? '';

        return $string === '' ? null : $string;
    }

    /**
     * Preserve new lines while trimming around them.
     */
    public static function sanitizeMultiline(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_scalar($value)) {
            throw new InvalidArgumentException('Expected scalar value for multiline sanitization.');
        }

        $string = str_replace(["\r\n", "\r"], "\n", (string) $value);
        $string = preg_replace('/[ \t]+\n/u', "\n", $string) ?? $string;
        $string = preg_replace('/\n[ \t]+/u', "\n", $string) ?? $string;
        $string = trim($string);

        return $string === '' ? null : $string;
    }

    /**
     * Normalize localized float inputs (handles commas and spaces).
     */
    public static function sanitizeFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 1.0 : 0.0;
        }

        if (! is_scalar($value)) {
            throw new InvalidArgumentException('Expected scalar value for float sanitization.');
        }

        $normalized = str_replace([' ', "\u{00A0}"], '', (string) $value);
        $normalized = str_replace(',', '.', $normalized);

        if (! is_numeric($normalized)) {
            throw new InvalidArgumentException('Unable to normalize float value.');
        }

        return (float) $normalized;
    }

    /**
     * Normalize known status values to slugs.
     */
    public static function sanitizeStatus(mixed $value): ?string
    {
        $string = self::sanitizeString($value);

        if ($string === null) {
            return null;
        }

        $slug = strtolower(str_replace([' ', '-'], '_', $string));

        $map = [
            'active' => 'active',
            'available' => 'active',
            'for_sale' => 'active',
            'for_rent' => 'active',
            'coming_soon' => 'pending',
            'pending' => 'pending',
            'under_contract' => 'pending',
            'sold' => 'sold',
            'closed' => 'sold',
            'leased' => 'sold',
            'off_market' => 'off_market',
            'withdrawn' => 'off_market',
            'inactive' => 'off_market',
            'published' => 'published',
            'publish' => 'published',
            'live' => 'published',
            'unpublished' => 'unpublished',
            'hidden' => 'unpublished',
            'draft' => 'draft',
            'archived' => 'archived',
        ];

        return $map[$slug] ?? null;
    }

    /**
     * Map feed offering types to Statamic property_status values.
     */
    public static function mapOfferingType(mixed $value): ?string
    {
        $string = self::sanitizeString($value);

        if ($string === null) {
            return null;
        }

        $map = [
            'rs' => 'sale',
            'rr' => 'rent',
        ];

        return $map[strtolower($string)] ?? null;
    }

    /**
     * Map property type codes to taxonomy slugs.
     */
    public static function mapPropertyType(mixed $value): ?string
    {
        $string = self::sanitizeString($value);

        if ($string === null) {
            return null;
        }

        $map = [
            'ap' => 'apartment',
            'th' => 'townhouse',
            'vh' => 'villa',
            'dx' => 'duplex',
        ];

        return $map[strtolower($string)] ?? null;
    }

    /**
     * Normalize integer-like inputs.
     */
    public static function sanitizeInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (! is_scalar($value)) {
            throw new InvalidArgumentException('Expected scalar value for integer sanitization.');
        }

        $normalized = str_replace([' ', "\u{00A0}"], '', (string) $value);
        $normalized = str_replace(',', '.', $normalized);

        if (! is_numeric($normalized)) {
            throw new InvalidArgumentException('Unable to normalize integer value.');
        }

        return (int) round((float) $normalized);
    }

    /**
     * Normalize date/datetime strings to `Y-m-d`.
     */
    public static function sanitizeDate(mixed $value): ?string
    {
        $string = self::sanitizeString($value);

        if ($string === null) {
            return null;
        }

        try {
            $date = new \DateTimeImmutable($string);
        } catch (\Exception $exception) {
            throw new InvalidArgumentException('Unable to normalize date value.', 0, $exception);
        }

        return $date->format('Y-m-d');
    }

    /**
     * Validate and normalize an URL value.
     */
    public static function sanitizeUrl(mixed $value): ?string
    {
        $string = self::sanitizeString($value);

        if ($string === null) {
            return null;
        }

        $validated = filter_var($string, FILTER_VALIDATE_URL);

        return $validated === false ? null : $validated;
    }

    public static function formatFloat(?float $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $formatted = rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }
}
