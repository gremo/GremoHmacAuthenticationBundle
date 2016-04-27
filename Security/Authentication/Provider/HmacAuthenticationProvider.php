<?php

/*
 * This file is part of the hmac-authentication package.
 *
 * (c) Marco Polichetti <gremo1982@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gremo\HmacAuthenticationBundle\Security\Authentication\Provider;

use Gremo\HmacAuthenticationBundle\Security\Authentication\HmacUserToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Util\StringUtils;

class HmacAuthenticationProvider implements AuthenticationProviderInterface
{
    /**
     * @var string The service label i.e the service identification string
     */
    private $serviceLabel;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * @var string Hashing algoritm to use
     */
    private $hmacAlgorithm;

    /**
     * @var string List of headers used to re-compute the signature
     */
    private $signedHeaders;

    public function __construct(UserProviderInterface $userProvider, $serviceLabel, $hmacAlgorithm, array $signedHeaders)
    {
        $this->userProvider = $userProvider;
        $this->serviceLabel = $serviceLabel;
        $this->hmacAlgorithm = $hmacAlgorithm;
        $this->signedHeaders = $signedHeaders;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(TokenInterface $token)
    {
        /** @var HmacUserToken $token */
        if ($this->validateServiceLabel($token->getServiceLabel())) {
            $user = $this->userProvider->loadUserByUsername($token->getUsername());

            if ($this->validateSignature($token->getRequest(), $token->getSignature(), $user->getPassword())) {
                $authenticatedToken = new HmacUserToken();
                $authenticatedToken->setUser($user);
                $authenticatedToken->setServiceLabel($token->getServiceLabel());
                $authenticatedToken->setRequest($token->getRequest());

                return $authenticatedToken;
            }
        }

        throw new AuthenticationException('The HMAC authentication failed.');
    }

    /**
     * Returns true if the provided service label equals (case-sensitive) the configured service label.
     *
     * @param string $serviceLabel
     * @return bool
     */
    private function validateServiceLabel($serviceLabel)
    {
        return $this->serviceLabel === $serviceLabel;
    }

    /**
     * Returns true if the provided signature matches the recomputed signature with the given key.
     *
     * @param Request $request
     * @param string $providedSignature
     * @param string $key
     * @return bool
     */
    private function validateSignature(Request $request, $providedSignature, $key)
    {
        return StringUtils::equals($providedSignature, $this->generateHmacSignature($this->buildCanonicalStringFromRequest($request), $key));
    }

    /**
     * Generates and returns a base64-encoded HMAC signature of the given data, using the secret key.
     *
     * @param string $canonicalString
     * @param string $key
     * @return string
     */
    private function generateHmacSignature($canonicalString, $key)
    {
        return base64_encode(hash_hmac($this->hmacAlgorithm, $canonicalString, $key));
    }

    /**
     * Builds the canonical string from the request, according to the following rules (missing headers replaced by LF):
     *
     * <HTTP method> + "\n" +
     * <path>?<sorted query string> + "\n" +
     * <header1> + "\n" +
     * <header2> + "\n" +
     * ...
     * <headerN> + "\n" +
     *
     * @param Request $request
     * @return string
     */
    private function buildCanonicalStringFromRequest(Request $request)
    {
        // Tokens used to build the canonical string
        $parts = [];

        // Add the HTTP method
        $parts[] = $request->getMethod();

        // Add the path with an alfabetically-sorted query string
        $query = $request->query->all();
        ksort($query);
        $request->query->replace($query);
        $parts[] = $request->getPathInfo().(($query = $request->getQueryString()) ? '?'.$query : null);

        // Sort headers and add them, replacing empty one with LF
        sort($this->signedHeaders);
        foreach ($this->signedHeaders as $headerName) {
            $parts[] = $request->headers->get($headerName);
        }

        // Glue tokens togheter with LF
        return implode("\n", $parts);
    }

    /**
     * {@inheritdoc}
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof HmacUserToken;
    }
}
