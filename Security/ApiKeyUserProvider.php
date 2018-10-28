<?php

namespace Axescloud\ApiBundle\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Axescloud\CoreBundle\Entity\Personnel;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Axescloud\CoreBundle\Entity\Utilisateur;

class ApiKeyUserProvider implements UserProviderInterface {

    protected $container;
    protected $em;
    protected $logger;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->em = $container->get("doctrine")->getManager();
        $this->logger = $container->get('logger');
    }

    public function getUsernameForApiKey($apiKey) {
        if (strpos(strtolower($apiKey), 'basic') === 0) {
            list($username, $password) = explode(':', base64_decode(substr($apiKey, 6)));
            $this->logger->info('API: tentative de connexion: username: ' . $username . ' password: ******,   _auth_' . $apiKey);
            $utilisateurService = $this->container->get('iMed.SiteBundle.Service.UtilisateurService');
            $isLoginSuccess = $utilisateurService->checkUser($username, $password);
            if (!$isLoginSuccess) {
                throw new BadCredentialsException();
            }
        } else {
            throw new BadCredentialsException("BadCredentialsException: Merci de vérifier le header de la request! ");
        }

        return $username;
    }

    public function loadUserByUsername($username) {
        /**
         * Or return an instance of Utilsateur|Personnel from the db!
         */
        $criteria = ['username' => $username];
        $user = $this->em->getRepository('AxescloudCoreBundle:Personnel')->findOneBy($criteria);
        if (empty($user)) {
            throw new UnsupportedUserException("Utilisateur introuvable! ");
        }
        return $user;

    }

    public function refreshUser(UserInterface $user) {
        $id = $user->getId();
        $user = $this->em->getRepository('AxescloudCoreBundle:Utilisateur')->find($id);
        return $user;
    }

    public function supportsClass($class) {
        return 'Symfony\Component\Security\Core\User\User' === $class;
    }

}