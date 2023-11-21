<?php

namespace tests;


use PHPUnit\Framework\TestCase;
use Smalot\Cups\Model\Job;
use Smalot\Cups\Model\Printer;
use Smalot\Cups\Transport\Client;

/**
 * Class Builder
 *
 * @package Smalot\Cups\Tests\Units\Builder
 */
class Builder extends TestCase
{

    public function testFormatStringLength()
    {
        $builder = new \Smalot\Cups\Builder\Builder();

        $results = $builder->formatStringLength('bonjour');
        $this->assertEquals(chr(0).chr(7), $results);

        $results = $builder->formatStringLength(str_repeat('X', 512));
        $this->assertEquals(chr(2).chr(0), $results);

        $results = $builder->formatStringLength(str_repeat('X', 513));
        $this->assertEquals(chr(2).chr(1), $results);

        $results = $builder->formatStringLength(str_repeat('X', 65535));
        $this->assertEquals(chr(255).chr(255), $results);

        $this->expectException(\Smalot\Cups\CupsException::class);
        $this->expectExceptionMessage('Max string length for an ipp meta-information = 65535, while here 65536.');
        $builder->formatStringLength(str_repeat('X', 65535 + 1));
    }

    public function testFormatInteger()
    {
        $builder = new \Smalot\Cups\Builder\Builder();

        $results = $builder->formatInteger(0);
        $this->assertEquals(chr(0).chr(0).chr(0).chr(0), $results);

        $results = $builder->formatInteger(5);
        $this->assertEquals(chr(0).chr(0).chr(0).chr(5), $results);

        $results = $builder->formatInteger(-5);
        $this->assertEquals(chr(255).chr(255).chr(255).chr(251), $results);

        $results = $builder->formatInteger(1024);
        $this->assertEquals(chr(0).chr(0).chr(4).chr(0), $results);

        $results = $builder->formatInteger(65535);
        $this->assertEquals(chr(0).chr(0).chr(255).chr(255), $results);

        $results = $builder->formatInteger(2147483646);
        $this->assertEquals(chr(127).chr(255).chr(255).chr(254), $results);

        $results = $builder->formatInteger(-2147483648);
        $this->assertEquals(chr(128).chr(0).chr(0).chr(0), $results);
    }

    public function testFormatIntegerToLarge()
    {
        $builder = new \Smalot\Cups\Builder\Builder();
        $this->expectException(\Smalot\Cups\CupsException::class);
        $this->expectExceptionMessage('Values must be between -2147483648 and 2147483647.');
        $builder->formatInteger(2147483647);
    }

    public function testFormatIntegerToSmall()
    {
        $builder = new \Smalot\Cups\Builder\Builder();
        $this->expectException(\Smalot\Cups\CupsException::class);
        $this->expectExceptionMessage('Values must be between -2147483648 and 2147483647.');
        $builder->formatInteger(-2147483649);
    }

        public function testFormatRangeOfInteger()
    {
        $builder = new \Smalot\Cups\Builder\Builder();

        $results = $builder->formatRangeOfInteger('1:5');
        $this->assertEquals(chr(0).chr(0).chr(0).chr(1).chr(0).chr(0).chr(0).chr(5), $results);

        $results = $builder->formatRangeOfInteger('1-5');
        $this->assertEquals(chr(0).chr(0).chr(0).chr(1).chr(0).chr(0).chr(0).chr(5), $results);

        $results = $builder->formatRangeOfInteger('5');
        $this->assertEquals(chr(0).chr(0).chr(0).chr(5).chr(0).chr(0).chr(0).chr(5), $results);
    }

    public function testBuildProperties()
    {
        $builder = new \Smalot\Cups\Builder\Builder();

        $properties = [
          'fit-to-page' => 1,
          'printer-resolution' => [
            '300dpi-300dpi',
          ],
        ];

        $results = $builder->buildProperties($properties);
        $this->assertEquals(
            // fit-to-page
            chr(0x21).
            chr(0).chr(0x0b).
            'fit-to-page'.
            chr(0).chr(0x4).
            chr(0).chr(0).chr(0).chr(1).
            // printer-resolution
            chr(0x32).
            chr(0).chr(0x12).
            'printer-resolution'.
            chr(0).chr(0x9).
            chr(0).chr(0).chr(0x01).chr(0x2c).chr(0x0).chr(0x0).chr(0x01).chr(0x2c).chr(0x3),
            $results
        );
    }

    public function testBuildProperty()
    {
        $builder = new \Smalot\Cups\Builder\Builder();

        $results = $builder->buildProperty('fit-to-page', 1);
        $this->assertEquals(
            chr(0x21).
            chr(0).chr(0x0b).
            'fit-to-page'.
            chr(0).chr(0x4).
            chr(0).chr(0).chr(0).chr(1),
            $results
        );
        $results = $builder->buildProperty('fit-to-page', 0);
        $this->assertEquals(
            chr(0x21).
            chr(0).chr(0x0b).
            'fit-to-page'.
            chr(0).chr(0x4).
            chr(0).chr(0).chr(0).chr(0),
            $results
        );

        $results = $builder->buildProperty('printer-resolution', '300dpi-300dpi');
        $this->assertEquals(
            chr(0x32).
            chr(0).chr(0x12).
            'printer-resolution'.
            chr(0).chr(0x9).
            chr(0).chr(0).chr(0x01).chr(0x2c).chr(0x0).chr(0x0).chr(0x01).chr(0x2c).chr(0x3),
            $results
        );

        $results = $builder->buildProperty('printer-resolution', '300dpc-300dpc');
        $this->assertEquals(
            chr(0x32).
            chr(0).chr(0x12).
            'printer-resolution'.
            chr(0).chr(0x9).
            chr(0).chr(0).chr(0x01).chr(0x2c).chr(0x0).chr(0x0).chr(0x01).chr(0x2c).chr(0x4),
            $results
        );

        $results = $builder->buildProperty('printer-resolution', '100x100');
        $this->assertEquals(
            chr(0x32).
            chr(0).chr(0x12).
            'printer-resolution'.
            chr(0).chr(0x8).
            chr(0).chr(0).chr(0x0).chr(0x64).chr(0x0).chr(0x0).chr(0x0).chr(0x64),
            $results
        );

        $results = $builder->buildProperty('orientation-requested', 'landscape');
        $this->assertEquals(
            chr(0x23).
            chr(0).chr(21).
            'orientation-requested'.
            chr(0).chr(0x9).
            'landscape',
            $results
        );

        $results = $builder->buildProperty('printer-uri', 'http://localhost/printer/pdf');
        $this->assertEquals(
            chr(0x45).
            chr(0).chr(11).
            'printer-uri'.
            chr(0).chr(28).
            'http://localhost/printer/pdf',
            $results
        );

        $results = $builder->buildProperty('job-uri', 'http://localhost/job/8');
        $this->assertEquals(
            chr(0x45).
            chr(0).chr(7).
            'job-uri'.
            chr(0).chr(22).
            'http://localhost/job/8',
            $results
        );

        $results = $builder->buildProperty('purge-jobs', true);
        $this->assertEquals(
            chr(0x22).
            chr(0).chr(10).
            'purge-jobs'.
            chr(0).chr(0x01).
            chr(0x01),
            $results
        );
    }

    public function testGetTypeFromProperty()
    {
        $builder = new \Smalot\Cups\Builder\Builder();

        $results = $builder->getTypeFromProperty('fit-to-page');
        $this->assertEquals(chr(0x21), $results['tag']);
        $this->assertEquals('integer', $results['build']);

        $results = $builder->getTypeFromProperty('document-format');
        $this->assertEquals(chr(0x49), $results['tag']);
        $this->assertEquals('string', $results['build']);

        $this->expectException(\Smalot\Cups\CupsException::class);
        $this->expectExceptionMessage('Property not found: "property not defined".');
        $builder->getTypeFromProperty('property not defined');
    }
}
