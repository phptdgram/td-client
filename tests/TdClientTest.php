<?php

declare(strict_types=1);

namespace PHPTdGram\TdClient\Tests;

use PHPTdGram\Adapter\AdapterInterface;
use PHPTdGram\Schema\Error;
use PHPTdGram\Schema\GetOption;
use PHPTdGram\Schema\LogStreamDefault;
use PHPTdGram\Schema\LogStreamEmpty;
use PHPTdGram\Schema\LogStreamFile;
use PHPTdGram\Schema\Ok;
use PHPTdGram\Schema\OptionValue;
use PHPTdGram\Schema\OptionValueString;
use PHPTdGram\Schema\SetLogStream;
use PHPTdGram\Schema\SetLogVerbosityLevel;
use PHPTdGram\Schema\UpdateOption;
use PHPTdGram\TdClient\Exception\ErrorReceivedException;
use PHPTdGram\TdClient\Exception\QueryTimeoutException;
use PHPTdGram\TdClient\Exception\TdClientException;
use PHPTdGram\TdClient\TdClient;
use PHPUnit\Framework\TestCase;

/**
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class TdClientTest extends TestCase
{
    public function testVerifyVersion(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->once())
            ->method('receive')
            ->with(10)
            ->willReturn(
                (new UpdateOption('version', new OptionValueString('1.6.0')))->typeSerialize()
            );

        $tdClient = new TdClient($adapter);
        $tdClient->verifyVersion();
    }

    public function testVerifyVersionWrongPacket(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->once())
            ->method('receive')
            ->with(10)
            ->willReturn(
                (new Ok())->typeSerialize()
            );

        $tdClient = new TdClient($adapter);

        $this->expectException(TdClientException::class);
        $this->expectExceptionMessage('First packet supposed to be "UpdateOption" received "ok"');

        $tdClient->verifyVersion();
    }

    public function testVerifyVersionWrongVersion(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->once())
            ->method('receive')
            ->with(10)
            ->willReturn(
                (new UpdateOption('version', new OptionValueString('1.5.0')))->typeSerialize()
            );

        $tdClient = new TdClient($adapter);

        $this->expectException(TdClientException::class);
        $this->expectExceptionMessage(
            'Client TdLib version "1.5.0" doesnt match Schema version "1.6.0"'
        );

        $tdClient->verifyVersion();
    }

    public function testReceive(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->at(0))
            ->method('receive')
            ->with(10)
            ->willReturn(
                (new UpdateOption('a', new OptionValue()))->typeSerialize()
            );

        $adapter->expects($this->at(1))
            ->method('receive')
            ->with(10)
            ->willReturn(null);

        $adapter->expects($this->at(2))
            ->method('receive')
            ->with(10)
            ->willReturn(
                (new Error(1, '2'))->typeSerialize()
            );

        $tdClient = new TdClient($adapter);

        /** @var UpdateOption $first */
        $first = $tdClient->receive(10);
        $this->assertInstanceOf(UpdateOption::class, $first);
        $this->assertEquals('a', $first->getName());
        $this->assertInstanceOf(OptionValue::class, $first->getValue());

        $this->assertNull($tdClient->receive(10));

        $this->expectException(ErrorReceivedException::class);
        $this->expectExceptionMessage('Received Error Packet 1: "2"');

        $tdClient->receive(10);
    }

    public function testSend(): void
    {
        $packet = new GetOption('foo');

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->at(0))
            ->method('send')
            ->with($packet);

        $tdClient = new TdClient($adapter);
        $tdClient->send($packet);
    }

    public function testQuery(): void
    {
        $tdExtra = '';
        $adapter = $this->createMock(AdapterInterface::class);

        $adapter->expects($this->once())
            ->method('send')
            ->willReturnCallback(
                function (GetOption $option) use (&$tdExtra): void {
                    $this->assertEquals('foo', $option->getName());

                    $tdExtra = $option->getTdExtra();
                }
            );

        $adapter->expects($this->at(1))
            ->method('receive')
            ->with(0.1)
            ->willReturn(
                (new UpdateOption('a', new OptionValue()))->typeSerialize()
            );

        $adapter->expects($this->at(2))
            ->method('receive')
            ->with(0.1)
            ->willReturn(null);

        $adapter->expects($this->at(3))
            ->method('receive')
            ->with(0.1)
            ->willReturnCallback(
                function () use (&$tdExtra) {
                    $expectedPacket = new Ok();
                    $expectedPacket->setTdExtra($tdExtra);

                    return $expectedPacket->jsonSerialize();
                }
            );

        $tdClient = new TdClient($adapter);
        $tdClient->query(new GetOption('foo'));

        $received = $tdClient->receive(1);
        $this->assertInstanceOf(UpdateOption::class, $received);
        $this->assertEquals('a', $received->getName());
    }

    public function testQueryTimeout(): void
    {
        $adapter  = $this->createMock(AdapterInterface::class);
        $tdClient = new TdClient($adapter);

        $this->expectException(QueryTimeoutException::class);
        $this->expectExceptionMessage('Query for "getOption" packet received timeout');

        $tdClient->query(new GetOption('foo'), 0);
    }

    public function testQueryTimeoutNonNull(): void
    {
        $adapter  = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->any())
            ->method('receive')
            ->with(0.1)
            ->willReturn(
                (new UpdateOption('a', new OptionValue()))->typeSerialize()
            );

        $tdClient = new TdClient($adapter);

        $this->expectException(QueryTimeoutException::class);
        $this->expectExceptionMessage('Query for "getOption" packet received timeout');

        $tdClient->query(new GetOption('foo'), 0);
    }

    public function testLogSettings(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->at(0))
            ->method('execute')
            ->with(
                $this->callback(
                    function (SetLogVerbosityLevel $packet) {
                        $this->assertEquals(1, $packet->getNewVerbosityLevel());

                        return true;
                    }
                )
            );

        $adapter->expects($this->at(1))
            ->method('execute')
            ->with(
                $this->callback(
                    function (SetLogStream $packet) {
                        $this->assertInstanceOf(LogStreamDefault::class, $packet->getLogStream());

                        return true;
                    }
                )
            );

        $adapter->expects($this->at(2))
            ->method('execute')
            ->with(
                $this->callback(
                    function (SetLogStream $packet) {
                        $this->assertInstanceOf(LogStreamEmpty::class, $packet->getLogStream());

                        return true;
                    }
                )
            );

        $adapter->expects($this->at(3))
            ->method('execute')
            ->with(
                $this->callback(
                    function (SetLogStream $packet) {
                        $this->assertInstanceOf(LogStreamFile::class, $packet->getLogStream());
                        $this->assertEquals('foo', $packet->getLogStream()->getPath());

                        return true;
                    }
                )
            );

        $tdClient = new TdClient($adapter);
        $tdClient->setLogVerbosityLevel(1);
        $tdClient->setLogToStderr();
        $tdClient->setLogToNone();
        $tdClient->setLogToFile('foo');
    }
}
