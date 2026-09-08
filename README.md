# Example app for EGroupware development

1. Step: [a minimal app](https://github.com/EGroupware/example/tree/step1)
2. Step: [an edit dialog](https://github.com/EGroupware/example/tree/step2)
3. Step: [storing hosts in the database](https://github.com/EGroupware/example/tree/step3)
4. Step: [a list of hosts](https://github.com/EGroupware/example/tree/step4)
5. Step: [linking hosts to other entries](https://github.com/EGroupware/example/tree/step5)
6. Step: [code that runs in the browser](https://github.com/EGroupware/example/tree/step6)
7. Step: [custom fields](https://github.com/EGroupware/example/tree/step7)
8. Step: [categories](https://github.com/EGroupware/example/tree/step8)
9. Step: [access control](https://github.com/EGroupware/example/tree/step9)
10. Step: [telling everyone else](https://github.com/EGroupware/example/tree/step10)

## 11. Step: settings for the whole installation

An application can have settings of its own: decisions an administrator makes once, for
everybody, about how this installation behaves. This step adds one - whether a host must
have a description - and enforces it.

![step11-01 The app's configuration page](https://raw.githubusercontent.com/wiki/EGroupware/example/images/step11-01.png)

Configuration is for rules about the installation, not for personal taste. A default a user
should be able to change for themselves is a *preference*, which is the next step.

### templates/default/config.xet

The page is an ordinary template. The administration application looks for one named
`<app>.config` and renders it, so there is no controller to write:

```xml
<template id="example.config">
	<grid width="100%" class="admin-config egwGridView_grid">
		<columns>
			<column width="60%"/>
			<column/>
		</columns>
		<rows>
			<row>
				<et2-description  value="Require a description for every host" label="%s:"></et2-description>
				<et2-select  id="newsettings[require_description]">
					<option value="">No</option>
					<option value="1">Yes</option>
				</et2-select>
			</row>
		</rows>
	</grid>
</template>
```

Every setting lives under **`newsettings[…]`** - that is the name the administration
application reads and writes; an id outside it is simply ignored. Any widget works: a setting
is only a value with a label.

### src/Hooks.php

The page needs a way in, which is the admin section from step 7:

```php
'Site Configuration' => Api\Egw::link('/index.php', [
	'menuaction' => 'admin.admin_config.index',
	'appname'    => Bo::APP,
	'ajax'       => 'true',
]),
```

`admin.admin_config.index` is the administration application's own page; `appname` is all it
needs to find our template and store what is set on it. The entry appears under our
application's name, and reads *App configuration* - that is what the phrase
`Site Configuration` is translated to.

### src/Ui.php

A setting nobody reads changes nothing. `Api\Config::read()` answers what is stored for an
application, and the rule is applied before anything is written:

```php
// An installation can insist on a description.  The browser could
// check this too, and should, but the server is the only place the
// rule can actually be enforced.
$config = Api\Config::read(Bo::APP);
if (!empty($config['require_description']) && empty($content['host_description']))
{
	Api\Etemplate::set_validation_error('host_description',
		lang('Description is required'));
	break;	// no save, and the dialog comes back with the error on it
}
```

`set_validation_error()` names the widget the message belongs to, so the dialog comes back
with the error attached to the field rather than as a toast the user has to interpret. The
`break` leaves the switch without saving, and execution falls through to the same `exec()`
that drew the form in the first place.

Doing this on the server is the point. A browser can check a field is filled in, and should,
because it saves a round trip and tells the user sooner - but a browser is also the one part
of this whole arrangement an attacker controls. A rule that only exists in the client is a
suggestion.

## Try it

1. **Admin > Applications > Example > App configuration**: set *Require a description for
   every host* to **Yes** and save.
2. Add a host, fill in only the hostname, and press **Save**. It does not save, and the
   description field is marked with *Description is required*.
3. Fill the description in and save again. It works.
4. Set the configuration back to **No**. A host with no description saves fine.

### Troubleshooting

**The configuration page is empty** - the template has to be named exactly `example.config`,
and the file has to be `templates/default/config.xet`.

**Settings look saved but nothing is stored** - the widget ids have to be inside
`newsettings[…]`.

**The rule never triggers** - `Api\Config::read()` takes the application name, and the key
has to match the id inside `newsettings[…]`.

**A changed setting does not take effect** - configuration is cached; *Admin > Clear
cache/register hooks*. While developing templates, a browser can also hold onto an old copy
of a `.xet` - a forced refresh rules that out.

--> continue to [step 12](https://github.com/EGroupware/example/tree/step12), which lets each
user choose for themselves
