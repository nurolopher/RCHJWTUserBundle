[![SensioLabsInsight](https://insight.sensiolabs.com/projects/021204ec-db7b-44d1-8d84-9bd9cd3a9ded/mini.png)](https://insight.sensiolabs.com/projects/021204ec-db7b-44d1-8d84-9bd9cd3a9ded)
[![StyleCI](https://styleci.io/repos/49818109/shield)](https://styleci.io/repos/49818109)
[![Build Status](https://travis-ci.org/chalasr/RCHJWTUserBundle.svg?branch=master)](https://travis-ci.org/chalasr/RCHJWTUserBundle)

RCH/JWTUserBundle
=================

Manages users through JSON Web Token in your REST Api.

What's inside
-------------

- [__FOSUserBundle__](https://github.com/FriendsOfSymfony/FOSUserBundle)
- [__LexikJWTAuthenticationBundle__](https://github.com/lexik/LexikJWTAuthenticationBundle)
- [__GesdinetRefreshTokenBundle__](https://github.com/gesdinet/JWTRefreshTokenBundle)

Requirements
------------

- PHP 5.4+
- Symfony 2.8+

__Note__ This branch requires `friendsofsymfony/user-bundle` in versions `~1.3`. For FOSUser `~2.0`, use the master branch.

Installation
------------

#### 1) Download the bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require rch/jwt-user-bundle dev-master
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

#### 2) Enable the Bundle

> __Note:__ This bundle requires 3rd party bundles that need to be registered too.

Then, enable the bundles by adding them to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
// app/AppKernel.php

<?php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new RCH\JWTUserBundle\RCHJWTUserBundle(),
            new FOS\UserBundle\FOSUserBundle(),
            new Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle(),
            new Gesdinet\JWTRefreshTokenBundle\GesdinetJWTRefreshTokenBundle(),
        );

        // ...
    }

    // ...
}
```

Configuration
--------------

#### 1) Create your User class

Your user class needs to extend the `RCH\JWTUserBundle\Entity\User` MappedSuperclass.

Example:

```php
<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use RCH\JWTUserBundle\Entity\User as BaseUser;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    public function __construct()
    {
        parent::__construct();
        // your own logic
    }
}
```

#### 2) Set the bundle configuration

Configure it:

```yaml
# app/config/config.yml
rch_jwt_user:
    user_class: AppBundle\Entity\User # your user class (required)
    user_identity_field: email        # the property used as authentication credential (tied to password)
    passphrase: foobar                # the passphrase of your RSA private key
```

Load the routing:

```yaml
# app/config/routing.yml
rch_jwt_user:
    resource: "@RCHJWTUserBundle/Resources/config/routing.yml"
    # Set a prefix if you want i.e. prefix: /api
```

Set the security config accordingly, example:

```yaml
security:
    providers:
        fos_userbundle:
            id: fos_user.user_provider.username_email

    encoders:
        FOS\UserBundle\Model\UserInterface: bcrypt

    role_hierarchy:
        ROLE_ADMIN:       ROLE_USER
        ROLE_SUPER_ADMIN: ROLE_ADMIN

    firewalls:
        dev:
            pattern:  ^/(_(profiler|wdt)|css|images|js)/
            security: false
        # Signin
        login:
            pattern:  ^/login
            stateless: true
            anonymous: true
            form_login:
                provider: fos_userbundle
                check_path: /login
                require_previous_session: false
                username_parameter: email
                password_parameter: password
                success_handler: lexik_jwt_authentication.handler.authentication_success
                failure_handler: lexik_jwt_authentication.handler.authentication_failure
        # Signup
        register:
            pattern: ^/register
            anonymous: true
            stateless: true
        # Refresh token
        refresh:
            pattern:  ^/refresh_token
            stateless: true
            anonymous: true
        # API (secured via JWT)
        api:
            pattern:   ^/
            stateless: true
            lexik_jwt: ~

    access_control:
        - { path: ^/register, role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/login, role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/refresh_token, role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/, role: IS_AUTHENTICATED_FULLY }
```

#### 3) Generate RSA keys for signing/verifying JWT tokens

```bash
$ php app/console rch:jwt:generate-keys
```
__Note__ This command generates keys using the configured passphrase.

Usage
------
 
Register users via the `/register` route:

```bash
$ curl -X POST http://localhost:8000/register -d username=johndoe -d password=test
```

Get a JWT token via the `/login` route:

```bash
$ curl -X POST http://localhost:8000/login -d username=johndoe -d password=test
```

Refresh the token once it expires via the `/refresh_token` route:

```bash
$ curl -X POST http://localhost:8000/refresh_token -d token=[the expired token] -d refresh_token=[the refresh_token]
```

Use the token to access secured resources:

```bash
$ curl -H "Authorization: Bearer [token here]" http://localhost:8000
```


__Note__ The refresh token is provided in the response of a successful login, in the same time as you get the token.

__Note2__ Each time `username` is used in the previous examples, it must be replaced by the `username_parameter` value, set into the secured firewall's mapping.

__Note3__ For the password field, it must be replaced by the `password_parameter` value.

Contributing
------------

See the contribution guidelines in the [CONTRIBUTING.md](CONTRIBUTING.md) distributed file.

License
-------

The code is released under the GPL-3.0 license.

For the whole copyright, see the [LICENSE](LICENSE) file.
