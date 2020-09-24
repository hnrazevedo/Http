<?php

namespace HnrAzevedo\Http;

use Psr\Http\Message\UriInterface;

class Uri implements UriInterface{

    private const HTTP_DEFAULT_HOST = 'localhost';

    private const DEFAULT_PORTS = [
        'http'  => 80,
        'https' => 443,
        'ftp' => 21,
        'gopher' => 70,
        'nntp' => 119,
        'news' => 119,
        'telnet' => 23,
        'tn3270' => 23,
        'imap' => 143,
        'pop' => 110,
        'ldap' => 389,
    ];

    private const CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~';
    private const CHAR_SUB_DELIMS = '!\$&\'\(\)\*\+,;=';

    private string $scheme = '';
    private string $userInfo = '';
    private string $host = '';
    private ?int $port = null;
    private string $path = '';
    private string $query = '';
    private string $fragment = '';
    private ?string $composedComponents = null;
    private string $authority = '';

    public function __construct(string $uri = '')
    {
        if($uri !== ''){
            $parts = parse_url($uri);
            if ($parts === false) {
                throw new \InvalidArgumentException("Unable to parse URI: $uri");
            }
            $this->applyParts($parts);
        }
    }
    
    public function getScheme(): string
    {
        return $this->scheme;
    }
    
    public function getAuthority(): string
    {
        return $this->authority;
    }
    
    public function getUserInfo(): string
    {
        return $this->userInfo;
    }
    
    public function getHost():string
    {
        return $this->host;
    }
    
    public function getPort(): ?int
    {
        return $this->port;
    }
    
    public function getPath(): string
    {
        return $this->path;
    }
    
    public function getQuery(): string
    {
        return $this->query;
    }
    
    public function getFragment(): string
    {
        return $this->fragment;
    }
    
    public function withScheme($scheme): UriInterface
    {
        $scheme = $this->filterScheme($scheme);

        if ($this->scheme === $scheme) {
            return $this;
        }

        $clone = clone $this;
        $clone->scheme = $scheme;
        $clone->composedComponents = null;
        $clone->removeDefaultPort();
        $clone->validateState();

        return $clone;
    }
    
    public function withUserInfo($user, $password = null): UriInterface
    {
        $info = $this->filterUserInfoComponent($user);
        if ($password !== null) {
            $info .= ':' . $this->filterUserInfoComponent($password);
        }

        if ($this->userInfo === $info) {
            return $this;
        }

        $clone = clone $this;
        $clone->userInfo = $info;
        $clone->composedComponents = null;
        $clone->validateState();

        return $clone;
    }
    
    public function withHost($host): UriInterface
    {
        $host = $this->filterHost($host);

        if ($this->host === $host) {
            return $this;
        }

        $clone = clone $this;
        $clone->host = $host;
        $clone->composedComponents = null;
        $clone->validateState();

        return $clone;
    }
    
    public function withPort($port): UriInterface
    {
        $port = $this->filterPort($port);

        if ($this->port === $port) {
            return $this;
        }

        $clone = clone $this;
        $clone->port = $port;
        $clone->composedComponents = null;
        $clone->removeDefaultPort();
        $clone->validateState();

        return $clone;
    }
    
    public function withPath($path): UriInterface
    {
        $path = $this->filterPath($path);

        if ($this->path === $path) {
            return $this;
        }

        $clone = clone $this;
        $clone->path = $path;
        $clone->composedComponents = null;
        $clone->validateState();

        return $clone;
    }
    
    public function withQuery($query): UriInterface
    {
        $query = $this->filterQueryAndFragment($query);

        if ($this->query === $query) {
            return $this;
        }

        $clone = clone $this;
        $clone->query = $query;
        $clone->composedComponents = null;

        return $clone;
    }
    
    public function withFragment($fragment): UriInterface
    {
        $fragment = $this->filterQueryAndFragment($fragment);

        if ($this->fragment === $fragment) {
            return $this;
        }

        $cloone = clone $this;
        $cloone->fragment = $fragment;
        $cloone->composedComponents = null;

        return $cloone;
    }
    
    public function __toString()
    {
        if ($this->composedComponents === null) {
            $this->composedComponents = self::composeComponents(
                $this->scheme,
                $this->getAuthority(),
                $this->host,
                $this->path,
                $this->query,
                $this->fragment
            );
        }

        return $this->composedComponents;
    }

    private function applyParts(array $parts): void
    {
        $this->scheme = isset($parts['scheme']) ? $this->filterScheme($parts['scheme']) : '';
        $this->userInfo = isset($parts['user']) ? $this->filterUserInfoComponent($parts['user']) : '';
        $this->host = isset($parts['host']) ? $this->filterHost($parts['host']) : '';
        $this->port = isset($parts['port']) ? $this->filterPort($parts['port']) : null;
        $this->path = isset($parts['path']) ? $this->filterPath($parts['path']) : '';
        $this->query = isset($parts['query']) ? $this->filterQueryAndFragment($parts['query']) : '';
        $this->fragment = isset($parts['fragment']) ? $this->filterQueryAndFragment($parts['fragment']) : '';
        
        if (isset($parts['pass'])) {
            $this->userInfo .= ':' . $this->filterUserInfoComponent($parts['pass']);
        }

        $this->removeDefaultPort();
    }

    private function filterScheme($scheme): string
    {
        if (!is_string($scheme)) {
            throw new \InvalidArgumentException('Scheme must be a string');
        }

        return strtolower($scheme);
    }

    private function filterUserInfoComponent($component): string
    {
        if (!is_string($component)) {
            throw new \InvalidArgumentException('User info must be a string');
        }

        return preg_replace_callback(
            '/(?:[^%' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . ']+|%(?![A-Fa-f0-9]{2}))/', [$this, 'rawurlencodeMatchZero'], $component
        );
    }

    private function filterHost($host): string
    {
        if (!is_string($host)) {
            throw new \InvalidArgumentException('Host must be a string');
        }

        return strtolower($host);
    }

    private function filterPort($port): ?int
    {
        if ($port === null) {
            return null;
        }

        $port = (int) $port;
        if (0 > $port || 0xffff < $port) {
            throw new \InvalidArgumentException(
                sprintf('Invalid port: %d. Must be between 0 and 65535', $port)
            );
        }

        return $port;
    }

    private function filterPath($path): string
    {
        if (!is_string($path)) {
            throw new \InvalidArgumentException('Path must be a string');
        }

        return preg_replace_callback(
            '/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/]++|%(?![A-Fa-f0-9]{2}))/',
            [$this, 'rawurlencodeMatchZero'],
            $path
        );
    }

    private function rawurlencodeMatchZero(array $match): string
    {
        return rawurlencode($match[0]);
    }

    private function filterQueryAndFragment($str): string
    {
        if (!is_string($str)) {
            throw new \InvalidArgumentException('Query and fragment must be a string');
        }

        return preg_replace_callback(
            '/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/\?]++|%(?![A-Fa-f0-9]{2}))/',
            [$this, 'rawurlencodeMatchZero'],
            $str
        );
    }

    private function removeDefaultPort(): void
    {
        if ($this->port !== null && self::isDefaultPort($this)) {
            $this->port = null;
        }
    }

    private function validateState(): void
    {
        if ($this->host === '' && ($this->scheme === 'http' || $this->scheme === 'https')) {
            $this->host = self::HTTP_DEFAULT_HOST;
        }

        if ($this->getAuthority() === '') {
            if (0 === strpos($this->path, '//')) {
                throw new \InvalidArgumentException('The path of a URI without an authority must not start with two slashes "//"');
            }
            if ($this->scheme === '' && false !== strpos(explode('/', $this->path, 2)[0], ':')) {
                throw new \InvalidArgumentException('A relative URI must not have a path beginning with a segment containing a colon');
            }
        } elseif (isset($this->path[0]) && $this->path[0] !== '/') {
            throw new \InvalidArgumentException('The path of a URI with an authority must start with a slash "/" or be empty');
        }
    }

    public static function isDefaultPort(UriInterface $uri): bool
    {
        return $uri->getPort() === null
            || (isset(self::DEFAULT_PORTS[$uri->getScheme()]) && $uri->getPort() === self::DEFAULT_PORTS[$uri->getScheme()]);
    }

    public static function composeComponents(?string $scheme, ?string $authority, string $host, string $path, ?string $query, ?string $fragment): string
    {
        $uri = '';
        $uri .= ($scheme != '') ? $scheme . ':' : '';
        $uri .= ($authority != ''|| $scheme === 'file') ? '//'.$authority : '//';
        $uri .= $host;
        $uri .= $path;
        $uri .= ($query != '') ? '?' . $query : '';
        $uri .= ($fragment != '') ? '#' . $fragment : '';
        return $uri;
    }

}