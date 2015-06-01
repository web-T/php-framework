# web-T::Framework
## Queues

Framework supports queues of the task with different priorities. Tasks can be performed either immediately or deferred. You can control execution mode by configuration options `$INFO['jobs']['mode']` (now supports `normal`, or `cron`).
To add task to the queue use this method:

```
$job_id = $p->jobs->add([PHP_CODE], [TIMESTAMP_TO_RUN], [PRIORITY], [EVENT_TYPE], [is_send_email_on_done], [is_forced_background]);
```
Queue use `CoreJob` model, so you can customize its storage.

To run full queue simply call:

```
$p->jobs->runQueue();
```
To run seleted job use this method:

```
$p->jobs->run([JOB_ID]);
```



#### [Back to the Table of Contents](../README_FRAMEWORK.md)