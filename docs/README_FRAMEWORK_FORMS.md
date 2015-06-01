
# web-T::Framework


## Forms

To get form fields you must to get special control `oForms` and send to it some parameters:

```
$fc = $p->Service('oForms')->AddParams(array(
	'data' => $data,
    'model' => [MODEL]
));
```

Sometimes, you need to add special parameters, like:

* **caller** - object, on which **oForms** will call callback methods
* **callbacks** - array of defined callbacks for some special operations (like `unique` validation)

To validate some data simply call:

```
$result = $fc->validate();
```
You will get structure with those items:

* **non_valid** - list of the non valid fields
* **valid_data** - normalized and filtered data 
* **valid_details** - detailed data for each validated field 


To get html of the specified field call this method:

```
$fc->getField([FIELD_NAME]);
```

To get value for storing in the storage, you need to use:

```
$fc->getSaveField([FIELD_NAME], [DATA_ROW], [OLD_DATA]);
```


The `oForms` control is not cached, so don't worry about its use.

#### [Back to the Table of Contents](../README_FRAMEWORK.md)

