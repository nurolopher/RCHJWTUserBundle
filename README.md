RCH/JWTUserBundle
=================

Integration of LexikJWTAuthentication + FOSUser bundles that provides a REST users management through JSON Web Token.

What's inside
-------------

- [__FOSUserBundle__]()
- __FOSRestBundle__
- __LexikJWTAuthenticationBundle__
- __GesdinetRefreshTokenBundle__
- __JMSSerializerBundle__

Installation
------------

#### Step 1: Download the Bundle


Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require chalasr/jwt-user-bundle dev-master
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

#### Step 2: Enable the Bundle

> __Note:__ This bundle requires 3rd party bundles that need to be registered too.

Then, enable the bundles by adding them to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new FOS\RestBundle\FOSRestBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new FOS\UserBundle\FOSUserBundle(),
            new Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle(),
            new Gesdinet\JWTRefreshTokenBundle\GesdinetJWTRefreshTokenBundle(),
            new RCH\JWTUserBundle\RCHJWTUserBundle(),
        );

        // ...
    }

    // ...
}
```

Contributing
============

See the contribution guidelines in the [CONTRIBUTING.md](CONTRIBUTING.md) distributed file.

License
-------

The code is released under the GPL-3.0 license.

For the whole copyright, see the [LICENSE](LICENSE) file.
