<?php

namespace Lukiman\AuthServer\Modules;

use Lukiman\AuthServer\Libraries\BaseApiModule;
use Lukiman\AuthServer\Libraries\Logger;
use Lukiman\AuthServer\Models\FileTagging;
use Lukiman\AuthServer\Models\TaggingText;
use Lukiman\Cores\Exception\NotFoundException;
use \Lukiman\Cores\Database\Query as Database_Query;

class Images extends BaseApiModule
{

    public function do_Index(array $param)
    {
        Logger::info("Path: " . json_encode($param));
        if (empty($param)) {
            return $this->getImages($this->request->getGetVars());
        }
        // param is array with example input: ["Images","mias","miau","mau","RsGSXOYi0JL.png"]
        // clear the elements, convert to lowercase except the last one which is the filename
        $param = array_map(function($item) use ($param) {
            if ($item === end($param)) {
                return $item;
            }
            return strtolower($item);
        }, $param);

        // Base upload dir is on constant UPLOAD_FILE_DIR
        // Find the file in the upload dir with the filename from param
        $filePath = urldecode(UPLOAD_FILE_DIR . '/' . implode('/', $param));
        if (!file_exists($filePath)) {
            Logger::info("Path: " . implode('/', $param));
            Logger::error('File not found: ' . $filePath);
            throw new NotFoundException('File not found', 404);
        }

        // Cache headers — use file mtime + size for a fast, stable ETag
        $lastModified = filemtime($filePath);
        $fileSize     = filesize($filePath);
        $etag         = '"' . md5($filePath . $lastModified . $fileSize) . '"';

        $photoCacheTtl = defined('PHOTO_CACHE_TTL') ? PHOTO_CACHE_TTL : 3600 * 12; // Default to 12 hours if not defined

        header('Cache-Control: public, max-age=' . $photoCacheTtl . ', immutable');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $photoCacheTtl) . ' GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
        header('ETag: ' . $etag);

        // Honour conditional requests — return 304 if browser copy is still fresh
        $ifNoneMatch     = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';

        if ($ifNoneMatch && $ifNoneMatch === $etag) {
            http_response_code(304);
            exit;
        }

        if (!$ifNoneMatch && $ifModifiedSince && strtotime($ifModifiedSince) >= $lastModified) {
            http_response_code(304);
            exit;
        }

        // Serve file
        header('Content-Type: ' . mime_content_type($filePath));
        header('Content-Length: ' . $fileSize);
        header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
        readfile($filePath);
        exit;
    }

    public function getImages(array $query): array 
    {
        throw new NotFoundException('Not found', 404);
    }
}