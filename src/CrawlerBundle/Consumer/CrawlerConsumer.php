<?php

namespace CrawlerBundle\Consumer;

use CrawlerBundle\Publisher;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

class CrawlerConsumer implements ConsumerInterface
{
    protected $publisher;
    protected $logger;

    public function __construct(Publisher $publisher, LoggerInterface $logger)
    {
        $this->publisher = $publisher;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(AMQPMessage $message)
    {
        try {
            $data = unserialize($message->body);

            $this->publisher->publish(
                $data['url'],
                $data['server'],
                $data['uuid']
            );

            return true;
        } catch (\Exception $e) {
            $this->logger->error(
                'Resource fail to be crawled',
                [
                    'url' => $data['url'],
                    'server' => $data['server'],
                ]
            );

            return false;
        }
    }
}
