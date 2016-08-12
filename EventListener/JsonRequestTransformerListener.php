<?php

/*
 * This file is part of the RCH package.
 *
 * (c) Robin Chalas <https://github.com/chalasr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace RCH\JWTUserBundle\EventListener;

use Qandidate\Common\Symfony\HttpKernel\EventListener\JsonRequestTransformerListener as BaseListener;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Transforms the body of a json request to POST parameters only for rch_jwt_user routes.
 */
class JsonRequestTransformerListener extends BaseListener
{
    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (0 === strpos($event->getRequest()->attributes->get('_route'), 'rch_jwt_user')) {
            parent::onKernelRequest($event);
        }
    }
}
