<?php

declare(strict_types = 1);

namespace HnrAzevedo\Http;

use Psr\Http\Message\StreamInterface;

trait MessageTrait
{
    private string $procotol = '1.1';
    private array $headers = [];
    private array $headerNames = [];
    private StreamInterface $stream;
    
    public function getProtocolVersion(): string
    {
        return $this->protocol;
    }

    public function withProtocolVersion($version)
    {
        $this->throwString($version);
        $clone = clone $this;
        $clone->protocol = $version;
        return $clone;
    }
    
    public function getHeaders(): array
    {
        return $this->headers;
    }
    
    public function hasHeader($name): bool
    {
        return isset($this->headersNames[$name]);
    }

    public function getHeader($name): array
    {
        $header = strtolower($name);

        if (!isset($this->headerNames[$header])) {
            return [];
        }

        $header = $this->headerNames[$header];
        return $this->headers[$header];
    }
    
    public function getHeaderLine($name): string
    {
        return implode(', ', $this->getHeader($name));
    }
    
    public function withHeader($name, $value)
    {
        $clone = clone $this;
        $normalized = strtolower($name);
        $clone->headerNames[$normalized] = $name;
        $clone->headers[$name] = $this->normalizeHeaderValue($value);
        return $clone;
    }
    
    public function withAddedHeader($name, $value)
    {
        $clone = clone $this;
        $normalized = strtolower($name);
        $value = $clone->normalizeHeaderValue($value);

        if (isset($clone->headerNames[$normalized])) {
            $header = $this->headerNames[$normalized];
            $clone->headers[$header] = array_merge($clone->headers[$header], $value);
        } else {
            $clone->headerNames[$normalized] = $name;
            $clone->headers[$name] = $value;
        }
        return $clone;
    }
    
    public function withoutHeader($name)
    {
        $this->throwString($name);
        $this->throwDefined($name);
        $clone = clone $this;
        unset($clone->headers[$name]);
        return $clone;
    }
    
    public function getBody(): StreamInterface
    {
        if(!isset($this->stream)){
            $this->stream = Stream::streamFor('');
        }

        return $this->stream;
    }

    public function withBody(StreamInterface $body)
    {
        if($body === (isset($this->stream) ? $this->stream : '')){
            return $this;
        }

        $clone = clone $this;
        $clone->stream = $body;
        return $clone;
    }

    private function throwDefined($name): void
    {
        if(!$this->hasHeader($name)){
            throw new \InvalidArgumentException('The requested header has not been defined.');
        }
    }

    private function throwString($name): void
    {
        if(!$this->hasHeader($name)){
            throw new \InvalidArgumentException(sprintf('Header value must be scalar or null but %s provided.', is_object($name) ? get_class($name) : gettype($name) ));
        }
    }

    private function setHeaders(array $headers): void
    {
        $this->headers = [];
        foreach ($headers as $header => $value) {
            $header = (is_int($header)) ? (string) $header : $header;
            $this->heraders[$header] = $value;
        }
    }



    private function normalizeHeaderValue($value): array
    {
        if (!is_array($value)) {
            return $this->trimHeaderValues([$value]);
        }

        if (count($value) === 0) {
            throw new \InvalidArgumentException('Header value can not be an empty array.');
        }

        return $this->trimHeaderValues($value);
    }

    private function trimHeaderValues(array $values): array
    {
        return array_map(function ($value) {
            if (!is_scalar($value) && null !== $value) {
                throw new \InvalidArgumentException(sprintf(
                    'Header value must be scalar or null but %s provided.',
                    is_object($value) ? get_class($value) : gettype($value)
                ));
            }

            return trim((string) $value, " \t");
        }, array_values($values));
    }

}
