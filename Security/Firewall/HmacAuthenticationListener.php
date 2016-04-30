<?php

/*
 * This file is part of the hmac-authentication package.
 *
 * (c) Marco Polichetti <gremo1982@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gremo\HmacAuthenticationBundle\Security\Firewall;

use Gremo\HmacAuthenticationBundle\Security\Authentication\HmacUserToken;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

class HmacAuthenticationListener implements ListenerInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthenticationManagerInterface
     */
    private $authenticationManager;

    /**
     * @var string The request header name containing the service label, id an signature
     */
    private $authenticationHeaderName;

    public function __construct(
        $tokenStorage,
        AuthenticationManagerInterface $authenticationManager,
        $authenticationHeaderName
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
        $this->authenticationHeaderName = $authenticationHeaderName;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (null !== $authorization = $request->headers->get($this->authenticationHeaderName)) {
            $headerParts = array_map('trim', explode(' ', $authorization, 2));

            if (2 === count($headerParts)) {
                $credentialParts = explode(':', $headerParts[1]);
                if (2 === count($credentialParts)) {
                    $token = new HmacUserToken();

                    $token->setServiceLabel($headerParts[0]);
                    $token->setUser($credentialParts[0]);
                    $token->setSignature($credentialParts[1]);
                    $token->setRequest($request);

                    try {
                        $authenticatedToken = $this->authenticationManager->authenticate($token);

                        // Call setToken() on an instance of SecurityContextInterface or TokenStorageInterface (>=2.6)
                        $this->tokenStorage->setToken($authenticatedToken);

                        // Success
                        return;
                    } catch (AuthenticationException $exception) {
                    }
                }
            }
        }

        $event->setResponse(new Response(null, 401));
    }
}
