<?php
declare(strict_types = 1);

namespace Crawler\AMQP\Message;

use Crawler\{
    Reference,
    Exception\LogicException
};
use Innmind\Url\Url;
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
    Message\UserId
};
use Innmind\TimeContinuum\{
    PointInTime,
    ElapsedPeriod
};
use Innmind\Json\Json;
use Innmind\Immutable\{
    Map,
    Str
};

final class Canonical implements Message
{
    private Message $inner;
    private Url $resource;
    private Reference $reference;

    public function __construct(
        Url $resource,
        Reference $reference
    ) {
        $this->resource = $resource;
        $this->reference = $reference;

        $payload = [
            'resource' => $resource->toString(),
            'origin' => $reference->identity()->toString(),
            'relationship' => 'canonical',
            'definition' => $reference->definition(),
            'server' => $reference->server()->toString(),
        ];

        $this->inner = (new Generic(Str::of(Json::encode($payload))))
            ->withContentType(new ContentType('application', 'json'))
            ->withAppId(new AppId('crawler'))
            ->withDeliveryMode(DeliveryMode::persistent());
    }

    public function resource(): Url
    {
        return $this->resource;
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
        throw new LogicException;
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
        throw new LogicException;
    }

    public function hasHeaders(): bool
    {
        return $this->inner->hasHeaders();
    }

    /**
     * @return Map<string, mixed>
     */
    public function headers(): Map
    {
        return $this->inner->headers();
    }

    public function withHeaders(Map $headers): Message
    {
        throw new LogicException;
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
        throw new LogicException;
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
        throw new LogicException;
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
        throw new LogicException;
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
        throw new LogicException;
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
        throw new LogicException;
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
        throw new LogicException;
    }

    public function hasTimestamp(): bool
    {
        return $this->inner->hasTimestamp();
    }

    public function timestamp(): PointInTime
    {
        return $this->inner->timestamp();
    }

    public function withTimestamp(PointInTime $timestamp): Message
    {
        $self = clone $this;
        $self->inner = $this->inner->withTimestamp($timestamp);

        return $self;
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
        throw new LogicException;
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
        throw new LogicException;
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
        throw new LogicException;
    }

    public function body(): Str
    {
        return $this->inner->body();
    }
}
