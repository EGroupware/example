# Example app for EGroupware development

#### 1. Step: [minimal "Hello World" app](https://github.com/EGroupware/example/tree/step1)
#### 2. Step: [an edit dialog](https://github.com/EGroupware/example/tree/step2)
#### 3. Step: create a database table for data persistence

* To persist the hosts in our example app, we have to create a database schema:

[![Create a database schema](https://img.youtube.com/vi/rvZsZz9InB8/0.jpg)](https://www.youtube.com/watch?v=rvZsZz9InB8 "Create a database schema")

* Database schema is stored in [/setup/tables_current.inc.php](https://github.com/EGroupware/example/tree/step3/setup/tables_current.inc.php)
```
$phpgw_baseline = array(
	'egw_example' => array(
		'fd' => array(
			'host_id' => array('type' => 'auto','nullable' => False),
			'host_name' => array('type' => 'varchar','precision' => '64','nullable' => False),
			'host_description' => array('type' => 'varchar','precision' => '16384'),
			'host_creator' => array('type' => 'int','meta' => 'account','precision' => '4','nullable' => False),
			'host_created' => array('type' => 'timestamp','nullable' => False),
			'host_modifier' => array('type' => 'int','meta' => 'account','precision' => '4'),
			'host_modified' => array('type' => 'timestamp','default' => 'current_timestamp')
		),
		'pk' => array('host_id'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	)
);
```
* And we had to add the table to [/setup/setup.inc.php](https://github.com/EGroupware/example/tree/step3/setup/setup.inc.php)
```
$setup_info['example']['tables'] = array('egw_example');
```
* As the app was installed without a database table, we have to uninstall and reinstall it as described in [step 1](https://github.com/EGroupware/example/tree/step1/README.md)

* Adding a [/setup/default_records.inc.php](https://github.com/EGroupware/example/tree/step3/setup/default_records.inc.php) to automatic add run rights for our Default (all users) group:
```
// give Default group rights for Example app
$defaultgroup = $GLOBALS['egw_setup']->add_account('Default', 'Default', 'Group', false, false);
$GLOBALS['egw_setup']->add_acl('example', 'run', $defaultgroup);
```

* We need some code to be able to store files, thought the heavy lifting comes from our [Api\Storage\Base](https://github.com/EGroupware/egroupware/blob/master/api/src/Storage/Base.php)
```
namespace EGroupware\Example;

use EGroupware\Api;

class Bo extends Api\Storage\Base
{
	const APP = 'example';
	const TABLE = 'egw_example';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(self::APP, self::TABLE);
	}

	/**
	 * saves the content of data to the db
	 *
	 * @param array $keys =null if given $keys are copied to data before saveing => allows a save as
	 * @param string|array $extra_where =null extra where clause, eg. to check an etag, returns true if no affected rows!
	 * @return int|boolean 0 on success, or errno != 0 on error, or true if $extra_where is given and no rows affected
	 */
	public function save($keys = null, $extra_where = null)
	{
		$this->data_merge($keys);

		if (empty($this->data['host_id']))
		{
			$this->data['host_creator'] = $GLOBALS['egw_info']['user']['account_id'];
			$this->data['host_created'] = $this->now;
		}
		$this->data['host_modifier'] = $GLOBALS['egw_info']['user']['account_id'];
		$this->data['host_modified'] = $this->now;

		return parent::save(null, $extra_where);
	}
}
```

--> [continue to step 4](https://github.com/EGroupware/example/tree/step3) by checking out branch ```step4``` in your workingcopy
