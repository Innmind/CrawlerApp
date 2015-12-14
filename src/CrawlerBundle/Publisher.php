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
        $accepts = [];

        foreach ($resources as $endpoint => $definition) {
            if (!$definition->hasMeta('accept')) {
                continue;
            }

            $accepts[$endpoint] = $definition->getMeta('accept');
        }

        $matches = [];
        $contentType = $resource->getContentType();

        foreach ($accepts as $endpoint => $accept) {
            if (!isset($accept['type'])) {
                continue;
            }

            $type = $accept['type'];
            $type = str_replace('*', '.*', $type);

            if ((bool) preg_match("#^$type$#", $contentType) === true) {
                $matches[] = [
                    'endpoint' => $endpoint,
                    'quality' => isset($accept['quality']) ? $accept['quality'] : 0,
                ];
            }
        }

        usort($matches, function($a, $b) {
            $a = $a['quality'];
            $b = $b['quality'];

            if ($a === $b) {
                return 0;
            }

            return $a > $b ? -1 : 1;
        });

        if (isset($matches[0])) {
            return $matches[0]['endpoint'];
        }

        throw new EndpointNotFoundException(sprintf(
            'No endpoint was found for the content type "%s" at "%s" for "%s"',
            $resource->getContentType(),
            $server,
            $resource->getUrl()
        ));
    }
}
