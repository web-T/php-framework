
# web-T::Framework


## Core

Core of the framework consists of several parts, which are lazy loaded and initialized (eg. they are not loaded, until you will call them first time). All core parts are loaded via special `\webtFramework\Core\cProxy` class.


### Portal

Accumulates access to all parts of the framework, contains methods for initialization any of the applications (bundles). 

When it is constructed, it runs query parsing and detect current locale automatically, so you can get it by call

```
$p->getLangNick();
```

If there is storage with languages, the you can get current language id and languages list:

```
$p->getLangId();
$p->getLangs();
```

It is always has revert array of the languages (lang_nick => lang_id):

```
$p->getLangTbl();
```

You can always get translation of the any text message by calling:

```
$p->trans('any.of.array.message');
```
It is helper for `oLanguage` control class


You can get access to all config settings by use specified getter and setter:

```
$p->getVar('VAR');
$p->setVar('VAR', 'VALUE');
```

You can access to the storage settings and change them in the storage, by call:

```
$p->Service('webtCMS:Core')->getVal('VAR');
$p->Service('webtCMS:Core')->setVal('VAR', 'VALUE');
```



`oPortal` includes several methods for loading modules, controls, controllers, models and clips.

They are all pretty simple to use:

* for load a module, simply call

```
$p->Module('[MODULE_NAME]');
```
* for load a control, simply call

```
$p->Service('[CONTROL_NAME]');
```
* for load a controller, call

```
$p->App('[CONTROLLER_NAME]');
```
* for create a model, call

```
$p->Model('[MODEL_NAME]');
```

You need to know, that controls, modules and controllers are cached (but there are some exceptions to the rules). Some of them have special method `cleanup()` to reset state.
By default, all models, modules, controls and controllers loaded from framework and frontend bundle, but you can load them from another bundles by adding a bundlename before the base name:

```
$p->Module('[BUNDLE_NAME]:[MODULE_NAME]');
```

<br>
For administration bundle we have special methods:

* to get controller app you need call:

```
$p->admApp('[ADMIN_CONTROLLER_NAME]');
```

* to get admin control you need to call (they are situated at the **lib/Admin/**):

```
$p->admControl('[ADMIN_CONTROL_NAME]');
```




Framework always uses synchronised time for all operations during one request. It means, that core make a snapshot of the start time and you can get it by calling:

```
$p->getTime();
```

We think, that is would be best practice to do this in you code.



The core `oPortal` injected in the classes, so you can use it anywhere without any limitation. 

#### [Back to the Table of Contents](../README_FRAMEWORK.md)
