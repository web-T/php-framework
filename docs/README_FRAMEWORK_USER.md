# web-T::Framework

## Authorization and authentication

Framework use separate authorization for frontend and backend applications.
Before work with user data you need to initialize user's core:

```
$p->user->init([IS_ADMIN_FLAG]);
```

After initialize you can:

* check if frontend user authed:

```
$p->user->isAuth();
```

* check if backend user authed:

```
$p->user->isAdminAuth();
```
* auth user like anonymouse:

```
$p->user->authAnonymous();
```
* authorize user with storage data:

```
$p->user->auth([USERNAME], [PASSWORD]);
```
* unauthorize user:

```
$p->user->unauth([IS_ADMIN_FLAG]);
```

* get user's data:

```
$p->user->getData();
```
* get user's ID:

```
$p->user->getId();
```

There are lot of methods, which you can explore in our **@PHPDoc**. 
Framework does not lock session and always closes it after doing some work. 


#### [Back to the Table of Contents](../README_FRAMEWORK.md)