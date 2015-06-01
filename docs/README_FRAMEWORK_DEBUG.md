# web-T::Framework
## Debugger

Debugger logs errors and custom information into log files (which situated in the /var/log directory) and collect system status at each step of its call.

It is always collects all storage queries (if they executed throw `$p->db->...` methods).

By default - there are five log levels (which are defined in the **etc/common.conf.php** at `$INFO['debugger']` section):

* error - collects all errors in the app (especialy, from storage queries)
* db - collects sql queries
* app - collects controllers info
* parser - collects any routes logs
* autoloader - autoloader logs

You can define your own log levels. Each log file is written on current environment's level.

It is very simply in use. You need such to define log level while you something loggin:

```
$p->debug->log("SOMETHING TO LOG", LOG_TYPE);
```
, where the LOG_TYPE is the necessary log level.

You can always add status report for particulary step of your code:

```
$p->debug->add("ANY STATUS MESSAGE", array('error' => array('message' => 'SOMETHING_WRONG')));
```
It will be displayed in the browser's debug pannel with highlited error message (it is not necessarily)





#### [Back to the Table of Contents](../README_FRAMEWORK.md)