# RCH/JWTAuthenticationBundle

Make deployment a part of your development environment by :
- Setup a fast and automated deployment workflow
- Create stagings in config format (YAML, PHP, XML)
- Control execution order by namespaces
- Add custom tasks and environment variables.

Requirements
============

- Symfony >= 2.3
- FOSUserBundle >= 1.3

Installation
============

Step 1: Download the Bundle
---------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require chalasr/capistrano-bundle dev-master
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Step 2: Enable the Bundle
-------------------------

Then, enable the bundle by adding it to the list of registered bundles
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

            new RCH\JWTAuthenticationBundle\RCHJWTAuthenticationBundle(),
        );

        // ...
    }

    // ...
}
```

Usage
======

**Coming soon**

Credits
=======

[Robin Chalas](https:/github.com/chalasr) -  [robin.chalas@gmail.com](mailto:robin.chalas@gmail.com)

License
=======

[![License](http://img.shields.io/:license-gpl3-blue.svg)](http://www.gnu.org/licenses/gpl-3.0.html)
