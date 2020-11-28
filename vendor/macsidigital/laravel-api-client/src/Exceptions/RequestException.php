<?php

namespace MacsiDigital\API\Exceptions;

use Exception;
use MacsiDigital\API\Support\Response;

class RequestException extends Exception
{
    /**
     * The response instance.
     *
     * @var \Illuminate\Http\Client\Response
     */
    public $response;

    /**
     * Create a new exception instance.
     *
     * @param  \Illuminate\Http\Client\Response  $response
     * @return void
     */
    public function __construct(Response $response)
    {
        parent::__construct("HTTP request returned status code {$response->status()}.", $response->status());

        $this->response = $response;
    }
}
