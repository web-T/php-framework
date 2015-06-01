# web-T::Framework
## Installation and configuration


### How to install

First of all you need to download framework from the GitHub.

You will find following files and directories in the folder:

* **app** - framework application directory
* **composer.json** - [Composer](https://packagist.org/) configuration file
* **docs** - documentations directory
* **src** - your applications directory (with Test bundle)
* **vendor** - vendor installations directory
* **www** - default document root directory

Now you need to create **var** dir in root folder and (if you want to use web-based application) give web-server rights to write to it. This directory will be used by framework for cache, migrations, temporary data, etc (but you will be able to configure all those settings).

Go to the **app/etc/** folder and rename file **common.conf.example.php** to **common.conf.php**.
**!IMPORTANT!** Change 'salt' parameter at the **common.conf.php** to any value.

it is time to install all dependencies for Composer. Go to the root folder and execute in your console (but, before that you can configure all dependencies in the **composer.json** file):

```
php composer.phar install
```

**Congratulations! You have installed framework** :D
  
Now we start some magic...  

     


### Configure 
The main configuration file is **etc/common.conf.php**. 
Additionaly (we must named them now), framework has several config files:

* common.conf.php
* assets.conf.php
* routes.conf.php

All of them described in etc/common.conf.php, so you can change their names (if you want).

Each file can be overrided by bundles configurations.

#### common.conf.php
Base configuration for whole application and for [bundles](README_FRAMEWORK_BUNDLES.md). 


There are a lot of  configure options. 
First of all you need to define base environment. You can simply define it in the common.conf.php, or create special environment file, called **environment**, at the **app/etc/** folder with content:

```
<?php
define('WEBT_ENV', 'production');
```
That's all. Framework will read it on startup and set the current environment to the *production*.

Default environment is **debug**.

One, of the most powerfull settings is the `is_dev_env` switcher. When it is switched to `true`, then framework switches current environment to the development mode and starts to grab debug information.

You can define another document root directory `DOC_DIR`, and whole application directory `APP_DIR` for your environment (but you have to remember that when you change the `APP_DIR` then you will have to change the pathes in the kernel files in `DOC_DIR`).

If you look to the config file you will see, that you can change any folder in it, select services for core parts, etc. 

You can always overwrite all settings by define special model in ['core']['settings'] section and mapping fields in it.


#### routes.conf.php

File with routes configs. We will leran how to work with the routes in special [chapter](README_FRAMEWORK_ROUTING.md).

#### assets.conf.php

Defines assets configuration. We will get to this topic in [Assets chapter](README_FRAMEWORK_ASSETS.md).






#### [Back to the Table of Contents](../README_FRAMEWORK.md)