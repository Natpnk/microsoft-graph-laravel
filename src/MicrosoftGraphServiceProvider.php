<?php

namespace Natpnk\MicrosoftGraphLaravel;

use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Filesystem;
use Microsoft\Graph\Graph;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\{Cache, Storage};
use GuzzleHttp\Exception\{BadResponseException, ConnectException};
use Natpnk\MicrosoftGraphLaravel\Exceptions\{CouldNotSendMail, CouldNotGetToken, CouldNotReachService};

/**
 * Microsoft Graph Service Provider
 *
 * This service provider integrates Microsoft Graph API into a Laravel application.
 * It sets up configuration, binds services, and provides access to the Microsoft Graph client.
 */
class MicrosoftGraphServiceProvider extends ServiceProvider {
    
    /**
     * Perform post-registration booting of services.
     *
     * This method publishes the configuration file and extends the mail transport
     * to include Microsoft Graph as an available mail driver.
     *
     * @throws CouldNotSendMail If the mail transport configuration is invalid.
     */
    public function boot(){
        
        // Publish the configuration file
        $this->publishes([
            __DIR__.'/../config/microsoftgraph.php' => config_path('microsoftgraph.php')
        ], 'config');
        
        // Extend the mail transport to include Microsoft Graph
        $this->app->get('mail.manager')->extend('microsoft-graph', function(array $config = []){
            
            // Ensure required configuration exists
            if(!isset($config['transport']) || !$this->app['config']->get('mail.from.address', false)){
                throw CouldNotSendMail::invalidConfig();
            }

            return new MicrosoftGraphMailTransport($config);
        }); 

        // Extend storage to support Microsoft Graph as a filesystem
        Storage::extend('microsoft-graph', function (Application $app, array $config){

            $graph = new Graph();          
            $graph->setAccessToken(self::getAccessToken());                 
            
            $adapter = new MicrosoftGraphStorageAdapter($graph, $config['drive_id']);

            return new FilesystemAdapter(
                new Filesystem($adapter, $config), 
                $adapter, $config
            );
        });
    }

    /**
     * Register any package services.
     *
     * This method merges the configuration file and binds the Microsoft Graph client
     * to the Laravel service container.
     */
    public function register(){
        
        // Merge package configuration with application's configuration
        $this->mergeConfigFrom(__DIR__.'/../config/microsoftgraph.php', 'microsoftgraph');

        // Bind Microsoft Graph client into the service container
        $this->app->bind(MicrosoftGraph::class, function (){
             
            $graph = new Graph();
            
            return $graph->setAccessToken(self::getAccessToken());
        });

        // Alias the MicrosoftGraph binding for easy access
        $this->app->alias(MicrosoftGraph::class, 'microsoftgraph');               
    }

    /**
     * Retrieves an access token for Microsoft Graph API using OAuth2 client credentials.
     *
     * This method caches the token for 45 minutes to minimize API requests.
     *
     * @return string The OAuth2 access token.
     *
     * @throws CouldNotGetToken If the API request fails.
     * @throws CouldNotReachService If there are connection or unknown errors.
     */
    public static function getAccessToken(){
        
        return Cache::remember('microsoft-graph-access-token', 45, function (){
            
            try {
                
                $config = config('microsoftgraph');                

                $guzzle = new \GuzzleHttp\Client();
                
                $response = $guzzle->post("https://login.microsoftonline.com/{$config['tenant_id']}/oauth2/token?api-version=1.0", [
                    'form_params' => [
                        'client_id' => $config['client_id'],
                        'client_secret' => $config['client_secret'],
                        'resource' => 'https://graph.microsoft.com/',
                        'grant_type' => 'client_credentials',
                    ],
                ]);

                $response = json_decode((string)$response->getBody());
                
                return $response->access_token;

            } catch(BadResponseException $exception){
                
                // Handle 4XX or 5XX response errors
                $response = json_decode((string)$exception->getResponse()->getBody());
                
                throw CouldNotGetToken::serviceRespondedWithError($response->error, $response->error_description);
        
            } catch(ConnectException $exception){
                
                // Handle network or connection issues
                throw CouldNotReachService::networkError();
        
            } catch(Exception $exception){
                
                // Handle any other unknown exceptions
                throw CouldNotReachService::unknownError();
            }
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * This method returns the services that the provider registers.
     *
     * @return array An array of service names.
     */
    public function provides(){
        return ['microsoftgraph'];
    }
}
