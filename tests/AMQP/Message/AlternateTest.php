<?php
declare(strict_types = 1);

namespace Tests\Crawler\AMQP\Message;

use Crawler\{
    AMQP\Message\Alternate,
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

class AlternateTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Message::class,
            $message = new Alternate(
                $resource = Url::of('example.com'),
                $reference = new Reference(
                    $this->createMock(Identity::class),
                    'foo',
                    Url::of('example.com')
                ),
                'fr'
            )
        );
        $this->assertSame($resource, $message->resource());
        $this->assertSame($reference, $message->reference());
        $this->assertSame('fr', $message->language());
    }

    public function testContentType()
    {
        $message = new Alternate(
            Url::of('example.com'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            ),
            'fr'
        );

        $this->assertTrue($message->hasContentType());
        $this->assertSame('application/json', $message->contentType()->toString());
        $this->expectException(LogicException::class);
        $message->withContentType(new ContentType('application', 'octet-stream'));
    }

    public function testContentEncoding()
    {
        $message = new Alternate(
            Url::of('example.com'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            ),
            'fr'
        );

        $this->assertFalse($message->hasContentEncoding());
        $this->expectException(LogicException::class);
        $message->withContentEncoding(new ContentEncoding('gzip'));
    }

    public function testHeaders()
    {
        $message = new Alternate(
            Url::of('example.com'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            ),
            'fr'
        );

        $this->assertFalse($message->hasHeaders());
        $this->expectException(LogicException::class);
        $message->withHeaders($message->headers());
    }

    public function testDeliveryMode()
    {
        $message = new Alternate(
            Url::of('example.com'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            ),
            'fr'
        );

        $this->assertTrue($message->hasDeliveryMode());
        $this->assertSame(DeliveryMode::persistent(), $message->deliveryMode());
        $this->expectException(LogicException::class);
        $message->withDeliveryMode(DeliveryMode::persistent());
    }

    public function testPriority()
    {
        $message = new Alternate(
            Url::of('example.com'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            ),
            'fr'
        );

        $this->assertFalse($message->hasPriority());
        $this->expectException(LogicException::class);
        $message->withPriority(new Priority(5));
    }

    public function testCorrelationId()
    {
        $message = new Alternate(
            Url::of('example.com'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            ),
            'fr'
        );

        $this->assertFalse($message->hasCorrelationId());
        $this->expectException(LogicException::class);
        $message->withCorrelationId(new CorrelationId('foo'));
    }

    public function testReplyTo()
    {
        $message = new Alternate(
            Url::of('example.com'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            ),
            'fr'
        );

        $this->assertFalse($message->hasReplyTo());
        $this->expectException(LogicException::class);
        $message->withReplyTo(new ReplyTo('foo'));
    }

    public function testExpiration()
    {
        $message = new Alternate(
            Url::of('example.com'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            ),
            'fr'
        );

        $this->assertFalse($message->hasExpiration());
        $this->expectException(LogicException::class);
        $message->withExpiration(new ElapsedPeriod(5));
    }

    public function testId()
    {
        $message = new Alternate(
            Url::of('example.com'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            ),
            'fr'
        );

        $this->assertFalse($message->hasId());
        $this->expectException(LogicException::class);
        $message->withId(new Id('foo'));
    }

    public function testTimestamp()
    {
        $message = new Alternate(
            Url::of('example.com'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            ),
            'fr'
        );
        $message2 = $message->withTimestamp(
            $expected = $this->createMock(PointInTime::class)
        );

        $this->assertInstanceOf(Alternate::class, $message2);
        $this->assertNotSame($message, $message2);
        $this->assertFalse($message->hasTimestamp());
        $this->assertTrue($message2->hasTimestamp());
        $this->assertSame($expected, $message2->timestamp());
    }

    public function testType()
    {
        $message = new Alternate(
            Url::of('example.com'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            ),
            'fr'
        );

        $this->assertFalse($message->hasType());
        $this->expectException(LogicException::class);
        $message->withType(new Type('foo'));
    }

    public function testUserId()
    {
        $message = new Alternate(
            Url::of('example.com'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            ),
            'fr'
        );

        $this->assertFalse($message->hasUserId());
        $this->expectException(LogicException::class);
        $message->withUserId(new UserId('foo'));
    }

    public function testAppId()
    {
        $message = new Alternate(
            Url::of('example.com'),
            new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            ),
            'fr'
        );

        $this->assertTrue($message->hasAppId());
        $this->assertSame('crawler', $message->appId()->toString());
        $this->expectException(LogicException::class);
        $message->withAppId(new AppId('foo'));
    }

    public function testBody()
    {
        $message = new Alternate(
            Url::of('/foo'),
            new Reference(
                new Identity\Identity('uuid'),
                'def',
                Url::of('/bar')
            ),
            'fr'
        );

        $this->assertSame(
            json_encode([
                'resource' => '/foo',
                'origin' => 'uuid',
                'relationship' => 'alternate',
                'attributes' => [
                    'language' => 'fr',
                ],
                'definition' => 'def',
                'server' => '/bar',
            ]),
            $message->body()->toString()
        );
    }
}
