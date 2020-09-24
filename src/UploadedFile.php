<?php

namespace HnrAzevedo\Http;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

class UploadedFile implements UploadedFileInterface{

    private const ERRORS = [
        UPLOAD_ERR_OK,
        UPLOAD_ERR_INI_SIZE,
        UPLOAD_ERR_FORM_SIZE,
        UPLOAD_ERR_PARTIAL,
        UPLOAD_ERR_NO_FILE,
        UPLOAD_ERR_NO_TMP_DIR,
        UPLOAD_ERR_CANT_WRITE,
        UPLOAD_ERR_EXTENSION,
    ];

    private ?string $clientFilename;
    private ?string $clientMediaType;
    private int $error;
    private ?string $file;
    private bool $moved = false;
    private ?int $size;
    private StreamInterface $stream;

    public function __construct(
        $streamOrFile,
        ?int $size,
        int $errorStatus,
        string $clientFilename = null,
        string $clientMediaType = null
    ) {
        $this->setError($errorStatus);
        $this->size = $size;
        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;

        if ($this->isOk()) {
            $this->setStreamOrFile($streamOrFile);
        }
    }
    
    public function getStream(): StreamInterface
    {
        $this->validateActive();

        if ($this->stream instanceof StreamInterface) {
            return $this->stream;
        }

        $file = $this->file;

        return Stream::streamFor(Stream::tryFopen($file, 'r+'));
    }
    
    public function moveTo($targetPath)
    {
        $this->validateActive();

        if (false === is_string($targetPath) && false === empty($targetPath)) {
            throw new \InvalidArgumentException(
                'Invalid path provided for move operation; must be a non-empty string'
            );
        }

        if ($this->file) {
            $this->moved = PHP_SAPI === 'cli'
                ? rename($this->file, $targetPath)
                : move_uploaded_file($this->file, $targetPath);
        } else {
            Stream::copyToStream(
                $this->getStream(),
                 Stream::streamFor(Stream::tryFopen($targetPath, 'w'))
            );

            $this->moved = true;
        }

        if (false === $this->moved) {
            throw new \RuntimeException(
                sprintf('Uploaded file could not be moved to %s', $targetPath)
            );
        }
    }
    
    public function getSize(): ?int
    {
        return $this->size;
    }
    
    public function getError(): int
    {
        return $this->error;
    }
    
    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }
    
    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

    private function isOk(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    public function isMoved(): bool
    {
        return $this->moved;
    }

    private function validateActive(): void
    {
        if (false === $this->isOk()) {
            throw new \RuntimeException('Cannot retrieve stream due to upload error');
        }

        if ($this->isMoved()) {
            throw new \RuntimeException('Cannot retrieve stream after it has already been moved');
        }
    }

    private function setError(int $error): void
    {
        if (false === in_array($error, UploadedFile::ERRORS, true)) {
            throw new \InvalidArgumentException('Invalid error status for UploadedFile');
        }

        $this->error = $error;
    }

    private function setStreamOrFile($streamOrFile): void
    {
        if (is_string($streamOrFile)) {
            $this->file = $streamOrFile;
        } elseif (is_resource($streamOrFile)) {
            $this->stream = new Stream($streamOrFile);
        } elseif ($streamOrFile instanceof StreamInterface) {
            $this->stream = $streamOrFile;
        } else {
            throw new \InvalidArgumentException('Invalid stream or file provided for UploadedFile');
        }
    }
}