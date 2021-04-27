<?php

namespace Natpnk\MicrosoftGraphLaravel;

class MicrosoftGraphFacade extends \Illuminate\Support\Facades\Facade {
    
    protected static function getFacadeAccessor(){
        return 'microsoftgraph';
    }
}