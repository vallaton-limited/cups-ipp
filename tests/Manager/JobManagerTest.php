<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use Smalot\Cups\Builder\Builder;
use Smalot\Cups\Manager\PrinterManager;
use Smalot\Cups\Model\Job;
use Smalot\Cups\Model\Printer;
use Smalot\Cups\Transport\Client;
use Smalot\Cups\Transport\ResponseParser;

/**
 * Class JobManager
 *
 * @package Smalot\Cups\Tests\Units\Manager
 */
class JobManagerTest extends TestCase
{
    protected $test_user = 'print-test';
    protected $test_pass = 'print-test';
    protected $test_host = null;
    protected $test_uri = 'ipp://localhost:631/printers/PDF';

    public function testJobManager()
    {
        $builder = new Builder();
        $client = new Client($this->test_user, $this->test_pass, ['remote_socket' => $this->test_host]);
        $response_parser = new ResponseParser();

        $job_manager = new \Smalot\Cups\Manager\JobManager($builder, $client, $response_parser);
        $job_manager->setCharset('utf-8');
        $job_manager->setLanguage('fr-fr');
        $job_manager->setRequestId(5);
        $job_manager->setUsername('testuser');

        $this->assertEquals('utf-8', $job_manager->getCharset());
        $this->assertEquals('fr-fr', $job_manager->getLanguage());
        $this->assertEquals(5, $job_manager->getRequestId());
        $this->assertEquals('testuser', $job_manager->getUsername());
        $this->assertEquals(5, $job_manager->getRequestId('current'));
        $this->assertEquals(6, $job_manager->getRequestId('new'));
    }

    public function testGetListEmpty()
    {
        $builder = new Builder();
        $client = new Client($this->test_user, $this->test_pass, ['remote_socket' => $this->test_host]);
        $response_parser = new ResponseParser();
        $printer = new Printer();
        $printer->setUri($this->test_uri);

        $job_manager = new \Smalot\Cups\Manager\JobManager($builder, $client, $response_parser);
        $jobs = $job_manager->getList($printer, false, 0, 'not-completed');
        $this->assertEmpty($jobs);
    }

    public function testCreateFileJob()
    {
        $builder = new Builder();
        $client = new Client($this->test_user, $this->test_pass, ['remote_socket' => $this->test_host]);
        $response_parser = new ResponseParser();
        $printer = new Printer();
        $printer->setUri($this->test_uri);
        $job_manager = new \Smalot\Cups\Manager\JobManager($builder, $client, $response_parser);

        // Create new Job.
        $job = new Job();
        $job->setName('job create file');
        $job->setUsername($this->test_user);
        $job->setCopies(1);
        $job->setPageRanges('1');
        $job->addFile(realpath(__DIR__ . '/../helloworld.pdf'));
        $job->addAttribute('media', 'A4');
        $job->addAttribute('fit-to-page', true);
        $result = $job_manager->send($printer, $job);

        sleep(5);
        $job_manager->reloadAttributes($job);

        $this->assertTrue($result);
        $this->assertGreaterThan(0, $job->getId());
        $this->assertEquals('completed', $job->getState());
        $this->assertEquals($job->getPrinterUri(), $printer->getUri());
        $this->assertEquals($job->getPrinterUri(), $this->test_uri);
    }

    public function testCreateTextJob()
    {
        $builder = new Builder();
        $client = new Client($this->test_user, $this->test_pass, ['remote_socket' => $this->test_host]);
        $response_parser = new ResponseParser();
        $printer = new Printer();
        $printer->setUri($this->test_uri);
        $job_manager = new \Smalot\Cups\Manager\JobManager($builder, $client, $response_parser);

        // Create new Job.
        $job = new Job();
        $job->setName('job create text');
        $job->setUsername($this->test_user);
        $job->setCopies(1);
        $job->addText('hello world', 'hello');
        $job->addAttribute('media', 'A4');
        $job->addAttribute('fit-to-page', true);
        $result = $job_manager->send($printer, $job);

        sleep(5);
        $job_manager->reloadAttributes($job);

        $this->assertTrue($result);
        $this->assertGreaterThan(0, $job->getId());
        $this->assertEquals('completed', $job->getState());
        $this->assertEquals($job->getPrinterUri(), $printer->getUri());
        $this->assertEquals($job->getPrinterUri(), $this->test_uri);
    }

    public function testGetList()
    {
        $builder = new Builder();
        $client = new Client($this->test_user, $this->test_pass, ['remote_socket' => $this->test_host]);
        $response_parser = new ResponseParser();
        $printer_manager = new PrinterManager($builder, $client, $response_parser);
        $printers = $printer_manager->getList();

        $this->assertNotEmpty($printers);
    }
}
