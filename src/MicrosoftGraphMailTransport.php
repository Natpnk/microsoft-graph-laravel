<?php


namespace Natpnk\MicrosoftGraphLaravel;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Mail\Transport\Transport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Natpnk\MicrosoftGraphLaravel\Exceptions\CouldNotGetToken;
use Natpnk\MicrosoftGraphLaravel\Exceptions\CouldNotReachService;
use Natpnk\MicrosoftGraphLaravel\Exceptions\CouldNotSendMail;
use Swift_Mime_Attachment;
use Swift_Mime_EmbeddedFile;
use Swift_Mime_SimpleMessage;

class MicrosoftGraphMailTransport extends Transport {

    /**
     * @var string
     */
    protected string $Endpoint = 'https://graph.microsoft.com/v1.0/users/{from}/sendMail';

    /**
     * @var array
     */
    protected array $Config;

    /**
     * @var Client|ClientInterface
     */
    protected ClientInterface $http;

    /**
     * MsGraphMailTransport constructor
     * @param array $Config
     * @param ClientInterface|null $client
     */
    public function __construct(array $Config, ClientInterface $Client = null){
        
        $this->Config = $Config;
        
        $this->Client = $Client ?? new Client();
    }

    /**
     * Send given email message
     * @param Swift_Mime_SimpleMessage $message
     * @param null $failedRecipients
     * @return int
     * @throws CouldNotSendMail
     * @throws CouldNotReachService
     * @throws CouldNotGetToken
     */
    public function send(Swift_Mime_SimpleMessage $Message, &$failedRecipients = null){
        
        $this->beforeSendPerformed($Message);
        
        $Payload = $this->getPayload($Message);
        
        $url = str_replace('{from}', urlencode($Payload['from']['emailAddress']['address']), $this->Endpoint);

        try {
            
            $this->Client->post($url, [
                'headers' => $this->getHeaders(),
                'json' => [
                    'message' => $Payload,
                ],
            ]);

            $this->sendPerformed($Message);
            return $this->numberOfRecipients($Message);
        
        } catch (BadResponseException $Exception) {
        
            // The API responded with 4XX or 5XX error
            $Response = json_decode((string)$Exception->getResponse()->getBody());
            throw CouldNotSendMail::serviceRespondedWithError($Response->error->code, $Response->error->message);
       
        } catch (ConnectException $Exception) {
        
            // A connection error (DNS, timeout, ...) occurred
            throw CouldNotReachService::networkError();
        }
    }

    /**
     * Transforms given SwiftMailer message instance into
     * Microsoft Graph message object
     * @param Swift_Mime_SimpleMessage $message
     * @return array
     */
    protected function getPayload(Swift_Mime_SimpleMessage $Message){
        
        $From = $Message->getFrom();
        $Priority = $Message->getPriority();
        $Attachments = $Message->getChildren();
    
        return array_filter([
            'subject' => $Message->getSubject(),
            'sender' => $this->toRecipientCollection($From)[0],
            'from' => $this->toRecipientCollection($From)[0],
            'replyTo' => $this->toRecipientCollection($Message->getReplyTo()),
            'toRecipients' => $this->toRecipientCollection($Message->getTo()),
            'ccRecipients' => $this->toRecipientCollection($Message->getCc()),
            'bccRecipients' => $this->toRecipientCollection($Message->getBcc()),
            'importance' => $Priority === 3 ? 'Normal' : ($Priority < 3 ? 'Low' : 'High'),
            'body' => [
                'contentType' => Str::contains($Message->getContentType(), ['html', 'plain']) ? 'html' : 'text',
                'content' => $Message->getBody(),
            ],
            'attachments' => $this->toAttachmentCollection($Attachments),            
        ]);        
    }

    /**
     * Transforms given SimpleMessage recipients into
     * Microsoft Graph recipients collection
     * @param array|string $recipients
     * @return array
     */
    protected function toRecipientCollection($Recipients) {
        
        $Collection = [];

        // If the provided list is empty
        // return an empty collection
        
        if(!$Recipients){
            return $Collection;
        }

        // Some fields yield single e-mail
        // addresses instead of arrays
        
        if(is_string($Recipients)){
            
            $Collection[] = [
                'emailAddress' => [
                    'name' => null,
                    'address' => $Recipients,
                ],
            ];

            return $Collection;
        }

        foreach($Recipients as $Address => $Name){
            
            $Collection[] = [
                'emailAddress' => [
                    'name' => $Name,
                    'address' => $Address,
                ],
            ];
        }

        return $Collection;
    }

    /**
     * Transforms given SwiftMailer children into
     * Microsoft Graph attachment collection
     * @param $attachments
     * @return array
     */
    protected function toAttachmentCollection($Attachments){
        
        $Collection = [];

        foreach($Attachments as $Attachment){
            
            if(!$Attachment instanceof Swift_Mime_Attachment){
                continue;
            }

            $Collection[] = [
                'name' => $Attachment->getFilename(),
                'contentId' => $Attachment->getId(),
                'contentType' => $Attachment->getContentType(),
                'contentBytes' => base64_encode($Attachment->getBody()),
                'size' => strlen($Attachment->getBody()),
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'isInline' => $Attachment instanceof Swift_Mime_EmbeddedFile,
            ];

        }

        return $Collection;
    }

    /**
     * Returns header collection for API request
     * @return string[]
     * @throws CouldNotGetToken
     * @throws CouldNotReachService
     */
    protected function getHeaders(){
        
        return [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . MicrosoftGraphServiceProvider::GetAccessToken(),
        ];
    }
}
