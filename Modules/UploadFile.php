<?php

namespace Lukiman\AuthServer\Modules;

use Lukiman\AuthServer\Libraries\Exceptions\UploadException;
use Lukiman\AuthServer\Libraries\Logger;
use Lukiman\AuthServer\Libraries\M2MBase;
use Lukiman\AuthServer\Models\Event;
use Lukiman\AuthServer\Models\EventClient;
use Lukiman\AuthServer\Models\FileTagging;
use Lukiman\AuthServer\Models\Location;
use Lukiman\AuthServer\Models\Photographer;
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
            $attributes = $this->psrRequest->getAttributes();
            Logger::info('Received file upload request with attributes: ' . json_encode($attributes));
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
        if (!str_ends_with($baseDir, '/')) $baseDir.='/';

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
            if (!mkdir($targetDir, 0777, true)) {
                Logger::error('Failed to create directory: ' . error_get_last()['message'] ?? 'Error mkdir()');
            }
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
            'path' => '/images/' . $urlPath,
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
        $attributes = $this->psrRequest->getAttributes();
        Logger::info('Received file upload request with attributes: ' . json_encode($attributes));
        Logger::info('Versions, APP: '.$this->psrRequest->getHeaderLine("APP-VERSION"));
        Logger::info('Versions, GUI: '.$this->psrRequest->getHeaderLine("GUI-VERSION"));
        Logger::info('Versions, MAIN: '.$this->psrRequest->getHeaderLine("MAIN-VERSION"));
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

        $this->fileTaggingModel->getDb()->beginTransaction();
        try {
            $requestAttributes = $this->psrRequest->getAttributes();
            $clientId = $requestAttributes['oauth_client_id'] ?? '';

            $appVersion = $this->psrRequest->getHeaderLine("APP-VERSION") ?? '';
            $guiVersion = $this->psrRequest->getHeaderLine("GUI-VERSION") ?? '';
            $mainVersion = $this->psrRequest->getHeaderLine("MAIN-VERSION") ?? '';

            $taggingFulltext = [];
            if (isset($data['mftgDescription']) && !empty($data['mftgDescription'])) {
                $taggingFulltext = $data['mftgDescription'];
                unset($data['mftgDescription']);
            }
            $data['mftgClientId'] = $clientId;
            $data['mftgAppVersion'] = $appVersion;
            $data['mftgGuiVersion'] = $guiVersion;
            $data['mftgMainVersion'] = $mainVersion;
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

            Logger::info('JSON data to be inserted: ' . json_encode($data));

            // create data event
            $eventName = $data['mftgEventName'] ?? null;
            $locationName = $data['mftgPhotoLocation'] ?? null;
            $photographerName = $data['mftgFotografer'] ?? null;

            $eventModel = new Event();
            $locationModel = new Location();
            $photgrapherModel = new Photographer();

            $eventProps = [];

            // create data event
            if (!empty($eventName)) {
                $existEventQuery = $eventModel->newQuery();
                $existEventQuery
                    ->limit(1)
                    ->where('msevName', $eventName)
                    ->execute($eventModel->getDb());
                if ($existEventQuery->count() > 0) {
                    Logger::info('Event already exists: ' . $eventName);
                    $existEvent = $existEventQuery->next('array');
                    $eventProps['msevId'] = $existEvent['msevId'];
                } else {
                    Logger::info('Creating new event: ' . $eventName);
                    $eventProps['msevName'] = $eventName;
                    $eventProps['msevAppVersion'] = $appVersion;
                    $eventProps['msevGuiVersion'] = $guiVersion;
                    $eventProps['msevMainVersion'] = $mainVersion;
                    $eventProps['msevCreatedClientId'] = $requestAttributes['oauth_client_id'] ?? '';
                    $eventProps['msevCreatedTime'] = date('Y-m-d H:i:s');
                    $eventProps['msevUpdatedTime'] = date('Y-m-d H:i:s');
                    $eventProps['msevId'] = $eventModel->insert($eventProps);
                }

                $eventClient = new EventClient();
                $existQuery = $eventClient->newQuery();
                $existQuery
                    ->limit(1)
                    ->where('evcaMsevId', $eventProps['msevId'])
                    ->where('evcaClntId', $clientId)
                    ->execute($eventClient->getDb());
                if ($existQuery->count() === 0) {
                    $eventClientProps = [
                        'evcaMsevId' => $eventProps['msevId'],
                        'evcaClntId' => $clientId,
                        'evcaCreatedTime' => date('Y-m-d H:i:s'),
                        'evcaUpdatedTime' => date('Y-m-d H:i:s'),
                    ];
                    $eventClient->insert($eventClientProps);
                }

            }
        
            // create data location
            if (!empty($locationName) && !empty($eventProps['msevId'])) {
                $existLocationQuery = $locationModel->newQuery();
                $existLocationQuery
                    ->limit(1)
                    ->where('mlocName', $locationName)
                    ->where('mlocMsevId', $eventProps['msevId'])
                    ->execute($locationModel->getDb());
                if (empty($existLocationQuery->count())) {
                    $locationProps = [
                        'mlocMsevId' => $eventProps['msevId'],
                        'mlocName' => $locationName,
                        'mlocCreatedClientId' => $requestAttributes['oauth_client_id'] ?? '',
                        'mlocCreatedTime' => date('Y-m-d H:i:s'),
                        'mlocUpdatedTime' => date('Y-m-d H:i:s'),
                    ];
                    $locationModel->insert($locationProps);
                }
            }

            // create data photographer
            if (!empty($photographerName) && !empty($eventProps['msevId'])) {
                $existPhotographerQuery = $photgrapherModel->newQuery();
                $existPhotographerQuery
                    ->limit(1)
                    ->where('mptgName', $photographerName)
                    ->where('mptgMsevId', $eventProps['msevId'])
                    ->execute($photgrapherModel->getDb());
                if (empty($existPhotographerQuery->count())) {
                    $photographerProps = [
                        'mptgMsevId' => $eventProps['msevId'],
                        'mptgName' => $photographerName,
                        'mptgCreatedClientId' => $requestAttributes['oauth_client_id'] ?? '',
                        'mptgCreatedTime' => date('Y-m-d H:i:s'),
                        'mptgUpdatedTime' => date('Y-m-d H:i:s'),
                    ];
                    $photgrapherModel->insert($photographerProps);
                }
            }

            $this->fileTaggingModel->getDb()->commit();
        } catch (\Throwable $e) {
            if ($this->fileTaggingModel->getDb()->inTransaction()) {
                $this->fileTaggingModel->getDb()->rollBack();
            }
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