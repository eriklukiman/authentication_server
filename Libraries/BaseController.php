<?php

namespace Lukiman\AuthServer\Libraries;

use Psr\Http\Message\ServerRequestInterface;
use Lukiman\Cores\Request;

class BaseController extends \Lukiman\Cores\Controller {

    protected ServerRequestInterface $psrRequest;

    public function __construct()
    {
        // Convert framework request to PSR-7 request
        $psr17Factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $creator = new \Nyholm\Psr7Server\ServerRequestCreator(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $psr17Factory
        );
        $this->psrRequest = $creator->fromGlobals();
    }
}