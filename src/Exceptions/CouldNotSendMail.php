<?php

namespace Natpnk\MicrosoftGraphLaravel\Exceptions;

use Exception;

/**
 * CouldNotSendMail
 * 
 * Exception class representing errors that occur when attempting to send mail 
 * using the Microsoft Graph API.
 */
class CouldNotSendMail extends Exception {

    /**
     * Creates an exception instance for invalid mail configuration.
     *
     * @return static An instance of CouldNotSendMail indicating missing or invalid configuration.
     */
    public static function invalidConfig(){
        return new static('The mail.php configuration is missing from address, transport, client and/or secret key configuration');
    }

    /**
     * Creates an exception instance when the Microsoft Graph API responds with an error.
     *
     * @param string $code The error code returned by the API.
     * @param string $message The error message provided by the API.
     *
     * @return static An instance of CouldNotSendMail with the API error details.
     */
    public static function serviceRespondedWithError(string $code, string $message){
        return new static('Microsoft Graph API responded with code ' . $code . ': ' . $message);
    }
}
