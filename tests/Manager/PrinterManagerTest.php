<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use Smalot\Cups\Builder\Builder;
use Smalot\Cups\Model\Printer;
use Smalot\Cups\Model\PrinterInterface;
use Smalot\Cups\Transport\Client;
use Smalot\Cups\Transport\ResponseParser;

/**
 * Class PrinterManager
 *
 * @package Smalot\Cups\Tests\Units\Manager
 */
class PrinterManagerTest extends TestCase
{
    protected $test_user = 'print-test';
    protected $test_pass = 'print-test';
    protected $test_host = null;
    protected $test_uri = 'ipp://localhost:631/printers/PDF';

    public function testPrinterManager()
    {
        $builder = new Builder();
        $client = new Client($this->test_user, $this->test_pass, ['remote_socket' => $this->test_host]);
        $responseParser = new ResponseParser();

        $printer_manager = new \Smalot\Cups\Manager\PrinterManager($builder, $client, $responseParser);
        $printer_manager->setCharset('utf-8');
        $printer_manager->setLanguage('fr-fr');
        $printer_manager->setRequestId(5);
        $printer_manager->setUsername('testuser');

        $this->assertEquals('utf-8', $printer_manager->getCharset());
        $this->assertEquals('fr-fr', $printer_manager->getLanguage());
        $this->assertEquals(5, $printer_manager->getRequestId());
        $this->assertEquals('testuser', $printer_manager->getUsername());
        $this->assertEquals(5, $printer_manager->getRequestId('current'));
        $this->assertEquals(6, $printer_manager->getRequestId('new'));
    }

    public function testFindByUri()
    {
        $builder = new Builder();
        $client = new Client($this->test_user, $this->test_pass, ['remote_socket' => $this->test_host]);
        $response_parser = new ResponseParser();

        $printer_manager = new \Smalot\Cups\Manager\PrinterManager($builder, $client, $response_parser);
        $printer_manager->setCharset('utf-8');
        $printer_manager->setLanguage('fr-fr');
        $printer_manager->setRequestId(5);
        $printer_manager->setUsername('testuser');

        $printer = $printer_manager->findByUri($this->test_uri);

        $this->assertEquals('PDF', $printer->getName());
        $this->assertEquals($this->test_uri, $printer->getUri());

        $printer = $printer_manager->findByUri('missing');
        $this->assertFalse($printer);
    }

    public function testFindByName()
    {
        $builder = new Builder();
        $client = new Client($this->test_user, $this->test_pass, ['remote_socket' => $this->test_host]);
        $response_parser = new ResponseParser();

        $printer_manager = new \Smalot\Cups\Manager\PrinterManager($builder, $client, $response_parser);
        $printer_manager->setCharset('utf-8');
        $printer_manager->setLanguage('fr-fr');
        $printer_manager->setRequestId(5);
        $printer_manager->setUsername('testuser');

        $printer = $printer_manager->findByName('PDF');

        $this->assertEquals('PDF', $printer->getName());
        $this->assertEquals($this->test_uri, $printer->getUri());

        $printer = $printer_manager->findByUri('missing');
        $this->assertFalse($printer);
    }

    public function testGetList()
    {
        $builder = new Builder();
        $client = new Client($this->test_user, $this->test_pass, ['remote_socket' => $this->test_host]);
        $responseParser = new ResponseParser();

        $printerManager = new \Smalot\Cups\Manager\PrinterManager($builder, $client, $responseParser);
        $printers = $printerManager->getList();

        $this->assertIsArray($printers);
        $this->assertGreaterThanOrEqual(1, count($printers));

        $found = false;
        foreach ($printers as $printer) {
            if ($printer->getName() == 'PDF') {
                $found = true;
                $this->assertEquals('PDF', $printer->getName());
                $this->assertEquals($this->test_uri, $printer->getUri());
                break;
            }
        }

        $this->assertTrue($found);
    }

    public function testPauseResume()
    {
        $builder = new Builder();
        $client = new Client($this->test_user, $this->test_pass, ['remote_socket' => $this->test_host]);
        $response_parser = new ResponseParser();
        $printer_manager = new \Smalot\Cups\Manager\PrinterManager($builder, $client, $response_parser);
        $printer = new Printer();
        $printer->setUri($this->test_uri);

        // Reset status
        $printer_manager->resume($printer);

        // Pause printer and check status
        $done = $printer_manager->pause($printer);
        $this->assertIsBool($done);
        $this->assertTrue($done);
        $this->assertEquals('stopped', $printer->getStatus());

        // Reset status and check status
        $done = $printer_manager->resume($printer);

        $this->assertIsBool($done);
        $this->assertTrue($done);
        $this->assertEquals('idle', $printer->getStatus());
    }

    public function testPurge()
    {
        $builder = new Builder();
        $client = new Client($this->test_user, $this->test_pass, ['remote_socket' => $this->test_host]);
        $response_parser = new ResponseParser();
        $printer_manager = new \Smalot\Cups\Manager\PrinterManager($builder, $client, $response_parser);
        $printer = new Printer();
        $printer->setUri($this->test_uri);

        // Reset status
        $done = $printer_manager->purge($printer);
        $this->assertIsBool($done);
        $this->assertTrue($done);
    }

    public function testGetDefault()
    {
        $builder = new Builder();
        $client = new Client($this->test_user, $this->test_pass, ['remote_socket' => $this->test_host]);
        $response_parser = new ResponseParser();
        $printer_manager = new \Smalot\Cups\Manager\PrinterManager($builder, $client, $response_parser);

        // Reset status
        $printer = $printer_manager->getDefault();
        $this->assertInstanceOf(\Smalot\Cups\Model\Printer::class, $printer);
        $this->assertEquals($this->test_uri, $printer->getUri());
    }
}
