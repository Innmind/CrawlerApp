<?php
declare(strict_types = 1);

namespace Crawler\AMQP\Consumer;

use Crawler\{
    Publisher,
    Linker,
    AMQP\Message\Resource,
    Exception\ResourceCannotBePublished,
    Exception\UrlCannotBeCrawled,
    Exception\CantLinkResourceAcrossServers,
    Exception\ResponseTooHeavy,
};
use Innmind\Crawler\Crawler;
use Innmind\Http\{
    Message\Request\Request,
    Message\Method\Method,
    Message\StatusCode\StatusCode,
    ProtocolVersion\ProtocolVersion,
    Headers\Headers,
    Header,
    Header\Value\Value,
};
use Innmind\HttpTransport\Exception\{
    ConnectionFailed,
    ClientError,
    ServerError,
};
use Innmind\AMQP\{
    Model\Basic\Message,
    Exception\Requeue,
};

final class CrawlConsumer
{
    private Crawler $crawl;
    private Publisher $publish;
    private Linker $link;
    private string $userAgent;

    public function __construct(
        Crawler $crawl,
        Publisher $publisher,
        Linker $linker,
        string $userAgent
    ) {
        $this->crawl = $crawl;
        $this->publish = $publisher;
        $this->link = $linker;
        $this->userAgent = $userAgent;
    }

    public function __invoke(Message $message): void
    {
        $message = new Resource($message);

        try {
            $resource = ($this->crawl)(
                new Request(
                    $message->resource(),
                    new Method(Method::GET),
                    new ProtocolVersion(2, 0),
                    Headers::of(
                        new Header\Header(
                            'User-Agent',
                            new Value($this->userAgent)
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
        } catch (ResponseTooHeavy $e) {
            return;
        }

        try {
            $reference = ($this->publish)(
                $resource,
                $message->reference()->server()
            );

            if ($message->hasRelationship()) {
                ($this->link)(
                    $reference,
                    $message->reference(),
                    $message->relationship(),
                    $message->attributes()
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
        } catch (ServerError $e) {
            throw new Requeue; //will retry later, maybe due to heavy load
        }
    }
}
