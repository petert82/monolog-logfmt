# monolog-logfmt

![CI](https://github.com/petert82/monolog-logfmt/workflows/CI/badge.svg?event=push)

A [logfmt](https://brandur.org/logfmt) formatter for [Monolog](https://github.com/Seldaek/monolog).

## Installation

The formatter can be installed using Composer:

```sh
$ composer require petert82/monolog-logfmt
```

## Requirements

- PHP 7.4.0 or later.

## Usage

Simply set an instance of `Petert82\Monolog\Formatter\LogfmtHandler` as the formatter for the Handler that you wish to output logfmt formatted logs.

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Petert82\Monolog\Formatter\LogfmtFormatter;

$log = new Logger('name');
$handler = new StreamHandler('php://stdout', Logger::WARNING);
$handler->setFormatter(new LogfmtFormatter());
$log->pushHandler($handler);

$log->addError('Danger! High voltage!', ['voltage' => 9000]);
```
Running this example would output something like:

```
ts=2017-11-21T20:02:10+00:00 lvl=ERROR chan=name msg="Danger! High voltage!" voltage=9000
```

### Customisation

By default these keys will be used for the default log fields in the logfmt output:

Key  | Content
-----|--------
`ts`   | Timestamp.
`lvl`  | Log level name.
`chan` | Channel name.
`msg`  | Log message.

All of these keys, and the format used for formatting logged `DateTimes`, can be customised by passing the appropriate parameters to the formatter's constructor. For example:

```php
$tsKey = 'date';
$levelKey = 'level';
$channelKey = 'channel';
$msgKey = 'message';
$dateFormat = 'Ymd-His';
$formatter = new LogfmtFormatter($tsKey, $levelKey, $channelKey, $msgKey, $dateFormat);
```

Logs formatted using this formatter would look like this:

```
date=20171119-190000 level=INFO channel=app message=Message
```

The standard keys can also be excluded from the output by passing an empty string (or `null`) to the appropriate constructor param. For example, to include only the message:

```php
$formatter = new LogfmtFormatter('', '', '', 'msg');
```

The formatter's output would now look like this:

```
msg=Message
```

## Development

A `Makefile` is provided to test the library locally, the only requirement for this is that Docker be installed your
development machine. Simply run `make` in the project root to execute the test suite.

## License

Licensed under the MIT License - see the `LICENSE` file for details