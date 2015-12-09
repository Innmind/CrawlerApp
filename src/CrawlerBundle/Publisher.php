<?php

namespace CrawlerBundle;

use CrawlerBundle\Exception\EndpointNotFoundException;
use Innmind\RestBundle\Client;
use Innmind\Crawler\CrawlerInterface;
use Innmind\Crawler\Request;
use Innmind\Crawler\HttpResource;
use Symfony\Component\Stopwatch\Stopwatch;
use Psr\Log\LoggerInterface;

class Publisher
{
    protected $client;
    protected $crawler;
    protected $logger;
    protected $resourceFactory;

    public function __construct(
        Client $client,
        CrawlerInterface $crawler,
        LoggerInterface $logger,
        ResourceFactory $resourceFactory
    ) {
        $this->client = $client;
        $this->crawler = $crawler;
        $this->logger = $logger;
        $this->resourceFactory = $resourceFactory;
    }

    /**
     * Crawl the given url and publish the new resource on the given server
     *
     * @param string $toCrawl
     * @param string $server
     * @param string $uuid
     *
     * @return Innmind\Rest\Client\HttpResourceInterface
     */
    public function publish($toCrawl, $server, $uuid = null)
    {
        $resource = $this->crawler->crawl(new Request($toCrawl));
        $stopwatch = $this->crawler->getStopwatch($resource);
        $this->crawler->release($resource);

        $this->logger->info('Page crawled', [
            'url' => $resource->getUrl(),
            'stopwatch' => $this->computeSections($stopwatch),
        ]);

        $endpoint = $this->findBest($server, $resource);
        $server = $this->client->getServer($server);
        $definition = $server->getResources()[$endpoint];
        $resource = $this->resourceFactory->make($definition, $resource);

        if ($uuid === null) {
            $server->create($endpoint, $resource);
        } else {
            $server->update($endpoint, $uuid, $resource);
        }

        return $resource;
    }

    /**
     * Transform stopwatch into an array
     *
     * @param Stopwatch $stopwatch
     *
     * @return array
     */
    protected function computeSections(Stopwatch $stopwatch)
    {
        $array = [];

        foreach ($stopwatch->getSections() as $sectionName => $section) {
            foreach ($section->getEvents() as $eventName => $event) {
                $array[$sectionName . '.' . $eventName] = [
                    'duration' => $event->getDuration(),
                    'memory' => $event->getMemory(),
                ];
            }
        }

        return $array;
    }

    /**
     * Find the appropriate endpoint where to send the crawled resource
     *
     * @param string $server
     * @param HttpResource $resource
     *
     * @return string
     */
    protected function findBest($server, HttpResource $resource)
    {
        $resources = $this->client->getServer($server)->getResources();

        foreach ($resources as $endpoint => $definition) {
            if (!$definition->hasMeta('content-types')) {
                continue;
            }

            $types = $definition->getMeta('content-types');

            foreach ($types as $type) {
                if (strpos($resource->getContentType(), $type) !== false) {
                    return $endpoint;
                }
            }
        }

        throw new EndpointNotFoundException(sprintf(
            'No endpoint was found for the content type "%s" at "%s" for "%s"',
            $resource->getContentType(),
            $server,
            $resource->getUrl()
        ));
    }
}
