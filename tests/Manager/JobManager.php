<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use Smalot\Cups\Builder\Builder;
use Smalot\Cups\Model\Job;
use Smalot\Cups\Model\Printer;
use Smalot\Cups\Transport\Client;
use Smalot\Cups\Transport\ResponseParser;

/**
 * Class JobManager
 *
 * @package Smalot\Cups\Tests\Units\Manager
 */
class JobManager extends TestCase
{

    public function testJobManager()
    {
        $builder = new Builder();
        $client = new Client();
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
        $user = getenv('USER');
        $password = getenv('PASS');
        $printer_uri = 'ipp://localhost:631/printers/Brother-HL-L2360D';

        $builder = new Builder();
        $client = new Client($user, $password, ['remote_socket' => 'tcp://192.168.1.21:631']);
        $response_parser = new ResponseParser();

        $printer = new Printer();
        $printer->setUri($printer_uri);

        $job_manager = new \Smalot\Cups\Manager\JobManager($builder, $client, $response_parser);
        $jobs = $job_manager->getList($printer, false, 0, 'completed');
        $this->assertEmpty($jobs);
    }

    public function testCreateFileJob()
    {
        $user = getenv('USER');
        $password = getenv('PASS');
        $printerUri = 'ipp://localhost:631/printers/PDF';

        $builder = new Builder();
        /** @var Client $client */
        $client = new Client();
        $client->setAuthentication($user, $password);
        $responseParser = new ResponseParser();

        $printer = new Printer();
        $printer->setUri($printerUri);

        $jobManager = new \Smalot\Cups\Manager\JobManager($builder, $client, $responseParser);
        //        $jobs = $jobManager->getList($printer, false);
        //        $this->array($jobs)->isEmpty();

        // Create new Job.
        $job = new Job();
        $job->setName('job create file');
        $job->setUsername($user);
        $job->setCopies(1);
        $job->setPageRanges('1');
        $job->addFile('./tests/helloworld.pdf');
        $job->addAttribute('media', 'A4');
        $job->addAttribute('fit-to-page', true);
        $result = $jobManager->send($printer, $job);

        sleep(5);
        $jobManager->reloadAttributes($job);

        $this->boolean($result)->isTrue();
        $this->integer($job->getId())->isGreaterThan(0);
        $this->string($job->getState())->isEqualTo('completed');
        $this->string($job->getPrinterUri())->isEqualTo($printer->getUri());
        $this->string($job->getPrinterUri())->isEqualTo($printerUri);

        //        $jobs = $jobManager->getList($printer, false);
        //         $this->array($jobs)->isNotEmpty();
    }

    public function testCreateTextJob()
    {
        $user = getenv('USER');
        $password = getenv('PASS');
        $printerUri = 'ipp://localhost:631/printers/PDF';

        $builder = new Builder();
        /** @var Client $client */
        $client = new Client();
        $client->setAuthentication($user, $password);
        $responseParser = new ResponseParser();

        $printer = new Printer();
        $printer->setUri($printerUri);

        $jobManager = new \Smalot\Cups\Manager\JobManager($builder, $client, $responseParser);
        $jobManager->setUsername($user);
        //        $jobs = $jobManager->getList($printer, false);
        //        $this->array($jobs)->isEmpty();

        // Create new Job.
        $job = new Job();
        $job->setName('job create text');
        $job->setUsername($user);
        $job->setCopies(1);
        $job->setPageRanges('1');
        $job->addText('hello world', 'hello');
        $job->addAttribute('media', 'A4');
        $job->addAttribute('fit-to-page', true);
        $result = $jobManager->send($printer, $job);

        sleep(5);
        $jobManager->reloadAttributes($job);

        $this->boolean($result)->isTrue();
        $this->integer($job->getId())->isGreaterThan(0);
        $this->string($job->getState())->isEqualTo('completed');
        $this->string($job->getPrinterUri())->isEqualTo($printer->getUri());
        $this->string($job->getPrinterUri())->isEqualTo($printerUri);

        //        $jobs = $jobManager->getList($printer, false);
        //        $this->array($jobs)->isNotEmpty();
    }

    public function testGetList()
    {
        $printerUri = 'ipp://localhost:631/printers/PDF';

        $builder = new Builder();
        $client = new Client();
        $responseParser = new ResponseParser();

        $printer = new Printer();
        $printer->setUri($printerUri);

        $jobManager = new \Smalot\Cups\Manager\JobManager($builder, $client, $responseParser);
        //        $jobs = $jobManager->getList($printer, false, 0, 'completed');
        //        $this->array($jobs)->isNotEmpty();
    }
}
