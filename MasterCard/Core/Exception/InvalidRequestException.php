<?php
/*
 * Copyright 2016 MasterCard International.
 *
 * Redistribution and use in source and binary forms, with or without modification, are
 * permitted provided that the following conditions are met:
 *
 * Redistributions of source code must retain the above copyright notice, this list of
 * conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice, this list of
 * conditions and the following disclaimer in the documentation and/or other materials
 * provided with the distribution.
 * Neither the name of the MasterCard International Incorporated nor the names of its
 * contributors may be used to endorse or promote products derived from this software
 * without specific prior written permission.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT
 * SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 * TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS;
 * OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER
 * IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING
 * IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
 * SUCH DAMAGE.
 *
 */

namespace MasterCard\Core\Exception;

use MasterCard\Core\Exception\FieldError;


/**
 * Exception raised when the API request contains errors.
 */
class InvalidRequestException extends ApiException {

    protected $fieldErrors;

    /**
     * @ignore
     */
    function __construct($message, $status = null, $errorData = null) {
        parent::__construct($message, $status, $errorData);

        $fieldErrors = array();

        if ($errorData != null) {
            
            
            if (array_key_exists('Errors', $errorData) && array_key_exists('Error', $errorData['Errors']))
            {
                $error = $errorData['Errors']['Error'];
                if (!$this->isAssoc($error))
                {
                    //arizzini: this is a fix when multiple errors are returned.
                    $error = $error[0];
                }
                                
                if (array_key_exists('FieldErrors', $error))
                {
                    $fieldErrors = $error['FieldErrors'];
                    $this->fieldErrors = array();                
                    foreach ($fieldErrors as $fieldError) {
                        array_push($this->fieldErrors, new FieldError($fieldError));
                    }
                }
            }
        }
    }

    /**
     * Returns a boolean indicating whether there are any field errors.
     * @return boolean true if there are field errors; false otherwise.
     */
    function hasFieldErrors() {
        return count($this->fieldErrors) > 0;
    }

    /**
     * Returns a list containing all field errors.
     * @return array list of field errors.
     */
    function getFieldErrors() {
        return $this->fieldErrors;
    }

    /**
     * Returns a description of the error.
     * @return string description of the error.
     */
    function describe() {
        $s = parent::describe();
        foreach ($this->getFieldErrors() as $fieldError) {
            $s = $s . "\n" . (string) $fieldError;
        }
        return $s . "\n";
    }

}

/**
 * Represents a single error in a field of a request sent to the API.
 */
class FieldError {

    protected $field;
    protected $code;
    protected $message;

    /**
     * @ignore
     */
    function __construct($errorData) {
    
        $this->field = $errorData['field'];
        $this->code = $errorData['code'];
        $this->message = $errorData['message'];
    }

    /**
     * Returns the name of the field with the error.
     * @return string the field name.
     */
    function getFieldName() {
        return $this->field;
    }

    /**
     * Returns the code for the error.
     * @return string the error code.
     */
    function getErrorCode() {
        return $this->code;
    }

    /**
     * Returns a description of the error.
     * @return string description of the error.
     */
    function getMessage() {
        return $this->message;
    }


    function __toString() {
        return "Field error: " . $this->getFieldName() . "\"" . $this->getMessage() . "\" (" . $this->getErrorCode() . ")";
    }

}