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
