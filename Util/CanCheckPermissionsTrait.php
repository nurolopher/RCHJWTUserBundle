<?php

/**
 * This file is part of RCH/JWTUserBundle.
 *
 * Robin Chalas <robin.chalas@gmail.com>
 *
 * For more informations about license, please see the LICENSE
 * file distributed in this source code.
 */
namespace RCH\JWTUserBundle\Util;

/**
 * Add methods for read users permissions.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
trait CanCheckPermissionsTrait
{
    /**
     * Get security authorization_checker.
     *
     * @return object
     */
    protected function getRolesManager()
    {
        return $this->container->get('security.authorization_checker');
    }

    /**
     * Get current authenticated user.
     *
     * @return User|null
     */
    protected function getCurrentUser()
    {
        $user = $this->container->get('security.context')->getToken()->getUser();

        if (!is_object($user)) {
            throw new NotFoundHttpException('There is no authenticated user');
        }

        return $user;
    }

    /**
     * Check if user has ROLE_ADMIN.
     *
     * @return bool
     */
    protected function isAdmin()
    {
        $rolesManager = $this->getRolesManager();

        return $rolesManager->isGranted('ROLE_ADMIN') || $rolesManager->isGranted('ROLE_SUPER_ADMIN');
    }

    /**
     * Check if user has ROLE_GUEST.
     *
     * @return bool
     */
    protected function isGuest()
    {
        $rolesManager = $this->getRolesManager();

        return $rolesManager->isGranted('ROLE_GUEST') && !$rolesManager->isGranted('ROLE_ADMIN');
    }

    /**
     * Check if user is the current user.
     *
     * @param User $user
     *
     * @return bool
     */
    protected function isCurrentUser($user)
    {
        return $user->getId() == $this->getCurrentUser()->getId();
    }

    /**
     * Check if user is the current user.
     *
     * @param User $user
     *
     * @return bool
     */
    protected function isCurrentUserId($id)
    {
        return $id == $this->getCurrentUser()->getId();
    }
}
