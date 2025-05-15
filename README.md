# Cups IPP

CUPS Implementation of IPP - PHP Client API
#### *(another fork of the awesome [smalot/cups-ipp](https://github.com/smalot/cups-ipp), updated to use GuzzleHttp\Client, supports PHP >= 8.0)*

CUPS (Common Unix Printing System) is a modular printing system for Unix-like computer operating systems which allows a computer to act as a print server. A computer running CUPS is a host that can accept print jobs from client computers, process them, and send them to the appropriate printer.

![Build Status](https://github.com/josh-gaby/cups-ipp/actions/workflows/ci.yml/badge.svg)
[![Current Version](https://poser.pugx.org/josh-gaby/cups-ipp/v)](https://packagist.org/packages/josh-gaby/cups-ipp)
[![Total Downloads](https://poser.pugx.org/josh-gaby/cups-ipp/downloads)](https://packagist.org/packages/josh-gaby/cups-ipp)
[![Latest Unstable Version](http://poser.pugx.org/josh-gaby/cups-ipp/v/unstable)](https://packagist.org/packages/josh-gaby/cups-ipp)
[![License](http://poser.pugx.org/josh-gaby/cups-ipp/license)](https://packagist.org/packages/josh-gaby/cups-ipp)

## Install via Composer

You can install the component using [Composer](https://getcomposer.org/).

````sh
composer require vallaton-limited/cups-ipp
````

Then, require the `vendor/autoload.php` file to enable the autoloading mechanism provided by Composer.
Otherwise, your application won't be able to find the classes of this component.


## Requirements

This library use unix sock connection: `unix:///var/run/cups/cups.sock`

First of all, check if you have correct access to this file: `/var/run/cups/cups.sock`


## Implementation

### List printers

````php
<?php

include 'vendor/autoload.php';

use Smalot\Cups\Builder\Builder;
use Smalot\Cups\Manager\PrinterManager;
use Smalot\Cups\Transport\Client;
use Smalot\Cups\Transport\ResponseParser;

$client = new Client();
$builder = new Builder();
$responseParser = new ResponseParser();

$printerManager = new PrinterManager($builder, $client, $responseParser);
$printers = $printerManager->getList();

foreach ($printers as $printer) {
    echo $printer->getName().' ('.$printer->getUri().')'.PHP_EOL;
}

````


### List all printer's jobs

````php
<?php

include 'vendor/autoload.php';

use Smalot\Cups\Builder\Builder;
use Smalot\Cups\Manager\JobManager;
use Smalot\Cups\Manager\PrinterManager;
use Smalot\Cups\Transport\Client;
use Smalot\Cups\Transport\ResponseParser;

$client = new Client();
$builder = new Builder();
$responseParser = new ResponseParser();

$printerManager = new PrinterManager($builder, $client, $responseParser);
$printer = $printerManager->findByUri('ipp://localhost:631/printers/HP-Photosmart-C4380-series');

$jobManager = new JobManager($builder, $client, $responseParser);
$jobs = $jobManager->getList($printer, false, 0, 'completed');

foreach ($jobs as $job) {
    echo '#'.$job->getId().' '.$job->getName().' - '.$job->getState().PHP_EOL;
}

````


### Create and send a new job

````php
<?php

include 'vendor/autoload.php';

use Smalot\Cups\Builder\Builder;
use Smalot\Cups\Manager\JobManager;
use Smalot\Cups\Manager\PrinterManager;
use Smalot\Cups\Model\Job;
use Smalot\Cups\Transport\Client;
use Smalot\Cups\Transport\ResponseParser;

$client = new Client();
$builder = new Builder();
$responseParser = new ResponseParser();

$printerManager = new PrinterManager($builder, $client, $responseParser);
// Find the printer by the Uri
//$printer = $printerManager->findByUri('ipp://localhost:631/printers/HP-Photosmart-C4380-series');

// or by the name
$printers = $printerManager->findByName('HP-Photosmart-C4380-series');
if (!empty($printers)) {
    $printer = $printers[0];
    $jobManager = new JobManager($builder, $client, $responseParser);
    
    $job = new Job();
    $job->setName('job create file');
    $job->setUsername('demo');
    $job->setCopies(1);
    $job->setPageRanges('1'); // This can be left out, it will print all pages by default
    $job->addFile('./helloworld.pdf');
    $job->addAttribute('media', 'A4');
    $job->addAttribute('fit-to-page', true);
    $result = $jobManager->send($printer, $job);
}

````

### Remote CUPS server

You can easily connect to a remote CUPS server by supplying the connection details when constructing the Client.
````php
<?php

include 'vendor/autoload.php';

use Smalot\Cups\Builder\Builder;
use Smalot\Cups\Manager\JobManager;
use Smalot\Cups\Manager\PrinterManager;
use Smalot\Cups\Transport\Client;
use Smalot\Cups\Transport\ResponseParser;

$client = new Client('username', 'password', ['remote_socket' => 'http://server-ip:631']);
$builder = new Builder();
$responseParser = new ResponseParser();

$printerManager = new PrinterManager($builder, $client, $responseParser);
$printer = $printerManager->findByUri('ipp://localhost:631/printers/HP-Photosmart-C4380-series');

$jobManager = new JobManager($builder, $client, $responseParser);
$jobs = $jobManager->getList($printer, false, 0, 'completed');

foreach ($jobs as $job) {
    echo '#'.$job->getId().' '.$job->getName().' - '.$job->getState().PHP_EOL;
}

````
