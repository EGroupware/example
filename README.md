# Example app for EGroupware development

1. Step: [a minimal app](https://github.com/EGroupware/example/tree/step1)
2. Step: [an edit dialog](https://github.com/EGroupware/example/tree/step2)
3. Step: [storing hosts in the database](https://github.com/EGroupware/example/tree/step3)
4. Step: [a list of hosts](https://github.com/EGroupware/example/tree/step4)
5. Step: [linking hosts to other entries](https://github.com/EGroupware/example/tree/step5)
6. Step: [code that runs in the browser](https://github.com/EGroupware/example/tree/step6)

## 7. Step: custom fields

An administrator can add fields to an application without touching its code. This step gives
the app somewhere to put them, and puts them on the dialog and in the list.

![step7-01 A custom field on the edit dialog](https://raw.githubusercontent.com/wiki/EGroupware/example/images/step7-01.png)

It is also the first schema change since the table was created, so it is where upgrades come in.

### A second table

Custom-field values go in their own table: one row per entry per field, so an administrator
adding a field costs nothing in the main table.

```php
'egw_example_extra' => array(
	'fd' => array(
		'host_id' => array('type' => 'int','precision' => '4','nullable' => False),
		'host_extra_name' => array('type' => 'varchar','meta' => 'cfname','precision' => '64','nullable' => False),
		'host_extra_value' => array('type' => 'text','meta' => 'cfvalue','nullable' => False)
	),
	'pk' => array(),
	'fk' => array(),
	'ix' => array(),
	'uc' => array(array('host_id','host_extra_name'))
)
```

The `meta` types are the important part: `cfname` and `cfvalue` are how the storage layer
recognises which column holds a field's name and which its value. The unique constraint on
`host_id, host_extra_name` is what stops one entry having the same field twice.

### Upgrading an installation that already exists

`tables_current.inc.php` describes the schema a *fresh* install gets. An installation that
already has the old schema needs to be brought forward, which is what `tables_update.inc.php`
is for - one function per version:

```php
function example_upgrade26_1_002()
{
	$GLOBALS['egw_setup']->oProc->CreateTable('egw_example_extra', array(
		/* ... same definition as above ... */
	));

	return $GLOBALS['setup_info']['example']['currentver'] = '26.1.003';
}
```

The name says which version it upgrades *from* - `26_1_002` runs against an installation
sitting at 26.1.002 - and the return value is where it leaves it. Setup runs them in sequence
until the app reaches the version in `setup.inc.php`, so a very old installation walks the
whole chain.

The definition is elided above because it is the same one already given for
`tables_current.inc.php` - and keeping those two in step by hand is what
[DB-Tools](https://github.com/EGroupware/developer) is for: it writes both files from the
schema editor, so a new column lands in the fresh-install schema and the upgrade together.

Bump the version whenever you change the schema, or the update never runs:

```php
$setup_info['example']['version']   = '26.1.003';
$setup_info['example']['tables']    = array('egw_example', 'egw_example_extra');
```

### src/Bo.php

`Api\Storage` is `Api\Storage\Base` plus custom fields. Switching the parent class and naming
the extra table is the whole change:

```php
class Bo extends Api\Storage
{
	const APP = 'example';
	const TABLE = 'egw_example';
	const EXTRA_TABLE = 'egw_example_extra';

	public function __construct()
	{
		// the last two name the columns holding a custom field's name and its value;
		// a leading underscore is prefixed with the column prefix, here "host"
		parent::__construct(self::APP, self::TABLE, self::EXTRA_TABLE, '', '_extra_name', '_extra_value');
	}
}
```

`read()`, `save()`, `search()` and `get_rows()` now carry custom fields along with everything
else, under keys beginning with `#` - `#location` for a field named `location`. Nothing else
in `Bo` or `Ui` changes.

### src/Hooks.php

Someone has to be able to *define* the fields. The `admin` hook is how an application adds
its own section to the Admin app:

```php
public static function admin($args)
{
	unset($args);	// not used, but part of the hook signature

	if (empty($GLOBALS['egw_info']['user']['apps']['admin']))
	{
		return;
	}
	display_section(Bo::APP, [
		'Custom fields' => Api\Egw::link('/index.php', [
			'menuaction' => 'admin.admin_customfields.index',
			'appname'    => Bo::APP,
			'ajax'       => 'true',
		]),
	]);
}
```

The interface itself is the API's, not ours - we only say which application it should be
editing. `display_section()` is what puts the entries under our application's name, and the
permission check keeps a normal user from being offered a page they cannot open.

Like every hook, it has to be declared:

```php
$setup_info['example']['hooks']['admin'] = 'EGroupware\\Example\\Hooks::admin';
```

### The templates

On the dialog, one tag renders whatever fields are defined:

```xml
<template id="example.edit.customfields">
	<customfields/>
</template>
```

In the list it takes two, a header and a row cell, both naming the application whose fields
they show:

```xml
<et2-nextmatch-header-customfields id="customfields" app="example"/>
```

```xml
<et2-customfields-list id="$row" class="customfields" app="example"></et2-customfields-list>
```

`id="$row"` hands the whole row to the widget, which then picks out the `#`-prefixed keys
itself. Without these two the fields would still be stored and still be on the dialog, and
the list would quietly ignore them.

## Try it

1. **Admin > Clear cache/register hooks.** The `admin` hook is new, and nothing appears
   until it is registered.
2. Open **Admin** and find **Applications > Example > Custom fields** in the tree.
3. Add a field: name `location`, label `Location`, type `Text`.
4. Open a host. There is a **Custom fields** tab; fill *Location* in and press **Save**.
5. The list now has a custom-fields column, with that value in it.
6. Add a second field and reload - it appears on both, with no code change.

### Troubleshooting

**No *Custom fields* entry under Applications > Example** - the hook is declared but not
registered. *Admin > Clear cache/register hooks*. If it survives that, the server is still
holding the old hook list: restart php-fpm.

**The field shows on the dialog but never saves** - `Bo` is still extending
`Api\Storage\Base`, or the extra table was never created. Setup's *Manage applications* lists
the installed version of every app; this step needs Example at 26.1.003.

**`Table 'egw_example_extra' doesn't exist`** - the update did not run. It only runs when the
version in `setup.inc.php` is higher than the installed one.

--> continue to [step 8](https://github.com/EGroupware/example/tree/step8), which adds
categories
