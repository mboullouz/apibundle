<?php

namespace Axescloud\ApiBundle\Security;


use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;

class ApiKeyAuthenticator implements SimplePreAuthenticatorInterface, AuthenticationFailureHandlerInterface
{
	protected $container;
	protected $em;
	protected $logger;

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
		$this->em = $container->get("doctrine")->getManager();
		$this->logger= $container->get('logger');

	}

    public function createToken(Request $request, $providerKey)
    {

        //$apiKey = $request->headers->get('apikey');
        $apikey  = $request->headers->get('Authorization');

        if (!$apikey) {
            throw new BadCredentialsException();
        }
        return new PreAuthenticatedToken(
            'anon.',
            $apikey,
            $providerKey
        );
    }


    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    { if (!$userProvider instanceof ApiKeyUserProvider) {
    		throw new \InvalidArgumentException(
    				sprintf(
    						'The user provider must be an instance of ApiKeyUserProvider (%s was given).',
    						get_class($userProvider)
    				)
    		);
    	}

    	$apiKey = $token->getCredentials();
    	$username = $userProvider->getUsernameForApiKey($apiKey);

    	// User is the Entity which represents your user
    	$user = $token->getUser();
    	if ($user instanceof User) {
    		return new PreAuthenticatedToken(
    				$user,
    				$apiKey,
    				$providerKey,
    				$user->getRoles()
    		);
    	}

    	if (!$username) {
    		// this message will be returned to the client
    		throw new AuthenticationException(
    				sprintf('API Key "%s" does not exist.', $apiKey)
    		);
    	}

    	$user = $userProvider->loadUserByUsername($username);

    	return new PreAuthenticatedToken(
    			$user,
    			$apiKey,
    			$providerKey,
    			$user->getRoles()
    	);
    }
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
    	return new Response("Authentication Failed.
    			 Please check:  1-Authorization key in the header
    			                2-Credentials
    			                3-Authorization value ", 401);
    }
}