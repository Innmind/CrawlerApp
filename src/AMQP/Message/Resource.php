<?php
declare(strict_types = 1);

namespace Crawler\AMQP\Message;

use Crawler\{
    Reference,
    Exception\DomainException
};
use Innmind\Url\{
    UrlInterface,
    Url
};
use Innmind\AMQP\Model\Basic\{
    Message,
    Message\AppId,
    Message\ContentEncoding,
    Message\ContentType,
    Message\CorrelationId,
    Message\DeliveryMode,
    Message\Generic,
    Message\Id,
    Message\Priority,
    Message\ReplyTo,
    Message\Type,
    Message\UserId,
    Message\Locked
};
use Innmind\TimeContinuum\{
    PointInTimeInterface,
    ElapsedPeriod
};
use Innmind\Rest\Client\Identity\Identity;
use Innmind\Immutable\{
    MapInterface,
    Str
};

final class Resource implements Message
{
    private $inner;
    private $resource;
    private $relationship;
    private $attributes;
    private $reference;

    public function __construct(Locked $message)
    {
        if (
            !$message->hasContentType() ||
            (string) $message->contentType() !== 'application/json'
        ) {
            throw new DomainException;
        }

        $payload = json_decode((string) $message->body(), true);

        $this->inner = $message;
        $this->resource = Url::fromString($payload['resource']);
        $this->relationship = $payload['relationship'] ?? null;
        $this->attributes = $payload['attributes'] ?? [];
        $this->reference = new Reference(
            new Identity($payload['origin']),
            $payload['definition'],
            Url::fromString($payload['server'])
        );
    }

    public function resource(): UrlInterface
    {
        return $this->resource;
    }

    public function hasRelationship(): bool
    {
        return is_string($this->relationship);
    }

    public function relationship(): string
    {
        return $this->relationship;
    }

    public function attributes(): array
    {
        return $this->attributes;
    }

    public function reference(): Reference
    {
        return $this->reference;
    }

    public function hasContentType(): bool
    {
        return $this->inner->hasContentType();
    }

    public function contentType(): ContentType
    {
        return $this->inner->contentType();
    }

    public function withContentType(ContentType $contentType): Message
    {
        $this->inner->withContentType($contentType);
    }

    public function hasContentEncoding(): bool
    {
        return $this->inner->hasContentEncoding();
    }

    public function contentEncoding(): ContentEncoding
    {
        return $this->inner->contentEncoding();
    }

    public function withContentEncoding(ContentEncoding $contentEncoding): Message
    {
        $this->inner->withContentEncoding($contentEncoding);
    }

    public function hasHeaders(): bool
    {
        return $this->inner->hasHeaders();
    }

    /**
     * @return MapInterface<string, mixed>
     */
    public function headers(): MapInterface
    {
        return $this->inner->headers();
    }

    public function withHeaders(MapInterface $headers): Message
    {
        $this->inner->withHeaders($headers);
    }

    public function hasDeliveryMode(): bool
    {
        return $this->inner->hasDeliveryMode();
    }

    public function deliveryMode(): DeliveryMode
    {
        return $this->inner->deliveryMode();
    }

    public function withDeliveryMode(DeliveryMode $deliveryMode): Message
    {
        $this->inner->withDeliveryMode($deliveryMode);
    }

    public function hasPriority(): bool
    {
        return $this->inner->hasPriority();
    }

    public function priority(): Priority
    {
        return $this->inner->priority();
    }

    public function withPriority(Priority $priority): Message
    {
        $this->inner->withPriority($priority);
    }

    public function hasCorrelationId(): bool
    {
        return $this->inner->hasCorrelationId();
    }

    public function correlationId(): CorrelationId
    {
        return $this->inner->correlationId();
    }

    public function withCorrelationId(CorrelationId $correlationId): Message
    {
        $this->inner->withCorrelationId($correlationId);
    }

    public function hasReplyTo(): bool
    {
        return $this->inner->hasReplyTo();
    }

    public function replyTo(): ReplyTo
    {
        return $this->inner->replyTo();
    }

    public function withReplyTo(ReplyTo $replyTo): Message
    {
        $this->inner->withReplyTo($replyTo);
    }

    public function hasExpiration(): bool
    {
        return $this->inner->hasExpiration();
    }

    public function expiration(): ElapsedPeriod
    {
        return $this->inner->expiration();
    }

    public function withExpiration(ElapsedPeriod $expiration): Message
    {
        $this->inner->withExpiration($expiration);
    }

    public function hasId(): bool
    {
        return $this->inner->hasId();
    }

    public function id(): Id
    {
        return $this->inner->id();
    }

    public function withId(Id $id): Message
    {
        $this->inner->withId($id);
    }

    public function hasTimestamp(): bool
    {
        return $this->inner->hasTimestamp();
    }

    public function timestamp(): PointInTimeInterface
    {
        return $this->inner->timestamp();
    }

    public function withTimestamp(PointInTimeInterface $timestamp): Message
    {
        $this->inner->withTimestamp($timestamp);
    }

    public function hasType(): bool
    {
        return $this->inner->hasType();
    }

    public function type(): Type
    {
        return $this->inner->type();
    }

    public function withType(Type $type): Message
    {
        $this->inner->withType($type);
    }

    public function hasUserId(): bool
    {
        return $this->inner->hasUserId();
    }

    public function userId(): UserId
    {
        return $this->inner->userId();
    }

    public function withUserId(UserId $userId): Message
    {
        $this->inner->withUserId($userId);
    }

    public function hasAppId(): bool
    {
        return $this->inner->hasAppId();
    }

    public function appId(): AppId
    {
        return $this->inner->appId();
    }

    public function withAppId(AppId $appId): Message
    {
        $this->inner->withAppId($appId);
    }

    public function body(): Str
    {
        return $this->inner->body();
    }
}
