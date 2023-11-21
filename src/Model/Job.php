<?php

namespace Smalot\Cups\Model;

/**
 * Class Job
 *
 * @package Smalot\Cups\Model
 */
class Job implements JobInterface
{

    use Traits\AttributeAware;
    use Traits\UriAware;

    const CONTENT_FILE = 'file';

    const CONTENT_TEXT = 'text';

    const SIDES_TWO_SIDED_LONG_EDGE = 'two-sided-long-edge';

    const SIDES_TWO_SIDED_SHORT_EDGE = 'two-sided-short-edge';

    const SIDES_ONE_SIDED = 'one-sided';

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $printer_uri;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $page_ranges;

    /**
     * @var int
     */
    protected $copies;

    /**
     * @var int
     */
    protected $sides;

    /**
     * @var int
     */
    protected $fidelity;

    /**
     * @var array
     */
    protected $content = [];

    /**
     * @var string
     */
    protected $state;

    /**
     * @var string
     */
    protected $state_reason;

    /**
     * Job constructor.
     */
    public function __construct()
    {
        $this->copies = 1;
        $this->sides = self::SIDES_ONE_SIDED;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Job
     */
    public function setId(int $id): JobInterface
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrinterUri(): string
    {
        return $this->printer_uri;
    }

    /**
     * @param string $printer_uri
     *
     * @return Job
     */
    public function setPrinterUri(string $printer_uri): JobInterface
    {
        $this->printer_uri = $printer_uri;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Job
     */
    public function setName(string $name): JobInterface
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return Job
     */
    public function setUsername(string $username): JobInterface
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getPageRanges(): string
    {
        return $this->page_ranges;
    }

    /**
     * @param string $page_ranges
     *
     * @return Job
     */
    public function setPageRanges(string $page_ranges = 'all'): JobInterface
    {
        $this->page_ranges = $page_ranges;

        return $this;
    }

    /**
     * @return int
     */
    public function getCopies(): int
    {
        return $this->copies;
    }

    /**
     * @param int $copies
     *
     * @return Job
     */
    public function setCopies(int $copies): JobInterface
    {
        $this->copies = $copies;

        return $this;
    }

    /**
     * @return int
     */
    public function getSides(): int
    {
        return $this->sides ?: self::SIDES_ONE_SIDED;
    }

    /**
     * @param int $sides
     *
     * @return Job
     */
    public function setSides(int $sides): JobInterface
    {
        $this->sides = $sides;

        return $this;
    }

    /**
     * @return int
     */
    public function getFidelity(): int
    {
        return $this->fidelity;
    }

    /**
     * @param int $fidelity
     *
     * @return Job
     */
    public function setFidelity(int $fidelity): JobInterface
    {
        $this->fidelity = $fidelity;

        return $this;
    }

    /**
     * @return array
     */
    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * @param string $filename
     * @param string $name
     * @param string $mime_type
     *
     * @return Job
     */
    public function addFile(string $filename, string $name = '', string $mime_type = 'application/octet-stream'): JobInterface
    {
        if (empty($name)) {
            $name = basename($filename);
        }

        $this->content[] = [
          'type' => self::CONTENT_FILE,
          'name' => $name,
          'mimeType' => $mime_type,
          'filename' => $filename,
        ];

        return $this;
    }

    /**
     * @param string $text
     * @param string $name
     * @param string $mime_type
     *
     * @return Job
     */
    public function addText(string $text, string $name = '', string $mime_type = 'text/plain'): JobInterface
    {
        $this->content[] = [
          'type' => self::CONTENT_TEXT,
          'name' => $name,
          'mimeType' => $mime_type,
          'text' => $text,
        ];

        return $this;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param string $state
     *
     * @return Job
     */
    public function setState(string $state): JobInterface
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return string
     */
    public function getStateReason(): string
    {
        return $this->state_reason;
    }

    /**
     * @param string $state_reason
     *
     * @return Job
     */
    public function setStateReason(string $state_reason): JobInterface
    {
        $this->state_reason = $state_reason;

        return $this;
    }
}
