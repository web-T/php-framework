
# web-T::Framework


## Storages

Storages are used to store and query data from the storage engines. It is good practice to create a **Model** an link it with the special storage/collection. Storage core uses DBAL for database connectivity, so you can replace one storage type to another without changing your code.

Storage core include instruments to get QueryBuilder (DBAL), model's manager, model's repositories. It is always includes methods for direct access to the storage engines. 

Current version of DBAL supports only Mysql and Postgres storages (because, there are enable drivers for them), but you can write your own drivers extending **oQueryBuilderAbstract** class and put them to the **lib/Components/Storage/** directory.

Model's manager can work with models (save, remove, update, init primary key etc.). You can get it by calling method:

```
$p->db->getManager();
```

Model's manager can load model's repository, which can finds models in their linked storages by conditions:

```
$repo = $p->db->getManager()->getRepository([MODELS_NAME]);

$repo->findBy(array(
	'where' => array('[PRIMARY]' => 1, 'lang_id' => $p->getLangId())
));

```

Conditions - is the DBAL metalanguage to work with models. Each condition is an array of:

* **select** - what you need to extract. It is must consists of current collection prefix (default: `'a'`), and array of the fields. Each result automatically groups by PRIMARY_KEY, but you can avoid it, by add `no_array_key` flag to the select condition, or add your own grouping key, by adding `__groupkey__` section with key name.
Of course, you can use some functions. Current version supports `max()`, `count()` and `lower()` functions, but - not each storage engine supports whole list. You can add an alias for seleced field, by add to the its condition `nick` attribute
* **index** - string or array of indexes, which must be used during quering
* **where** - very complex structure of the query, it can be or simple pair of `(key => value)`, then it compiles to the `key='value'` expression, or 

``` 
array(
	'table' => [COLLECTION_NAME], 
	'op' => [OPERATOR], 
	'value' => [VALUE], 
	'type' => [FIELD_TYPE], 
	'function' => [FUNCTION])
)
```
Each part of the where item depends on operator. Supported operators are: `=` (default operator), `in`, `not in`, `mva_in`, `between`, `like`, `not like`.  *Where* condition always supports functions: `bitsearch()`. It is always supports quering foreign feilds by set `type` of the expression to `foreign_key` value.

* **join** - defines joined collections (not all storage engines can support it). Each join expression must be defined by:

```
array(
	'method' => [JOIN_METHOD],
	'model' => [oModel instance ot model's name]
	'tbl_name' => [JOIN_TABLE] (if model not defined)
	'alias' => [JOIN_TABLE_ALIAS],
	'conditions' => [WHERE_CONDITIONS]
)
```
* **order** - expression for quering entities by selected order. it is simply array of *key => value* pairs, where *key* is the field name and *value* is the sort order (it can be `desc` or `asc`), but there are always extended syntax:

```
[ORDER_KEY] => array(
	'function' => [ORDER_FUNCTION],
	'table' => [ORDER_KEY_COLLECTION],
	'order' => [SORT_METHOD],
	'value' => [SORT_VALUE]
)
```
*Functions* list consists of `field()`, `rand()`, and you need to define `'value'` for `field()` function.

* **group** - grouping results of the query by array of field names. You can add collection name to each field by adding it with **dot** separator

One of most complex examples of the conditions query:


```
array(
	'select' => array(
		'a' => array('title', 'name', 'descr'),
		'b' => array('elem_id', 'tbl_name'),
		'__groupkey__' => 'b.elem_id'
	),
	'join' => array(
		array(
			'method' => 'left', 
			'model' => 'RatingElem', 
			'alias' => 'b', 
			'conditions' => array(
				array(
					'table' => 'a',
					'field' => 'real_id',
					'value' => 'b.elem_id',
					'type' => 'foreign_key'
				)
			)
		)
	)
	'where' => array(
		0 => array(
			'table' => 'a',
			'field' => 'date_add',
			'op' => 'between',
			'value' => array(100, 200)
		),
		1 => array(
			'table' => 'b',
			'field' => 'tbl_name',
			'value' => 'webt_news_tape',
		),
		'is_on' = 1,
		'lang_id' => 3
	),
	'order' => array(
		'value' => array(
			'table' => 'b',
			'order' => 'desc'
		),
		'date_add' => array(
			'table' => 'a',
			'order' => 'asc'
		),
		'title' => 'asc'
	),
	'group' => array('a.[PRIMARY]')
)
```

To compile conditions you must execute `compile` method on selected QueryBuilder (all storage types defined in the main **common.conf.php** file in the `$INFO['storages']` section):

```
$sql = $p->db->getQueryBuilder([STORAGE_TYPE])->compile($conditions);
```


To execute compiled query on the storage you must to define it as a second parameter:

```
$p->db->query($sql, [STORAGE_TYPE]);
```

If you don't know particular storage type of the known model, you can extract it like this:

```
$Model->getModelStorage();
```

## Models

Models - are the entities containers, which contains a lot of settings and data of the entity.

They are located at the **/lib/Models/** directory, but bundles can have their own models at the **[BUNDLES_DIR]/[BUNDLE_NAME]/lib/Models**.

You can load model by calling:

```
$p->Model([BUNDLE_NAME]:[MODEL_NAME]);
```
or, if model is located at the default directory:

```
$p->Model([MODEL_NAME]);
```

Also, if you dont have model's definition, but you have storage with collection, you can create model from it:

```
$p->db->getQueryBuilder([STORAGE_NAME])->createModel([COLLECTION_NAME]);
```
Yep, it is very powerfull.


First of all - they contain fields definitions of the entity. Fields definitions used by QueryBuilder, form validators, controllers, etc. It is very important part:

```
$Model->getModelFields();
```

Secondly, they contain storage and collection settings. All parts of the framework also use these settings.

```
$Model->getModelStorage();
$Model->getModelTable();
```

Each model can have its own directory for uploaded data (it is situated in **[DOC_DIR]/files/**). 

```
$Model->getUploadDir();
```

You can get and set model's data:

```
$Model->getModelData();
$Model->setModelData(array(...))
```
To get/set value of the model's field you can call magic method with uppercased first letter fields name:

```
$Model->getReal_id();
$Model->setReal_id();
```

BTW, model's repository returns model instances by default, so, if you want to get arrays you need to setup hydration method:

```
$repo = $p->db->getManager()->getRepository('webtCMS:News');
$repo->findBy(
	array(
		'where' => array('is_on' => 1)
	), 
	$repo::ML_HYDRATION_ARRAY
);
```

To save/remove/update model you must to use **ModelManager**. It knows, how to initialize primary key and what to do with model during saving:

```
$em = $p->db->getManager();
$model = $p->Model('webtCMS:News');
$model->setModelData(array(
	'title' => 'Lorem ipsum',
	'date_add' => $p->getTime()
));
$id = $em->initPrimaryValue($model);
$em->save($model);

```

To update model there is much better to use model's repository:

```
$p->db->getManager()->getModelRepository('webtCMS:News')->update(
	$model, 
	array(
		'title' => 'No Lorem ipsum'
	));

```
Model can have some linked serialized data (like, schema definitions, lists  or trees of the model entities, which generated by **oList** and **oTree** objects).


#### [Back to the Table of Contents](../README_FRAMEWORK.md)

