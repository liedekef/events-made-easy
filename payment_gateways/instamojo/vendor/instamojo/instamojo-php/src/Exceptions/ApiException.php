<?php

namespace Instamojo\Exceptions;

class ApiException extends InstamojoException {
    public function __construct($httpErrorCode, $errorNumber, $errorMessage) {
        parent::__construct ($httpErrorCode, $errorNumber, $errorMessage);
    }
}