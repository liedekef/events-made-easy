<?php

namespace Instamojo\Exceptions;

class ActionForbiddenException extends InstamojoException {
    public function __construct($errorMessage=null) {
        parent::__construct (403, 0, $errorMessage ?: 'Forbidden');
    }
}