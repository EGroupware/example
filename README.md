# Example app for EGroupware development

1. Step: [a minimal app](https://github.com/EGroupware/example/tree/step1)
2. Step: [an edit dialog](https://github.com/EGroupware/example/tree/step2)

## 3. Step: storing hosts in the database

The dialog collects a hostname and forgets it. This step gives the application a table, and
wires the dialog to it: saving stores, reopening reads back, and deleting removes.

![step3-01 A saved host, reopened from its own id](https://raw.githubusercontent.com/wiki/EGroupware/example/images/step3-01.png)

### setup/tables_current.inc.php

The schema a fresh installation gets, as an array rather than SQL - which is what lets the
same definition create the table on MySQL, PostgreSQL or MS SQL:

```php
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

`type => 'auto'` is the auto-incrementing primary key. `meta => 'account'` says a column
holds an account id, which is what lets the API show a name instead of a number and notice
when an account is deleted. Columns share the `host_` prefix by convention, so a query
joining several tables stays readable.

Writing these by hand is one option; the [developer](https://github.com/EGroupware/developer)
app's **DB-Tools** generates both this file and the update file from a schema editor.

### setup/tables_update.inc.php

An installation that already exists needs the table created, which is a second upgrade
function:

```php
function example_upgrade26_1_001()
{
	$GLOBALS['egw_setup']->oProc->CreateTable('egw_example', array(
		/* ... the same definition as above ... */
	));

	return $GLOBALS['setup_info']['example']['currentver'] = '26.1.002';
}
```

The function name says which version it upgrades *from*, and the return value where it
leaves things. `tables_current.inc.php` and the upgrade have to agree: one is for new
installations, the other for existing ones, and a difference between them is a bug that only
shows up on one of the two.

`setup.inc.php` gains the version and the table list:

```php
$setup_info['example']['version']   = '26.1.002';
$setup_info['example']['tables']    = array('egw_example');
```

### src/Bo.php

Storage is a class of its own, extending `Api\Storage\Base`, which reads the table
definition and provides `read()`, `save()`, `delete()`, `search()` and `init()`. Naming the
app and the table is most of the work:

```php
class Bo extends Api\Storage\Base
{
	const APP = 'example';
	const TABLE = 'egw_example';

	public function __construct()
	{
		parent::__construct(self::APP, self::TABLE);

		// Work with Api\DateTime objects instead of raw timestamps: the storage layer
		// then converts our date columns between server- and user-time on every read
		// and write, which is what makes times right for a user in another timezone.
		// set_times() also makes $this->now an Api\DateTime, which save() stores.
		$this->set_times('object');
		$this->convert_all_timestamps();
	}
```

Those two calls are worth making a habit of. A timestamp stored without knowing which
timezone it meant is a bug waiting for the first user in another one; `Api\DateTime` objects
carry that with them, and the storage layer converts on the way in and out.

`save()` is reimplemented to record who did what:

```php
if (empty($this->data['host_id']))
{
	$this->data['host_creator'] = $GLOBALS['egw_info']['user']['account_id'];
	$this->data['host_created'] = $this->now;
}
$this->data['host_modifier'] = $GLOBALS['egw_info']['user']['account_id'];
$this->data['host_modified'] = $this->now;

return parent::save(null, $extra_where);
```

An empty `host_id` is how you tell a new host from an existing one: the creator and creation
time are set once and never touched again, the modifier every time.

### src/Ui.php

`edit()` now reads, saves and deletes. Opening an existing host takes its id from the URL:

```php
if (!empty($_GET['host_id']))
{
	// read() answers false for a host that is not there.  init() answers an
	// empty array, which is a perfectly good new host - so only treat a
	// failed read as "not found", not an empty one.
	if (($content = $this->bo->read(['host_id' => (int)$_GET['host_id']])) === false)
	{
		Api\Framework::message(lang('Entry not found!'), 'error');
		$content = $this->bo->init();
	}
}
```

The `=== false` is required. `init()` hands back an empty array for a new host, and
`read()` hands back `false` for one that is not there; both are falsy, so a plain `if (!…)`
would greet every new host with *Entry not found!*.

Saving asks storage and reports what happened:

```php
if (!$this->bo->save($content))
{
	Api\Framework::message(lang('Entry saved.'));
	// take back what storage made of it: the new id, and the
	// timestamps it set
	$content = array_merge($content, $this->bo->data);
}
```

`save()` answers 0 on success, which reads backwards until you remember it is an error
number. Merging `$this->bo->data` back into the content is what puts the newly assigned
`host_id` on the form, so the next Apply updates that host instead of creating another.

`host_id` also has to survive the next submit, and it has no widget on the dialog:

```php
$preserv = [
	'host_id' => $content['host_id'] ?? null,
];

$tmpl->exec('example.'.self::class.'.edit', $content, [], $readonlys, $preserv);
```

That last argument is the preserved values. Anything in it comes back on the next submit
without a widget carrying it - and therefore without the browser being able to change it,
which is what you want for the id deciding which row gets written.

Name the values that need preserving rather than handing `$content` back wholesale. A
preserved copy of something a widget already submits is a second, stale version of the same
value, and which one wins is not worth finding out.

Delete needs something to delete, so the button is switched off until there is:

```php
$readonlys = [
	'button[delete]' => empty($content['host_id']),
];
```

### setup/default_records.inc.php

Records created when the application is installed. There is nothing an example app must
have, so the file exists mostly to say where such things go.

## Try it

1. In Setup, run the update for *example* - it goes to 26.1.002 and creates `egw_example`.
2. Open **Example**, fill in a hostname and a description, press **Save**.
3. The message says *Entry saved.*, and **Delete** is no longer greyed out - the host has an
   id now.
4. Reload the page and add its id to the URL:
   `…menuaction=example.EGroupware\Example\Ui.edit&host_id=1`. Your host comes back.
5. Open the app again from the navbar. You get an empty form, not the host you just saved:
   there is nowhere yet that lists what exists, which is what the next step adds.
6. Press **Delete** on a saved host and confirm. It is gone.

### Troubleshooting

**`Table 'egw_example' doesn't exist`** - the update did not run. It only runs when the
version in `setup.inc.php` is higher than the installed one.

**Every new host is greeted with *Entry not found!*** - the read is being tested with `!`
rather than `=== false`.

**Times are hours out** - `set_times('object')` and `convert_all_timestamps()` are what make
the storage layer convert between server- and user-time.

**Apply keeps creating new hosts instead of updating one** - `host_id` is not coming back.
It has no widget, so it has to be preserved.

--> continue to [step 4](https://github.com/EGroupware/example/tree/step4), which adds a list
of everything stored
