# Example app for EGroupware development

1. Step: [a minimal app](https://github.com/EGroupware/example/tree/step1)

## 2. Step: an edit dialog

`echo` is not how EGroupware builds a page. This step replaces it with a template - a form
with fields and buttons, described in XML and rendered by the framework.

![step2-01 The edit dialog](https://raw.githubusercontent.com/wiki/EGroupware/example/images/step2-01.png)

### templates/default/edit.xet

A template is a `.xet` file under `templates/default/`, named after what it shows. Inside,
one `<template>` element with an id the server refers to it by:

```xml
<template id="example.edit" template="" lang="" group="0" version="26.1">
	<grid width="100%">
		<columns>
			<column width="100"/>
			<column/>
		</columns>
		<rows>
			<row>
				<et2-description  for="host_name" value="Hostname"></et2-description>
				<et2-textbox  id="host_name" tabindex="1" maxlength="64"></et2-textbox>
			</row>
```

The id is `<app>.<file>`, and it has to match: `example.edit` lives in `edit.xet`. A
mismatch is worth knowing about because the two sides disagree - the server refuses to find
it, while the browser quietly falls back to the last template in the file.

Widgets are web components, all prefixed `et2-`. `et2-description` is a label, and its `for`
points at the field it labels. `et2-textbox` is the input; its **`id` is the name its value
arrives under**, which is the single most important thing on the line.

Buttons work the same way, with an id that says which was pressed:

```xml
<et2-button  accesskey="s" label="Save" id="button[save]"></et2-button>
<et2-button  label="Apply" id="button[apply]"></et2-button>
```

`button[save]` is an array index, so all the buttons arrive together under `button`, and the
one that was pressed is the only key in it.

### src/Ui.php

`edit()` is called **twice for one round trip**: once with `null`, to draw the form, and
again with whatever the browser submitted.

```php
public function edit(?array $content=null)
{
	if (!is_array($content))
	{
		$content = [];
	}
	else
	{
		// which button was pressed?  Guard the key: a submit can arrive without one.
		$button = key($content['button'] ?? []);
		unset($content['button']);

		switch($button)
		{
			case 'save':
			case 'apply':
				// nowhere to put it yet - the next step gives the app a table
				Api\Framework::message(lang('Entry saved.'));
				break;
		}
	}
	$tmpl = new Api\Etemplate('example.edit');
	$tmpl->exec('example.'.self::class.'.edit', $content);
}
```

`key($content['button'] ?? [])` is worth the `??`. A form can be submitted without any
button - pressing Enter in a field is enough, and so is a widget that submits on change - so
the key may not be there at all.

`exec()` takes the method to submit back to, and the content to fill the form with. On the
first call that content is empty, which draws an empty form.

`edit` has to be added to `$public_functions` to be reachable, and `index()` now opens the
dialog rather than echoing:

```php
public $public_functions = [
	'index' => true,
	'edit'  => true,
];
```

Nothing is stored yet. Saving reports success and shows you the values back, and they are
gone on the next reload - the following step gives the app somewhere to put them.

### setup/setup.inc.php

Two changes, and the second is only there to make the first take effect:

```php
$setup_info['example']['version']   = '26.1.001';
$setup_info['example']['index']     = 'example.EGroupware\\Example\\Ui.index&ajax=true';
```

`&ajax=true` is what tells the framework this application draws itself into the page.
Without it the app is loaded in an iframe, which works but is not how a modern application
behaves - the header, the sidebox and the browser history all stop cooperating.

### setup/tables_update.inc.php

The entry point is read at install time and stored, so changing `setup.inc.php` alone
changes nothing on an installation that already exists. Setup only re-reads it when the
version goes up, and it only walks versions it has a function for:

```php
/**
 * Nothing to change in the database - the new version is what makes setup re-read
 * setup.inc.php, and with it the changed entry point.
 *
 * @return string
 */
function example_upgrade26_1()
{
	return $GLOBALS['setup_info']['example']['currentver'] = '26.1.001';
}
```

An upgrade function that changes no data still earns its place: the version bump is the
mechanism, and the function is what makes the bump reachable. Later steps use the same file
for real schema changes.

### lang/egw_en.lang

Everything a user reads goes through `lang()`, and the phrases live in tab-separated files -
one line per phrase, `phrase`, `app-name`, `language`, translation:

```
entry saved.	example	en	Entry saved.
example	common	en	Example
hostname	example	en	Hostname
```

The lookup key is the lower-cased English phrase, which is why `lang('Entry saved.')` finds
the line beginning `entry saved.`. An app-name of `common` means the phrase is shared with
every application; anything else belongs to this one.

Editing these by hand is fine for a few lines. The
[developer](https://github.com/EGroupware/developer) app's **TranslationTools** does it
properly, and translators work from <https://translation.egroupware.org/> rather than in the
repository.

## Try it

1. In Setup, run the update for *example* - it goes to 26.1.001.
2. Open **Example**. Instead of the greeting there is a form with Hostname and Description.
3. Fill both in and press **Save**. A message says *Entry saved.* and the values are still
   on screen.
4. Reload the page. The form is empty again: nothing was stored.

### Troubleshooting

**The version is right but the app is still in an iframe** - the list of installed
applications is cached, and the running webserver may still be holding the old entry point.
*Admin > Clear cache/register hooks* fixes it. This bites in particular if you ran the
update with `setup/setup-cli.php` rather than in the browser: the command line and the
webserver use different cache backends, so invalidating one leaves the other stale.

**The update does nothing** - setup skips an application whose installed version already
matches `setup.inc.php`. Check what is actually installed before assuming the function is
wrong.

**`Template example.edit not found`** - the template id and the filename have to match.

**Changes in `.xet` files do not show up** - templates are cached. *Admin > Clear
cache/register hooks*.

--> continue to [step 3](https://github.com/EGroupware/example/tree/step3), which gives the
app a table to store hosts in
