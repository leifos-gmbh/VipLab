<?php

include_once('Services/Exceptions/classes/class.ilException.php');

class ilECSResourceNotFoundException extends ilException
{
    private int $httpCode = 0;
    
    public function __construct($a_message, $a_code = 0, Throwable $previous = null)
    {
        $this->httpCode = $a_code;
        parent::__construct($a_message, $a_code, $previous);
    }
    
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }
}
