The Oro Platform - Business Application Platform (BAP)
======================================================

The platform is based on the Symfony 2 framework.

This repository contains base bundles forming the Oro Platform (BAP) which allows to easily create new custom business applications.

Installation
------------

```bash
git clone https://github.com/orocrm/platform.git

curl -s https://getcomposer.org/installer | php

php composer.phar install
```

Run unit tests
--------------

To run unit tests of any bundnles :

```bash
phpunit
```

Use as dependency in composer
-----------------------------
Until it's a private repository and it's not published on packagist :

```yaml
    "require": {
        "oro/platform": "dev-master",
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/orocrm/platform.git",
            "branch": "master"
        }
    ],
```
Instant messaging between the browser and the web server
--------------------------------------------------------
To use this feature you need to configure parameters.yml websocket parameters and run server with console command

 ```bash
app/console clank:server
