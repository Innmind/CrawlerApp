<?php
declare(strict_types = 1);

namespace Tests\Crawler\AMQP\Message;

use Crawler\{
    AMQP\Message\Resource,
    Reference,
    Exception\DomainException,
};
use Innmind\AMQP\{
    Model\Basic\Message,
    Model\Basic\Message\ContentType,
    Model\Basic\Message\ContentEncoding,
    Model\Basic\Message\DeliveryMode,
    Model\Basic\Message\Priority,
    Model\Basic\Message\CorrelationId,
    Model\Basic\Message\ReplyTo,
    Model\Basic\Message\Id,
    Model\Basic\Message\Type,
    Model\Basic\Message\UserId,
    Model\Basic\Message\AppId,
    Model\Basic\Message\Locked,
    Model\Basic\Message\Generic,
    Exception\MessageLocked,
};
use Innmind\Url\Url;
use Innmind\TimeContinuum\{
    Earth\ElapsedPeriod,
    PointInTime,
};
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class ResourceTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Message::class,
            $message = new Resource(
                new Locked(
                    (new Generic(Str::of(\json_encode([
                        'resource' => '/foo',
                        'origin' => 'uuid',
                        'relationship' => 'referrer',
                        'definition' => 'def',
                        'server' => '/server',
                    ]))))
                        ->withContentType(new ContentType('application', 'json'))
                )
            )
        );
        $this->assertInstanceOf(Url::class, $message->resource());
        $this->assertSame('/foo', $message->resource()->toString());
        $this->assertInstanceOf(Reference::class, $message->reference());
        $this->assertSame('uuid', $message->reference()->identity()->toString());
        $this->assertSame('def', $message->reference()->definition());
        $this->assertSame('/server', $message->reference()->server()->toString());
        $this->assertTrue($message->hasRelationship());
        $this->assertSame('referrer', $message->relationship());
    }

    public function testThrowWhenNotContentType()
    {
        $this->expectException(DomainException::class);

        new Resource(new Locked(new Generic(Str::of(''))));
    }

    public function testThrowWhenInvalidContentType()
    {
        $this->expectException(DomainException::class);

        new Resource(new Locked(
            (new Generic(Str::of('')))
                ->withContentType(new ContentType('application', 'octet-stream'))
        ));
    }

    public function testContentType()
    {
        $message = new Resource(
            new Locked(
                (new Generic(Str::of(\json_encode([
                    'resource' => '/',
                    'origin' => 'uuid',
                    'relationship' => 'referrer',
                    'definition' => 'def',
                    'server' => 'server',
                ]))))
                    ->withContentType(new ContentType('application', 'json'))
            )
        );

        $this->assertTrue($message->hasContentType());
        $this->assertSame('application/json', $message->contentType()->toString());
        $this->expectException(MessageLocked::class);
        $message->withContentType(new ContentType('application', 'octet-stream'));
    }

    public function testContentEncoding()
    {
        $message = new Resource(
            new Locked(
                (new Generic(Str::of(\json_encode([
                    'resource' => '/',
                    'origin' => 'uuid',
                    'relationship' => 'referrer',
                    'definition' => 'def',
                    'server' => 'server',
                ]))))
                    ->withContentType(new ContentType('application', 'json'))
            )
        );

        $this->assertFalse($message->hasContentEncoding());
        $this->expectException(MessageLocked::class);
        $message->withContentEncoding(new ContentEncoding('gzip'));
    }

    public function testHeaders()
    {
        $message = new Resource(
            new Locked(
                (new Generic(Str::of(\json_encode([
                    'resource' => '/',
                    'origin' => 'uuid',
                    'relationship' => 'referrer',
                    'definition' => 'def',
                    'server' => 'server',
                ]))))
                    ->withContentType(new ContentType('application', 'json'))
            )
        );

        $this->assertFalse($message->hasHeaders());
        $this->expectException(MessageLocked::class);
        $message->withHeaders($message->headers());
    }

    public function testDeliveryMode()
    {
        $message = new Resource(
            new Locked(
                (new Generic(Str::of(\json_encode([
                    'resource' => '/',
                    'origin' => 'uuid',
                    'relationship' => 'referrer',
                    'definition' => 'def',
                    'server' => 'server',
                ]))))
                    ->withContentType(new ContentType('application', 'json'))
            )
        );

        $this->assertFalse($message->hasDeliveryMode());
        $this->expectException(MessageLocked::class);
        $message->withDeliveryMode(DeliveryMode::persistent());
    }

    public function testPriority()
    {
        $message = new Resource(
            new Locked(
                (new Generic(Str::of(\json_encode([
                    'resource' => '/',
                    'origin' => 'uuid',
                    'relationship' => 'referrer',
                    'definition' => 'def',
                    'server' => 'server',
                ]))))
                    ->withContentType(new ContentType('application', 'json'))
            )
        );

        $this->assertFalse($message->hasPriority());
        $this->expectException(MessageLocked::class);
        $message->withPriority(new Priority(5));
    }

    public function testCorrelationId()
    {
        $message = new Resource(
            new Locked(
                (new Generic(Str::of(\json_encode([
                    'resource' => '/',
                    'origin' => 'uuid',
                    'relationship' => 'referrer',
                    'definition' => 'def',
                    'server' => 'server',
                ]))))
                    ->withContentType(new ContentType('application', 'json'))
            )
        );

        $this->assertFalse($message->hasCorrelationId());
        $this->expectException(MessageLocked::class);
        $message->withCorrelationId(new CorrelationId('foo'));
    }

    public function testReplyTo()
    {
        $message = new Resource(
            new Locked(
                (new Generic(Str::of(\json_encode([
                    'resource' => '/',
                    'origin' => 'uuid',
                    'relationship' => 'referrer',
                    'definition' => 'def',
                    'server' => 'server',
                ]))))
                    ->withContentType(new ContentType('application', 'json'))
            )
        );

        $this->assertFalse($message->hasReplyTo());
        $this->expectException(MessageLocked::class);
        $message->withReplyTo(new ReplyTo('foo'));
    }

    public function testExpiration()
    {
        $message = new Resource(
            new Locked(
                (new Generic(Str::of(\json_encode([
                    'resource' => '/',
                    'origin' => 'uuid',
                    'relationship' => 'referrer',
                    'definition' => 'def',
                    'server' => 'server',
                ]))))
                    ->withContentType(new ContentType('application', 'json'))
            )
        );

        $this->assertFalse($message->hasExpiration());
        $this->expectException(MessageLocked::class);
        $message->withExpiration(new ElapsedPeriod(5));
    }

    public function testId()
    {
        $message = new Resource(
            new Locked(
                (new Generic(Str::of(\json_encode([
                    'resource' => '/',
                    'origin' => 'uuid',
                    'relationship' => 'referrer',
                    'definition' => 'def',
                    'server' => 'server',
                ]))))
                    ->withContentType(new ContentType('application', 'json'))
            )
        );

        $this->assertFalse($message->hasId());
        $this->expectException(MessageLocked::class);
        $message->withId(new Id('foo'));
    }

    public function testTimestamp()
    {
        $message = new Resource(
            new Locked(
                (new Generic(Str::of(\json_encode([
                    'resource' => '/',
                    'origin' => 'uuid',
                    'relationship' => 'referrer',
                    'definition' => 'def',
                    'server' => 'server',
                ]))))
                    ->withContentType(new ContentType('application', 'json'))
            )
        );

        $this->assertFalse($message->hasTimestamp());
        $this->expectException(MessageLocked::class);
        $message->withTimestamp($this->createMock(PointInTime::class));
    }

    public function testType()
    {
        $message = new Resource(
            new Locked(
                (new Generic(Str::of(\json_encode([
                    'resource' => '/',
                    'origin' => 'uuid',
                    'relationship' => 'referrer',
                    'definition' => 'def',
                    'server' => 'server',
                ]))))
                    ->withContentType(new ContentType('application', 'json'))
            )
        );

        $this->assertFalse($message->hasType());
        $this->expectException(MessageLocked::class);
        $message->withType(new Type('foo'));
    }

    public function testUserId()
    {
        $message = new Resource(
            new Locked(
                (new Generic(Str::of(\json_encode([
                    'resource' => '/',
                    'origin' => 'uuid',
                    'relationship' => 'referrer',
                    'definition' => 'def',
                    'server' => 'server',
                ]))))
                    ->withContentType(new ContentType('application', 'json'))
            )
        );

        $this->assertFalse($message->hasUserId());
        $this->expectException(MessageLocked::class);
        $message->withUserId(new UserId('foo'));
    }

    public function testAppId()
    {
        $message = new Resource(
            new Locked(
                (new Generic(Str::of(\json_encode([
                    'resource' => '/',
                    'origin' => 'uuid',
                    'relationship' => 'referrer',
                    'definition' => 'def',
                    'server' => 'server',
                ]))))
                    ->withContentType(new ContentType('application', 'json'))
            )
        );

        $this->assertFalse($message->hasAppId());
        $this->expectException(MessageLocked::class);
        $message->withAppId(new AppId('foo'));
    }

    public function testBody()
    {
        $message = new Resource(
            new Locked(
                (new Generic(Str::of(\json_encode([
                    'resource' => '/',
                    'origin' => 'uuid',
                    'relationship' => 'referrer',
                    'definition' => 'def',
                    'server' => 'server',
                ]))))
                    ->withContentType(new ContentType('application', 'json'))
            )
        );

        $this->assertSame(
            \json_encode([
                'resource' => '/',
                'origin' => 'uuid',
                'relationship' => 'referrer',
                'definition' => 'def',
                'server' => 'server',
            ]),
            $message->body()->toString()
        );
    }
}
