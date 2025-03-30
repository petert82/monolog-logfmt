<?php

declare(strict_types=1);

use Petert82\Monolog\Formatter\LogfmtFormatter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Petert82\Monolog\Formatter\LogfmtFormatter
 *
 * @internal
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

        $record = $this->getRecord('
Hi
');
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
            "you ain't seen me" => 'right',
            'cool%story' => 'bro',
        ];
        $record['extra'] = [
            'this=wrong' => 1,
            'no	tabs	pls' => 2,
            '%^asdf' => true,
            '
what?' => false,
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

    public function testItFlattensArraysInContext(): void
    {
        $formatter = new LogfmtFormatter(
            'ts',
            'lvl',
            'chan',
            'msg',
            DateTime::RFC3339,
            "\n",
            true // Enable flattening
        );

        $record = $this->getRecord('Message');
        $record['context'] = [
            'items' => ['apple', 'banana', 'cherry'],
        ];
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=Message items_0=apple items_1=banana items_2=cherry'."\n";
        $this->assertEquals($expected, $formatter->format($record));
    }

    public function testItFlattensAssociativeArraysInContext(): void
    {
        $formatter = new LogfmtFormatter(
            'ts',
            'lvl',
            'chan',
            'msg',
            DateTime::RFC3339,
            "\n",
            true // Enable flattening
        );

        $record = $this->getRecord('Message');
        $record['context'] = [
            'user' => [
                'id' => 123,
                'name' => 'John Doe',
                'roles' => ['admin', 'editor'],
            ],
        ];
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=Message user_id=123 user_name="John Doe" user_roles_0=admin user_roles_1=editor'."\n";
        $this->assertEquals($expected, $formatter->format($record));
    }

    public function testItFlattensNestedArraysInContext(): void
    {
        $formatter = new LogfmtFormatter(
            'ts',
            'lvl',
            'chan',
            'msg',
            DateTime::RFC3339,
            "\n",
            true // Enable flattening
        );

        $record = $this->getRecord('Message');
        $record['context'] = [
            'data' => [
                'items' => [
                    ['id' => 1, 'name' => 'Item 1'],
                    ['id' => 2, 'name' => 'Item 2'],
                ],
            ],
        ];
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=Message data_items_0_id=1 data_items_0_name="Item 1" data_items_1_id=2 data_items_1_name="Item 2"'."\n";
        $this->assertEquals($expected, $formatter->format($record));
    }

    public function testItFlattensObjectsInContext(): void
    {
        $formatter = new LogfmtFormatter(
            'ts',
            'lvl',
            'chan',
            'msg',
            DateTime::RFC3339,
            "\n",
            true // Enable flattening
        );

        $record = $this->getRecord('Message');

        $user = new stdClass();
        $user->id = 123;
        $user->name = 'John Doe';

        $record['context'] = [
            'user' => $user,
        ];
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=Message user_id=123 user_name="John Doe"'."\n";
        $this->assertEquals($expected, $formatter->format($record));
    }

    public function testItFlattensExceptionsInContext(): void
    {
        $formatter = new LogfmtFormatter(
            'ts',
            'lvl',
            'chan',
            'msg',
            DateTime::RFC3339,
            "\n",
            true // Enable flattening
        );

        $record = $this->getRecord('Message');

        $previous = new Exception('Previous exception', 100);
        $exception = new Exception('Main exception', 200, $previous);

        $record['context'] = [
            'exception' => $exception,
        ];

        // Use assertStringContainsString for the parts we can reliably predict
        $formatted = $formatter->format($record);
        $this->assertStringContainsString('ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=Message', $formatted);
        $this->assertStringContainsString('exception_message="Main exception"', $formatted);
        $this->assertStringContainsString('exception_code=200', $formatted);
        $this->assertStringContainsString('exception_previous_message="Previous exception"', $formatted);
        $this->assertStringContainsString('exception_previous_code=100', $formatted);
    }

    public function testCompareJsonAndFlattenedOutput(): void
    {
        $jsonFormatter = new LogfmtFormatter(); // Default: JSON format
        $flattenFormatter = new LogfmtFormatter(
            'ts',
            'lvl',
            'chan',
            'msg',
            DateTime::RFC3339,
            "\n",
            true // Enable flattening
        );

        $record = $this->getRecord('Message');
        $record['context'] = [
            'exception' => [
                'class' => 'RuntimeException',
                'message' => 'Something went wrong',
                'code' => 500,
            ],
        ];

        $jsonOutput = $jsonFormatter->format($record);
        $flattenOutput = $flattenFormatter->format($record);

        $this->assertStringContainsString('exception={"class":"RuntimeException","message":"Something went wrong","code":500}', $jsonOutput);
        $this->assertStringContainsString('exception_class=RuntimeException exception_message="Something went wrong" exception_code=500', $flattenOutput);
    }

    public function testItHandlesCyclicalReferences(): void
    {
        $formatter = new LogfmtFormatter(
            'ts',
            'lvl',
            'chan',
            'msg',
            DateTime::RFC3339,
            "\n",
            true // Enable flattening
        );

        $record = $this->getRecord('Message');

        // Create objects with cyclical references
        $obj1 = new stdClass();
        $obj2 = new stdClass();
        $obj1->ref = $obj2;
        $obj2->back = $obj1;

        $record['context'] = [
            'cyclic' => $obj1,
        ];

        $formatted = $formatter->format($record);
        $this->assertStringContainsString('ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=Message', $formatted);
        $this->assertStringContainsString('cyclic_ref_back=NULL', $formatted);
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

    public function testOverrideLineSuffix(): void
    {
        $formatter = new LogfmtFormatter('ts', 'lvl', 'chan', 'msg', DateTime::RFC3339, null);
        $record = $this->getRecord('Message');
        $expected = 'ts=2017-11-19T19:00:00+00:00 lvl=INFO chan=app msg=Message';
        $this->assertEquals($expected, $formatter->format($record));
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
