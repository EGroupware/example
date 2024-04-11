# Example app for EGroupware development

#### 1. Step: [minimal "Hello World" app](https://github.com/EGroupware/example/tree/step1)
#### 2. Step: [an edit dialog](https://github.com/EGroupware/example/tree/step2)
#### 3. Step: [create a database table for data persistence](https://github.com/EGroupware/example/tree/step3)
#### 4. Step: [add UI to list hosts](https://github.com/EGroupware/example/tree/step4)
#### 5. Step: [Linking with other EGroupware entries and attaching files](https://github.com/EGroupware/example/tree/step5)
#### 6. Step: [Client-side actions in TypeScript and eTemplate dialogs](https://github.com/EGroupware/example/tree/step6)
#### 7. Step: Adding custom-fields

> Custom fields can be added without any coding by every EGroupware administrator, **if the app supports it**.

In this step we are going to add the necessary support:

1. first we need to add a hook to be able to define / configure custom-fields as shown in the following screenshot:

We add the following method to implement the hook to `example/src/Hooks.php`:
```php
namespace EGroupware\Example;

use EGroupware\api;

class Hooks
{
    /** existing code omitted ... */

	/**
	 * hooks to build example app's sidebox-menu plus the admin and preferences sections
	 *
	 * @param string|array $args hook args
	 */
	static function all_hooks($args)
	{
		$appname = Bo::APP;
		$location = is_array($args) ? $args['location'] : $args;

		if ($GLOBALS['egw_info']['user']['apps']['admin'])
		{
			$file = Array(
				//'Site Configuration' => Api\Egw::link('/index.php','menuaction=admin.admin_config.index&appname=' . $appname,'&ajax=true'),
				'Custom fields' => Api\Egw::link('/index.php','menuaction=admin.admin_customfields.index&appname='.$appname.'&ajax=true'),
				/*'Global Categories'  => Api\Egw::link('/index.php',array(
					'menuaction' => 'admin.admin_categories.index',
					'appname'    => $appname,
					'global_cats'=> True,
					'ajax' => 'true',
				)),*/
			);
			if ($location == 'admin')
			{
				display_section($appname,$file);
			}
			else
			{
				display_sidebox($appname,lang('Admin'),$file);
			}
		}
	}
}
```
The hook-method need then to be added to our `example/setup/setup.inc.php` and we need to (re-)register hooks (Admin > Clear cache and register hooks)
```php
$setup_info['example']['hooks']['admin'] = Example\Hooks::class.'::all_hooks';
$setup_info['example']['hooks']['sidebox_menu'] = Example\Hooks::class.'::all_hooks';
```
After a reload (F5) or new login we are be able to add our first custom fields, as shown in the screenshot:
![step7-cfs](https://raw.githubusercontent.com/wiki/EGroupware/example/images/step7-cfs.png)

In the above screenshot we add a single custom-field named `log` with label `Log` and of type `Formatted text (HTML)` to be shown in tab `Log`. 

If you don't specify a tab, the custom-field will be shown by default in a tab called `Custom fields`.

The order of the fields (in each tab) is specified by the value in `Order`. Some custom-field types allow to specify additional options, length and number of rows.

2. To persist the content of the custom-fields we need to add another table to our database scheme, which requires changes to the following files:
* `example/setup/setup.inc.php`:
```php
// existing code omitted, just change the following *existing* variables:
$setup_info['example']['version']   = '23.1';

$setup_info['example']['tables']    = array('egw_example','egw_example_extra');
```
* `example/setup/tables_current.inc.php`:
```php
$phpgw_baseline = array(
	'egw_example' => array(
		// existing definition omitted ...
	),
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
);
```
* the new file `example/setup/tables_update.inc.php` is required for the schema-update of an existing installation from previous version `19.1` to new version `23.1`:
```php
function example_upgrade19_1()
{
	$GLOBALS['egw_setup']->oProc->CreateTable('egw_example_extra',array(
		'fd' => array(
			'host_id' => array('type' => 'int','precision' => '4','nullable' => False),
			'host_extra_name' => array('type' => 'varchar','meta' => 'cfname','precision' => '64','nullable' => False),
			'host_extra_value' => array('type' => 'text','meta' => 'cfvalue','nullable' => False)
		),
		'pk' => array(),
		'fk' => array(),
		'ix' => array(),
		'uc' => array(array('host_id','host_extra_name'))
	));

	return $GLOBALS['setup_info']['example']['currentver'] = '23.1';
}
```
3. after the above changes, we need to log into Setup (`https://example.org/egroupware/setup/`) and run the schema-update we just added

4. to be able to use the new table, we need to change our existing `Bo` class in `example/src/Bo.php` as follows:
```php
namespace EGroupware\Example;

use EGroupware\Api;

class Bo extends Api\Storage
{
	const APP = 'example';
	const TABLE = 'egw_example';
	const EXTRA_TABLE = 'egw_example_extra';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(self::APP, self::TABLE, self::EXTRA_TABLE, '', '_extra_name', '_extra_value');
	}

	// unchanged rest of the class is omitted ...
}
```
* extending `Api\Storage` instead of `Api\Storage\Base` add the necessary code to deal with (optional) custom-fields, defined by an EGroupware admin
* in the constructor we need to pass the name of our new table and the (unprefixed) names or the name- and value-columns (the `host` prefix is added automatic)

5. if you not already added at least a single custom field, as shown in the above screenshot, please do so now. Then add a new host or edit an existing one to see the custom-fields you added:

![step7-edit](https://raw.githubusercontent.com/wiki/EGroupware/example/images/step7-edit.png)

--> [continue to step 8](https://github.com/EGroupware/example/tree/step8) by checking out branch ```step8``` in your workingcopy