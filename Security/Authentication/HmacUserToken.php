<?php

/*
 * This file is part of the hmac-authentication package.
 *
 * (c) Marco Polichetti <gremo1982@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gremo\HmacAuthenticationBundle\Security\Authentication;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class HmacUserToken extends AbstractToken
{
    /**
     * @var string
     */
    private $serviceLabel;

    /**
     * @var string
     */
    private $signature;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param string $signature
     * @return $this
     */
    public function setSignature($signature)
    {
        $this->signature = $signature;
    }

    /**
     * @return string
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * @param string $serviceLabel
     * @return $this
     */
    public function setServiceLabel($serviceLabel)
    {
        $this->serviceLabel = $serviceLabel;
    }

    /**
     * @return string
     */
    public function getServiceLabel()
    {
        return $this->serviceLabel;
    }

    /**
     * @param Request $request
     * @return $this
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return null;
    }
}
