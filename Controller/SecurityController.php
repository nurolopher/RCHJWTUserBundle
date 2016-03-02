<?php

/**
 * This file is part of the RCH package.
 *
 * Robin Chalas <robin.chalas@gmail.com>
 *
 * For more informations about license, please see the LICENSE
 * file distributed in this source code.
 */
namespace RCH\JWTUserBundle\Controller;

use FOS\UserBundle\Model\UserInterface;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken;
use RCH\JWTUserBundle\Exception\InvalidPropertyUserException;
use RCH\JWTUserBundle\Exception\UserException;
use RCH\JWTUserBundle\Validator\Constraints\Email;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use UserBundle\Entity\User;

/**
 * Security Controller.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class SecurityController extends Controller
{
    /**
     * Register a new User and authenticate it.
     *
     * @return object The authentication token
     */
    public function registerAction()
    {
        $paramFetcher = $this->get('rch_jwt_user.credential_fetcher');
        $userManager = $this->container->get('fos_user.user_manager');

        $paramFetcher->create(array(
            'email'    => array(
                'requirements' => array(new Email(), new UniqueEntity('email')),
                'class'        => new User(),
            ),
            'password' => array('requirements' => '[^/]+'),
        ));

        $data = $paramFetcher->all();

        $user = $this->createUser($data);

        return $this->generateToken($user, 201);
    }

    /**
     * Processes user authentication from email/password.
     *
     * @return JsonResponse The authentication token
     */
    public function loginAction()
    {
        throw new \RuntimeException('You must configure the check path to be handled by the firewall using form_login in your security firewall configuration.');
    }

    /**
     * Registers and authenticates User from a facebook OAuth Response.
     *
     * @return object The authentication token
     */
    public function loginFromOAuthResponseAction()
    {
        $paramFetcher = $this->get('rch_jwt_user.credential_fetcher');
        $userManager = $this->container->get('fos_user.user_manager');

        $paramFetcher->create(array(
            'email'                 => array('requirements' => new Email()),
            'facebook_id'           => array('requirements' => '\d+'),
            'facebook_access_token' => array('requirements' => '[^/]+'),
        ));

        $data = $paramFetcher->all();

        if (true !== $this->isValidFacebookAccount($data['facebook_id'], $data['facebook_access_token'])) {
            throw new InvalidPropertyUserException(422, 'The given facebook_id does not correspond to a valid acount');
        }

        $existingByFacebookId = $userManager->findUserBy(array('facebookId' => $data['facebook_id']));

        if (is_object($existingByFacebookId)) {
            return $this->generateToken($existingByFacebookId);
        }

        $existingByEmail = $userManager->findUserBy(array('email' => $data['email']));

        if (is_object($existingByEmail)) {
            $existingByEmail->setFacebookId($data['facebook_id']);
            $userManager->updateUser($existingByEmail);

            return $this->generateToken($existingByEmail);
        }

        $data['password'] = $this->generateRandomPassword();

        return $this->generateToken($this->createUser($data));
    }

    /**
     * Creates a new User.
     *
     * @param array $data
     * @param bool  $isOAuth
     *
     * @return UserInterface $user
     */
    protected function createUser(array $data)
    {
        $userManager = $this->container->get('fos_user.user_manager');

        $user = $userManager->createUser()
            ->setUsername($data['email'])
            ->setEmail($data['email'])
            ->setEnabled(true)
            ->setPlainPassword($data['password']);

        if (isset($data['facebook_id'])) {
            $user->setFacebookId($data['facebook_id']);
        }

        try {
            $userManager->updateUser($user);
        } catch (\Exception $e) {
            $message = $e->getMessage() ?: 'An error occured while creating the user.';

            throw new UserException(422, $message, $e);
        }

        return $user;
    }

    /**
     * Generates a JWT from given User.
     *
     * @param UserInterface $user
     * @param int           $statusCode
     *
     * @return array Response body containing the User and its tokens
     */
    protected function renderToken(UserInterface $user, $statusCode = 200)
    {
        $body = array(
            'token'         => $this->container->get('lexik_jwt_authentication.jwt_manager')->create($user),
            'refresh_token' => $this->attachRefreshToken($user),
            'user'          => $user->getUsername(),
        );

        return new JsonResponse($body, $statusCode);
    }

    /**
     * Provides a refresh token.
     *
     * @param UserInterface $user
     *
     * @return string The refresh Json Web Token.
     */
    protected function attachRefreshToken(UserInterface $user)
    {
        $refreshTokenManager = $this->container->get('gesdinet.jwtrefreshtoken.refresh_token_manager');
        $refreshToken = $refreshTokenManager->getLastFromUsername($user->getUsername());
        $refreshTokenTtl = $this->container->getParameter('gesdinet_jwt_refresh_token.ttl');

        if (!$refreshToken instanceof RefreshToken) {
            $refreshToken = $refreshTokenManager->create();
            $expirationDate = new \DateTime();
            $expirationDate->modify(sprintf('+%s seconds', $refreshTokenTtl));
            $refreshToken->setUsername($user->getUsername());
            $refreshToken->setRefreshToken();
            $refreshToken->setValid($expirationDate);

            $refreshTokenManager->save($refreshToken);
        }

        return $refreshToken->getRefreshToken();
    }

    /**
     * Verifiy facebook account from id/access_token.
     *
     * @param int    $facebookId          Facebook account id
     * @param string $facebookAccessToken Facebook access_token
     *
     * @return bool Facebook account status
     */
    protected function isValidFacebookAccount($id, $accessToken)
    {
        $client = new \Goutte\Client();
        $client->request('GET', sprintf('https://graph.facebook.com/me?access_token=%s', $accessToken));
        $response = json_decode($client->getResponse()->getContent());

        if ($response->error) {
            throw new InvalidPropertyUserException($response->error->message);
        }

        return $response->id == $id;
    }

    /**
     * Generates a random password of 8 characters.
     *
     * @return string
     */
    protected function generateRandomPassword()
    {
        $tokenGenerator = $this->container->get('fos_user.util.token_generator');

        return substr($tokenGenerator->generateToken(), 0, 8);
    }
}
