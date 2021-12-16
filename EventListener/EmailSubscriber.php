<?php

namespace MauticPlugin\MauticAdvancedTemplatesBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event as Events;
use Mautic\EmailBundle\Helper\PlainTextHelper;
use MauticPlugin\MauticAdvancedTemplatesBundle\Helper\TemplateProcessor;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Monolog\Logger;

/**
 * Class EmailSubscriber.
 */
class EmailSubscriber implements EventSubscriberInterface
{
    /**
     * @var TemplateProcessor $templateProcessor;
     */
    protected $templateProcessor;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var EmailModel
     */
    private $emailModel;

    /**
     * EmailSubscriber constructor.
     *
     * @param Logger $templateProcessor
     * @param TemplateProcessor $templateProcessor
     */
    public function __construct(Logger $logger, TemplateProcessor $templateProcessor, EmailModel $emailModel)
    {
        $this->logger            = $logger;
        $this->templateProcessor = $templateProcessor;
        $this->emailModel        = $emailModel;

    }
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            EmailEvents::EMAIL_ON_SEND    => ['onEmailGenerate', 0],
            EmailEvents::EMAIL_ON_DISPLAY => ['onEmailGenerate', 0],
        ];
    }

    /**
     * Search and replace tokens with content
     *
     * @param Events\EmailSendEvent $event
     * @throws \Throwable
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Syntax
     */
    public function onEmailGenerate(Events\EmailSendEvent $event)
    {
        $this->logger->info('onEmailGenerate MauticAdvancedTemplatesBundle\EmailSubscriber');

        $email   = $event->getEmail();
        $emailId = ($email) ? $email->getId() : null;
        if (!$email instanceof Email) {
            $email = $this->emailModel->getEntity($emailId);
        }

        $subject = $this->templateProcessor->processTemplate($email->getSubject(), $event->getLead());
        $event->setSubject($subject);

        $content = $this->templateProcessor->processTemplate($email->getCustomHtml(), $event->getLead());
        // $event->setContent($content); would be ideal, however it disables the tracking pixel, so we recreate it without tracking pixel disabled.

        if ($event->getHelper()) {
            $event->getHelper()->setBody($content);
        } else {
            $event->setContent($content);
        }
        // this forces generated plaintext with our new content, if it's not staticly set.
        if (empty($email->getPlainText())) {
            $event->setPlainText('');
        }
    }
}
