<?php

namespace Lukiman\AuthServer\Modules;

use Lukiman\AuthServer\Libraries\Exceptions\UploadException;
use Lukiman\AuthServer\Libraries\Logger;
use Lukiman\AuthServer\Libraries\M2MBase;
use Lukiman\AuthServer\Models\FileTagging;
use Lukiman\AuthServer\Models\TaggingText;
use Lukiman\Cores\Exception\ServerErrorException;
use Psr\Http\Message\UploadedFileInterface;

class UploadFile extends M2MBase {

    private FileTagging $fileTaggingModel;
    private TaggingText $taggingTextModel;

    public function __construct()
    {
        parent::__construct();
        $this->fileTaggingModel = new FileTagging();
        $this->taggingTextModel = new TaggingText();
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
                return $this->addTag($param);
            case 'delete':
                return $this->deleteTag($param);
            case 'put':
                return $this->editTag($param);
            default:
                throw new ServerErrorException('Method not allowed', 405);
        }
    }

    private function addTag(array $param): array
    {
        $payload = $this->getParsedBody();
        $data = $payload;
        if (!is_array($data) || empty($data)) {
            throw new ServerErrorException('No data provided for insert', 400);
        }

        try {
            $taggingFulltext = [];
            if (isset($data['mftgDescription']) && !empty($data['mftgDescription'])) {
                $taggingFulltext = $data['mftgDescription'];
                unset($data['mftgDescription']);
            }
            $insertId = $this->fileTaggingModel->insert($data);
            if (!empty($taggingFulltext)) {
                // loop fulltext and extract content, first as fulltext and second as number if the content is number
                // example: G10001 will be fulltext G10001 and number 10001, while G10002 will be fulltext G10002 and number 10002, but if the content is not number like GABC then the fulltext will be GABC and number will be empty
                // example: G10A01 will be fulltext G10A01 and number will be empty, while G10001 will be fulltext G10001 and number 10001
                // the number will be extracted by removing first and last non-numeric characters from the content
                foreach ($taggingFulltext as $content) {
                    $number =  preg_replace('/^\D+|\D+$/', '', $content);
                    $taggingText = [
                        'mftxText'      => $content,
                        'mftxNumber'    =>is_numeric($number) ? $number : '',
                    ];
                    $taggingText['mftxId'] = $insertId;
                    $this->taggingTextModel->insert($taggingText);
                }
            }
        } catch (\Throwable $e) {
            Logger::error('Failed to insert tagging: ' . $e);
            throw new ServerErrorException('Insert failed: ' . $e->getMessage(), 500);
        }

        return [
            'status' => 'success',
            'message' => 'Record inserted',
            'data' => [
                'mftgId' => (int) $insertId,
            ],
        ];
    }

    private function deleteTag(array $param): array
    {
        $id = $param[0] ?? ($this->psrRequest->getQueryParams()['id'] ?? null);
        if ($id === null || $id === '') {
            throw new ServerErrorException('ID is required for delete', 404);
        }

        try {
            $this->fileTaggingModel->delete($id);
            $this->taggingTextModel->delete($id);
        } catch (\Throwable $e) {
            Logger::error('Failed to delete tagging: ' . $e);
            throw new ServerErrorException('Delete failed: ' . $e->getMessage(), 500);
        }

        return [
            'status' => 'success',
            'message' => 'Record deleted',
            'data' => [
                'mftgId' => (int) $id,
            ],
        ];
    }

    private function editTag(array $param): array
    {
        $id = $param[0] ?? null;
        if ($id === null || $id === '') {
            throw new ServerErrorException('ID is required for update', 404);
        }

        $payload = $this->getParsedBody();
        $data = $payload;
        if (!is_array($data) || empty($data)) {
            throw new ServerErrorException('No data provided for update', 400);
        }

        try {
            $taggingFulltext = [];
            if (isset($data['mftgDescription']) && !empty($data['mftgDescription'])) {
                $taggingFulltext = $data['mftgDescription'];
                unset($data['mftgDescription']);
            }
            $this->fileTaggingModel->update($id, $data);
            if (!empty($taggingFulltext)) {
                // delete existing tagging text associated with the id
                $this->taggingTextModel->delete($id);

                // loop fulltext and extract content, first as fulltext and second as number if the content is number
                // example: G10001 will be fulltext G10001 and number 10001, while G10002 will be fulltext G10002 and number 10002, but if the content is not number like GABC then the fulltext will be GABC and number will be empty
                // example: G10A01 will be fulltext G10A01 and number will be empty, while G10001 will be fulltext G10001 and number 10001
                // the number will be extracted by removing first and last non-numeric characters from the content
                foreach ($taggingFulltext as $content) {
                    $number =  preg_replace('/^\D+|\D+$/', '', $content);
                    $taggingText = [
                        'mftxId'      => $id,
                        'mftxText'    => $content,
                        'mftxNumber'  => is_numeric($number) ? $number : '',
                    ];
                    $this->taggingTextModel->insert($taggingText);
                }
            }
        } catch (\Throwable $e) {
            Logger::error('Failed to update tagging: ' . $e);
            throw new ServerErrorException('Update failed: ' . $e->getMessage(), 500);
        }

        return [
            'status' => 'success',
            'message' => 'Record updated',
            'data' => [
                'mftgId' => (int) $id,
            ],
        ];
    }
}