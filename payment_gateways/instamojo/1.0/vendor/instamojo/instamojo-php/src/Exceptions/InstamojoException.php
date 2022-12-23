<?php

namespace Instamojo\Exceptions;

use Exception;

class InstamojoException extends Exception {
    private $httpErrorCode;
    private $errorNumber;
    private $errorMessage;
    
    public function __construct($httpErrorCode, $errorNumber, $errorMessage) {
        parent::__construct ($errorMessage);

        $this->httpErrorCode = $httpErrorCode;
        $this->errorNumber   = $errorNumber;
        $this->errorMessage  = $errorMessage;
    }

    public function getHttpErrorCode() {
        return $this->httpErrorCode;
    }
    
    public function getErrorNumber() {
        return $this->errorNumber;
    }
    
    public function getErrorMessage() {
        return $this->errorMessage;
    }
}
