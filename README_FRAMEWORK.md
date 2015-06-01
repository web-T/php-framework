# web-T::Framework

## Overview

**web-T::Framework** was based on "handmade" code. It includes many ideas from Symfony, but, it has special features (like, oldschool code), which make life easier.

We have wrote or own CMS on it (with additional bundles, like Shop, Forum, etc.). So, we have tested it on the real projects.

## System requirements

Minimum requirements:

* Unix compatible OS (Windows is not supported)
* PHP >= 5.5
* MySQL >= 5.1 or PostGREs (but supports other databases)
* GD lib or ImageMagick lib on the server
* 2 MB on HDD
* Apache or Nginx as a web servers (.htaccess included)
* 4-8 MB of RAM (but 32 MB would be much better)

Additional requirements:

* Any memory cache (Memcache, APC, Eaccelerator, etc.)


## Features

* Liteweight and fast
* Built-in multi-language support
* Extensible and scalable (i.e. you can simply move application servers on several nodes)
* Multiple-storages support
* Multi-level cache support
* Supports multi-thread execution (for some tasks)
* Supports events queues
* Console application support
* Built-in API 
* Composer ready


## Table of contents

1. [Install and configure](docs/README_FRAMEWORK_INSTALL.md)
2. [Core features](docs/README_FRAMEWORK_CORE.md)
  	1. [Debugging](docs/README_FRAMEWORK_DEBUG.md)
  	2. [Events](docs/README_FRAMEWORK_EVENTS.md)
  	3. [Routing. Requests, Queries and Responses](docs/README_FRAMEWORK_ROUTING.md)
  	4. [Cache](docs/README_FRAMEWORK_CACHE.md)
  	5. [Storage](docs/README_FRAMEWORK_STORAGE.md)
  	6. [Templating](docs/README_FRAMEWORK_TEMPLATING.md)
  	7. [Queues](docs/README_FRAMEWORK_QUEUE.md)
  	8. [Authentication and authorization](docs/README_FRAMEWORK_USER.md)
  	9. [Console](docs/README_FRAMEWORK_CONSOLE.md)
  	10. [API and cluster building](docs/README_FRAMEWORK_API.md)
  	11.  [Multi-threading tasks](docs/README_FRAMEWORK_THREADS.md)
3. [Helpers](docs/README_FRAMEWORK_HELPERS.md)
4. [Apps](docs/README_FRAMEWORK_APPS.md)
5. [Services](docs/README_FRAMEWORK_SERVICES.md)
6. [Modules](docs/README_FRAMEWORK_MODULES.md)
7. [Bundles](docs/README_FRAMEWORK_BUNDLES.md)
8. [Work with forms](docs/README_FRAMEWORK_FORMS.md)
9. [Assets](docs/README_FRAMEWORK_ASSETS.md)
10. [Create simple application](docs/README_FRAMEWORK_EXAMPLE.md)



