<?php

namespace Natpnk\MicrosoftGraphLaravel\Exceptions;

use Exception;

/**
 * CouldNotGetToken
 * 
 * Exception class representing errors that occur when retrieving an access token
 * from the Microsoft Identity platform.
 */
class CouldNotGetToken extends Exception {

    /**
     * Creates an exception instance for a service error response.
     *
     * @param string $code The error code returned by the Microsoft Identity platform.
     * @param string $message The error message returned by the platform.
     * @return static An instance of CouldNotGetToken with a detailed error message.
     */
    public static function serviceRespondedWithError(string $code, string $message){
        return new static('Microsoft Identity platform responded with code ' . $code . ': ' . $message);
    }
}