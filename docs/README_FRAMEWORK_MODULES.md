
# web-T::Framework


## Modules

Modules are much comlex shared helpers. They are can have administrative methods and their own templates. 

Each *Module* must extends the `oModule` class. You may have your own config for *Module* in the **[MODULE_DIR]/etc/**.

There are a lot of methods in the `oModule` class, please, look at the **@PHPDoc**.

You can get access to the module, by calling `Module` method from core:

```
$p->Module([BUNDLE_NAME]:[MODULE_NAME]);
```
or, if you call it from default location:

```
$p->Module([MODULE_NAME]);
```

#### [Back to the Table of Contents](../README_FRAMEWORK.md)