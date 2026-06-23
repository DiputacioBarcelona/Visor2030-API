<?php

namespace App\Security\WebserviceVus;
use SimpleXMLElement;
use InvalidArgumentException;
use Throwable;

// germi: added $vusResponse parameter to the constructor
// in the VusUserAuthenticationService class I modify all the InvalidVusUserCredentialsException so the response gets passed

class InvalidVusUserCredentialsException extends InvalidArgumentException
{
    private SimpleXMLElement $vusResponse;

    public function __construct(SimpleXMLElement $vusResponse, Throwable $previous = null)
    {
        parent::__construct($vusResponse->resposta->text_resposta, 0, $previous);
        $this->vusResponse = $vusResponse;
    }

    public function getVusResponse(): string
    {
        return $this->vusResponse;
    }
}
