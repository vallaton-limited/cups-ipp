<?php

namespace Smalot\Cups\Manager;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Smalot\Cups\Builder\Builder;
use Smalot\Cups\CupsException;
use Smalot\Cups\Transport\Response;
use Smalot\Cups\Transport\ResponseParser;

/**
 * Class ManagerAbstract
 *
 * @package Smalot\Cups\Manager
 */
class ManagerAbstract
{

    use Traits\CharsetAware;
    use Traits\LanguageAware;
    use Traits\RequestIdAware;
    use Traits\UsernameAware;

    /**
     * @var ClientInterface;
     */
    protected $client;

    /**
     * @var Builder
     */
    protected $builder;

    /**
     * @var ResponseParser
     */
    protected $response_parser;

    /**
     * @var string
     */
    protected $version;

    /**
     * ManagerAbstract constructor.
     *
     * @param Builder         $builder
     * @param ClientInterface $client
     * @param ResponseParser  $response_parser
     */
    public function __construct(Builder $builder, ClientInterface $client, ResponseParser $response_parser)
    {
        $this->client = $client;
        $this->builder = $builder;
        $this->response_parser = $response_parser;
        $this->version = chr(0x01).chr(0x01);

        $this->setCharset('us-ascii');
        $this->setLanguage('en-us');
        $this->setRequestId(0);
        $this->setUsername('');
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @param bool   $empty_if_missing
     *
     * @return string
     * @throws CupsException
     */
    public function buildProperty(string $name, $value, bool $empty_if_missing = false): string
    {
        return $this->builder->buildProperty($name, $value, $empty_if_missing);
    }

    /**
     * @param array $properties
     *
     * @return string
     * @throws CupsException
     */
    public function buildProperties(array $properties = []): string
    {
        return $this->builder->buildProperties($properties);
    }

    /**
     * @param ResponseInterface $response
     *
     * @return Response
     */
    public function parseResponse(ResponseInterface $response): Response
    {
        return $this->response_parser->parse($response);
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }
}
