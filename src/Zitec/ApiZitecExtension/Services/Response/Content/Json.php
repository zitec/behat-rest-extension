<?php

namespace Zitec\ApiZitecExtension\Services\Response\Content;

/**
 * Class JsonResponse
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
class Json extends AbstractContent
{
    /**
     * Decodes the response.
     *
     * @throws \Exception
     */
    protected function parseData()
    {
        $this->parsedContent = json_decode($this->rawContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception(sprintf("Invalid JSON response\n%s", $this->rawContent));
        }
    }
}
