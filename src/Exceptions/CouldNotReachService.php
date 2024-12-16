<?php

namespace Natpnk\MicrosoftGraphLaravel\Exceptions;

use Exception;

/**
 * CouldNotReachService
 * 
 * Exception class representing errors that occur when the Microsoft Graph service
 * cannot be reached, either due to network issues or unknown errors.
 */
class CouldNotReachService extends Exception {

    /**
     * Creates an exception instance for a network error.
     *
     * @return static An instance of CouldNotReachService indicating a network issue.
     */
    public static function networkError(){
        return new static('The server couldn\'t be reached');
    }

    /**
     * Creates an exception instance for an unknown error.
     *
     * @return static An instance of CouldNotReachService indicating an unknown issue.
     */
    public static function unknownError(){
        return new static('An unknown error occurred');
    }
}
