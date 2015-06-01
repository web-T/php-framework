
# web-T::Framework


## Services

Controls - are simple shared helpers. They can do any work and consist only  a single file. You can get access to them, by calling `Service` method from  core:

```
$p->Service([BUNDLE_NAME]:[SERVICE_NAME]);
```
or, if you call it from default location:

```
$p->Service([SERVICE_NAME]);
```

#### [Back to the Table of Contents](../README_FRAMEWORK.md)