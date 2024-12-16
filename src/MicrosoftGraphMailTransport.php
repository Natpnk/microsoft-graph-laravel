<?php

namespace Natpnk\MicrosoftGraphLaravel;

use MicrosoftGraph;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\{Email, Address, Message, MessageConverter};
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Natpnk\MicrosoftGraphLaravel\Exceptions\{CouldNotGetToken, CouldNotReachService};

/**
 * Microsoft Graph Mail Transport
 * 
 * This class provides an implementation of Symfony's AbstractTransport to send emails via Microsoft Graph API.
 */
class MicrosoftGraphMailTransport extends AbstractTransport {
    
    /**
     * Instance of the Microsoft Graph service.
     * 
     * @var MicrosoftGraph
     */
    protected MicrosoftGraph $graph;

    /**
     * Constructor to initialize Microsoft Graph Mail Transport.
     *
     * @param array $config Configuration options for the transport.
     * @param EventDispatcherInterface|null $dispatcher Optional event dispatcher.
     * @param LoggerInterface|null $logger Optional logger.
     */
    public function __construct(public array $config = [], ?EventDispatcherInterface $dispatcher = null, ?LoggerInterface $logger = null){
        
        $this->graph = new MicrosoftGraph;
        parent::__construct($dispatcher, $logger);
    }

    /**
     * Sends the email message using Microsoft Graph API.
     *
     * @param SentMessage $message The email message to send.     
     */
    protected function doSend(SentMessage $message): void {
        
        $from = $message->getEnvelope()->getSender()->toString();

        // Perform the API request to send the mail
        MicrosoftGraph::createRequest("POST", "/users/{$from}/sendmail")->attachBody($this->getBody($message))->execute();
    }

    /**
     * Prepares the email body payload for the Microsoft Graph API.
     *
     * @param SentMessage $message The original message object.
     *
     * @return array The prepared payload for the API request.
     */
    protected function getBody(SentMessage $message): array {
        
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        return array_filter([
            'message' => [
                'subject' => $email->getSubject(),
                'sender' => $this->toRecipientCollection($email->getFrom())[0],
                'from' => $this->toRecipientCollection($email->getFrom())[0],
                'replyTo' => $this->toRecipientCollection($email->getReplyTo()),
                'toRecipients' => $this->toRecipientCollection($email->getTo()),
                'ccRecipients' => $this->toRecipientCollection($email->getCc()),
                'bccRecipients' => $this->toRecipientCollection($email->getBcc()),
                'importance' => $email->getPriority() === 3 ? 'Normal' : ($email->getPriority() < 3 ? 'Low' : 'High'),
                'body' => $this->getContent($email),
                'attachments' => $this->toAttachmentCollection($email->getAttachments()),           
            ]
        ]);        
    }

    /**
     * Converts a recipient list into the Microsoft Graph API format.
     *
     * @param Address[]|string|null $recipients List of recipients.
     *
     * @return array An array of formatted recipient objects.
     */
    protected function toRecipientCollection($recipients): array {
        
        $collection = [];

        if(!$recipients){
            return $collection;
        }

        foreach((array)$recipients as $recipient){
            
            $collection[] = [
                'emailAddress' => [
                    'name' => $recipient instanceof Address ? $recipient->getName() : null,
                    'address' => $recipient instanceof Address ? $recipient->getAddress() : $recipient,
                ],
            ];
        }

        return $collection;
    }

    /**
     * Extracts the content (HTML or text) from the Email object.
     *
     * @param Email $email The email object.
     *
     * @return array The content of the email.
     */
    private function getContent(Email $email): array {
        
        if($email->getHtmlBody() !== null){
            
            return [
                'contentType' => 'html',
                'content' => $email->getHtmlBody(),
            ];
        }

        if($email->getTextBody() !== null){
            
            return [
                'contentType' => 'text',
                'content' => $email->getTextBody(),
            ];
        }

        return [];
    }

    /**
     * Converts attachments to the Microsoft Graph API format.
     *
     * @param array $attachments List of attachments.
     *
     * @return array The formatted attachment objects.
     */
    protected function toAttachmentCollection(array $attachments): array {
        
        $collection = [];

        foreach($attachments as $attachment){
            
            $content = $attachment->getBody();

            $collection[] = [
                'name' => $attachment->getFilename(),
                'contentId' => $attachment->getContentId(),
                'contentType' => $attachment->getContentType(),
                'contentBytes' => base64_encode($content),
                'size' => strlen($content),
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'isInline' => false,
            ];
        }

        return $collection;
    }

    /**
     * Returns a string representation of the transport name.
     *
     * @return string The transport identifier.
     */
    public function __toString(): string {
        return 'microsoftgraph';
    }
}
