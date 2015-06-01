# web-T::Framework
## Bundles (or Applications)

 
Section is under construction

Bundles - is the complexed applications with their own controllers, modules, models, controls, messages, temlates, etc. 

You can define you own settings for the bundle in its **etc/** directory, overwriting global framework settings.

Always, you can define you own routes. 

All bundles located in the **src/** directory. 

You can create kernel file in **[DOC_DIR]/** and use it to start you own bunle.





You may ask us: "How to overrides environment from any application loader?". The answer is pretty simple: you need to define default application name (`WEBT_APP`), BEFORE you will include bootstrap file:

```
<?php

use webtFramework\Core\oPortal;

define('WEBT_APP', 'webtBackend');

/**
* include bootstrap
*/
include('../app/common.php');

```


Another dynamic way to initialize bundle in your code: 

```
$p->initApplication('[APPLICATION_NAME]');
```

#### [Back to the Table of Contents](../README_FRAMEWORK.md)
