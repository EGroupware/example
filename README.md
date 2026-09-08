# Example app for EGroupware development

1. Step: [a minimal app](https://github.com/EGroupware/example/tree/step1)
2. Step: [an edit dialog](https://github.com/EGroupware/example/tree/step2)
3. Step: [storing hosts in the database](https://github.com/EGroupware/example/tree/step3)
4. Step: [a list of hosts](https://github.com/EGroupware/example/tree/step4)
5. Step: [linking hosts to other entries](https://github.com/EGroupware/example/tree/step5)
6. Step: [code that runs in the browser](https://github.com/EGroupware/example/tree/step6)
7. Step: [custom fields](https://github.com/EGroupware/example/tree/step7)

## 8. Step: categories

Custom fields let an administrator add *fields*. Categories let users sort entries into
groups the API already knows how to manage, share and colour. A host gets one, the list gets
a column for it - and the app gets its first real filter.

![step8-01 The category column and filter](https://raw.githubusercontent.com/wiki/EGroupware/example/images/step8-01.png)

### One more column

The same schema-change pattern as the last step: describe it for a fresh install, and write
the upgrade for the installations that already exist.

```php
'cat_id' => array('type' => 'int','meta' => 'category','precision' => '4','default' => '0','comment' => 'category')
```

```php
function example_upgrade26_1_003()
{
	$GLOBALS['egw_setup']->oProc->AddColumn('egw_example', 'cat_id', array(
		'type' => 'int',
		'meta' => 'category',
		'precision' => '4',
		'default' => '0',
		'comment' => 'category'
	));

	return $GLOBALS['setup_info']['example']['currentver'] = '26.1.004';
}
```

`meta => 'category'` is what marks the column as holding a category id rather than any
integer. The column is called `cat_id` because that is the name the rest of EGroupware uses
for it.

### src/Hooks.php

Answering the `categories` hook is what puts the application in the category
administration's list:

```php
public static function categories($location)
{
	unset($location);	// not used, but part of the hook signature

	return true;
}
```

That is the whole implementation - the hook is a question ("do you use categories?"), and
returning true is the answer. Declared as usual:

```php
$setup_info['example']['hooks']['categories'] = 'EGroupware\\Example\\Hooks::categories';
```

Somewhere to maintain them belongs in the admin section from the last step, next to the
custom fields:

```php
'Global Categories' => Api\Egw::link('/index.php', [
	'menuaction'  => 'admin.admin_categories.index',
	'appname'     => Bo::APP,
	'global_cats' => true,
	'ajax'        => 'true',
]),
```

### The templates

On the dialog, one widget. `application="example"` matters: without it you get whichever
application's categories the widget guesses at.

```xml
<et2-description  for="cat_id" value="Category"></et2-description>
<et2-select-cat  id="cat_id" application="example" emptyLabel="None"></et2-select-cat>
```

`emptyLabel` is what lets a host have no category at all; without it the first category is
preselected and the field is effectively mandatory.

In the list, a sortable header and a read-only cell:

```xml
<et2-nextmatch-sortheader label="Category" id="cat_id"/>
```

```xml
<et2-select-cat id="cat_id" application="example" readonly="true"></et2-select-cat>
```

### The filter, and where filters live

The list has offered no filters so far, which is why `index()` switched them off:

```php
'no_filter'           => true,	// we offer no filter ...
'no_filter2'          => true,	// ... and no second one
```

Now that hosts have categories, there is something worth filtering by, so `no_cat` comes off
that list and the category filter appears.

It appears in the **filterbox**, the panel of filters beside the search box. You do not build
it: it is generated from the list's own row template, by collecting the header widgets that
can filter - `et2-nextmatch-header-account` and `et2-nextmatch-header-customfields` from the
earlier steps are already in there, alongside the category filter. Settings like `no_filter`
and `no_cat` are how a list says which of the standard filters it does not want. The filterbox
can be customised further when an application needs it to be.

The filter still has to reach the database. It arrives as its own query field rather than in
`col_filter`, so `get_rows()` maps it across:

```php
if ((string)($query['cat_id'] ?? '') !== '')
{
	$query['col_filter']['cat_id'] = $query['cat_id'];
}
else
{
	// nothing selected: drop it, or the last choice would filter forever
	unset($query['col_filter']['cat_id']);
}
```

The `else` branch is not optional - mapping only when a category *is* selected leaves the
previous one in the stored query, and the list keeps filtering by a category the user has
already cleared. The mapping also happens **before** the query is stored in the session, so
an action running on *select all* filters exactly as the list did.

## Try it

You need at least one category first: **Admin > Applications > Example > Global categories**.
Add `Production` and `Staging`.

1. Open a host, choose a category, **Save**.
2. The list has a **Category** column showing it, and sorts by it when you click the header.
3. Open the filterbox beside the search box: there is a category filter. Choose `Production`
   and the list narrows to those hosts.
4. Reload the page - the filter is still applied, because the query is kept in the session.
5. With the filter still active, press *Select all*: only the hosts the filter shows are
   selected. An action run on that selection uses the same query the list did - add a
   throwaway host if you want to prove it by deleting one.
6. Clear the filter back to *All*. Every host is listed again.

### Troubleshooting

**The category selector is empty, or offers another application's categories** - the widget
needs `application="example"`.

**Categories cannot be created for Example** - the `categories` hook is not registered.
*Admin > Clear cache/register hooks*. If it survives that, the server is still holding the
old hook list: restart php-fpm.

**A filter keeps applying after you clear it** - the `else` branch above is missing, so the
old value stays in the session query.

**The column shows a number instead of a name** - the cell is not an `et2-select-cat`, or is
missing `application`.

--> continue to [step 9](https://github.com/EGroupware/example/tree/step9), which adds access
control
