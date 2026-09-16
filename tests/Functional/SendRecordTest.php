<?php
/**
 * Created by PhpStorm.
 * User: Jozef Môstka
 * Date: 4. 9. 2024
 * Time: 14:26
 */

namespace BugCatcher\Reporter\Tests\Functional;


use BugCatcher\Reporter\Service\BugCatcherInterface;
use BugCatcher\Reporter\Tests\App\KernelTestCase;
use BugCatcher\Reporter\Tests\App\Service\VoidWriter;
use BugCatcher\Reporter\UrlCatcher\ConsoleUriCatcher;
use Exception;

class SendRecordTest extends KernelTestCase
{
    public function testBugCatcherLog()
    {

        /** @var BugCatcherInterface $bugCatcher */
        $bugCatcher = $this->getContainer()->get(BugCatcherInterface::class);
        $data = [
            "key" => "value",
            'projectCode' => 'dev'
        ];
        $bugCatcher->log($data);
        $this->assertTrue(true);
        $writer = $this->getContainer()->get('test.writer');
        $this->assertInstanceOf(VoidWriter::class, $writer);
        $this->assertSame($data, $writer->popLastRequest());
    }

    public function testBugCatcherLogRecord()
    {
        /** @var BugCatcherInterface $bugCatcher */
        $bugCatcher = $this->getContainer()->get(BugCatcherInterface::class);

        $consoleUriCatcher = new ConsoleUriCatcher();
        $data = [

            'foo' => 'bar',
            'api_uri' => '/api/record_logs',
            'message' => 'message',
            'level' => 200,
            'projectCode' => 'dev',

        ];
        $bugCatcher->logRecord("message", 200, null, ["foo" => "bar"]);
        $this->assertTrue(true);
        $writer = $this->getContainer()->get('test.writer');
        $this->assertInstanceOf(VoidWriter::class, $writer);
        $request = $writer->popLastRequest();
        unset($request['requestUri']);
        $this->assertSame($data, $request);
    }

    public function testBugCatcherLogException()
    {
        /** @var BugCatcherInterface $bugCatcher */
        $bugCatcher = $this->getContainer()->get(BugCatcherInterface::class);

        $data = [
            'api_uri' => '/api/record_log_traces',
            'message' => 'message',
            'level' => 200,
            'projectCode' => 'dev',
            'requestUri' => 'uri',
        ];
        $bugCatcher->logException(new Exception("message"), 200, "uri");
        $this->assertTrue(true);
        $writer = $this->getContainer()->get('test.writer');
        $this->assertInstanceOf(VoidWriter::class, $writer);
        $request = $writer->popLastRequest();
        $this->assertArrayHasKey('stackTrace', $request);
        unset($request['stackTrace']);
        $this->assertSame($data, $request);
    }

    public function testBugCatcherLogExceptionWithoutMessage()
    {
        /** @var BugCatcherInterface $bugCatcher */
        $bugCatcher = $this->getContainer()->get(BugCatcherInterface::class);

        $bugCatcher->logException(new Exception(""), 200, "uri");
        $writer = $this->getContainer()->get('test.writer');
        $this->assertInstanceOf(VoidWriter::class, $writer);
        $request = $writer->popLastRequest();
        $this->assertSame(Exception::class, $request['message']);
    }

    public function testBugCatcherLogRecordWithBlankMessage()
    {
        /** @var BugCatcherInterface $bugCatcher */
        $bugCatcher = $this->getContainer()->get(BugCatcherInterface::class);

        $bugCatcher->logRecord("  \n ", 200, "uri");
        $writer = $this->getContainer()->get('test.writer');
        $this->assertInstanceOf(VoidWriter::class, $writer);
        $request = $writer->popLastRequest();
        $this->assertSame('Empty log message', $request['message']);
    }

    public function testConsoleUriCatcher()
    {
        /** @var BugCatcherInterface $bugCatcher */
        $bugCatcher = $this->getContainer()->get(BugCatcherInterface::class);

        $consoleUriCatcher = new ConsoleUriCatcher();
        $data = [

            'foo' => 'bar',
            'api_uri' => '/api/record_logs',
            'message' => 'message',
            'level' => 200,
            'projectCode' => 'dev',
            'requestUri' => $consoleUriCatcher->getUri(),

        ];
        $bugCatcher->logRecord("message", 200, null, ["foo" => "bar"]);
        $this->assertTrue(true);
        $writer = $this->getContainer()->get('test.writer');
        $this->assertInstanceOf(VoidWriter::class, $writer);
        $request = $writer->popLastRequest();
        $this->assertSame($data, $request);
    }
}