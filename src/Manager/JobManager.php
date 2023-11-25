<?php

namespace Smalot\Cups\Manager;

use Smalot\Cups\Builder\IppRequestBuilder;
use Smalot\Cups\CupsException;
use Smalot\Cups\Model\Job;
use Smalot\Cups\Model\JobInterface;
use Smalot\Cups\Model\Operations;
use Smalot\Cups\Model\PrinterInterface;
use Smalot\Cups\Tags\AttributeGroup;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;

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

            // Send parts.
            $content = $job->getContent();
            $count = count($content);
            $success = $count > 0;

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
     * @param string           $which_jobs 'all'|'completed'|'not-completed'|''
     * @param bool             $subset
     *
     * @return Request
     * @throws CupsException
     */
    protected function prepareGetListRequest(PrinterInterface $printer, bool $my_jobs = true, int $limit = 0, string $which_jobs = 'not-completed', bool $subset = false): Request
    {
        $operation_id = $this->buildRequestId();
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $username = $this->buildUsername();

        $printer_uri = $this->buildProperty('printer-uri', $printer->getUri());
        $meta_limit = $this->buildProperty('limit', $limit, true);
        $meta_my_jobs = $this->buildProperty('my-jobs', $my_jobs, true);

        $meta_which_jobs = in_array($which_jobs, ['completed', 'all', 'not-completed']) ? $this->buildProperty('which-jobs', $which_jobs, true) : '';

        $request = new IppRequestBuilder($this->getVersion(), Operations::GET_JOBS);
        $request->addAddAttribute($operation_id)
                ->addAddAttributeTag(AttributeGroup::OPERATION_ATTRIBUTES_TAG)
                ->addAddAttribute($charset)
                ->addAddAttribute($language)
                ->addAddAttribute($username)
                ->addAddAttribute($printer_uri)
                ->addAddAttribute($meta_limit)
                ->addAddAttribute($meta_which_jobs)
                ->addAddAttribute($meta_my_jobs);

        if ($subset) {
            $attributes_group = [
              'job-uri',
              'job-name',
              'job-state',
              'job-state-reason',
            ];

            $request->addAddAttribute($this->buildProperty('requested-attributes', $attributes_group));
        } else {
            // Cups 1.4.4 doesn't return much of anything without this.
            $request->addAddAttribute($this->buildProperty('requested-attributes', 'all'));
        }

        $request->addAddAttributeTag(AttributeGroup::END_OF_ATTRIBUTES_TAG);

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/jobs/', $headers, $request);
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
        $request_id = $this->buildRequestId();
        $username = $this->buildUsername();
        $job_uri = $this->buildProperty('job-uri', $job->getUri());

        $request = new IppRequestBuilder($this->getVersion(), Operations::GET_JOB_ATTRIBUTES);
        $request->addAddAttribute($request_id)
            ->addAddAttributeTag(AttributeGroup::OPERATION_ATTRIBUTES_TAG)
            ->addAddAttribute($charset)
            ->addAddAttribute($language)
            ->addAddAttribute($job_uri)
            ->addAddAttribute($username);

        if ($subset) {
            $attributes_group = [
              'job-uri',
              'job-name',
              'job-state',
              'job-state-reason',
            ];

            $request->addAddAttribute($this->buildProperty('requested-attributes', $attributes_group));
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

            $request->addAddAttribute($this->buildProperty('requested-attributes', $attributes_group));
        }
        $request->addAddAttributeTag(AttributeGroup::END_OF_ATTRIBUTES_TAG);

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/jobs/', $headers, $request);
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
        $operation_id = $this->buildRequestId();
        $username = $this->buildUsername();
        $job_uri = $this->buildProperty('job-uri', $job->getUri());
        $copies = $this->buildProperty('copies', $job->getCopies());
        $sides = $this->buildProperty('sides', $job->getSides());
        $page_ranges = $this->buildPageRanges($job->getPageRanges());

        $job_attributes = $this->buildProperties($update);

        $deleted_attributes = '';

        $request = new IppRequestBuilder($this->getVersion(), Operations::SET_JOB_ATTRIBUTES);
        $request->addAddAttribute($operation_id)
                ->addAddAttributeTag(AttributeGroup::OPERATION_ATTRIBUTES_TAG)
                ->addAddAttribute($charset)
                ->addAddAttribute($language)
                ->addAddAttribute($job_uri)
                ->addAddAttribute($username)
                ->addAddAttributeTag(AttributeGroup::JOB_ATTRIBUTES_TAG)
                ->addAddAttribute($job_attributes)
                ->addAddAttribute($copies)
                ->addAddAttribute($sides)
                ->addAddAttribute($page_ranges)
                ->addAddAttribute($deleted_attributes)
                ->addAddAttributeTag(AttributeGroup::END_OF_ATTRIBUTES_TAG);

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/jobs/', $headers, $request);
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
        $request_id = $this->buildRequestId();
        $username = $this->buildUsername();
        $printer_uri = $this->buildProperty('printer-uri', $printer->getUri());
        $job_name = $this->buildProperty('job-name', $job->getName());
        $fidelity = $this->buildProperty('ipp-attribute-fidelity', $job->getFidelity());
        $timeout_attribute = $this->buildProperty('multiple-operation-time-out', $timeout);
        $copies = $this->buildProperty('copies', $job->getCopies());
        $sides = $this->buildProperty('sides', $job->getSides());
        $page_ranges = $this->buildPageRanges($job->getPageRanges());

        $operation_attributes = '';
        $job_attributes = $this->buildProperties($job->getAttributes());

        $request = new IppRequestBuilder($this->getVersion(), Operations::CREATE_JOB);
        $request->addAddAttribute($request_id)
                ->addAddAttributeTag(AttributeGroup::OPERATION_ATTRIBUTES_TAG)
                ->addAddAttribute($charset)
                ->addAddAttribute($language)
                ->addAddAttribute($printer_uri)
                ->addAddAttribute($username)
                ->addAddAttribute($job_name)
                ->addAddAttribute($fidelity)
                ->addAddAttribute($timeout_attribute)
                ->addAddAttribute($operation_attributes)
                ->addAddAttributeTag(AttributeGroup::JOB_ATTRIBUTES_TAG)
                ->addAddAttribute($copies)
                ->addAddAttribute($sides)
                ->addAddAttribute($page_ranges)
                ->addAddAttribute($job_attributes)
                ->addAddAttributeTag(AttributeGroup::END_OF_ATTRIBUTES_TAG);

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/printers/', $headers, $request);
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
        $request_id = $this->buildRequestId();
        $username = $this->buildUsername();
        $job_uri = $this->buildProperty('job-uri', $job->getUri());

        // Needs a build function call.
        $request_body_malformed = '';
        $message = '';

        $request = new IppRequestBuilder($this->getVersion(), Operations::CANCEL_JOB);
        $request->addAddAttribute($request_id)
                ->addAddAttribute($request_body_malformed)
                ->addAddAttributeTag(AttributeGroup::OPERATION_ATTRIBUTES_TAG)
                ->addAddAttribute($charset)
                ->addAddAttribute($language)
                ->addAddAttribute($job_uri)
                ->addAddAttribute($username)
                ->addAddAttribute($message)
                ->addAddAttributeTag(AttributeGroup::END_OF_ATTRIBUTES_TAG);

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/jobs/', $headers, $request);
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
        $request_id = $this->buildRequestId();
        $username = $this->buildUsername();
        $job_uri = $this->buildProperty('job-uri', $job->getUri());

        // Needs a build function call.
        $message = '';

        $request = new IppRequestBuilder($this->getVersion(), Operations::RELEASE_JOB);
        $request->addAddAttribute($request_id)
                ->addAddAttributeTag(AttributeGroup::OPERATION_ATTRIBUTES_TAG)
                ->addAddAttribute($charset)
                ->addAddAttribute($language)
                ->addAddAttribute($job_uri)
                ->addAddAttribute($username)
                ->addAddAttribute($message)
                ->addAddAttributeTag(AttributeGroup::END_OF_ATTRIBUTES_TAG);

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/jobs/', $headers, $request);
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
        $request_id = $this->buildRequestId();
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

        $request = new IppRequestBuilder($this->getVersion(), Operations::HOLD_JOB);
        $request->addAddAttribute($request_id)
                ->addAddAttributeTag(AttributeGroup::OPERATION_ATTRIBUTES_TAG)
                ->addAddAttribute($charset)
                ->addAddAttribute($language)
                ->addAddAttribute($username)
                ->addAddAttribute($job_uri)
                ->addAddAttribute($message)
                ->addAddAttribute($hold_until)
                ->addAddAttributeTag(AttributeGroup::END_OF_ATTRIBUTES_TAG);

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/jobs/', $headers, $request);
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
        $request_id = $this->buildRequestId();
        $username = $this->buildUsername();
        $job_uri = $this->buildProperty('job-uri', $job->getUri());

        // Needs a build function call.
        $message = '';

        $request = new IppRequestBuilder($this->getVersion(), Operations::RESTART_JOB);
        $request->addAddAttribute($request_id)
                ->addAddAttributeTag(AttributeGroup::OPERATION_ATTRIBUTES_TAG)
                ->addAddAttribute($charset)
                ->addAddAttribute($language)
                ->addAddAttribute($job_uri)
                ->addAddAttribute($username)
                ->addAddAttribute($message)
                ->addAddAttributeTag(AttributeGroup::END_OF_ATTRIBUTES_TAG);

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/jobs/', $headers, $request);
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
        $request_id = $this->buildRequestId();
        $charset = $this->buildCharset();
        $language = $this->buildLanguage();
        $username = $this->buildUsername();

        $job_uri = $this->buildProperty('job-uri', $job->getUri());
        $document_name = $this->buildProperty('document-name', $part['name']);
        $fidelity = $this->buildProperty('ipp-attribute-fidelity', $job->getFidelity(), true);
        $mime_media_type = $this->buildProperty('document-format', $part['mimeType'], true);

        $operation_attributes = '';
        $last_document = $this->buildProperty('last-document', $is_last);

        $request = new IppRequestBuilder($this->getVersion(), Operations::SEND_DOCUMENT);
        $request->addAddAttribute($request_id)
                ->addAddAttributeTag(AttributeGroup::OPERATION_ATTRIBUTES_TAG)
                ->addAddAttribute($charset)
                ->addAddAttribute($language)
                ->addAddAttribute($job_uri)
                ->addAddAttribute($username)
                ->addAddAttribute($document_name)
                ->addAddAttribute($fidelity)
                ->addAddAttribute($mime_media_type)
                ->addAddAttribute($operation_attributes)
                ->addAddAttribute($last_document)
                ->addAddAttributeTag(AttributeGroup::END_OF_ATTRIBUTES_TAG);

        if ($part['type'] == Job::CONTENT_FILE) {
            $request->addAddAttribute($part['binary']);
        } else {
            $request->addAddAttribute(chr(0x16)); // datahead
            $request->addAddAttribute($part['text']);
            $request->addAddAttribute(chr(0x0c)); // datatail
        }

        $headers = ['Content-Type' => 'application/ipp'];

        return new Request('POST', '/printers/', $headers, $request);
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
