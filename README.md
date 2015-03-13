W3docsLogviewerBundle
======================

## Installation
-----------------------

### Step1: Download W3docsLogviewerBundle using composer

Add W3docsLogviewerBundle in your composer.json:

```js
{
    "require": {
        "w3docs/log-viewer-bundle": "dev-master"
    }
}
```

Now update composer.

Composer will install the bundle to your project's `vendor/w3docs` directory.

### Step 2: Enable the bundle

Enable the bundle in the kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new W3docs\LogViewerBundle\W3docsLogViewerBundle()
    );
}
```

### Usage
show logs from prod.log level error or higher (CRITICAL, ALERT, EMERGENCY) 

    app/console log:view prod.log --level=error
    
show logs for given chanal "security"

    app/console log:view prod.log --logger=security
    
group errors and order then, this will allow to find most occurred logs

    app/console log:view prod.log --group
    
group and limit result to 10 (log must be at least 10 times or any other given number). 

    app/console log:view prod.log --group --having=10
    
filtering by start and/or end date

    app/console log:view prod.log --start=01-03-2015 --end=05-03-2015
