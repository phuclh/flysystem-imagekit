<?php

namespace Phuclh\Imagekit;

use DateTime;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use ImageKit\ImageKit;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;

class ImagekitAdapter implements FilesystemAdapter
{
    protected MimeTypeDetector $mimeTypeDetector;

    public function __construct(
        protected ImageKit $client,
        MimeTypeDetector $mimeTypeDetector = null
    ) {
        $this->mimeTypeDetector = $mimeTypeDetector ?: new FinfoMimeTypeDetector();
    }

    public function getClient(): ImageKit
    {
        return $this->client;
    }

    public function getUrl($path): string
    {
        return $this->client->url([
            'path' => $path,
        ]);
    }

    public function fileExists(string $path): bool
    {
        $filePath = $this->getFileFolderName($path);

        // Search for files
        $file = $this->client->listFiles([
            'name' => $filePath['fileName'],
            'path' => $filePath['directory'],
            'includeFolder' => true,
        ]);

        return ! empty($file->success);
    }

    public function directoryExists(string $path): bool
    {
        return $this->fileExists($path);
    }

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $this->upload($path, $contents);
        } catch (\Exception $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        try {
            $this->upload($path, $contents);
        } catch (\Exception $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function read(string $path): string
    {
        $filePath = $this->getFileFolderName($path);

        $file = $this->client->listFiles([
            'name' => $filePath['fileName'],
            'includeFolder' => true,
        ]);

        if (empty($file->success)) {
            throw UnableToReadFile::fromLocation($path, 'File not found.');
        }

        return file_get_contents($file->success[0]->url);
    }

    public function readStream(string $path)
    {
        $filePath = $this->getFileFolderName($path);

        $file = $this->client->listFiles([
            'name' => $filePath['fileName'],
            'includeFolder' => true,
        ]);

        if (empty($file->success)) {
            throw UnableToReadFile::fromLocation($path, 'File not found.');
        }

        return fopen($file->success[0]->url, 'rb');
    }

    public function delete(string $path): void
    {
        $file = $this->searchFile($path);

        // Make a purge cache request
        if (config('imagekit.purge_cache_update')) {
            $this->client->purgeCache(config('imagekit.endpoint') . '/' . $path);
        }

        $this->client->deleteFile($file->fileId);
    }

    public function deleteDirectory(string $path): void
    {
        $folder = $this->client->listFiles([
            'name' => $path,
            'includeFolder' => true,
        ]);

        if (empty($folder->success)) {
            throw UnableToDeleteDirectory::atLocation($path, 'Directory not found.');
        }

        $this->client->deleteFile($folder->success[0]->folderId);
    }

    public function createDirectory(string $path, Config $config): void
    {
        // The ImageKit API does not offer an endpoint for this action currently.
        // A workaround is to upload an "empty" file, the upload endpoint can create
        // folders when the "folder" parameter is set.
        $this->client->upload([
            'file' => 'xxx',
            'fileName' => 'empty',
            'useUniqueFileName' => false,
            'folder' => $path,
        ]);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        throw UnableToSetVisibility::atLocation($path, 'Adapter does not support visibility controls.');
    }

    public function visibility(string $path): FileAttributes
    {
        // Noop
        return new FileAttributes($path);
    }

    public function mimeType(string $path): FileAttributes
    {
        return new FileAttributes(
            $path,
            null,
            null,
            null,
            $this->mimeTypeDetector->detectMimeTypeFromPath($path)
        );
    }

    public function lastModified(string $path): FileAttributes
    {
        try {
            $response = $this->getMetadata($path);
        } catch (\Exception $e) {
            throw UnableToRetrieveMetadata::lastModified($path, $e->getMessage());
        }

        return new FileAttributes(
            $path,
            null,
            null,
            $response['timestamp']
        );
    }

    public function fileSize(string $path): FileAttributes
    {
        try {
            $response = $this->getMetadata($path);
        } catch (\Exception $e) {
            throw UnableToRetrieveMetadata::fileSize($path, $e->getMessage());
        }

        return new FileAttributes(
            $path,
            $response['size'] ?? null
        );
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $list = $this->client->listFiles([
            'name' => $path,
            'includeFolder' => config('imagekit.include_folders'),
        ]);

        // If not recursive remove files
        if (! $deep) {
            foreach ($list->success as $key => $e) {
                $pathParts = isset($e->filePath)
                    ? explode('/', $e->filePath)
                    : explode('/', $e->folderPath);

                // Get directory name
                end($pathParts);
                $dirName = prev($pathParts);

                if ($dirName != $path) {
                    unset($list->success[$key]);
                }
            }
        }

        return array_map(function ($e) use ($deep, $path) {
            // Get path parts
            if (isset($e->filePath)) {
                $pathParts = explode('/', $e->filePath);
                $filePath = $e->filePath;
            } else {
                $pathParts = explode('/', $e->folderPath);
                $filePath = $e->folderPath;
            }

            // Get directory name
            end($pathParts);
            $dirName = prev($pathParts);

            return [
                'path' => $filePath,
                'dirname' => $dirName,
            ];
        }, (array)$list->success);
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $this->copy($source, $destination, $config);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $oldFile = $this->searchFile($source);
        $oldFileUrl = $oldFile->url;

        // Upload new file
        $this->upload($destination, $oldFileUrl);
    }

    protected function getMetadata($path): array
    {
        $file = $this->searchFile($path);

        $return = (array)$file;

        // Get timestamp
        $createdAt = $file->createdAt;
        $date = new DateTime($createdAt);
        $timestamp = $date->getTimestamp();
        $return['timestamp'] = $timestamp;

        // Get mimetype
        $return['mimetype'] = $this->mimeTypeDetector->detectMimeTypeFromPath($path);

        // Get more meta data
        $moreData = $this->client->getFileMetaData($file->fileId);

        return array_merge(
            (array)$moreData->success,
            $return
        );
    }

    protected function applyPathPrefix($path): string
    {
        return '/' . trim($path, '/');
    }

    protected function getFileFolderName(string $path): array
    {
        $folder = '/';
        $fileName = $path;

        // Check for folders in path (file name)
        $folders = explode('/', $path);
        if (count($folders) > 1) {
            $fileName = end($folders);
            $folder = str_replace('/' . end($folders), '', $path);
        }

        return [
            'fileName' => $fileName,
            'directory' => $folder,
        ];
    }

    protected function searchFile($path): object
    {
        $filePath = $this->getFileFolderName($path);

        $file = $this->client->listFiles([
            'name' => $filePath['fileName'],
            'path' => $filePath['directory'],
        ]);

        if (empty($file->success)) {
            throw new FileNotFoundException('File not found: ' . $path);
        }

        return $file->success[0];
    }

    protected function upload(string $path, $contents): void
    {
        if (! ($file = $this->getFileFolderName($path))) {
            return;
        }

        $this->client->upload([
            'file' => $contents,
            'fileName' => $file['fileName'],
            'useUniqueFileName' => false,
            'folder' => $file['directory'],
        ]);
    }
}
