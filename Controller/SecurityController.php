<?php

/**
 * This file is part of RCH/JWTUserBundle.
 *
 * Robin Chalas <robin.chalas@gmail.com>
 *
 * For more informations about license, please see the LICENSE
 * file distributed in this source code.
 */
namespace RCH\JWTUserBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken;
use RCH\JWTUserBundle\Util\CanSerializeTrait as CanSerialize;
use RCH\JWTUserBundle\Validator\Constraints\Email;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
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
     * @Rest\View
     * @Rest\RequestParam(name="email", requirements=@Email, nullable=false, allowBlank=false)
     * @Rest\RequestParam(name="password", requirements="[^/]+", nullable=false, allowBlank=false)
     *
     * @param ParamFetcher $paramFetcher
     *
     * @return object The authentication token
     */
    public function registerUserAccountAction(ParamFetcher $paramFetcher)
    {
        $data = $paramFetcher->all();
        $userManager = $this->getUserManager();

        if ($userManager->findUserByEmail($data['email']) !== null) {
            throw new UnprocessableEntityHttpException(sprintf('An user with email \'%s\' already exists', $data['email']));
        }

        return $this->generateToken($this->createUser($data), 201);
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
    public function authenticateUserAction()
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
    public function authenticateByOAuthAction(ParamFetcher $paramFetcher)
    {
        $data = $paramFetcher->all();
        $userManager = $this->getUserManager();

        if (false === $this->isValidFacebookAccount($data['facebook_id'], $data['facebook_access_token'])) {
            throw new UnprocessableEntityHttpException('The given id has no valid facebook account associated');
        }

        $existingByFacebookId = $userManager->findUserBy(['facebookId' => $data['facebook_id']]);

        if (null !== $existingByFacebookId) {
            return $this->generateToken($existingByFacebookId, 200);
        }

        $existingByEmail = $userManager->findUserBy(['email' => $data['email']]);

        if (null !== $existingByEmail) {
            $existingByEmail->setFacebookId($data['facebook_id']);
            $userManager->updateUser($existingByEmail);

            return $this->generateToken($existingByEmail, 200);
        }

        $data['password'] = $this->generateRandomPassword();

        return $this->generateToken($this->createUser($data, true), 201);
    }

    /**
     * Reset expired Token.
     *
     * @Rest\Post("/refresh_token")
     *
     * @Rest\RequestParam(name="token", allowBlank=false, nullable=false)
     * @Rest\RequestParam(name="refresh_token", allowBlank=false, nullable=false)
     *
     * @param Request $request
     *
     * @return object The new authentication token
     */
    public function refreshTokenAction(Request $request)
    {
        return $this->forward('gesdinet.jwtrefreshtoken:refresh', array(
            'request' => $request,
        ));
    }

    /**
     * Creates new User.
     *
     * @param array  $data
     * @param string $username
     * @param string $password
     * @param bool   $isOAuth
     *
     * @return User $user
     */
    protected function createUser($data, $isOAuth = false)
    {
        $userManager = $this->getUserManager();
        $em = $this->getDoctrine()->getManager();

        $user = $userManager->createUser();
        $user->setUsername($data['email']);
        $user->setEmail($data['email']);
        $user->setEnabled(true);
        $user->setCreatedAt(new \DateTime());
        $user->setPlainPassword($data['password']);

        if (true === $isOAuth) {
            $user->setFacebookId($data['facebook_id']);
        }

        $userManager->updateUser($user);

        return $user;
    }

    /**
     * Generates token from user.
     *
     * @param User $user
     *
     * @return JsonResponse $token
     */
    protected function generateToken($user, $statusCode = 200)
    {
        $response = array(
            'token'         => $this->get('lexik_jwt_authentication.jwt_manager')->create($user),
            'refresh_token' => $this->attachRefreshToken($user),
            'user'          => array('email' => $user->getUsername()),
        );

        return new JsonResponse($response, $statusCode);
    }

    /**
     * Provides a refresh token.
     *
     * @param UserManager $user
     *
     * @return string refresh_token
     */
    protected function attachRefreshToken($user)
    {
        $refreshTokenManager = $this->get('gesdinet.jwtrefreshtoken.refresh_token_manager');
        $refreshToken = $refreshTokenManager->getLastFromUsername($user->getUsername());

        if (!$refreshToken instanceof RefreshToken) {
            $datetime = new \DateTime();
            $datetime->modify('+2592000 seconds');

            $refreshToken = $refreshTokenManager->create();
            $refreshToken->setUsername($user->getUsername());
            $refreshToken->setRefreshToken();
            $refreshToken->setValid($datetime);

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
        $request = $client->request('GET', sprintf('https://graph.facebook.com/me?access_token=%s', $accessToken));
        $response = json_decode($client->getResponse()->getContent());

        if ($response->error) {
            throw new UnprocessableEntityHttpException($response->error->message);
        }

        return $response->id == $id;
    }

    /**
     * Returns Entity Manager.
     *
     * @return EntityManager $entityManager
     */
    protected function getEntityManager()
    {
        return $this->getDoctrine()->getManager();
    }

    /**
     * Returns authentication provider.
     *
     * @return UserManager $userManager
     */
    protected function getUserManager()
    {
        return $this->get('fos_user.user_manager');
    }

    /**
     * Generates a random password of 8 characters.
     *
     * @return string
     */
    protected function generateRandomPassword()
    {
        $tokenGenerator = $this->get('fos_user.util.token_generator');

        return substr($tokenGenerator->generateToken(), 0, 8);
    }
}
