<?php

namespace elish\client;

class EnumaException extends \RuntimeException
{
    private $resp;
    private ?int $httpCode;

    public function __construct($msg, $httpCode = 0, $resp = null)
    {
        parent::__construct($msg);
        $this->resp = $resp;
        $this->httpCode = $httpCode;
    }

    /**
     * @return mixed|null
     */
    public function getResp()
    {
        return $this->resp;
    }

    public function getHttpCode(): ?int
    {
        return $this->httpCode;
    }

}