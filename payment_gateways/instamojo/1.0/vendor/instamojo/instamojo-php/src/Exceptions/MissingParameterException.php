<?php

namespace Instamojo\Exceptions;

class MissingParameterException extends InstamojoException {
    public function __construct($errorMessage) {
        parent::__construct (null, null, $errorMessage);
    }
}