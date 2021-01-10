<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Petert82\Monolog\Formatter\LogfmtFormatter;

/**
 * @covers \Petert82\Monolog\Formatter\LogfmtFormatter
 */
class LogfmtFormatterTest extends TestCase
{
    public function testItDoesntQuotePlainMessages(): void
    {
        $formatter = new LogfmtFormatter();
        $record = $this->getRecord('Hi');
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=Hi'."\n";
        $this->assertEquals($expected, $formatter->format($record));

        $record = $this->getRecord('åéü');
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=åéü'."\n";
        $this->assertEquals($expected, $formatter->format($record));
    }

    public function testItQuotesMessages(): void
    {
        $formatter = new LogfmtFormatter();
        $record = $this->getRecord('Hi there');
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg="Hi there"'."\n";
        $this->assertEquals($expected, $formatter->format($record));

        $record = $this->getRecord('1=2');
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg="1=2"'."\n";
        $this->assertEquals($expected, $formatter->format($record));

        $record = $this->getRecord('"speech_marks"');
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg="\"speech_marks\""'."\n";
        $this->assertEquals($expected, $formatter->format($record));

        $record = $this->getRecord('');
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=""'."\n";
        $this->assertEquals($expected, $formatter->format($record));

        $record = $this->getRecord("
Hi
");
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg="\nHi\n"'."\n";
        $this->assertEquals($expected, $formatter->format($record));
    }

    public function testItFormatsScalarMessages(): void
    {
        $formatter = new LogfmtFormatter();
        $record = $this->getRecord(1);
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=1'."\n";
        $this->assertEquals($expected, $formatter->format($record));

        $record = $this->getRecord(1.1);
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=1.1'."\n";
        $this->assertEquals($expected, $formatter->format($record));

        $record = $this->getRecord(true);
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=true'."\n";
        $this->assertEquals($expected, $formatter->format($record));

        $record = $this->getRecord(false);
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=false'."\n";
        $this->assertEquals($expected, $formatter->format($record));

        $record = $this->getRecord(null);
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=NULL'."\n";
        $this->assertEquals($expected, $formatter->format($record));
    }

    public function testItIncludesContext(): void
    {
        $formatter = new LogfmtFormatter();
        $record = $this->getRecord('Message');
        $record['context']['foo'] = 'bar';
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=Message foo=bar'."\n";
        $this->assertEquals($expected, $formatter->format($record));

        $record = $this->getRecord('Message');
        $record['context']['baz'] = 'something with spaces';
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=Message baz="something with spaces"'."\n";
        $this->assertEquals($expected, $formatter->format($record));
    }

    public function testItIncludesExtra(): void
    {
        $formatter = new LogfmtFormatter();
        $record = $this->getRecord('Message');
        $record['extra']['foo'] = 'bar';
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=Message foo=bar'."\n";
        $this->assertEquals($expected, $formatter->format($record));

        $record = $this->getRecord('Message');
        $record['extra']['baz'] = 'something with spaces';
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=Message baz="something with spaces"'."\n";
        $this->assertEquals($expected, $formatter->format($record));
    }

    public function testContextOverridesExtra(): void
    {
        $formatter = new LogfmtFormatter();
        $record = $this->getRecord('Message');
        $record['context']['foo'] = 'context val';
        $record['extra']['foo'] = 'extra val';
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=Message foo="context val"'."\n";
        $this->assertEquals($expected, $formatter->format($record));
    }

    public function testItIgnoresInvalidKeys(): void
    {
        $formatter = new LogfmtFormatter();
        $record = $this->getRecord('Message');
        $record['context'] = [
            "you ain't seen me" => "right",
            'cool%story' => 'bro',
        ];
        $record['extra'] = [
            "this=wrong" => 1,
            '%^asdf' => true,
            "
what?" => false,
            '' => 'ignore this',
        ];
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=Message cool%story=bro %^asdf=true'."\n";
        $this->assertEquals($expected, $formatter->format($record));
    }

    public function testItFormatsDateTimes(): void
    {
        $formatter = new LogfmtFormatter();
        $record = $this->getRecord(new DateTime('2017-11-19T20:00:00', new DateTimeZone('Europe/Vienna')));
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=2017-11-19T20:00:00+01:00'."\n";
        $this->assertEquals($expected, $formatter->format($record));

        $record = $this->getRecord('Message');
        $record['context']['a_date'] = new DateTime('2017-11-19T20:00:00', new DateTimeZone('Europe/Vienna'));
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=Message a_date=2017-11-19T20:00:00+01:00'."\n";
        $this->assertEquals($expected, $formatter->format($record));

        $record = $this->getRecord('Message');
        $record['extra']['a_date'] = new DateTime('2017-11-19T20:00:00', new DateTimeZone('Europe/Vienna'));
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=Message a_date=2017-11-19T20:00:00+01:00'."\n";
        $this->assertEquals($expected, $formatter->format($record));
    }

    public function testItAllowsDateTimeFormatToBeOverridden(): void
    {
        $formatter = new LogfmtFormatter(
            'ts',
            'lvl',
            'chan',
            'msg',
            'YmdHis'
        );

        $record = $this->getRecord(new DateTime('2017-11-19T20:00:00', new DateTimeZone('Europe/Vienna')));
        $expected = 'ts=20171119190000 lvl=INFO chan=app msg=20171119200000'."\n";
        $this->assertEquals($expected, $formatter->format($record));
    }

    public function testItFormatsNestedContextOrExtra(): void
    {
        $formatter = new LogfmtFormatter();
        $record = $this->getRecord('Message');
        $record['context'] = [
            'outer' => [
                'inner' => ['one', 'two'],
            ],
        ];
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=Message outer={"inner":["one","two"]}'."\n";
        $this->assertEquals($expected, $formatter->format($record));

        $record = $this->getRecord('Message');
        $record['extra'] = [
            'outer' => [
                'inner' => ['one' => 1, 'two'],
            ],
        ];
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=Message outer={"inner":{"one":1,"0":"two"}}'."\n";
        $this->assertEquals($expected, $formatter->format($record));
    }

    public function testKeysCanBeCustomised(): void
    {
        $formatter = new LogfmtFormatter('date', 'level', 'channel', 'message');
        $record = $this->getRecord('Message');
        $expected = 'date=2017-11-19T19:00:00+00:00 level=INFO channel=app message=Message'."\n";
        $this->assertEquals($expected, $formatter->format($record));
    }

    public function testKeysCanBeExcluded(): void
    {
        $formatter = new LogfmtFormatter('', null, null, 'msg');
        $record = $this->getRecord('Message');
        $expected = 'msg=Message'."\n";
        $this->assertEquals($expected, $formatter->format($record));

        $formatter = new LogfmtFormatter(null, 'lvl', 'chan', '');
        $record = $this->getRecord('Message');
        $expected = 'lvl=INFO chan=app'."\n";
        $this->assertEquals($expected, $formatter->format($record));

        // Invalid keys should also be excluded
        $formatter = new LogfmtFormatter('time stamp', 'le"v"el', ' ', 'mess=age');
        $record = $this->getRecord('Message');
        $record['context']['foo'] = 'bar';
        $expected = 'foo=bar'."\n";
        $this->assertEquals($expected, $formatter->format($record));
    }

    public function testMainKeysCantBeOverwritten(): void
    {
        $formatter = new LogfmtFormatter();
        $record = $this->getRecord('Message');
        $record['context']['ts'] = 'This should not be output';
        $record['context']['lvl'] = 'And neither should this';
        $record['extra']['chan'] = 'Or this...';
        $record['extra']['msg'] = '...or this';
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=Message'."\n";
        $this->assertEquals($expected, $formatter->format($record));

        $formatter = new LogfmtFormatter('date', 'level', 'channel', 'message');
        $record = $this->getRecord('Message');
        $record['context']['date'] = 'This should not be output';
        $record['context']['level'] = 'And neither should this';
        $record['extra']['channel'] = 'Or this...';
        $record['extra']['message'] = '...or this';
        $expected = 'date=2017-11-19T19:00:00+00:00 level=INFO channel=app message=Message'."\n";
        $this->assertEquals($expected, $formatter->format($record));
    }

    public function testItFormatsBatches(): void
    {
        $formatter = new LogfmtFormatter();
        $batch = [
            $this->getRecord('Message 1'),
            $this->getRecord('Message 2'),
        ];
        $expected = <<<EOS
ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg="Message 1"
ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg="Message 2"\n
EOS;
        $this->assertEquals($expected, $formatter->formatBatch($batch));
    }

    protected function getRecord($message = 'A log message'): array
    {
        return [
            'message' => $message,
            'level' => 200,
            'level_name' => 'INFO',
            'context' => [],
            'channel' => 'app',
            'datetime' => new DateTime('2017-11-19T19:00:00', new DateTimeZone('UTC')),
            'extra' => [],
        ];
    }
}
