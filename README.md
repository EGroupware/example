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


--> [continue to step 4](https://github.com/EGroupware/example/tree/step3) by checking out branch ```step4``` in your workingcopy
