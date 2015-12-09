<?php

namespace CrawlerBundle\Event;

use Innmind\Crawler\HttpResource;
use Innmind\Rest\Client\Definition\ResourceDefinition;
use Innmind\Rest\Client\Definition\Property;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event fired when a resource property can't be built by the resource factory
 */
class ResourcePropertyBuildEvent extends Event
{
    protected $definition;
    protected $property;
    protected $resource;
    protected $value;
    protected $hasValue = false;

    public function __construct(
        ResourceDefinition $definition,
        Property $property,
        HttpResource $resource
    ) {
        $this->definition = $definition;
        $this->property = $property;
        $this->resource = $resource;
    }

    /**
     * Return the resource definition for the rest resource we want to build
     *
     * @return ResourceDefinition
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * Return the property we want to build
     *
     * @return Property
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * Return the crawled resource
     *
     * @return HttpResource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Set a value to be used for this property
     *
     * @param mixed $value
     *
     * @return void
     */
    public function setValue($value)
    {
        $this->value = $value;
        $this->hasValue = true;
        $this->stopPropagation();
    }

    /**
     * Check if a value has been set
     *
     * @return bool
     */
    public function hasValue()
    {
        return $this->hasValue;
    }

    /**
     * Return the value that has been generated for this property
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
