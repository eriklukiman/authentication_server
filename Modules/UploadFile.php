<?php

namespace Lukiman\AuthServer\Modules;

use Lukiman\AuthServer\Libraries\Exceptions\UploadException;
use Lukiman\AuthServer\Libraries\M2MBase;
use Lukiman\Cores\Exception\ServerErrorException;
use Psr\Http\Message\UploadedFileInterface;

class UploadFile extends M2MBase {

    public function do_Index($param) {
        $method = strtolower($this->psrRequest->getMethod());

        if ($method == 'post') {
            $uploadedFiles = $this->psrRequest->getUploadedFiles();
            if (empty($uploadedFiles['image'])) {
                throw new ServerErrorException('No file uploaded', 400);
            }
            $properties = (array) $this->psrRequest->getParsedBody();
            return $this->uploadPhoto($param, $properties);
        } else {
            throw new ServerErrorException('Method not allowed', 405);
        }
    }

    public function uploadPhoto(mixed $param, array $properties) {
        if (!is_array($param)) {
            throw new ServerErrorException('Invalid parameter', 400);
        }

        $baseDir = UPLOAD_FILE_DIR;

        if (file_exists($baseDir) && !is_dir($baseDir)) {
            throw new ServerErrorException('Upload path is not a directory', 500);
        }

        if (!file_exists($baseDir) && !mkdir($baseDir, 0777, true) && !is_dir($baseDir)) {
            throw new ServerErrorException('Failed to create upload directory', 500);
        }

        $maxSize = 6 * 1024 * 1024;
        $allowedTypes = ['image/jpeg', 'image/jpeg', 'image/png', 'image/gif'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $uploadedFiles = $this->psrRequest->getUploadedFiles();
        $file = $uploadedFiles['image'] ?? null;

        if (!$file instanceof UploadedFileInterface) {
            throw new ServerErrorException('No file uploaded', 400);
        }

        $clientMediaType = $file->getClientMediaType();
        if ($clientMediaType && !in_array($clientMediaType, $allowedTypes, true)) {
            throw new ServerErrorException('Invalid file type', 400);
        }

        $extensionIndex = array_search($clientMediaType, $allowedTypes, true);
        $extension = $extensionIndex === false ? null : $allowedExtensions[$extensionIndex];
        if (empty($extension)) {
            $clientExtension = strtolower(pathinfo((string) $file->getClientFilename(), PATHINFO_EXTENSION));
            if (!in_array($clientExtension, $allowedExtensions, true)) {
                throw new ServerErrorException('Invalid file type', 400);
            }
            $extension = $clientExtension;
        }

        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new UploadException($file->getError());
        }
        if (($file->getSize() ?? 0) > $maxSize) {
            throw new ServerErrorException('File size exceeds limit', 400);
        }

        $forceReplace = filter_var($properties['forceReplace'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $path = $this->normalizeRelativePath((string) ($properties['path'] ?? ''));
        $filename = $this->randomString(40) . '.' . $extension;
        $filename = basename($filename);

        $targetDir = $baseDir . ($path === '' ? '' : $path . '/');
        $fullPath = $targetDir . $filename;
        $urlPath = $this->urlSafe(preg_replace('/\/{2,}/', '/', $baseDir . '/' . ($path === '' ? '' : $path . '/') . $filename));
        $urlPath = str_replace($baseDir,'', $urlPath);

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
            assert(is_dir($targetDir));
        }

        if (is_file($fullPath)) {
            if (!$forceReplace) {
                while (is_file($fullPath)) {
                    $filename = $this->randomString(5) . '_' . $filename;
                    $fullPath = $targetDir . $filename;
                    $urlPath = $this->urlSafe(preg_replace('/\/{2,}/', '/', $baseDir . '/' . ($path === '' ? '' : $path . '/') . $filename));
                    $urlPath = str_replace($baseDir,'', $urlPath);
                }
            } else if (!unlink($fullPath)) {
                throw new ServerErrorException('Failed to replace existing file', 500);
            }
        }

        try {
            $file->moveTo($fullPath);
        } catch (\Throwable $e) {
            throw new ServerErrorException('File upload failed', 500);
        }

        return [
            'status' => 'success',
            'message' => 'File uploaded successfully',
            'path' => 'images/' . $urlPath,
        ];
    }

    private function randomString(int $length = 10): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    private function urlSafe(string $path): string
    {
        return implode('/', array_map(function ($value) {
            return rawurlencode($value);
        }, explode('/', $path)));
    }

    private function normalizeRelativePath(string $path): string
    {
        $path = strtolower($path);
        $path = trim(str_replace('\\', '/', $path), '/');
        if ($path === '') {
            return '';
        }
        if (str_contains($path, '..')) {
            throw new ServerErrorException('Invalid path', 400);
        }

        return preg_replace('/\/{2,}/', '/', $path);
    }

    public function do_Tagging(array $param)
    {
        $method = strtolower($this->psrRequest->getMethod());
        switch ($method) {
            case 'post':
                $this->addTag($param);
                break;
            case 'delete':
                $this->deleteTag($param);
                break;
            case 'put':
                $this->editTag($param);
                break;
            default:
                throw new ServerErrorException('Method not allowed', 405);
        }    
    }

    private function addTag(array $param): void
    {
    }

    private function deleteTag(array $param): void
    {
    }

    private function editTag(array $param): void
    {
    }
}