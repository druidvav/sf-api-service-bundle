Api Service Symfony Bundle
=======================

Installation
------------

### Step 1: Download DvApiServiceBundle using composer

Require the `druidvav/api-service-bundle` with composer [Composer](http://getcomposer.org/).

```bash
$ composer require druidvav/api-service-bundle
```

### Step 2: Enable the bundle

Enable the bundle in the kernel:

```php
<?php

// app/AppKernel.php
public function registerBundles()
{
    $bundles = array(
        // ...
            new Druidvav\ApiServiceBundle\DvApiServiceBundle(),
        // ...
    );
}
```

### Step 3: Configure the DvApiServiceBundle


```yml
# app/config/config.yml

dv_api_service:
    logger: "@monolog.logger.api"

```

### Step 4:  Define your api service files


```yml
# src/AppBundle/Resources/config/services.yml

services:
    _defaults:
        autowire: true      # Automatically injects dependencies in your services.
        autoconfigure: true # Automatically registers your services as commands, event subscribers, etc.

    AppBundle\ApiService\:
        resource: '../../ApiService/*'
        tags: [ "jsonrpc.api-service" ]
```
