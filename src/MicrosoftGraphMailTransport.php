<?php

namespace Natpnk\MicrosoftGraphLaravel;

use MicrosoftGraph;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

use Natpnk\MicrosoftGraphLaravel\Exceptions\CouldNotGetToken;
use Natpnk\MicrosoftGraphLaravel\Exceptions\CouldNotReachService;
use Natpnk\MicrosoftGraphLaravel\Exceptions\CouldNotSendMail;

class MicrosoftGraphMailTransport extends AbstractTransport {

    /**
     * The MicrosoftGraph client.
     *
     * @var MicrosoftGraph
     */
    protected $Graph;

    /**
     * Create a new MicrosoftGraph transport instance.
     *
     * @param  $Config
     * @return void
     */
    public function __construct(array $Config = []){
        
        $this->Config = $Config;        
        $this->Graph = new MicrosoftGraph;
    }

    protected function doSend(SentMessage $Message): void {
                
        $From = $Message->getEnvelope()->getSender()->toString();

        MicrosoftGraph::createRequest("POST", "/users/{$From}/sendmail")->attachBody($this->getBody($Message))->execute();
    }

    protected function getBody($Message){
                    
        $Email = MessageConverter::toEmail($Message->getOriginalMessage());

        return array_filter([
            'message' => [
                'subject' => $Email->getSubject(),
                'sender' => $this->toRecipientCollection($Email->getFrom())[0],
                'from' => $this->toRecipientCollection($Email->getFrom())[0],
                'replyTo' => $this->toRecipientCollection($Email->getReplyTo()),
                'toRecipients' => $this->toRecipientCollection($Email->getTo()),
                'ccRecipients' => $this->toRecipientCollection($Email->getCc()),
                'bccRecipients' => $this->toRecipientCollection($Email->getBcc()),
                'importance' => $Email->getPriority() === 3 ? 'Normal' : ($Email->getPriority() < 3 ? 'Low' : 'High'),
                'body' => $this->getContent($Email)
                //'attachments' => $this->toAttachmentCollection($Attachments),            
            ]
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

        if(!$Recipients){
            return $Collection;
        }
        
        if(is_string($Recipients)){
            
            $Collection[] = [
                'emailAddress' => [
                    'name' => null,
                    'address' => $Recipients->getAddress(),
                ],
            ];

            return $Collection;
        }

        foreach($Recipients as $Address){
            
            $Collection[] = [
                'emailAddress' => [
                    'name' => $Address->getName(),
                    'address' => $Address->getAddress(),
                ],
            ];
        }

        return $Collection;
    }

    /**
     * @param Email $email
     * @return array
     */
    private function getContent(Email $Email): array {
        
        if (!is_null($Email->getHtmlBody())) {
            $Content = [
                'contentType' => 'html',
                'content' => $Email->getHtmlBody(),
            ];
        }

        if (!is_null($Email->getTextBody())) {
            $Content = [
                'contentType' => 'text',
                'content' => $Email->getTextBody(),
            ];
        }

        return $Content;
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
     * Get the string representation of the transport.
     *
     * @return string
     */
    public function __toString(): string{
        return 'microsoftgraph';
    }
}
