# TdClient

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-travis]][link-travis]
[![Code Quality][ico-quality]][link-scrutinizer]
[![Code Coverage][ico-coverage]][link-scrutinizer]
[![Mutation testing badge][ico-mutation]][link-mutator]
[![Total Downloads][ico-downloads]][link-downloads]

[![Email][ico-email]][link-email]

The PHP TD Gram TdLib Client. Manages communication between TdLib and PHP. Accepts [phptdgram/adapter][link-adapter] based adapters. Works with [phptdgram/schema][link-schema] objects. 


## Install

Via Composer

```bash
$ composer require phptdgram/td-client
```

## Basic Usage

```php
<?php

use PHPTdGram\Schema\FormattedText;
use PHPTdGram\Schema\InputMessageText;
use PHPTdGram\Schema\SendMessage;
use PHPTdGram\Schema\SendMessageOptions;
use PHPTdGram\Schema\TdObject;
use PHPTdGram\TdClient\TdClient;

$adapter = new FFIAdapter();

$tdClient = new TdClient($adapter);
$tdClient->verifyVersion(); // Make sure that libtdjson version and our Schema version matches

while (true) {
    /** @var TdObject $packet */
    $packet = $tdClient->receive(10);

    // ... Your logic

    $sendMessagePacket = new SendMessage(
        312321312,
        0,
        new SendMessageOptions(
            // ...
        ),
        null,
        new InputMessageText(
            new FormattedText(
                'Hello world',
                []
            ),
            false,
            true,
        )
    );
    
    $tdClient->send($sendMessagePacket);
}
```

## Reference

```php
<?php

declare(strict_types=1);

namespace PHPTdGram\TdClient;

class TdClient
{
    public function __construct(AdapterInterface $adapter, LoggerInterface $logger = null);

    /**
     * @throws AdapterException
     * @throws JsonException
     * @throws TdClientException
     */
    public function verifyVersion(): void;

    /**
     * @param float $timeout        the maximum number of seconds allowed for this function to wait for new data
     * @param bool  $processBacklog should process backlog packets
     *
     * @throws AdapterException
     * @throws ErrorReceivedException
     * @throws JsonException
     */
    public function receive(float $timeout, bool $processBacklog = true): ?TdObject;

    /**
     * @param int $level New value of the verbosity level for logging. Value 0 corresponds to fatal errors, value 1
     *                   corresponds to errors, value 2 corresponds to warnings and debug warnings, value 3 corresponds
     *                   to informational, value 4 corresponds to debug, value 5 corresponds to verbose debug, value
     *                   greater than 5 and up to 1023 can be used to enable even more logging.
     *
     * @return $this
     *
     * @throws AdapterException
     * @throws JsonException
     */
    public function setLogVerbosityLevel(int $level): self;

    /**
     * @param string $file           path to the file to where the internal TDLib log will be written
     * @param int    $maxLogFileSize the maximum size of the file to where the internal TDLib log is written before the
     *                               file will be auto-rotated
     *
     * @return $this
     *
     * @throws AdapterException
     * @throws JsonException
     */
    public function setLogToFile(string $file, int $maxLogFileSize = PHP_INT_MAX): self;

    /**
     * @return $this
     *
     * @throws AdapterException
     * @throws JsonException
     */
    public function setLogToStderr(): self;

    /**
     * @return $this
     *
     * @throws AdapterException
     * @throws JsonException
     */
    public function setLogToNone(): self;
    }

    /**
     * Sends packet to TdLib marked with extra identifier and loops till received marked response back or timeout
     * occurs. Stores all in between packets in backlog
     *
     * @param TdFunction $packet         request packet to send to TdLib
     * @param int        $timeout        the maximum number of seconds allowed for this function to wait for a response
     *                                   packet
     * @param float      $receiveTimeout the maximum number of seconds allowed for this function to wait for new data
     *
     * @throws AdapterException
     * @throws ErrorReceivedException
     * @throws JsonException
     * @throws QueryTimeoutException
     */
    public function query(TdFunction $packet, int $timeout = 10, float $receiveTimeout = 0.1): TdObject;

    /**
     * Sends packet to TdLib
     *
     * @param TdFunction $packet request packet to send to TdLib
     *
     * @throws AdapterException
     * @throws JsonException
     */
    public function send(TdFunction $packet): void;
}
```

## Testing

Run test cases

```bash
$ composer test
```

Run test cases with coverage (HTML format)

```bash
$ composer test-coverage
```

Run PHP style checker

```bash
$ composer cs-check
```

Run PHP style fixer

```bash
$ composer cs-fix
```

Run all continuous integration tests

```bash
$ composer ci-run
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.


## License

Please see [License File](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/phptdgram/td-client.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/com/phptdgram/td-client/master.svg?style=flat-square
[ico-quality]: https://img.shields.io/scrutinizer/quality/g/phptdgram/td-client?style=flat-square
[ico-coverage]: https://img.shields.io/scrutinizer/coverage/g/phptdgram/td-client?style=flat-square
[ico-mutation]: https://img.shields.io/endpoint?style=flat-square&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fphptdgram%2Ftd-client%2Fmaster
[ico-downloads]: https://img.shields.io/packagist/dt/phptdgram/td-client.svg?style=flat-square
[ico-email]: https://img.shields.io/badge/email-aurimas@niekis.lt-blue.svg?style=flat-square

[link-travis]: https://travis-ci.com/phptdgram/td-client
[link-packagist]: https://packagist.org/packages/phptdgram/td-client
[link-scrutinizer]: https://scrutinizer-ci.com/g/phptdgram/td-client
[link-mutator]: https://dashboard.stryker-mutator.io/reports/github.com/phptdgram/td-client/master
[link-downloads]: https://packagist.org/packages/phptdgram/td-client/stats
[link-adapter]: https://github.com/phptdgram/adapter
[link-schema]: https://github.com/phptdgram/schema
[link-email]: mailto:aurimas@niekis.lt
