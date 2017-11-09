<?php

namespace Zitec\ApiZitecExtension\Services\Authentication\Algorithms;

/**
 * Class Authentication
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
abstract class AbstractAlgorithm
{
    /**
     * @var string
     */
    protected $date;

    /**
     * Authentication constructor.
     *
     * @param string $timeDifference
     */
    public function __construct($timeDifference)
    {
        $this->date = gmdate('r', strtotime(gmdate('r') . $timeDifference));
    }


    /**
     * Process and return the authentication headers.
     *
     * @return array
     */
    public abstract function getAuthHeaders();

    /**
     * @param string $key
     * @param string$message
     * @return string
     */
    protected static function hmacSha1($key, $message)
    {
        $blocksize = 64;
        $opad = str_repeat(chr(0x5c), $blocksize);
        $ipad = str_repeat(chr(0x36), $blocksize);

        if (strlen($key) < $blocksize) {
            $key = $key . str_repeat(chr(0), ($blocksize - strlen($key)));
        }

        $hmac = sha1(($key ^ $opad) . sha1(($key ^ $ipad) . $message, true), true);

        return base64_encode($hmac);
    }
}
