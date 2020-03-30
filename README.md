# Crawler Robot

[![Build Status](https://github.com/Innmind/CrawlerApp/workflows/CI/badge.svg)](https://github.com/Innmind/CrawlerApp/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/Innmind/CrawlerApp/branch/develop/graph/badge.svg)](https://codecov.io/gh/Innmind/CrawlerApp)
[![Type Coverage](https://shepherd.dev/github/Innmind/CrawlerApp/coverage.svg)](https://shepherd.dev/github/Innmind/CrawlerApp)

This is an app to crawl internet and publish resource attributes to a [Library](https://github.com/Innmind/Library).

## Installation

```sh
composer install
docker-compose up -d
```

Copy `config/.env.dist` to `config/.env` and adapt the url of the amqp server to your need.

## Usage

```sh
bin/crawler consume crawler
```

This will launch a consumer to read the urls to crawl

```sh
bin/console crawl http://the.url/to/crawl https://innmind_library.host/
```

This will crawl `http://the.url/to/crawl`, extract the resource attributes and publish them to the library `https://innmind_library.host/`. It will automatically detect the api resource to publish to.
