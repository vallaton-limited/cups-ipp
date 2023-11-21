<?php

namespace Smalot\Cups\Transport;

use Psr\Http\Message\ResponseInterface;

/**
 * Class Response
 *
 * @package Smalot\Cups\Transport
 */
class Response
{

    /**
     * @var string
     */
    protected $ipp_version;

    /**
     * @var string
     */
    protected $status_code;

    /**
     * @var string
     */
    protected $request_id;

    /**
     * @var array
     */
    protected $body;

    /**
     * @var array
     */
    protected $values;

    /**
     * Response constructor.
     *
     * @param string $ipp_version
     * @param string $status_code
     * @param string $request_id
     * @param array  $body
     */
    public function __construct(string $ipp_version, string $status_code, string $request_id, array $body)
    {
        $this->ipp_version = $ipp_version;
        $this->status_code = $status_code;
        $this->request_id = $request_id;
        $this->body = $body;

        $this->values = $this->prepareValues($body);
    }

    /**
     * @return string
     */
    public function getIppVersion(): string
    {
        return $this->ipp_version;
    }

    /**
     * @return string
     */
    public function getStatusCode(): string
    {
        return $this->status_code;
    }

    /**
     * @return string
     */
    public function getStatusMessage()
    {
        if (!empty($this->values['operation-attributes'][0]['status-message'][0])) {
            return $this->values['operation-attributes'][0]['status-message'][0];
        }

        return false;
    }

    /**
     * @return string
     */
    public function getRequestId(): string
    {
        return $this->request_id;
    }

    /**
     * @return array
     */
    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * @return string|false
     */
    public function getCharset()
    {
        if (!empty($this->values['operation-attributes'][0]['attributes-charset'][0])) {
            return $this->values['operation-attributes'][0]['attributes-charset'][0];
        }

        return false;
    }

    /**
     * @return string|false
     */
    public function getLanguage()
    {
        if (!empty($this->values['operation-attributes'][0]['attributes-natural-language'][0])) {
            return $this->values['operation-attributes'][0]['attributes-natural-language'][0];
        }

        return false;
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        $values = $this->values;

        unset($values['operation-attributes']);
        unset($values['end-of-attributes']);

        return $values;
    }

    /**
     * @param array $list
     *
     * @return array
     */
    protected function prepareValues(array $list): array
    {
        unset($list['attributes']);

        $values = [];
        $name = '';

        foreach ($list as $item) {
            if (isset($item['attributes'])) {
                $name = $item['attributes'];
                unset($item['attributes']);
                $values[$name][] = $this->prepareValues($item);
                continue;
            } elseif (!empty($item['name'])) {
                $name = $item['name'];
            }

            $values[$name][] = $item['value'];
        }

        return $values;
    }
}
