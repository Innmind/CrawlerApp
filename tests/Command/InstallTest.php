<?php
declare(strict_types = 1);

namespace Tests\Crawler\Command;

use Crawler\Command\Install;
use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\InstallationMonitor\{
    Client,
    Event,
};
use Innmind\Stream\Writable;
use Innmind\Filesystem\Adapter;
use Innmind\Immutable\{
    Map,
    Str,
    Stream,
};
use PHPUnit\Framework\TestCase;

class InstallTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Command::class,
            new Install(
                $this->createMock(Client::class),
                $this->createMock(Adapter::class)
            )
        );
    }

    public function testUsage()
    {
        $usage = <<<USAGE
install

This will configure the config/.env file

It will do so by reading events recorded by the installation monitor
USAGE;

        $this->assertSame(
            $usage,
            (string) new Install(
                $this->createMock(Client::class),
                $this->createMock(Adapter::class)
            )
        );
    }

    public function testInvokation()
    {
        $install = new Install(
            $client = $this->createMock(Client::class),
            $config = $this->createMock(Adapter::class)
        );
        $client
            ->expects($this->once())
            ->method('events')
            ->willReturn(Stream::of(
                Event::class,
                new Event(
                    new Event\Name('library_installed'),
                    Map::of('string', 'variable')
                        ('apiKey', 'somethings3cret')
                ),
                new Event(
                    new Event\Name('amqp.user_added'),
                    Map::of('string', 'variable')
                        ('name', 'monitor')
                        ('password', 'foo')
                ),
                new Event(
                    new Event\Name('amqp.user_added'),
                    Map::of('string', 'variable')
                        ('name', 'consumer')
                        ('password', 'bar')
                )
            ));
        $config
            ->expects($this->once())
            ->method('has')
            ->willReturn(false);
        $config
            ->expects($this->once())
            ->method('add')
            ->with($this->callback(static function($file): bool {
                return (string) $file->name() === '.env' &&
                    (string) $file->content() === "API_KEY=somethings3cret\nAMQP_SERVER=amqp://consumer:bar@localhost:5672/";
            }));

        $this->assertNull($install(
            $this->createMock(Environment::class),
            new Arguments,
            new Options
        ));
    }

    public function testFailWhenFileAlreadyExist()
    {
        $install = new Install(
            $client = $this->createMock(Client::class),
            $config = $this->createMock(Adapter::class)
        );
        $client
            ->expects($this->never())
            ->method('events');
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('error')
            ->willReturn($error = $this->createMock(Writable::class));
        $error
            ->expects($this->once())
            ->method('write')
            ->with(Str::of("App already installed\n"));
        $env
            ->expects($this->once())
            ->method('exit')
            ->with(1);
        $config
            ->expects($this->once())
            ->method('has')
            ->with('.env')
            ->willReturn(true);
        $config
            ->expects($this->never())
            ->method('add');

        $this->assertNull($install(
            $env,
            new Arguments,
            new Options
        ));
    }

    public function testFailWhenNoLibraryEvent()
    {
        $install = new Install(
            $client = $this->createMock(Client::class),
            $config = $this->createMock(Adapter::class)
        );
        $client
            ->expects($this->once())
            ->method('events')
            ->willReturn(Stream::of(
                Event::class,
                new Event(
                    new Event\Name('amqp.user_added'),
                    Map::of('string', 'variable')
                        ('name', 'monitor')
                        ('password', 'foo')
                ),
                new Event(
                    new Event\Name('amqp.user_added'),
                    Map::of('string', 'variable')
                        ('name', 'consumer')
                        ('password', 'bar')
                )
            ));
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('error')
            ->willReturn($error = $this->createMock(Writable::class));
        $error
            ->expects($this->once())
            ->method('write')
            ->with(Str::of("Configuration can't be determined\n"));
        $env
            ->expects($this->once())
            ->method('exit')
            ->with(1);
        $config
            ->expects($this->once())
            ->method('has')
            ->with('.env')
            ->willReturn(false);
        $config
            ->expects($this->never())
            ->method('add');

        $this->assertNull($install(
            $env,
            new Arguments,
            new Options
        ));
    }

    public function testFailWhenNoAMQPEvent()
    {
        $install = new Install(
            $client = $this->createMock(Client::class),
            $config = $this->createMock(Adapter::class)
        );
        $client
            ->expects($this->once())
            ->method('events')
            ->willReturn(Stream::of(
                Event::class,
                new Event(
                    new Event\Name('library_installed'),
                    Map::of('string', 'variable')
                        ('apiKey', 'somethings3cret')
                )
            ));
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('error')
            ->willReturn($error = $this->createMock(Writable::class));
        $error
            ->expects($this->once())
            ->method('write')
            ->with(Str::of("Configuration can't be determined\n"));
        $env
            ->expects($this->once())
            ->method('exit')
            ->with(1);
        $config
            ->expects($this->once())
            ->method('has')
            ->with('.env')
            ->willReturn(false);
        $config
            ->expects($this->never())
            ->method('add');

        $this->assertNull($install(
            $env,
            new Arguments,
            new Options
        ));
    }
}
