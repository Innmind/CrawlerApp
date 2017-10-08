<?php
declare(strict_types = 1);

namespace AppBundle\AMQP\Consumer;

use AppBundle\{
    Publisher,
    Linker,
    Reference,
    Exception\ResourceCannotBePublished,
    Exception\UrlCannotBeCrawled,
    Exception\CantLinkResourceAcrossServers
};
use Innmind\Crawler\Crawler;
use Innmind\Rest\Client\Identity\Identity;
use Innmind\Http\{
    Message\Request\Request,
    Message\Method\Method,
    Message\StatusCode\StatusCode,
    ProtocolVersion\ProtocolVersion,
    Headers\Headers,
    Header,
    Header\Value\Value
};
use Innmind\Url\Url;
use Innmind\HttpTransport\Exception\{
    ConnectionFailed,
    ClientError,
    ServerError
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
        Crawler $crawler,
        Publisher $publisher,
        Linker $linker,
        string $userAgent
    ) {
        $this->crawler = $crawler;
        $this->publish = $publisher;
        $this->link = $linker;
        $this->userAgent = $userAgent;
    }

    public function execute(AMQPMessage $message): bool
    {
        $data = unserialize($message->body);

        try {
            $resource = $this->crawler->execute(
                new Request(
                    Url::fromString($data['resource']),
                    new Method(Method::GET),
                    new ProtocolVersion(2, 0),
                    new Headers(
                        (new Map('string', Header::class))
                            ->put(
                                'User-Agent',
                                new Header\Header(
                                    'User-Agent',
                                    new Value($this->userAgent)
                                )
                            )
                    )
                )
            );
        } catch (ConnectionFailed $e) {
            return true;
        } catch (ClientError $e) {
            return true;
        } catch (ServerError $e) {
            return false; //will retry later
        } catch (UrlCannotBeCrawled $e) {
            return true;
        }

        try {
            $server = Url::fromString($data['server']);
            $reference = ($this->publish)($resource, $server);

            if (isset($data['relationship'])) {
                ($this->link)(
                    $reference,
                    new Reference(
                        new Identity($data['origin']),
                        $data['definition'],
                        $server
                    ),
                    $data['relationship'],
                    $data['attributes'] ?? []
                );
            }
        } catch (ClientError $e) {
            $code = $e->response()->statusCode()->value();

            if ($code !== StatusCode::codes()->get('CONFLICT')) {
                throw $e;
            }
        } catch (ResourceCannotBePublished $e) {
            //pass
        } catch (CantLinkResourceAcrossServers $e) {
            //pass
        }

        return true;
    }
}
