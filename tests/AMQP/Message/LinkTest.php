<?php
declare(strict_types = 1);

namespace Tests\Crawler\AMQP\Message;

use Crawler\{
    AMQP\Message\Link,
    Reference,
    Exception\LogicException
};
use Innmind\AMQP\Model\Basic\{
    Message,
    Message\ContentType,
    Message\ContentEncoding,
    Message\DeliveryMode,
    Message\Priority,
    Message\CorrelationId,
    Message\ReplyTo,
    Message\Id,
    Message\Type,
    Message\UserId,
    Message\AppId
};
use Innmind\Rest\Client\Identity;
use Innmind\Url\{
    UrlInterface,
    Url
};
use Innmind\TimeContinuum\{
    ElapsedPeriod,
    PointInTimeInterface
};
use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Message::class,
            $message = new Link(
                $this->createMock(UrlInterface::class),
                $resource = $this->createMock(UrlInterface::class),
                $reference = new Reference(
                    $this->createMock(Identity::class),
                    'foo',
                    $this->createMock(UrlInterface::class)
                )
            )
        );
        $this->assertSame($resource, $message->resource());
        $this->assertSame($reference, $message->reference());
    }

    public function testContentType()
    {
        $message = new Link(
            $this->createMock(UrlInterface::class),
            $this->createMock(UrlInterface::class),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                $this->createMock(UrlInterface::class)
            )
        );

        $this->assertTrue($message->hasContentType());
        $this->assertSame('application/json', (string) $message->contentType());
        $this->expectException(LogicException::class);
        $message->withContentType(new ContentType('application', 'octet-stream'));
    }

    public function testContentEncoding()
    {
        $message = new Link(
            $this->createMock(UrlInterface::class),
            $this->createMock(UrlInterface::class),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                $this->createMock(UrlInterface::class)
            )
        );

        $this->assertFalse($message->hasContentEncoding());
        $this->expectException(LogicException::class);
        $message->withContentEncoding(new ContentEncoding('gzip'));
    }

    public function testHeaders()
    {
        $message = new Link(
            $this->createMock(UrlInterface::class),
            $this->createMock(UrlInterface::class),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                $this->createMock(UrlInterface::class)
            )
        );

        $this->assertFalse($message->hasHeaders());
        $this->expectException(LogicException::class);
        $message->withHeaders($message->headers());
    }

    public function testDeliveryMode()
    {
        $message = new Link(
            $this->createMock(UrlInterface::class),
            $this->createMock(UrlInterface::class),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                $this->createMock(UrlInterface::class)
            )
        );

        $this->assertTrue($message->hasDeliveryMode());
        $this->assertSame(DeliveryMode::persistent(), $message->deliveryMode());
        $this->expectException(LogicException::class);
        $message->withDeliveryMode(DeliveryMode::persistent());
    }

    public function testPriority()
    {
        $message = new Link(
            $this->createMock(UrlInterface::class),
            $this->createMock(UrlInterface::class),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                $this->createMock(UrlInterface::class)
            )
        );

        $this->assertFalse($message->hasPriority());
        $this->expectException(LogicException::class);
        $message->withPriority(new Priority(5));
    }

    public function testAddPriorityWhenLinkOnAnotherHost()
    {
        $message = new Link(
            Url::fromString('http://example.com/'),
            Url::fromString('http://github.com/'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                $this->createMock(UrlInterface::class)
            )
        );

        $this->assertTrue($message->hasPriority());
        $this->assertSame(5, $message->priority()->toInt());
    }

    public function testCorrelationId()
    {
        $message = new Link(
            $this->createMock(UrlInterface::class),
            $this->createMock(UrlInterface::class),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                $this->createMock(UrlInterface::class)
            )
        );

        $this->assertFalse($message->hasCorrelationId());
        $this->expectException(LogicException::class);
        $message->withCorrelationId(new CorrelationId('foo'));
    }

    public function testReplyTo()
    {
        $message = new Link(
            $this->createMock(UrlInterface::class),
            $this->createMock(UrlInterface::class),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                $this->createMock(UrlInterface::class)
            )
        );

        $this->assertFalse($message->hasReplyTo());
        $this->expectException(LogicException::class);
        $message->withReplyTo(new ReplyTo('foo'));
    }

    public function testExpiration()
    {
        $message = new Link(
            $this->createMock(UrlInterface::class),
            $this->createMock(UrlInterface::class),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                $this->createMock(UrlInterface::class)
            )
        );

        $this->assertFalse($message->hasExpiration());
        $this->expectException(LogicException::class);
        $message->withExpiration(new ElapsedPeriod(5));
    }

    public function testId()
    {
        $message = new Link(
            $this->createMock(UrlInterface::class),
            $this->createMock(UrlInterface::class),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                $this->createMock(UrlInterface::class)
            )
        );

        $this->assertFalse($message->hasId());
        $this->expectException(LogicException::class);
        $message->withId(new Id('foo'));
    }

    public function testTimestamp()
    {
        $message = new Link(
            $this->createMock(UrlInterface::class),
            $this->createMock(UrlInterface::class),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                $this->createMock(UrlInterface::class)
            )
        );
        $message2 = $message->withTimestamp(
            $expected = $this->createMock(PointInTimeInterface::class)
        );

        $this->assertInstanceOf(Link::class, $message2);
        $this->assertNotSame($message, $message2);
        $this->assertFalse($message->hasTimestamp());
        $this->assertTrue($message2->hasTimestamp());
        $this->assertSame($expected, $message2->timestamp());
    }

    public function testType()
    {
        $message = new Link(
            $this->createMock(UrlInterface::class),
            $this->createMock(UrlInterface::class),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                $this->createMock(UrlInterface::class)
            )
        );

        $this->assertFalse($message->hasType());
        $this->expectException(LogicException::class);
        $message->withType(new Type('foo'));
    }

    public function testUserId()
    {
        $message = new Link(
            $this->createMock(UrlInterface::class),
            $this->createMock(UrlInterface::class),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                $this->createMock(UrlInterface::class)
            )
        );

        $this->assertFalse($message->hasUserId());
        $this->expectException(LogicException::class);
        $message->withUserId(new UserId('foo'));
    }

    public function testAppId()
    {
        $message = new Link(
            $this->createMock(UrlInterface::class),
            $this->createMock(UrlInterface::class),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                $this->createMock(UrlInterface::class)
            )
        );

        $this->assertTrue($message->hasAppId());
        $this->assertSame('crawler', (string) $message->appId());
        $this->expectException(LogicException::class);
        $message->withAppId(new AppId('foo'));
    }

    public function testBody()
    {
        $message = new Link(
            Url::fromString('/baz'),
            Url::fromString('/foo'),
            new Reference(
                new Identity\Identity('uuid'),
                'def',
                Url::fromString('/bar')
            )
        );

        $this->assertSame(
            json_encode([
                'resource' => '/foo',
                'origin' => 'uuid',
                'relationship' => 'referrer',
                'definition' => 'def',
                'server' => '/bar',
            ]),
            (string) $message->body()
        );
    }
}
