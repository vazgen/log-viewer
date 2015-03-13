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
