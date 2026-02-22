<?php

declare(strict_types=1);

namespace Utils;

/**
 * Request input helper. Accepts both JSON body and form data (x-www-form-urlencoded / form-data)
 * so the API can be tested with Postman using either Body → raw (JSON) or Body → form-data / x-www-form-urlencoded.
 */
final class Request
{
    private static ?array $input = null;

    /**
     * Get request body as array. Uses JSON if Content-Type is application/json, otherwise $_POST.
     */
    public static function input(): array
    {
        if (self::$input !== null) {
            return self::$input;
        }
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $decoded = $raw !== '' ? json_decode($raw, true) : null;
            self::$input = is_array($decoded) ? $decoded : [];
            return self::$input;
        }
        self::$input = $_POST;
        return self::$input;
    }

    /**
     * Get a single value from input, with optional default.
     */
    public static function get(string $key, $default = null)
    {
        $input = self::input();
        return $input[$key] ?? $default;
    }

    /**
     * Ensure array fields from form (e.g. subject_ids=1,2,3 or subject_ids[]=1&subject_ids[]=2) become arrays.
     */
    public static function inputWithArrays(array $arrayKeys = []): array
    {
        $input = self::input();
        foreach ($arrayKeys as $key) {
            if (!array_key_exists($key, $input)) {
                continue;
            }
            $v = $input[$key];
            if (is_array($v)) {
                continue;
            }
            if (is_string($v) && $v !== '') {
                $input[$key] = array_map('trim', explode(',', $v));
            }
        }
        return $input;
    }
}
