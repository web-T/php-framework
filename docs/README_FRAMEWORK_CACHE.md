
# web-T::Framework

## Cache  

 
Framework has several types of the cache:

* compiled templates (by Smarty or another templator)
* serialized data (by any structures, collections schemas, etc.)
* compiled meta-data of the entities
* cached pages and page's blocks (called **clips**)
* cached static pages

Some of them can be used only on the frontend application, and another - when you turned on them at the administrator's backend.

Framework can use several types of the cache:

* files
* any shared memory interfaces (like, Memcache, APC)
* SQLite

To store serialized data, use:

```
$p->cache->saveSerial($fname, $content, $dir = null);
$p->cache->getSerial($fname, $dir = null, $time = 0);
$p->cache->removeSerial($fname = null, $dir = null);
```

Default method is `serialize`, but you can use `bittorent2` or `json` to store/restore data.

To work with compiled meta data, you can use:

```
$p->cache->saveMeta($tbl_name, $elem_id, $metadata = '', $lang_id = null);
$p->cache->getMeta($tbl_name, $elem_id, $metadata = '', $lang_id = null);
```
Another methods you can find in our **@PHPDoc**

#### [Back to the Table of Contents](../README_FRAMEWORK.md)
