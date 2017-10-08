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
use Innmind\AMQP\{
    Model\Basic\Message,
    Exception\Requeue
};
use Innmind\Immutable\{
    Map,
    Set
};

final class CrawlConsumer
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

    public function __invoke(Message $message): void
    {
        $data = json_decode((string) $message->body(), true);

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
            return;
        } catch (ClientError $e) {
            return;
        } catch (ServerError $e) {
            throw new Requeue; //will retry later
        } catch (UrlCannotBeCrawled $e) {
            return;
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

        return;
    }
}
