<?php

namespace luya\mailjet\tests;

use luya\mailjet\jobs\TemplateEmailSendJob;
use luya\mailjet\Mailer;
use luya\mailjet\MailerMessage;

class MailerTest extends MailjetTestCase
{
    public function testDefaultErrorReportingValue()
    {
        $mailer = new Mailer();
        $mailer->defaultTemplateErrorReporting = ['error@luya.io' => 'John Error'];
        $mailer->sandbox = true;

        $message = new MailerMessage();
        $message->setTemplate(123);
        $message->setVariables([
            'bar' => 'foo',
            'int' => 123,
            'null' => null,
            'bool' => false,
        ]);
        $message->setDeduplicateCampaign(false);
        $message->setCustomId('MyCustomId');
        $message->setCustomCampaign('MyCustomCampaign');
        $message->setTemplateLanguage(false);

        $this->assertSame([
            'TemplateID' => 123,
            'TemplateLanguage' => false,
            'Variables' => [
                'bar' => 'foo',
                'int' => '123',
                'null' => '',
                'bool' => '',
            ],
            'TemplateErrorReporting' => [
                'Email' => 'error@luya.io',
                'Name' => 'John Error',
            ],
            'CustomCampaign' => 'MyCustomCampaign',
            'DeduplicateCampaign' => false,
            'CustomID' => 'MyCustomId',
            'TemplateErrorDeliver' => true,
        ], $mailer->extractMessage($message));

        $this->assertFalse($mailer->sendMessage($message));
        $this->assertNotEmpty($mailer->lastError);
    }

    public function testJobWithMailer()
    {
        // invoke job tests

        $job = new TemplateEmailSendJob();
        $job->variables = [];
        $job->templateId = 1;
        $job->recipient = ['none'];
        $job->from = 'sender';
        $this->expectException('luya\Exception');
        $job->execute($this->app->adminqueue);
    }

    public function testToEmailAndName()
    {
        $this->assertSame(['Email' => 'foo@bar.com', 'Name' => 'foo@bar.com'], Mailer::toEmailAndName('foo@bar.com'));
        $this->assertSame(['Email' => 'email', 'Name' => 'name'], Mailer::toEmailAndName(['email' => 'name']));

        $this->expectException("luya\Exception");
        Mailer::toEmailAndName(null);
    }

    public function testToMultiEmailAndName()
    {
        $this->assertSame([
            ['Email' => 'foo1', 'Name' => 'foo1'],
            ['Email' => 'foo2', 'Name' => 'foo2'],
        ], Mailer::toMultiEmailAndName(['foo1', 'foo2']));
    }

    public function testAttachementBody()
    {
        $mailer = new Mailer();
        $mailer->sandbox = true;

        $message = new MailerMessage();
        $message->attachContent('helloworld');

        $this->assertSame([
            'Attachments' => [
                0 => [
                    'ContentType' => 'text/plain',
                    'Filename' => 'attachment.txt',
                    'Base64Content' => 'aGVsbG93b3JsZA==',
                ]
            ]
        ], $mailer->extractMessage($message));

        $this->assertFalse($mailer->sendMessage($message));
        $this->assertNotEmpty($mailer->lastError);
    }

    public function testAttachementBodyWithNames()
    {
        $mailer = new Mailer();
        $mailer->sandbox = true;

        $message = new MailerMessage();
        $message->attachContent('helloworld', ['fileName' => 'foobar.txt', 'contentType' => 'application/pdf']);

        $this->assertSame([
            'Attachments' => [
                0 => [
                    'ContentType' => 'application/pdf',
                    'Filename' => 'foobar.txt',
                    'Base64Content' => 'aGVsbG93b3JsZA==',
                ]
            ]
        ], $mailer->extractMessage($message));

        $this->assertFalse($mailer->sendMessage($message));
        $this->assertNotEmpty($mailer->lastError);
    }

    public function testAttachementBodyWithPathToFile()
    {
        $mailer = new Mailer();
        $mailer->sandbox = true;

        $message = new MailerMessage();
        $message->attach(__DIR__ . '/.env.dist');

        $this->assertSame([
            'Attachments' => [
                0 => [
                    'ContentType' => 'text/plain',
                    'Filename' => '.env.dist',
                    'Base64Content' => 'YXBpa2V5PWZvbwphcGlzZWNyZXQ9YmFy',
                ]
            ]
        ], $mailer->extractMessage($message));

        $this->assertFalse($mailer->sendMessage($message));
        $this->assertNotEmpty($mailer->lastError);
    }
}
