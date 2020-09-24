<?php

namespace HnrAzevedo\HttpServer\Handler;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use HnrAzevedo\HttpServer\Helper\Helper;

class Request implements RequestInterface{
    use MessageTrait, Helper;

    private string $method;
    private ?string $requestTarget;
    private UriInterface $uri;
    
    public function __construct(
        string $method,
        $uri,
        array $headers = [],
        $body = null,
        string $version = '1.1'
    ) {
        $this->assertMethod($method);
        if (!($uri instanceof UriInterface)) {
            $uri = new Uri($uri);
        }

        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->setHeaders($headers);
        $this->protocol = $version;

        if (!isset($this->headerNames['host'])) {
            $this->updateHostFromUri();
        }

        if ($body !== '' && $body !== null) {
            $this->stream = Stream::streamFor($body);
        }
    }
    
    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        if ($target === '') {
            $target = '/';
        }
        if ($this->uri->getQuery() != '') {
            $target .= '?' . $this->uri->getQuery();
        }

        return $target;
    }
    
    public function withRequestTarget($requestTarget): RequestInterface
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new \InvalidArgumentException(
                'Invalid request target provided; cannot contain whitespace'
            );
        }

        $clone = clone $this;
        $clone->requestTarget = $requestTarget;
        return $clone;
    }
    
    public function getMethod(): string
    {
        return $this->method;
    }
    
    public function withMethod($method): RequestInterface
    {
        $this->assertMethod($method);
        $clone = clone $this;
        $clone->method = strtoupper($method);
        return $clone;
    }
    
    public function getUri(): UriInterface
    {
        return $this->uri;
    }
    
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        if ($uri === $this->uri) {
            return $this;
        }

        $clone = clone $this;
        $clone->uri = $uri;

        if (!$preserveHost || !isset($this->headerNames['host'])) {
            $clone->updateHostFromUri();
        }

        return $clone;
    }

    private function assertMethod($method): void
    {
        if (!is_string($method) || $method === '') {
            throw new \InvalidArgumentException('Method must be a non-empty string.');
        }
    }

}