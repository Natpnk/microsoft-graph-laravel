# Microsoft Graph for Laravel

This laravel package contains a wrapper for Microsoft Graph with the following features:

 - Microsoft Graph API calls
 - Mail transport

## Requirements

 - Laravel 10 or higher (Use version 1.1.2 for Laravel 9)
 - PHP 8.2 or higher

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
	      
	    'tenant_id' => env('MSGRAPH_TENANT_ID'),
	    'client_id' => env('MSGRAPH_CLIENT_ID'),
	    'client_secret' => env('MSGRAPH_CLIENT_SECRET')    
	];

#### Step 3: Create app registration
To obtain the Tenant, ClientID and ClientSecret. Please follow this [article](https://docs.microsoft.com/azure/active-directory/develop/quickstart-register-app) to generate a app registration within Azure.

#### Step 4: Use Microsoft Graph as mail transport (Optional)

It is possible to use Microsoft Graph as an mail transport within Laravel. Add the following to config/mail.php inside your Laravel installation.

    'microsoftgraph' => [
		'transport' => 'microsoft-graph',
	],

Additional settings are not required due to settings inside config/microsoftgraph.php. Remember to use a from address which is a valid mailbox inside your tenant.

#### Step 5: Use Microsoft Graph as filesystem (Optional)
It is possible to use Microsoft Graph (OneDrive/ Sharepoint) as storage within Laravel. Add the following to config/filesystems.php inside your Laravel installation.

    'disks' => [

        'microsoft-graph' => [
            'driver' => 'microsoft-graph',
            'drive_id' => 'YOUR DRIVE ID'
        ],  

To obtain the Drive ID. You can use the Microsoft Graph Explorer to produce a GET request to the following: 

	https://graph.microsoft.com/v1.0/sites/{SHAREPOINT_DOMAIN},{SITE_ID}/drives

You should also have set the Sites.ReadAll or Sites.ReadWriteAll permission based on your needs.
	
##  Usage
A basic example usage example get's all the users from a tentant:

	use MicrosoftGraph;
	....
        $Users = MicrosoftGraph::createRequest("GET", "/Users")->setReturnType(\Microsoft\Graph\Model\User::class)->execute();

	print_r($Users);

for futher documentation about usage consult: [Microsoft Graph API docs](https://docs.microsoft.com/en-us/graph/overview)

## Contributing
Feel free contribute and send pull requests.