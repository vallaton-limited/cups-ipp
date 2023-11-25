<?php

namespace Smalot\Cups\Builder;

use Smalot\Cups\CupsException;
use Symfony\Component\Yaml\Parser;

/**
 * Class Builder
 *
 * @package Smalot\Cups\Builder
 */
class Builder
{

    /**
     * @var array
     */
    protected $tags_types = [];

    /**
     * @var array
     */
    protected $operation_tags = [];

    /**
     * @var array
     */
    protected $job_tags = [];

    /**
     * @var array
     */
    protected $printer_tags = [];

    /**
     * @var bool
     */
    protected $pass_unknown_as_string = false;

    /**
     * Builder constructor.
     *
     * @param null|string $path
     */
    public function __construct(string $path = null, bool $pass_unknown_as_string = false)
    {
        if (is_null($path)) {
            $path = __DIR__.'/../../config/';
        }

        $this->pass_unknown_as_string = $pass_unknown_as_string;

        $this->init($path);
    }

    /**
     * @param string $path
     */
    protected function init(string $path)
    {
        $parser = new Parser();

        $content = file_get_contents($path.'type.yml');
        $this->tags_types = $parser->parse($content);

        $content = file_get_contents($path.'operation.yml');
        $this->operation_tags = $parser->parse($content);

        $content = file_get_contents($path.'job.yml');
        $this->job_tags = $parser->parse($content);

        $content = file_get_contents($path.'printer.yml');
        $this->printer_tags = $parser->parse($content);
    }

    /**
     * @param string $string
     *
     * @return string
     * @throws CupsException
     */
    public function formatStringLength(string $string): string
    {
        $length = strlen($string);

        if ($length > ((0xFF << 8) + 0xFF)) {
            $message = sprintf(
              'Max string length for an ipp meta-information = %d, while here %d.',
              ((0xFF << 8) + 0xFF),
              $length
            );

            throw new CupsException($message);
        }

        $int1 = $length & 0xFF;
        $length -= $int1;
        $length = $length >> 8;
        $int2 = $length & 0xFF;

        return chr($int2).chr($int1);
    }

    /**
     * @param string $value
     *
     * @return string
     * @throws CupsException
     */
    public function formatInteger(string $value): string
    {
        if ($value >= 2147483647 || $value < -2147483648) {
            throw new CupsException('Values must be between -2147483648 and 2147483647.');
        }

        $initial_value = $value;
        $int1 = $value & 0xFF;
        $value -= $int1;
        $value = $value >> 8;
        $int2 = $value & 0xFF;
        $value -= $int2;
        $value = $value >> 8;
        $int3 = $value & 0xFF;
        $value -= $int3;
        $value = $value >> 8;
        $int4 = $value & 0xFF; // 64bits.

        if ($initial_value < 0) {
            $int4 = chr($int4) | chr(0x80);
        } else {
            $int4 = chr($int4);
        }

        return $int4.chr($int3).chr($int2).chr($int1);
    }

    /**
     * @param string $range
     *
     * @return string
     * @throws CupsException
     */
    public function formatRangeOfInteger(string $range): string
    {
        $integers = preg_split('/[:-]/', $range);

        if (count($integers) == 1) {
            return $this->formatInteger($integers[0]).$this->formatInteger($integers[0]);
        } else {
            return $this->formatInteger($integers[0]).$this->formatInteger($integers[1]);
        }
    }

    /**
     * @param array $properties
     *
     * @return string
     * @throws CupsException
     */
    public function buildProperties(array $properties = []): string
    {
        $build = '';

        foreach ($properties as $name => $values) {
            $build .= $this->buildProperty($name, $values);
        }

        return $build;
    }

    /**
     * @param string $name
     * @param mixed  $values
     * @param bool   $empty_if_missing
     *
     * @return string
     * @throws CupsException
     */
    public function buildProperty(string $name, $values, bool $empty_if_missing = false): string
    {
        if (!is_array($values)) {
            $values = [$values];
        }

        $build = '';
        $first = true;
        foreach ($values as $value) {
            if (!empty($value) || !$empty_if_missing) {
                $type = $this->getTypeFromProperty($name);

                switch ($type['build']) {
                    case 'boolean':
                        $value = ($value ? chr(0x01) : chr(0x0));
                        break;

                    case 'integer':
                        $value = $this->formatInteger(intval($value));
                        break;

                    case 'enum':
                        if (is_numeric($value)) {
                            $value = pack('N', $value);
                        }
                        break;

                    case 'range_of_integers':
                        $value = $this->formatRangeOfInteger($value);
                        break;

                    case 'resolution':
                        if (preg_match('/dpi/', $value)) {
                            $unit = chr(0x3);
                        } elseif (preg_match('/dpc/', $value)) {
                            $unit = chr(0x4);
                        } else {
                            $unit = '';
                        }
                        $search = ['/(dpi|dpc)/', '/(x|-)/'];
                        $replace = ['', ':'];
                        $value = $this->formatRangeOfInteger(preg_replace($search, $replace, $value)).$unit;
                        break;

                    case 'uri':
                    case 'keyword':
                    case 'string':
                        // Nothing to do.
                        break;

                    case 'no_value':
                        $value = '';
                        break;

                    case 'datetime':
                    case 'extended':
                    case 'octet_string':
                    default:
                        throw new CupsException('Property type not supported: "'.$type['tag'].'".');
                }

                if ($first) {
                    $build .= $type['tag']
                      .$this->formatStringLength($name)
                      .$name
                      .$this->formatStringLength($value)
                      .$value;

                    $first = false;
                } else {
                    $build .= $type['tag']
                      .$this->formatStringLength('')
                      .$this->formatStringLength($value)
                      .$value;
                }
            }
        }

        return $build;
    }

    /**
     * @param string $name
     *
     * @return array
     * @throws CupsException
     */
    public function getTypeFromProperty(string $name)
    {
        foreach (['operation', 'job', 'printer'] as $prefix) {
            if (!empty($this->{$prefix.'_tags'}[$name])) {
                $tag = $this->{$prefix.'_tags'}[$name]['tag'];

                if (!empty($this->tags_types[$tag])) {
                    return $this->tags_types[$tag];
                } else {
                    throw new CupsException('Type not found: "'.$tag.'".');
                }
            }
        }

        if ($this->pass_unknown_as_string) {
            return $this->tags_types['string'];
        }

        throw new CupsException('Property not found: "'.$name.'".');
    }
}
