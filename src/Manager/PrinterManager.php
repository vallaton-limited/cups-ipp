<?php

namespace Smalot\Cups\Manager;

use Psr\Http\Client\ClientExceptionInterface;
use Smalot\Cups\CupsException;
use Smalot\Cups\Model\Operations;
use Smalot\Cups\Model\Printer;
use Smalot\Cups\Model\PrinterInterface;
use GuzzleHttp\Psr7\Request;
use Smalot\Cups\Tags\AttributeGroup;
use Smalot\Cups\Builder\IppRequestBuilder;

/**
 * Class Printer
 *
 * @package Smalot\Cups\Manager
 */
class PrinterManager extends ManagerAbstract
{
    /**
     * @param string $uri
     *
     * @return Printer|false
     * @throws ClientExceptionInterface
     * @throws CupsException
     */
    public function findByUri(string $uri)
    {
        $printer = new Printer();
        $printer->setUri($uri);

        $this->reloadAttributes($printer);

        if ($printer->getName()) {
            return $printer;
        } else {
            return false;
        }
    }

    /**
     * @param string $name
     *
     * @return Printer[]|false
     * @throws ClientExceptionInterface
     * @throws CupsException
     */
    public function findByName(string $name)
    {
        $all_printers = $this->getList();
        $printers = [];
        if (!empty($all_printers)) {
            foreach ($all_printers as $printer) {
                if ($printer->getName() === $name) {
                    $printers[] = $printer;
                }
            }

            return !empty($printers) ? $printers : false;
        } else {
            return false;
        }
    }

    /**
     * @param PrinterInterface $printer
     *
     * @return PrinterInterface
     * @throws ClientExceptionInterface
     * @throws CupsException
     */
    public function reloadAttributes(PrinterInterface $printer): PrinterInterface
    {
        $request = $this->prepareReloadAttributesRequest($printer);
        $response = $this->client->sendRequest($request);
        $result = $this->parseResponse($response);
        $values = $result->getValues();

        if (isset($values['printer-attributes'][0])) {
            $this->fillAttributes($printer, $values['printer-attributes'][0]);
        }

        return $printer;
    }

    /**
     * @return Printer|null
     * @throws ClientExceptionInterface
     * @throws CupsException
     */
    public function getDefault()
    {
        $request = $this->prepareGetDefaultRequest(['all']);
        $response = $this->client->sendRequest($request);
        $result = $this->parseResponse($response);
        $values = $result->getValues();

        $printer = null;

        if (isset($values['printer-attributes'][0])) {
            $printer = new Printer();
            $this->fillAttributes($printer, $values['printer-attributes'][0]);
        }

        return $printer;
    }

    /**
     * @param array $attributes
     *
     * @return Printer[]
     * @throws ClientExceptionInterface
     * @throws CupsException
     */
    public function getList(array $attributes = []): array
    {
        $request = $this->prepareGetListRequest($attributes);
        $response = $this->client->sendRequest($request);
        $result = $this->parseResponse($response);
        $values = $result->getValues();
        $list = [];

        if (!empty($values['printer-attributes'])) {
            foreach ($values['printer-attributes'] as $item) {
                $printer = new Printer();
                $this->fillAttributes($printer, $item);

                $list[] = $printer;
            }
        }

        return $list;
    }

    /**
     * @param PrinterInterface $printer
     *
     * @return bool
     * @throws ClientExceptionInterface
     * @throws CupsException
     */
    public function pause(PrinterInterface $printer): bool
    {
        $request = $this->preparePauseRequest($printer);
        $response = $this->client->sendRequest($request);
        $result = $this->parseResponse($response);

        // Reload attributes to update printer status.
        $this->reloadAttributes($printer);

        return $result->getStatusCode() == 'successfull-ok';
    }

    /**
     * @param PrinterInterface $printer
     *
     * @return bool
     * @throws ClientExceptionInterface
     * @throws CupsException
     */
    public function resume(PrinterInterface $printer): bool
    {
        $request = $this->prepareResumeRequest($printer);
        $response = $this->client->sendRequest($request);
        $result = $this->parseResponse($response);

        // Reload attributes to update printer status.
        $this->reloadAttributes($printer);

        return $result->getStatusCode() == 'successfull-ok';
    }

    /**
     * @param PrinterInterface $printer
     *
     * @return bool
     * @throws ClientExceptionInterface
     * @throws CupsException
     */
    public function purge(PrinterInterface $printer): bool
    {
        $request = $this->preparePurgeRequest($printer);
        $response = $this->client->sendRequest($request);
        $result = $this->parseResponse($response);

        return $result->getStatusCode() == 'successfull-ok';
    }

    /**
     * @param array $attributes
     *
     * @return Request
     * @throws CupsException
     */
    protected function prepareGetListRequest(array $attributes = []): Request
    {
        $request_id = $this->buildRequestId();
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();

        $meta_attributes = $this->buildPrinterRequestedAttributes($attributes);
        $request = new IppRequestBuilder($this->getVersion(), Operations::CUPS_GET_PRINTERS);
        $request->addAddAttribute($request_id)
                ->addAddAttributeTag(AttributeGroup::OPERATION_ATTRIBUTES_TAG)
                ->addAddAttribute($charset)
                ->addAddAttribute($language)
                ->addAddAttribute($meta_attributes)
                ->addAddAttributeTag(AttributeGroup::END_OF_ATTRIBUTES_TAG);

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/', $headers, $request);
    }

    /**
     * @param PrinterInterface $printer
     *
     * @return Request
     * @throws CupsException
     */
    protected function prepareReloadAttributesRequest(PrinterInterface $printer): Request
    {
        $request_id = $this->buildRequestId();
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $username = $this->buildUsername();

        $printer_uri = $this->buildProperty('printer-uri', $printer->getUri());
        $printer_attributes = '';

        $request = new IppRequestBuilder($this->getVersion(), Operations::GET_PRINTER_ATTRIBUTES);
        $request->addAddAttribute($request_id)
                ->addAddAttributeTag(AttributeGroup::OPERATION_ATTRIBUTES_TAG)
                ->addAddAttribute($charset)
                ->addAddAttribute($language)
                ->addAddAttribute($username)
                ->addAddAttribute($printer_uri)
                ->addAddAttribute($printer_attributes)
                ->addAddAttributeTag(AttributeGroup::END_OF_ATTRIBUTES_TAG);

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/', $headers, $request);
    }

    /**
     * @param array $attributes
     *
     * @return Request
     * @throws CupsException
     */
    protected function prepareGetDefaultRequest(array $attributes = []): Request
    {
        $request_id = $this->buildRequestId();
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();

        $meta_attributes = $this->buildPrinterRequestedAttributes($attributes);

        $request = new IppRequestBuilder($this->getVersion(), Operations::CUPS_GET_DEFAULT);
        $request->addAddAttribute($request_id)
                ->addAddAttributeTag(AttributeGroup::OPERATION_ATTRIBUTES_TAG)
                ->addAddAttribute($charset)
                ->addAddAttribute($language)
                ->addAddAttribute($meta_attributes)
                ->addAddAttributeTag(AttributeGroup::END_OF_ATTRIBUTES_TAG);

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/', $headers, $request);
    }

    /**
     * @param PrinterInterface $printer
     *
     * @return Request
     * @throws CupsException
     */
    protected function preparePauseRequest(PrinterInterface $printer): Request
    {
        $request_id = $this->buildRequestId();
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $username = $this->buildUsername();

        $printer_uri = $this->buildProperty('printer-uri', $printer->getUri());

        $request = new IppRequestBuilder($this->getVersion(), Operations::PAUSE_PRINTER);
        $request->addAddAttribute($request_id)
                ->addAddAttributeTag(AttributeGroup::OPERATION_ATTRIBUTES_TAG)
                ->addAddAttribute($charset)
                ->addAddAttribute($language)
                ->addAddAttribute($username)
                ->addAddAttribute($printer_uri)
                ->addAddAttributeTag(AttributeGroup::END_OF_ATTRIBUTES_TAG);

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/admin/', $headers, $request);
    }

    /**
     * @param PrinterInterface $printer
     *
     * @return Request
     * @throws CupsException
     */
    protected function prepareResumeRequest(PrinterInterface $printer): Request
    {
        $request_id = $this->buildRequestId();
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $username = $this->buildUsername();

        $printer_uri = $this->buildProperty('printer-uri', $printer->getUri());

        $request = new IppRequestBuilder($this->getVersion(), Operations::RESUME_PRINTER);
        $request->addAddAttribute($request_id)
                ->addAddAttributeTag(AttributeGroup::OPERATION_ATTRIBUTES_TAG)
                ->addAddAttribute($charset)
                ->addAddAttribute($language)
                ->addAddAttribute($username)
                ->addAddAttribute($printer_uri)
                ->addAddAttributeTag(AttributeGroup::END_OF_ATTRIBUTES_TAG);

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/admin/', $headers, $request);
    }

    /**
     * @param PrinterInterface $printer
     *
     * @return Request
     * @throws CupsException
     */
    protected function preparePurgeRequest(PrinterInterface $printer): Request
    {
        $request_id = $this->buildRequestId();
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $username = $this->buildUsername();

        $printer_uri = $this->buildProperty('printer-uri', $printer->getUri());
        $purge_job = $this->buildProperty('purge-jobs', 1);

        $request = new IppRequestBuilder($this->getVersion(), Operations::PURGE_JOBS);
        $request->addAddAttribute($request_id)
                ->addAddAttributeTag(AttributeGroup::OPERATION_ATTRIBUTES_TAG)
                ->addAddAttribute($charset)
                ->addAddAttribute($language)
                ->addAddAttribute($username)
                ->addAddAttribute($printer_uri)
                ->addAddAttribute($purge_job)
                ->addAddAttributeTag(AttributeGroup::END_OF_ATTRIBUTES_TAG);

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/admin/', $headers, $request);
    }

    /**
     * @param array $attributes
     *
     * @return string
     * @throws CupsException
     * @todo: move this method into a dedicated builder
     */
    protected function buildPrinterRequestedAttributes(array $attributes = []): string
    {
        if (empty($attributes)) {
            $attributes = [
              'printer-uri-supported',
              'printer-name',
              'printer-state',
              'printer-location',
              'printer-info',
              'printer-type',
              'printer-icons',
            ];
        }

        $meta_attributes = '';

        for ($i = 0; $i < count($attributes); $i++) {
            if ($i == 0) {
                $meta_attributes .= chr(0x44) // Keyword
                  .$this->builder->formatStringLength('requested-attributes')
                  .'requested-attributes'
                  .$this->builder->formatStringLength($attributes[0])
                  .$attributes[0];
            } else {
                $meta_attributes .= chr(0x44) // Keyword
                  .chr(0x0).chr(0x0) // zero-length name
                  .$this->builder->formatStringLength($attributes[$i])
                  .$attributes[$i];
            }
        }

        return $meta_attributes;
    }

    /**
     * @param PrinterInterface $printer
     * @param $item
     *
     * @return PrinterInterface
     */
    protected function fillAttributes(PrinterInterface $printer, $item): PrinterInterface
    {
        $printer->setUri($item['printer-uri-supported'][0]);
        $printer->setName($item['printer-name'][0]);
        $printer->setStatus($item['printer-state'][0]);

        // Merge with attributes already set.
        $attributes = $printer->getAttributes();
        foreach ($item as $name => $value) {
            $attributes[$name] = $value;
        }
        $printer->setAttributes($attributes);

        return $printer;
    }
}
