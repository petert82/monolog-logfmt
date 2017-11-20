<?php
use PHPUnit\Framework\TestCase;
use Petert82\Monolog\Formatter\LogfmtFormatter;

class LogfmtFormatterTest extends TestCase
{
    public function testItDoesntQuotePlainMessages()
    {
        $formatter = new LogfmtFormatter();
        $record = $this->getRecord('Hi');
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=Hi'."\n";
        $this->assertEquals($expected, $formatter->format($record));

        $record = $this->getRecord('åéü');
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=åéü'."\n";
        $this->assertEquals($expected, $formatter->format($record));
    }
    
    public function testItQuotesMessages()
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

        $record = $this->getRecord("
Hi
");
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg="\nHi\n"'."\n";
        $this->assertEquals($expected, $formatter->format($record));
    }

    public function testItFormatsScalarMessages()
    {
        $formatter = new LogfmtFormatter();
        $record = $this->getRecord(1);
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=1'."\n";
        $this->assertEquals($expected, $formatter->format($record));

        $formatter = new LogfmtFormatter();
        $record = $this->getRecord(1.1);
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=1.1'."\n";
        $this->assertEquals($expected, $formatter->format($record));

        $formatter = new LogfmtFormatter();
        $record = $this->getRecord(true);
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=true'."\n";
        $this->assertEquals($expected, $formatter->format($record));

        $formatter = new LogfmtFormatter();
        $record = $this->getRecord(false);
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=false'."\n";
        $this->assertEquals($expected, $formatter->format($record));

        $formatter = new LogfmtFormatter();
        $record = $this->getRecord(null);
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=NULL'."\n";
        $this->assertEquals($expected, $formatter->format($record));
    }

    public function testItIncludesContext()
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

    public function testItIncludesExtra()
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

    public function testContextOverridesExtra()
    {
        $formatter = new LogfmtFormatter();

        $record = $this->getRecord('Message');
        $record['context']['foo'] = 'context val';
        $record['extra']['foo'] = 'extra val';
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=Message foo="context val"'."\n";
        $this->assertEquals($expected, $formatter->format($record));
    }

    public function testItIgnoresInvalidKeys()
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
what?" => false
        ];
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=Message cool%story=bro %^asdf=true'."\n";
        $this->assertEquals($expected, $formatter->format($record));
    }

    public function testItFormatsDateTimes()
    {
        $formatter = new LogfmtFormatter();
        $record = $this->getRecord(new \DateTime('2017-11-19T20:00:00', new \DateTimeZone('Europe/Vienna')));
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=2017-11-19T20:00:00+01:00'."\n";
        $this->assertEquals($expected, $formatter->format($record));

        $record = $this->getRecord('Message');
        $record['context']['a_date'] = new \DateTime('2017-11-19T20:00:00', new \DateTimeZone('Europe/Vienna'));
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=Message a_date=2017-11-19T20:00:00+01:00'."\n";
        $this->assertEquals($expected, $formatter->format($record));

        $record = $this->getRecord('Message');
        $record['extra']['a_date'] = new \DateTime('2017-11-19T20:00:00', new \DateTimeZone('Europe/Vienna'));
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=Message a_date=2017-11-19T20:00:00+01:00'."\n";
        $this->assertEquals($expected, $formatter->format($record));
    }

    public function testItFormatsNestedContextOrExtra()
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

    protected function getRecord($message = 'A log message')
    {
        return [
            'message' => $message,
            'level' => 200,
            'level_name' => 'INFO',
            'context' => [],
            'channel' => 'app',
            'datetime' => new \DateTime('2017-11-19T19:00:00', new \DateTimeZone('UTC')),
            'extra' => [],
        ];
    }
}