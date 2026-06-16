<?php

/**
 * Parse Accept-Language header to detect preferred language.
 * Pure function — no MODX dependency, suitable for unit testing.
 *
 * @param string $acceptLanguage HTTP Accept-Language header value (e.g. "en-US,en;q=0.9,ru;q=0.8")
 * @param array $availableKeys Available language keys (e.g. ['ru', 'en'])
 * @return string|null Matched language key or null
 */
function localizator_detect_language_from_accept($acceptLanguage, array $availableKeys)
{
    if (empty($acceptLanguage) || empty($availableKeys)) {
        return !empty($availableKeys) ? $availableKeys[0] : null;
    }

    $preferred = [];
    foreach (array_map('trim', explode(',', $acceptLanguage)) as $part) {
        $q = 1.0;
        if (strpos($part, ';q=') !== false) {
            list($part, $q) = explode(';q=', $part, 2);
            $q = (float) trim($q);
        }
        $lang = strtolower(substr(trim($part), 0, 2));
        $preferred[$lang] = max($q, $preferred[$lang] ?? 0);
    }
    arsort($preferred);

    foreach (array_keys($preferred) as $lang) {
        if (in_array($lang, $availableKeys, true)) {
            return $lang;
        }
        foreach ($availableKeys as $key) {
            if (strpos(strtolower($key), $lang) === 0) {
                return $key;
            }
        }
    }

    return $availableKeys[0];
}
