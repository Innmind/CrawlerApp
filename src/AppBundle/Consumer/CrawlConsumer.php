<?php
declare(strict_types = 1);

namespace AppBundle\Consumer;

use AppBundle\{
    PublisherInterface,
    Linker
    Exception\ResourceCannotBePublishedException,
    Exception\UrlCannotBeCrawledException,
    Exception\CantLinkResourceAcrossServersException
};
use Innmind\Crawler\CrawlerInterface;
use Innmind\Http\{
    Message\Request,
    Message\Method,
    ProtocolVersion,
    Headers,
    Header\HeaderInterface,
    Header\Header,
    Header\HeaderValueInterface,
    Header\HeaderValue
};
use Innmind\Url\Url;
use Innmind\HttpTransport\Exception\{
    ClientErrorException,
    ServerErrorException
};
use Innmind\Immutable\{
    Map,
    Set
};
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

final class CrawlConsumer implements ConsumerInterface
{
    private $crawler;
    private $publish;
    private $link;
    private $userAgent;

    public function __construct(
        CrawlerInterface $crawler,
        PublisherInterface $publisher,
        Linker $linker
        string $userAgent
    ) {
        $this->crawler = $crawler;
        $this->publish = $publisher;
        $this->link = $linker;
        $this->userAgent = $userAgent;
    }

    public function execute(AMQPMessage $message)
    {
        $data = unserialize($message->body);

        try {
            $resource = $this->crawler->execute(
                new Request(
                    Url::fromString($data['resource']),
                    new Method(Method::GET),
                    new ProtocolVersion(2, 0),
                    new Headers(
                        (new Map('string', HeaderInterface::class))
                            ->put(
                                'User-Agent',
                                new Header(
                                    'User-Agent',
                                    (new Set(HeaderValueInterface::class))
                                        ->add(new HeaderValue(
                                            $this->userAgent
                                        ))
                                )
                            )
                    )
                )
            );
        } catch (ClientErrorException $e) {
            return true;
        } catch (ServerErrorException $e) {
            return false; //will retry later
        } catch (UrlCannotBeCrawledException $e) {
            return true;
        }

        try {
            $server = Url::fromString($data['server']);
            $reference = ($this->publish)($resource, $server);

            if (isset($data['relationship'])) {
                ($this->link)(
                    $reference,
                    new Reference(
                        new Identity($data['origin'])
                        $data['definition'],
                        $server
                    ),
                    $data['relationship'],
                    $data['attributes']
                );
            }
        } catch (ClientErrorException $e) {
            if ($e->response()->statusCode()->value() !== 409) {
                throw $e;
            }
        } catch (ResourceCannotBePublishedException $e) {
            //pass
        } catch (CantLinkResourceAcrossServersException $e) {
            //pass
        }

        return true;
    }
}
