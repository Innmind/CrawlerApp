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
use Innmind\Url\Path;
use Innmind\Immutable\{
    Map,
    Str,
    Stream,
};
use PHPUnit\Framework\TestCase;

class InstallTest extends TestCase
{
    public function setUp(): void
    {
        @mkdir('/tmp/config');
    }

    public function tearDown(): void
    {
        @unlink('/tmp/config/.env');
        @rmdir('/tmp/config');
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Command::class,
            new Install(
                $this->createMock(Client::class)
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
            (string) new Install($this->createMock(Client::class))
        );
    }

    public function testInvokation()
    {
        $install = new Install(
            $client = $this->createMock(Client::class)
        );
        $client
            ->expects($this->once())
            ->method('events')
            ->willReturn(Stream::of(
                Event::class,
                new Event(
                    new Event\Name('library_installed'),
                    (new Map('string', 'variable'))
                        ->put('apiKey', 'somethings3cret')
                ),
                new Event(
                    new Event\Name('amqp.user_added'),
                    (new Map('string', 'variable'))
                        ->put('name', 'monitor')
                        ->put('password', 'foo')
                ),
                new Event(
                    new Event\Name('amqp.user_added'),
                    (new Map('string', 'variable'))
                        ->put('name', 'consumer')
                        ->put('password', 'bar')
                )
            ));
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('workingDirectory')
            ->willReturn(new Path('/tmp'));

        $this->assertNull($install(
            $env,
            new Arguments,
            new Options
        ));
        $this->assertSame(
            "API_KEY=somethings3cret\nAMQP_SERVER=amqp://consumer:bar@localhost:5672/",
            file_get_contents('/tmp/config/.env')
        );
    }

    public function testFailWhenFileAlreadyExist()
    {
        file_put_contents('/tmp/config/.env', 'clean');

        $install = new Install(
            $client = $this->createMock(Client::class)
        );
        $client
            ->expects($this->never())
            ->method('events');
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('workingDirectory')
            ->willReturn(new Path('/tmp'));
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

        $this->assertNull($install(
            $env,
            new Arguments,
            new Options
        ));
        $this->assertSame(
            'clean',
            file_get_contents('/tmp/config/.env')
        );
    }

    public function testFailWhenNoLibraryEvent()
    {
        $install = new Install(
            $client = $this->createMock(Client::class)
        );
        $client
            ->expects($this->once())
            ->method('events')
            ->willReturn(Stream::of(
                Event::class,
                new Event(
                    new Event\Name('amqp.user_added'),
                    (new Map('string', 'variable'))
                        ->put('name', 'monitor')
                        ->put('password', 'foo')
                ),
                new Event(
                    new Event\Name('amqp.user_added'),
                    (new Map('string', 'variable'))
                        ->put('name', 'consumer')
                        ->put('password', 'bar')
                )
            ));
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('workingDirectory')
            ->willReturn(new Path('/tmp'));
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

        $this->assertNull($install(
            $env,
            new Arguments,
            new Options
        ));
        $this->assertFalse(file_exists('/tmp/config/.env'));
    }

    public function testFailWhenNoAMQPEvent()
    {
        $install = new Install(
            $client = $this->createMock(Client::class)
        );
        $client
            ->expects($this->once())
            ->method('events')
            ->willReturn(Stream::of(
                Event::class,
                new Event(
                    new Event\Name('library_installed'),
                    (new Map('string', 'variable'))
                        ->put('apiKey', 'somethings3cret')
                )
            ));
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('workingDirectory')
            ->willReturn(new Path('/tmp'));
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

        $this->assertNull($install(
            $env,
            new Arguments,
            new Options
        ));
        $this->assertFalse(file_exists('/tmp/config/.env'));
    }
}
