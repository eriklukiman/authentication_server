<?php

namespace Lukiman\AuthServer\Modules;

use League\OAuth2\Server\Exception\OAuthServerException;
use Lukiman\AuthServer\Libraries\Repositories\NullAccessTokenRepository;
use League\OAuth2\Server\ResourceServer;
use Lukiman\AuthServer\Libraries\BaseApiModule;
use Lukiman\Cores\Exception\PermissionDeniedException;
use Lukiman\Cores\Exception\ServerErrorException;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ServerRequestInterface;

class Upload_File extends BaseApiModule {

    private ServerRequestInterface $psrRequest;

    public function __construct()
    {
        parent::__construct();
        $psr17Factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $creator = new \Nyholm\Psr7Server\ServerRequestCreator(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $psr17Factory
        );
        $this->psrRequest = $creator->fromGlobals();
    }

    public function beforeExecute(): void
    {
        try {

            parent::beforeExecute();

            $resourceServer = new ResourceServer(
                new NullAccessTokenRepository(),
                'file://' . __DIR__ . '/../public.key'
            );

            $this->psrRequest = $resourceServer
                    ->validateAuthenticatedRequest($this->psrRequest);
        } catch (OAuthServerException $e) {
            throw new PermissionDeniedException('OAuth Unauthorized: ' . $e->getMessage());
        }
        catch (\Exception $e) {
            throw new PermissionDeniedException('Unauthorized: ' . $e->getMessage());
        }
    }

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

        $baseDir = '/images/';
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

        $usedDir = rtrim(__DIR__ . '/' . trim($baseDir, '/'), '/') . '/';
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new ServerErrorException('File upload error', 400);
        }
        if (($file->getSize() ?? 0) > $maxSize) {
            throw new ServerErrorException('File size exceeds limit', 400);
        }

        $forceReplace = filter_var($properties['forceReplace'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $path = $this->normalizeRelativePath((string) ($properties['path'] ?? ''));
        $filename = $this->randomString(40) . '.' . $extension;
        $filename = basename($filename);

        $targetDir = $usedDir . ($path === '' ? '' : $path . '/');
        $fullPath = $targetDir . $filename;
        $urlPath = $this->urlSafe(preg_replace('/\/{2,}/', '/', $baseDir . '/' . ($path === '' ? '' : $path . '/') . $filename));

        if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
            throw new ServerErrorException('Failed to create upload directory', 500);
        }

        if (is_file($fullPath)) {
            if (!$forceReplace) {
                while (is_file($fullPath)) {
                    $filename = $this->randomString(5) . '_' . $filename;
                    $fullPath = $targetDir . $filename;
                    $urlPath = $this->urlSafe(preg_replace('/\/{2,}/', '/', $baseDir . '/' . ($path === '' ? '' : $path . '/') . $filename));
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
            'path' => $urlPath,
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
        $path = trim(str_replace('\\', '/', $path), '/');
        if ($path === '') {
            return '';
        }
        if (str_contains($path, '..')) {
            throw new ServerErrorException('Invalid path', 400);
        }

        return preg_replace('/\/{2,}/', '/', $path);
    }
}