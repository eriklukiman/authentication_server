<?php

namespace Lukiman\AuthServer\Modules;

use Lukiman\AuthServer\Libraries\BaseApiModule;
use Lukiman\Cores\Exception\NotFoundException;
use Lukiman\Cores\Exception\ServerErrorException;

class Images extends BaseApiModule
{
    public function do_Index(array $param)
    {
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
        $filePath = UPLOAD_FILE_DIR . '/' . implode('/', $param);
        if (!file_exists($filePath)) {
            throw new NotFoundException('File not found', 404);
        }

        // Serve file with chunks 8KB to display in browser
        header('Content-Type: ' . mime_content_type($filePath));
        header('Content-Length: ' . filesize($filePath));
        header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
        readfile($filePath);
        exit;
    }

    public function getImages(array $query): array 
    {
        return [];
    }
}