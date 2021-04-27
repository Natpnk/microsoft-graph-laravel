<?php

namespace Natpnk\MicrosoftGraphLaravel;

use Microsoft\Graph\Graph;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Natpnk\MicrosoftGraphLaravel\Exceptions\CouldNotSendMail;

class MicrosoftGraphServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot(){
        
        /**
         * Publish config
         */
        $this->publishes([__DIR__.'/../config/microsoftgraph.php' => config_path('microsoftgraph.php')], 'config');

        /**
         * Mail Transport
         */        
        $this->app->get('mail.manager')->extend('microsoftgraph', function(array $Config){
                            
            if(!isset($Config['transport']) || !$this->app['config']->get('mail.from.address', false)){
                throw CouldNotSendMail::invalidConfig();
            }            

            return new MicrosoftGraphMailTransport($Config);
        }); 
    }

    /**
     * Register any package services.
     */
    public function register(){
        
        /**
         * Merge config
         */ 
        $this->mergeConfigFrom(__DIR__.'/../config/microsoftgraph.php', 'microsoftgraph');

        $this->app->bind(MicrosoftGraph::class, function (){
                        
            $Graph = new Graph();
            return $Graph->setAccessToken(self::GetAccessToken());

        });

        $this->app->alias(MicrosoftGraph::class, 'microsoftgraph');               
    }

    /**
     * Get AccessToken
     */
    public static function GetAccessToken(){

        return Cache::remember('microsoftgraph-accesstoken', 45, function (){

            try {

                $Config = config('microsoftgraph');                

                $Guzzle = new \GuzzleHttp\Client();
                $Response = $Guzzle->post("https://login.microsoftonline.com/{$Config['tenant']}/oauth2/token?api-version=1.0", [
                    'form_params' => [
                        'client_id' => $Config['clientid'],
                        'client_secret' => $Config['clientsecret'],
                        'resource' => 'https://graph.microsoft.com/',
                        'grant_type' => 'client_credentials',
                    ],
                ]);

                $Response = json_decode((string)$Response->getBody());
                
                return $Response->access_token;

            } catch (BadResponseException $Exception){
        
                // The endpoint responded with 4XX or 5XX error
                $Response = json_decode((string)$Exception->getResponse()->getBody());
        
                throw CouldNotGetToken::serviceRespondedWithError($Response->error, $Response->error_description);
        
            } catch (ConnectException $Exception){
        
                // A connection error (DNS, timeout, ...) occurred
                throw CouldNotReachService::networkError();
        
            } catch (Exception $Exception){
        
                // An unknown error occurred
                throw CouldNotReachService::unknownError();
            }            
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(){
        return ['microsoftgraph'];
    }
}