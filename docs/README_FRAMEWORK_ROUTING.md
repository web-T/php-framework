
# web-T::Framework

## Routing

 

System router consists of two parts (defined by their weights): 

1. Custom routes from files
2. Storage routes

**Custom files routes** must be defined in the *[bundle_name]/etc/routing.conf.php* file.

This file must have `$INFO['ROUTES']` array with routes items.

Instead, each route must consists of:

* `path` - pattern, which can be handled from request's URI
* `defaults` - array of settings, where you can define default values settings, such as:
	* `_controller` - default controller for handle request in format, it is can be either function, or string with full namespace to it (like a `Frontend:Default_controller:getGraphText`, where `Frontend` - the name of the bundle, `Default_controller` - controller name, `getGraphText` - method, which would be called)
	* `_format` - type of the request (support `json` or `html`),
	* any other parameter from pattern

* `requirements` - array of required parameters:
	* `_format` - type of the request (support `json` or `html`),
	* any other parameter from pattern
* `options` - array of any other options (not used yet)
* `host` - regexp for host variable
* `schemes` - array of regexps of possible schemes for this route, for example - `http`, `https`, `ftp`, etc.
* `methods`- array of possible methods for the request (ex. `get`, `post`, `delete`)

For example:

```
$INFO['ROUTES'] = array('get_image' => array(
        'path' => '/get_image/id/{id}/',
        'defaults' => array(
            '_controller' => 'Frontend:Default_controller:getImage'
        ),
        'requirements' => array(
            'id' => '\d+'
        )
    )
);
```
or use function instead of external controller:


```
$INFO['ROUTES'] = array(
	'get_file' => array(
        'path' => '/get_file/id/{id}',
        'defaults' => array(
            '_controller' => function(oPortal $p, $id){

                if ($id != '' && $p->user->checkUploadRules($id)){
                    webtParser::getDBFile($p, $id);
                } else {
                    $p->Service('webtCMS:Core')->redirect404();
                }
            }
        ),
        'requirements' => array(
            'id' => '.+'
        )
    )
);
```

You can add route at any time, by create Route instance:

```
$p->query->addRoute('[ROUTE_NAME]', new \webtFramework\Components\Request\oRoute(
    '[ROUTE_REGEXP]',
    '[ROUTE_QUERY_ARRAY]',
    [ROUTE_PARAMS]
));

```


When you are using custom routes you can define your default route for all other queries:

```
$p->query->addRoute('__default__', new \webtFramework\Components\Request\oRoute(
    '.*',
    null,
    array('_controller' => '[CONTROLLER_NAME]')
));

```

**Storage routes** based on the **webt_pages** collection and linked with the *nick* field. When framework parsing request URI it is creating structure like this:

```
array('page' => [PAGE_NAME], [PARAM1] => [VALUE1], [PARAM2] => [VALUE2]) 

```

So, if the request is */faq/method/get_elems/* core will parses it to the

```
array('page' => 'faq', 'method' => 'get_elems')

```

You can simply build request URI for this scheme, by calling:

```
$p->query->build(array('page' => [PAGE_NAME], [PARAM1] => [VALUE1], [PARAM2] => [VALUE2]));
```
Core adds language parameter to the query automatically (if framework setuped to multilanguage mode)

Of course, there is special method for parsing query:

```
$p->query->parse([REQUEST_URI]);
```

To start routing you need to initialize request instance and call `route` method:

```
$p->query->request->createFromGlobals();

$p->query->route();

```

#### [Back to the Table of Contents](../README_FRAMEWORK.md)
