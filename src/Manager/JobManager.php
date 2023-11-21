<?php

namespace Smalot\Cups\Manager;

use Psr\Http\Client\ClientExceptionInterface;
use Smalot\Cups\CupsException;
use Smalot\Cups\Model\Job;
use Smalot\Cups\Model\JobInterface;
use Smalot\Cups\Model\PrinterInterface;
use GuzzleHttp\Psr7\Request;

/**
 * Class Job
 *
 * @package Smalot\Cups\Manager
 */
class JobManager extends ManagerAbstract
{

    /**
     * @param PrinterInterface $printer
     * @param bool             $my_jobs
     * @param int              $limit
     * @param string           $which_jobs
     * @param bool             $subset
     *
     * @return JobInterface[]
     * @throws ClientExceptionInterface
     * @throws CupsException
     */
    public function getList(PrinterInterface $printer, bool $my_jobs = true, int $limit = 0, string $which_jobs = 'not-completed', bool $subset = false): array
    {
        $request = $this->prepareGetListRequest($printer, $my_jobs, $limit, $which_jobs, $subset);
        $response = $this->client->sendRequest($request);
        $result = $this->parseResponse($response);
        $values = $result->getValues();

        $list = [];

        if (!empty($values['job-attributes'])) {
            foreach ($values['job-attributes'] as $item) {
                $job = new Job();
                $this->fillAttributes($job, $item);

                $list[] = $job;
            }
        }

        return $list;
    }

    /**
     * @param JobInterface $job
     * @param bool         $subset
     * @param string       $attributes_group
     *
     * @return JobInterface
     * @throws ClientExceptionInterface
     * @throws CupsException
     */
    public function reloadAttributes(JobInterface $job, bool $subset = false, string $attributes_group = 'all'): JobInterface
    {
        if ($job->getUri()) {
            $request = $this->prepareReloadAttributesRequest($job, $subset, $attributes_group);
            $response = $this->client->sendRequest($request);
            $result = $this->parseResponse($response);
            $values = $result->getValues();

            if (isset($values['job-attributes'][0])) {
                $this->fillAttributes($job, $values['job-attributes'][0]);
            }
        }

        return $job;
    }

    /**
     * @param PrinterInterface $printer
     * @param JobInterface     $job
     * @param int              $timeout
     *
     * @return bool
     * @throws ClientExceptionInterface
     * @throws CupsException
     */
    public function send(PrinterInterface $printer, JobInterface $job, int $timeout = 60): bool
    {
        // Create job.
        $request = $this->prepareSendRequest($printer, $job, $timeout);
        $response = $this->client->sendRequest($request);
        $result = $this->parseResponse($response);
        $values = $result->getValues();

        $success = false;

        if ($result->getStatusCode() == 'successfull-ok') {
            $job = $this->fillAttributes($job, $values['job-attributes'][0]);
            $job->setPrinterUri($printer->getUri());

            $success = (count($job->getContent()) > 0);

            // Send parts.
            $content = $job->getContent();
            $count = count($job->getContent());

            foreach ($content as $part) {
                $request = $this->prepareSendPartRequest($job, $part, !(--$count));
                $response = $this->client->sendRequest($request);
                $result = $this->parseResponse($response);

                if ($result->getStatusCode() != 'successfull-ok') {
                    $success = false;
                    break;
                }
            }
        }

        return $success;
    }

    /**
     * @param JobInterface $job
     * @param array        $update
     * @param array        $delete
     *
     * @return bool
     * @throws ClientExceptionInterface
     * @throws CupsException
     */
    public function update(JobInterface $job, array $update = [], array $delete = []): bool
    {
        $request = $this->prepareUpdateRequest($job, $update, $delete);
        $response = $this->client->sendRequest($request);
        $result = $this->parseResponse($response);

        // Refresh attributes.
        $this->reloadAttributes($job);

        return $result->getStatusCode() == 'successfull-ok';
    }

    /**
     * @param JobInterface $job
     *
     * @return bool
     * @throws ClientExceptionInterface
     * @throws CupsException
     */
    public function cancel(JobInterface $job): bool
    {
        $request = $this->prepareCancelRequest($job);
        $response = $this->client->sendRequest($request);
        $result = $this->parseResponse($response);

        // Refresh attributes.
        $this->reloadAttributes($job);

        return $result->getStatusCode() == 'successfull-ok';
    }

    /**
     * @param JobInterface $job
     *
     * @return bool
     * @throws ClientExceptionInterface
     * @throws CupsException
     */
    public function release(JobInterface $job): bool
    {
        $request = $this->prepareReleaseRequest($job);
        $response = $this->client->sendRequest($request);
        $result = $this->parseResponse($response);

        // Refresh attributes.
        $this->reloadAttributes($job);

        return $result->getStatusCode() == 'successfull-ok';
    }

    /**
     * @param JobInterface $job
     * @param string       $until
     * Can be:
     * - no-hold
     * - day-time
     * - evening
     * - night
     * - weekend
     * - second-shift
     * - third-shift
     *
     * @return bool
     * @throws ClientExceptionInterface
     * @throws CupsException
     */
    public function hold(JobInterface $job, string $until = 'indefinite'): bool
    {
        $request = $this->prepareHoldRequest($job, $until);
        $response = $this->client->sendRequest($request);
        $result = $this->parseResponse($response);

        // Refresh attributes.
        $this->reloadAttributes($job);

        return $result->getStatusCode() == 'successfull-ok';
    }

    /**
     * @param JobInterface $job
     *
     * @return bool
     * @throws ClientExceptionInterface
     * @throws CupsException
     */
    public function restart(JobInterface $job): bool
    {
        $request = $this->prepareRestartRequest($job);
        $response = $this->client->sendRequest($request);
        $result = $this->parseResponse($response);

        // Refresh attributes.
        $this->reloadAttributes($job);

        return $result->getStatusCode() == 'successfull-ok';
    }

    /**
     * @param PrinterInterface $printer
     * @param bool             $my_jobs
     * @param int              $limit
     * @param string           $which_jobs
     * @param bool             $subset
     *
     * @return Request
     * @throws CupsException
     */
    protected function prepareGetListRequest(PrinterInterface $printer, bool $my_jobs = true, int $limit = 0, string $which_jobs = 'not-completed', bool $subset = false): Request
    {
        $operation_id = $this->buildOperationId();
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $username = $this->buildUsername();

        $printer_uri = $this->buildProperty('printer-uri', $printer->getUri());
        $meta_limit = $this->buildProperty('limit', $limit, true);
        $meta_my_jobs = $this->buildProperty('my-jobs', $my_jobs, true);

        $meta_which_jobs = $which_jobs == 'completed' ? $this->buildProperty('which-jobs', $which_jobs, true) : '';

        $content = $this->getVersion() // 1.1  | version-number
          .chr(0x00).chr(0x0A) // Get-Jobs | operation-id
          .$operation_id //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$username
          .$printer_uri
          .$meta_limit
          .$meta_which_jobs
          .$meta_my_jobs;

        if ($subset) {
            $attributes_group = [
              'job-uri',
              'job-name',
              'job-state',
              'job-state-reason',
            ];

            $content .= $this->buildProperty('requested-attributes', $attributes_group);
        } else {
            // Cups 1.4.4 doesn't return much of anything without this.
            $content .= $this->buildProperty('requested-attributes', 'all');
        }

        $content .= chr(0x03); // end-of-attributes | end-of-attributes-tag

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/jobs/', $headers, $content);
    }

    /**
     * @param JobInterface $job
     * @param bool         $subset
     * @param string       $attributes_group
     *
     * @return Request
     * @throws CupsException
     */
    protected function prepareReloadAttributesRequest(JobInterface $job, bool $subset = false, string $attributes_group = 'all'): Request
    {
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $operation_id = $this->buildOperationId();
        $username = $this->buildUsername();
        $job_uri = $this->buildProperty('job-uri', $job->getUri());

        $content = $this->getVersion() // 1.1  | version-number
          .chr(0x00).chr(0x09) // Get-Job-Attributes | operation-id
          .$operation_id //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$job_uri
          .$username;

        if ($subset) {
            $attributes_group = [
              'job-uri',
              'job-name',
              'job-state',
              'job-state-reason',
            ];

            $content .= $this->buildProperty('requested-attributes', $attributes_group);
        } elseif ($attributes_group) {
            switch ($attributes_group) {
                case 'job-template':
                case 'job-description':
                case 'all':
                    break;
                default:
                    trigger_error('Invalid attribute group: "'.$attributes_group.'"', E_USER_NOTICE);
                    $attributes_group = '';
                    break;
            }

            $content .= $this->buildProperty('requested-attributes', $attributes_group);
        }
        $content .= chr(0x03); // end-of-attributes | end-of-attributes-tag

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/jobs/', $headers, $content);
    }

    /**
     * @param JobInterface $job
     * @param array        $update
     * @param array        $delete
     *
     * @return Request
     * @throws CupsException
     */
    protected function prepareUpdateRequest(JobInterface $job, array $update = [], array $delete = []): Request
    {
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $operation_id = $this->buildOperationId();
        $username = $this->buildUsername();
        $job_uri = $this->buildProperty('job-uri', $job->getUri());
        $copies = $this->buildProperty('copies', $job->getCopies());
        $sides = $this->buildProperty('sides', $job->getSides());
        $page_ranges = $this->buildPageRanges($job->getPageRanges());

        $job_attributes = $this->buildProperties($update);

        $deleted_attributes = '';

        $content = $this->getVersion() // 1.1  | version-number
          .chr(0x00).chr(0x14) // Set-Job-Attributes | operation-id
          .$operation_id //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$job_uri
          .$username
          .chr(0x02) // start job-attributes
          .$job_attributes // set by setAttribute($attribute,$value)
          .$copies
          .$sides
          .$page_ranges
          .$deleted_attributes
          .chr(0x03); // end-of-attributes | end-of-attributes-tag

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/jobs/', $headers, $content);
    }

    /**
     * @param PrinterInterface $printer
     * @param JobInterface     $job
     * @param int              $timeout
     *
     * @return Request
     * @throws CupsException
     */
    protected function prepareSendRequest(PrinterInterface $printer, JobInterface $job, int $timeout = 60): Request
    {
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $operation_id = $this->buildOperationId();
        $username = $this->buildUsername();
        $printer_uri = $this->buildProperty('printer-uri', $printer->getUri());
        $job_name = $this->buildProperty('job-name', $job->getName());
        $fidelity = $this->buildProperty('ipp-attribute-fidelity', $job->getFidelity());
        $timeout_attribute = $this->buildProperty('multiple-operation-time-out', $timeout);
        $copies = $this->buildProperty('copies', $job->getCopies());
        $sides = $this->buildProperty('sides', $job->getSides());
        $page_ranges = $this->buildPageRanges($job->getPageRanges());

        // todo
        $operation_attributes = '';//$this->buildOperationAttributes();
        $job_attributes = $this->buildProperties($job->getAttributes());

        $content = $this->getVersion() // 1.1  | version-number
          .chr(0x00).chr(0x05) // Create-Job | operation-id
          .$operation_id //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$printer_uri
          .$username
          .$job_name
          .$fidelity
          .$timeout_attribute
          .$operation_attributes
          .chr(0x02) // start job-attributes | job-attributes-tag
          .$copies
          .$sides
          .$page_ranges
          .$job_attributes
          .chr(0x03); // end-of-attributes | end-of-attributes-tag

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/printers/', $headers, $content);
    }

    /**
     * @param JobInterface $job
     *
     * @return Request
     * @throws CupsException
     */
    protected function prepareCancelRequest(JobInterface $job): Request
    {
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $operation_id = $this->buildOperationId();
        $username = $this->buildUsername();
        $job_uri = $this->buildProperty('job-uri', $job->getUri());

        // Needs a build function call.
        $request_body_malformed = '';
        $message = '';

        $content = $this->getVersion() // 1.1  | version-number
          .chr(0x00).chr(0x08) // cancel-Job | operation-id
          .$operation_id //           request-id
          .$request_body_malformed
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$job_uri
          .$username
          .$message
          .chr(0x03); // end-of-attributes | end-of-attributes-tag

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/jobs/', $headers, $content);
    }

    /**
     * @param JobInterface $job
     *
     * @return Request
     * @throws CupsException
     */
    protected function prepareReleaseRequest(JobInterface $job): Request
    {
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $operation_id = $this->buildOperationId();
        $username = $this->buildUsername();
        $job_uri = $this->buildProperty('job-uri', $job->getUri());

        // Needs a build function call.
        $message = '';

        $content = $this->getVersion() // 1.1  | version-number
          .chr(0x00).chr(0x0d) // release-Job | operation-id
          .$operation_id //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$job_uri
          .$username
          .$message
          .chr(0x03); // end-of-attributes | end-of-attributes-tag

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/jobs/', $headers, $content);
    }

    /**
     * @param JobInterface $job
     * @param string       $until
     *
     * @return Request
     * @throws CupsException
     */
    protected function prepareHoldRequest(JobInterface $job, string $until = 'indefinite'): Request
    {
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $operation_id = $this->buildOperationId();
        $username = $this->buildUsername();
        $job_uri = $this->buildProperty('job-uri', $job->getUri());

        // Needs a build function call.
        $message = '';

        $until_strings = [
          'no-hold',
          'day-time',
          'evening',
          'night',
          'weekend',
          'second-shift',
          'third-shift',
        ];

        if (!in_array($until, $until_strings)) {
            $until = 'indefinite';
        }

        $hold_until = chr(0x42) // keyword
          .$this->builder->formatStringLength('job-hold-until')
          .'job-hold-until'
          .$this->builder->formatStringLength($until)
          .$until;

        $content = $this->getVersion() // 1.1  | version-number
          .chr(0x00).chr(0x0C) // hold-Job | operation-id
          .$operation_id //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$username
          .$job_uri
          .$message
          .$hold_until
          .chr(0x03); // end-of-attributes | end-of-attributes-tag

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/jobs/', $headers, $content);
    }

    /**
     * @param JobInterface $job
     *
     * @return Request
     * @throws CupsException
     */
    protected function prepareRestartRequest(JobInterface $job): Request
    {
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $operation_id = $this->buildOperationId();
        $username = $this->buildUsername();
        $job_uri = $this->buildProperty('job-uri', $job->getUri());

        // Needs a build function call.
        $message = '';

        $content = $this->getVersion() // 1.1  | version-number
          .chr(0x00).chr(0x0E) // release-Job | operation-id
          .$operation_id //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$job_uri
          .$username
          .$message
          .chr(0x03); // end-of-attributes | end-of-attributes-tag

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/jobs/', $headers, $content);
    }

    /**
     * @param JobInterface $job
     * @param array        $part
     * @param bool         $is_last
     *
     * @return Request
     * @throws CupsException
     */
    protected function prepareSendPartRequest(JobInterface $job, $part, $is_last = false): Request
    {
        $operation_id = $this->buildOperationId();
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $username = $this->buildUsername();

        $job_uri = $this->buildProperty('job-uri', $job->getUri());
        $document_name = $this->buildProperty('document-name', $part['name']);
        $fidelity = $this->buildProperty('ipp-attribute-fidelity', $job->getFidelity(), true);
        $mime_media_type = $this->buildProperty('document-format', $part['mimeType'], true);

        // @todo
        $operation_attributes = '';//$this->buildOperationAttributes();
        $last_document = $this->buildProperty('last-document', $is_last);

        $content = $this->getVersion() // 1.1  | version-number
          .chr(0x00).chr(0x06) // Send-Document | operation-id
          .$operation_id //           request-id
          .chr(0x01) // start operation-attributes | operation-attributes-tag
          .$charset
          .$language
          .$job_uri
          .$username
          .$document_name
          .$fidelity
          .$mime_media_type
          .$operation_attributes
          .$last_document
          .chr(0x03); // end-of-attributes | end-of-attributes-tag

        if ($part['type'] == Job::CONTENT_FILE) {
            $data = file_get_contents($part['filename']);
            //            $content .= chr(0x16); // datahead
            $content .= $data;
        } else {
            $content .= chr(0x16); // datahead
            $content .= $part['text'];
            $content .= chr(0x0c); // datatail
        }

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/printers/', $headers, $content);
    }

    /**
     * @param JobInterface $job
     * @param $item
     *
     * @return JobInterface
     */
    protected function fillAttributes(JobInterface $job, $item): JobInterface
    {
        $name = empty($item['job-name'][0]) ? 'Job #'.$job->getId() : $item['job-name'][0];
        $copies = empty($item['number-up'][0]) ? 1 : $item['number-up'][0];

        $job->setId($item['job-id'][0]);
        $job->setUri($item['job-uri'][0]);
        $job->setName($name);
        $job->setState($item['job-state'][0]);
        $job->setStateReason($item['job-state-reasons'][0]);
        $job->setCopies($copies);

        if (isset($item['job-printer-uri'][0])) {
            $job->setPrinterUri($item['job-printer-uri'][0]);
        }

        if (isset($item['job-originating-user-name'][0])) {
            $job->setUsername($item['job-originating-user-name'][0]);
        }

        if (isset($item['page-ranges'][0])) {
            $job->setPageRanges($item['page-ranges'][0]);
        }

        unset($item['job-id']);
        unset($item['job-uri']);
        unset($item['job-name']);
        unset($item['job-state']);
        unset($item['job-state-reasons']);
        unset($item['number-up']);
        unset($item['page-range']);
        unset($item['job-printer-uri']);
        unset($item['job-originating-user-name']);

        // Merge with attributes already set.
        $attributes = $job->getAttributes();
        $attributes += $item;
        $job->setAttributes($attributes);

        return $job;
    }

    /**
     * @param string $page_ranges
     *
     * @return string
     * @throws CupsException
     */
    protected function buildPageRanges(string $page_ranges = 'all'): string
    {
        if ($page_ranges === 'all' || empty($page_ranges)) {
            return '';
        }
        $page_ranges = trim(str_replace('-', ':', $page_ranges));
        $page_ranges = explode(',', $page_ranges);

        return $this->buildProperty('page-ranges', $page_ranges);
    }
}
