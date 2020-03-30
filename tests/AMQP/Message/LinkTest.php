<?php
declare(strict_types = 1);

namespace Tests\Crawler\AMQP\Message;

use Crawler\{
    AMQP\Message\Link,
    Reference,
    Exception\LogicException,
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
    Message\AppId,
};
use Innmind\Rest\Client\Identity;
use Innmind\Url\Url;
use Innmind\TimeContinuum\{
    Earth\ElapsedPeriod,
    PointInTime,
};
use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Message::class,
            $message = new Link(
                Url::of('example.com'),
                $resource = Url::of('example.com'),
                $reference = new Reference(
                    $this->createMock(Identity::class),
                    'foo',
                    Url::of('example.com')
                )
            )
        );
        $this->assertSame($resource, $message->resource());
        $this->assertSame($reference, $message->reference());
    }

    public function testContentType()
    {
        $message = new Link(
            Url::of('example.com'),
            Url::of('example.com'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            )
        );

        $this->assertTrue($message->hasContentType());
        $this->assertSame('application/json', $message->contentType()->toString());
        $this->expectException(LogicException::class);
        $message->withContentType(new ContentType('application', 'octet-stream'));
    }

    public function testContentEncoding()
    {
        $message = new Link(
            Url::of('example.com'),
            Url::of('example.com'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            )
        );

        $this->assertFalse($message->hasContentEncoding());
        $this->expectException(LogicException::class);
        $message->withContentEncoding(new ContentEncoding('gzip'));
    }

    public function testHeaders()
    {
        $message = new Link(
            Url::of('example.com'),
            Url::of('example.com'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            )
        );

        $this->assertFalse($message->hasHeaders());
        $this->expectException(LogicException::class);
        $message->withHeaders($message->headers());
    }

    public function testDeliveryMode()
    {
        $message = new Link(
            Url::of('example.com'),
            Url::of('example.com'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
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
            Url::of('example.com'),
            Url::of('example.com'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            )
        );

        $this->assertFalse($message->hasPriority());
        $this->expectException(LogicException::class);
        $message->withPriority(new Priority(5));
    }

    public function testAddPriorityWhenLinkOnAnotherHost()
    {
        $message = new Link(
            Url::of('http://example.com/'),
            Url::of('http://github.com/'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            )
        );

        $this->assertTrue($message->hasPriority());
        $this->assertSame(5, $message->priority()->toInt());
    }

    public function testCorrelationId()
    {
        $message = new Link(
            Url::of('example.com'),
            Url::of('example.com'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            )
        );

        $this->assertFalse($message->hasCorrelationId());
        $this->expectException(LogicException::class);
        $message->withCorrelationId(new CorrelationId('foo'));
    }

    public function testReplyTo()
    {
        $message = new Link(
            Url::of('example.com'),
            Url::of('example.com'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            )
        );

        $this->assertFalse($message->hasReplyTo());
        $this->expectException(LogicException::class);
        $message->withReplyTo(new ReplyTo('foo'));
    }

    public function testExpiration()
    {
        $message = new Link(
            Url::of('example.com'),
            Url::of('example.com'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            )
        );

        $this->assertFalse($message->hasExpiration());
        $this->expectException(LogicException::class);
        $message->withExpiration(new ElapsedPeriod(5));
    }

    public function testId()
    {
        $message = new Link(
            Url::of('example.com'),
            Url::of('example.com'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            )
        );

        $this->assertFalse($message->hasId());
        $this->expectException(LogicException::class);
        $message->withId(new Id('foo'));
    }

    public function testTimestamp()
    {
        $message = new Link(
            Url::of('example.com'),
            Url::of('example.com'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            )
        );
        $message2 = $message->withTimestamp(
            $expected = $this->createMock(PointInTime::class)
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
            Url::of('example.com'),
            Url::of('example.com'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            )
        );

        $this->assertFalse($message->hasType());
        $this->expectException(LogicException::class);
        $message->withType(new Type('foo'));
    }

    public function testUserId()
    {
        $message = new Link(
            Url::of('example.com'),
            Url::of('example.com'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            )
        );

        $this->assertFalse($message->hasUserId());
        $this->expectException(LogicException::class);
        $message->withUserId(new UserId('foo'));
    }

    public function testAppId()
    {
        $message = new Link(
            Url::of('example.com'),
            Url::of('example.com'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            )
        );

        $this->assertTrue($message->hasAppId());
        $this->assertSame('crawler', $message->appId()->toString());
        $this->expectException(LogicException::class);
        $message->withAppId(new AppId('foo'));
    }

    public function testBody()
    {
        $message = new Link(
            Url::of('/baz'),
            Url::of('/foo'),
            new Reference(
                new Identity\Identity('uuid'),
                'def',
                Url::of('/bar')
            )
        );

        $this->assertSame(
            \json_encode([
                'resource' => '/foo',
                'origin' => 'uuid',
                'relationship' => 'referrer',
                'definition' => 'def',
                'server' => '/bar',
            ]),
            $message->body()->toString()
        );
    }
}
