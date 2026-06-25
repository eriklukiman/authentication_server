<?php

/**
 * Helper function to convert a photo URL to a file path on the server.
 * Input: https://auth.wish.co.id/images/starbucks%20run/start%20finish/136nd500/5gNqK0WC60bMXBvidbb1Grb8f9UNCSZlts5VFhoq.jpg
 * 
 * Output: UPLOAD_FILE_DIR/starbucks run/start finish/136nd500/5gNqK0WC60bMXBvidbb1Grb8f9UNCSZlts5VFhoq.jpg
 */
function photoUrlToPath(string $url): string {
    $uploadDir = realpath(rtrim(UPLOAD_FILE_DIR, '/'));
    $relativePath = str_replace('/images/', '', parse_url($url, PHP_URL_PATH));
    return $uploadDir . '/' . urldecode($relativePath);
}

/**
 * Validates if a string is a valid ULID format.
 * 
 * ULID (Universally Unique Lexicographically Sortable Identifier) is a 26-character
 * identifier with the first character in range [0-7] and remaining 25 characters
 * from the base32 alphabet (0-9, A-H, J-N, P-T, V-Z, excluding I, L, O, U).
 * 
 * @param string $ulid The ULID string to validate
 * @return bool True if the string is a valid ULID, false otherwise
 */
function isValidUlid(string $ulid): bool
{
    return preg_match(
        '/^[0-7][0-9A-HJKMNP-TV-Z]{25}$/',
        strtoupper($ulid)
    ) === 1;
}