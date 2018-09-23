# Crawler Robot

| `master` | `develop` |
|----------|-----------|
| [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/CrawlerApp/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Innmind/Http/?branch=master) | [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/CrawlerApp/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/Http/?branch=develop) |
| [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/CrawlerApp/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Innmind/Http/?branch=master) | [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/CrawlerApp/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/Http/?branch=develop) |
| [![Build Status](https://scrutinizer-ci.com/g/Innmind/CrawlerApp/badges/build.png?b=master)](https://scrutinizer-ci.com/g/Innmind/Http/build-status/master) | [![Build Status](https://scrutinizer-ci.com/g/Innmind/CrawlerApp/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/Http/build-status/develop) |

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
