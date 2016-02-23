<?php

/**
 * This file is part of the RCHJWTUserBundle package.
 *
 * Robin Chalas <robin.chalas@gmail.com>
 *
 * For more informations about license, please see the LICENSE
 * file distributed in this source code.
 */
namespace RCH\JWTUserBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\UserBundle\Model\UserInterface;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken;
use RCH\JWTUserBundle\Exception\UserAlreadyExistsException;
use RCH\JWTUserBundle\Util\SerializableTrait as CanSerialize;
use RCH\JWTUserBundle\Validator\Constraints\Email;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Security Controller.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class SecurityController extends Controller
{
    use CanSerialize;

    /**
     * Register a new User and authenticate it.
     *
     * @Rest\Post("/register")
     *
     * @Rest\View(statusCode=201)
     * @Rest\RequestParam(name="email", requirements=@Email, nullable=false, allowBlank=false)
     * @Rest\RequestParam(name="password", requirements="[^/]+", nullable=false, allowBlank=false)
     *
     * @param ParamFetcher $paramFetcher
     *
     * @return object The authentication token
     */
    public function registerAction(ParamFetcher $paramFetcher)
    {
        $data = $paramFetcher->all();
        $userManager = $this->container->get('fos_user.user_manager');

        if ($userManager->findUserByEmail($data['email']) !== null) {
            throw new UserAlreadyExistsException(sprintf('An user with email \'%s\' already exists', $data['email']));
        }

        return $this->generateToken($this->createUser($data));
    }

    /**
     * Processes user authentication from email/password.
     *
     * @Rest\Post("/login")
     *
     * @Rest\RequestParam(name="email", requirements=@Email, nullable=false, allowBlank=false)
     * @Rest\RequestParam(name="password", requirements="[^/]+", nullable=false, allowBlank=false)
     *
     * @return object The authentication token
     */
    public function loginAction()
    {
        /* Virtual method originally handled by Security Component */
    }

    /**
     * Register/Authenticate user from OAuth Response.
     *
     * @Rest\Post("/oauth/login")
     *
     * @Rest\RequestParam(name="email", requirements=@Email, nullable=false, allowBlank=false)
     * @Rest\RequestParam(name="facebook_id", requirements="\d+", nullable=false, allowBlank=false)
     * @Rest\RequestParam(name="facebook_access_token", requirements="[^/]", nullable=false, allowBlank=false)
     *
     * @param ParemFetcher $paramFetcher
     *
     * @return object The authentication token
     */
    public function loginFromOAuthResponseAction(ParamFetcher $paramFetcher)
    {
        $data = $paramFetcher->all();
        $userManager = $this->container->get('fos_user.user_manager');

        if (false === $this->isValidFacebookAccount($data['facebook_id'], $data['facebook_access_token'])) {
            throw new UnprocessableEntityHttpException('The given id has no valid facebook account associated');
        }

        $existingByFacebookId = $userManager->findUserBy(['facebookId' => $data['facebook_id']]);

        if (is_object($existingByFacebookId)) {
            return $this->generateToken($existingByFacebookId);
        }

        $existingByEmail = $userManager->findUserBy(['email' => $data['email']]);

        if (is_object($existingByEmail)) {
            $existingByEmail->setFacebookId($data['facebook_id']);
            $userManager->updateUser($existingByEmail);

            return $this->generateToken($existingByEmail);
        }

        $data['password'] = $this->generateRandomPassword();

        return $this->generateToken($this->createUser($data, true));
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

        $userManager->updateUser($user);

        return $user;
    }

    /**
     * Generates a JWT from given User.
     *
     *
     * @param UserInterface $user
     * @param int           $statusCode
     *
     * @return array Response body containing the User and its tokens
     */
    protected function generateToken(UserInterface $user)
    {
        $response = array(
            'token'         => $this->container->get('lexik_jwt_authentication.jwt_manager')->create($user),
            'refresh_token' => $this->attachRefreshToken($user),
            'user'          => $user->getUsername(),
        );

        return $response;
    }

    /**
     * Provides a refresh token.
     *
     * @param UserInterface $user
     *
     * @return string refresh_token
     */
    protected function attachRefreshToken(UserInterface $user)
    {
        $refreshTokenManager = $this->container->get('gesdinet.jwtrefreshtoken.refresh_token_manager');
        $refreshToken = $refreshTokenManager->getLastFromUsername($user->getUsername());
        $refreshTokenTtl = $this->container->getParameter('gesdinet_jwt_refresh_token.ttl');

        if (!$refreshToken instanceof RefreshToken) {
            $expirationDate = new \DateTime();
            $expirationDate->modify(sprintf('+%s seconds', $refreshTokenTtl));

            $refreshToken = $refreshTokenManager->create();
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
            throw new UnprocessableEntityHttpException($response->error->message);
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
