
# web-T::Framework


## Templators

Default templator of the framework is Smarty2. It has many plugins of the frontend application, so, if you want to use your own templator you should migrate all plugins to it.
The basic methods of the templator are:

* to add variables 

```
$p->tpl->addToken([TOKEN_NAME] => [VARIABLE]);
```
* to add template

```
$p->tpl->add([TEMPLATE_NAME], [FILE_NAME]);
```
* to get executed template

```
$p->tpl->get([TEMPLATE_NAME]);
```

Default pathes for templates are: **[DOC_DIR]/skin/project** (now it is deprecated), and **[BUNDLES_DIR]/frontend/views**, but you can call templates from another bundle (from its **views** directory):

```
$p->tpl->get([BUNDLE_NAME]:[TEMPLATE_NAME]);
```
or, from another base path:

```
$p->tpl->add([TEMPLATE_NAME], [FILE_NAME], $is_main_page, $is_var, 'www/skin/Modules/oRating/skin/');
```
or from any variable:

```
$p->tpl->add([TEMPLATE_NAME], 'Some variable {%$VAR%}', $is_main_page, true);
```

#### [Back to the Table of Contents](../README_FRAMEWORK.md)
 
