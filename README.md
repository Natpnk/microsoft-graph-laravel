# Microsoft Graph for Laravel

This laravel package contains a wrappr for Microsoft Graph with the following features:

 - Microsoft Graph API calls
 - Mail transport

## Requirements

 - Laravel 8^
 - PHP 7.2.5^

## Installation
To install and use this package follow instructions below:

#### Step 1: Install via composer

    $ composer require natpnk/microsoft-graph-laravel

#### Step 2: Publish config

Run the following to publish the config

    $ php artisan vendor:publish --provider="Natpnk\MicrosoftGraphLaravel\MicrosoftGraphServiceProvider" --tag="config"

The config file will be published as follow:

	<?php

	return [
	   
	    /*    
	    |--------------------------------------------------------------------------    
	    | Microsoft Graph Laravel wrapper   
	    |--------------------------------------------------------------------------   
	    |    
	    */
	      
	    'tenant' => env('MSGRAPH_TENANT'),
	    'clientid' => env('MSGRAPH_CLIENT_ID'),
	    'clientsecret' => env('MSGRAPH_CLIENT_SECRET')    
	];

#### Step 3: Create app registration
To obtain the Tenant, ClientID and ClientSecret. Please follow this [article](https://docs.microsoft.com/azure/active-directory/develop/quickstart-register-app) to generate a app registration within Azure.

#### Step 4: Use Microsoft Graph as mail transport
It is possible to use Microsoft Graph as an mail transport within Laravel. Add the following to config/mail.php inside your Laravel installation.

    'microsoftgraph' => [
		'transport' => 'microsoftgraph',
	],

Additional settings are not required due to settings inside config/microsoftgraph.php. Remember to use a from address which is a valid mailbox inside your tenant.

##  Usage
A basic example usage example get's all the users from a tentant:

	use MicrosoftGraph;
	....
        $Users = MicrosoftGraph::createRequest("GET", "/Users")->setReturnType(\Microsoft\Graph\Model\User::class)->execute();

	print_r($Users);

for futher documentation about usage consult: [Microsoft Graph API docs](https://docs.microsoft.com/en-us/graph/overview)

## Inspiration
The inspiration for this package came from [wapacro/laravel-msgraph-mail](https://github.com/wapacro/laravel-msgraph-mail) and the search for a wrapper for Microsoft Graph to use in Laravel.  

## Contributing
Feel free contribute and send pull requests.
