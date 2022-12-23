<?php

namespace Instamojo\Exception;

class InvalidRequestException extends InstamojoException {
    public function __construct($errorMessage) {
        parent::__construct (null, null, $errorMessage);
    }
}