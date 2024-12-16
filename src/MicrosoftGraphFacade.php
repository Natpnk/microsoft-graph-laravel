<?php

namespace Natpnk\MicrosoftGraphLaravel;

use Illuminate\Support\Facades\Facade;

/**
 * Microsoft Graph Facade
 *
 * This class provides a facade for the Microsoft Graph service within the Laravel application.
 */
class MicrosoftGraphFacade extends Facade {

    /**
     * Get the registered name of the component.
     *
     * This method defines the service container binding that this facade resolves to.
     *
     * @return string The service binding key 'microsoftgraph'.
     */
    protected static function getFacadeAccessor(){
        return 'microsoftgraph';
    }
}
