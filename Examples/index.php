<?php

require __DIR__.'/../vendor/autoload.php';

use HnrAzevedo\Http\Uri;
use HnrAzevedo\Http\Stream;
use HnrAzevedo\Http\Request;
use HnrAzevedo\Http\Response;
use HnrAzevedo\Http\ServerRequest;

$uri = new Uri('https://localhost/test?test=teste');

$stream = Stream::streamFor('aaaa');

$request = new Request('GET',$uri);

$header = $request->withHeader('foo', 'bar')->withAddedHeader('foo', 'baz')->getHeader('foo');

$response = new Response();

$serverRequest = new ServerRequest('GET',$uri);


