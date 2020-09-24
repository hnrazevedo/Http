<?php

namespace HnrAzevedo\HttpServer\Helper;

trait Helper{
    protected array $headers = [];
    protected array $headerNames = [];

    private function updateHostFromUri(): void
    {
        $host = $this->uri->getHost();

        if ($host == '') {
            return;
        }

        if (($port = $this->uri->getPort()) !== null) {
            $host .= ':' . $port;
        }

        if (isset($this->headerNames['host'])) {
            $header = $this->headerNames['host'];
        } else {
            $header = 'Host';
            $this->headerNames['host'] = 'Host';
        }
        
        $this->headers = [$header => [$host]] + $this->headers;
    }

}