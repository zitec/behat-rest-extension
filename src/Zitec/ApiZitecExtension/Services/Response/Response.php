<?php

namespace Zitec\ApiZitecExtension\Services\Response;

use Zitec\ApiZitecExtension\Services\Response\Content\AbstractContent;

/**
 * Class Response
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
class Response
{
    /**
     * @var AbstractContent|null
     */
    protected $content;

    /**
     * @var string
     */
    protected $rawContent;

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * Response constructor.
     *
     * @param string $content
     * @param array $headers
     */
    public function __construct($content, $headers)
    {

        foreach ($headers as $key => $header) {
            $this->headers[$key] = is_array($header) ? reset($header) : $header;    
        }
        if (strlen($content) === 0) {
            return;
        }
        $this->rawContent = $content;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param string $name
     * @return null|string
     */
    public function getHeader($name)
    {
        $name = strtolower($name);
        foreach ($this->headers as $key => $header) {
            if (strtolower($key) === $name) {
                return $header;
            }
        }

        return null;
    }

    /**
     * @return AbstractContent
     * @throws \Exception
     */
    public function getContent()
    {
        if (strlen($this->rawContent) === 0) {
            return null;
        }

        if ($this->content === null) {
            if ($this->contentTypeIs('json')) {
                $this->content = new Content\Json($this->rawContent);
            } elseif ($this->contentTypeIs('xml')) {
                $this->content = new Content\Xml($this->rawContent);
            } else {
                throw new \Exception('Unhandled content type');
            }
        }

        return $this->content;
    }

    /**
     * @param string $type
     * @return bool
     */
    public function contentTypeIs($type) {
        $contentType = strtolower($this->getHeader('Content-Type'));
        return stripos($contentType, strtolower($type)) !== false;
    }
}
