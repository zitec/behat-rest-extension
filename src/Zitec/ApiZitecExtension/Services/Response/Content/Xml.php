<?php

namespace Zitec\ApiZitecExtension\Services\Response\Content;

/**
 * Class XMLResponse
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
class Xml extends AbstractContent
{
    /**
     * Decodes the response.
     *
     * @throws \Exception
     */
    protected function parseData()
    {
        libxml_use_internal_errors(true);
        $xmlResponse = simplexml_load_string($this->rawContent);
        if (!$xmlResponse) {
            throw new \Exception(sprintf("Invalid XML response\n%s", $this->rawContent));
        }

        $this->parsedContent = json_decode(json_encode($xmlResponse),true);
    }
}
