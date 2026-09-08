# Example app for EGroupware development

1. Step: [a minimal app](https://github.com/EGroupware/example/tree/step1)
2. Step: [an edit dialog](https://github.com/EGroupware/example/tree/step2)
3. Step: [storing hosts in the database](https://github.com/EGroupware/example/tree/step3)
4. Step: [a list of hosts](https://github.com/EGroupware/example/tree/step4)

## 5. Step: linking hosts to other entries

A host on its own is an island. This step connects it to the rest of EGroupware: a host can
be linked to a contact, an InfoLog entry or a project, and those applications can search for
hosts and show their names in return.

![step5-01 The Links tab of the edit dialog](https://raw.githubusercontent.com/wiki/EGroupware/example/images/step5-01.png)

All of it comes from one hook.

### setup/setup.inc.php

A hook is a function some other part of EGroupware calls. Which ones an app implements is
declared in its setup file:

```php
// Hooks we implement, see src/Hooks.php.  Registered when the app is installed,
// and by Admin > Clear cache/register hooks.
$setup_info['example']['hooks']['search_link'] = 'EGroupware\\Example\\Hooks::search_link';
```

Declaring it is not enough by itself - the list is read when the app is installed or
upgraded. This step adds no table and no column, so there is no new version to update to,
and *Admin > Clear cache/register hooks* is what puts the hook into service.

### src/Hooks.php

`search_link` is the hook the link system calls to ask an app how it can be linked to. What
it returns is that app's entry in the **link registry**:

```php
public static function search_link($location)
{
	unset($location);	// not used, but part of the hook signature

	return [
		// finding an entry, and what to call it once found
		'query'      => Bo::APP.'.'.Bo::class.'.link_query',
		'title'      => Bo::APP.'.'.Bo::class.'.link_title',
		// opening one - view is what a user without edit rights gets
		'view'       => ['menuaction' => Bo::APP.'.'.Ui::class.'.edit'],
		'view_id'    => 'host_id',
		'view_popup' => '640x480',
		'edit'       => ['menuaction' => Bo::APP.'.'.Ui::class.'.edit'],
		'edit_id'    => 'host_id',
		'edit_popup' => '640x480',
		// the whole list, and adding a new entry
		'list'       => ['menuaction' => Bo::APP.'.'.Ui::class.'.index', 'ajax' => 'true'],
		'add'        => ['menuaction' => Bo::APP.'.'.Ui::class.'.edit'],
		'add_popup'  => '640x480',
	];
}
```

`query` and `title` name two methods on `Bo`: one to search hosts, one to name a single
host. `view_id` says which parameter carries the id, and `view_popup` how big a window to
open. The registry reaches the browser too, which is what makes the next change possible.

### templates/default/index.xet

The Add button knows less than it did, and works better for it:

```xml
<et2-button-icon statustext="Add" id="add" image="add" slot="prefix"
	onclick="egw.open('','example','add'); return false;"
	noSubmit="true"></et2-button-icon>
```

`egw.open(id, app, type)` looks the app up in the link registry and opens it the way the
registry says - here the `add` entry, in a popup of the size `add_popup` gives. An empty id
means there is no entry to open yet. The same call works from any application, which is how
one app opens another's dialog without knowing its URLs.

### src/Bo.php

The two methods the registry pointed at. `link_title()` names one host:

```php
public function link_title($entry)
{
	if (!is_array($entry))
	{
		// read() replaces $this->data, which whoever called us may still be using
		$backup =& $this->data;
		unset($this->data);
		$entry = $this->read(['host_id' => $entry]);
		$this->data =& $backup;
	}
	return $entry ? $entry['host_name'] : $entry;
}
```

`link_query()` is the search behind the link dialog. `$options` arrives with paging and
leaves with the total:

```php
public function link_query($pattern, array &$options = [])
{
	$limit = false;
	$need_count = false;
	if (!empty($options['start']) || !empty($options['num_rows']))
	{
		$limit = [$options['start'], $options['num_rows']];
		$need_count = true;
	}
	$result = [];
	foreach((array)$this->search($pattern, false, 'host_name', '', '%', false, 'OR',
		$limit, null, '', $need_count) as $host)
	{
		if ($host) $result[$host['host_id']] = $this->link_title($host);
	}
	$options['total'] = $need_count ? $this->total : count($result);

	return $result;
}
```

Saving needs one addition. A host that does not exist yet has no id to link anything to, so
the dialog holds those links and hands them over once there is one:

```php
if (($error = parent::save(null, $extra_where)) == 0 &&
	// A new host cannot be linked before it exists, so the dialog holds its
	// links until now and hands them over as link_to[to_id].
	!empty($keys['link_to']['to_id']) && is_array($keys['link_to']['to_id']))
{
	Api\Link::link(self::APP, $this->data['host_id'], $keys['link_to']['to_id']);
}
return $error;
```

Deleting needs the mirror of it. Links have two ends, and nothing else cleans up the other
one:

```php
if (($deleted = parent::delete($keys)) && $host_id)
{
	Api\Link::unlink(0, self::APP, $host_id);
}
return $deleted;
```

[The whole file on this branch](https://github.com/EGroupware/example/blob/step5/src/Bo.php)

### src/Ui.php

The link widgets work on a `link_to` value saying what they are linking to:

```php
$content['link_to'] = [
	'to_id'  => $content['host_id'] ?: ($content['link_to']['to_id'] ?? 0),
	'to_app' => Bo::APP,
];
```

A `to_id` of 0 is how you say "no entry yet": the widget then keeps new links client-side
and submits them with the form, instead of storing them against an id that does not exist.
Once the host is saved, `to_id` is its `host_id` and links are stored as you make them.

Which brings up what to preserve, because this dialog is the first with something that
could go wrong. The whitelist from step 3 is unchanged:

```php
$preserv = [
	'host_id' => $content['host_id'] ?? null,
];

$tmpl = new Api\Etemplate('example.edit');
$tmpl->exec('example.'.self::class.'.edit', $content, [], $readonlys, $preserv);
```

`link_to` is why it matters here. It has widgets of its own, and they submit it. Preserving
`$content` wholesale would send a second copy of it back alongside theirs - and a preserved
`to_id` of 0 merged into what the widgets submitted is enough to lose every link on the form.
Preserve what you mean to preserve, and let the widgets carry the rest.

### templates/default/edit.xet

The dialog gets tabs, because there is now more than one thing on it:

```xml
<et2-tabbox  id="tabs" span="all" width="100%" tabHeight="220">
	<tabs>
		<tab id="description" label="Description"/>
		<tab id="links" label="Links"/>
	</tabs>
	<tabpanels>
		<template id="example.edit.description"/>
		<template id="example.edit.links"/>
	</tabpanels>
</et2-tabbox>
```

Each tab is its own template. The Links one holds both link widgets:

```xml
<template id="example.edit.links">
	<et2-vbox  width="100%">
		<et2-link-to  id="link_to"></et2-link-to>
		<et2-link-list  id="link_to"></et2-link-list>
	</et2-vbox>
</template>
```

They share the id on purpose: `et2-link-to` makes new links, `et2-link-list` shows the ones
that exist, and both work on the same `link_to` value.

Linking is symmetric, and nothing extra is needed for the other side of it: the contact
shows the host straight away, named by `link_title()`.

![step5-02 The host listed under an Addressbook contact](https://raw.githubusercontent.com/wiki/EGroupware/example/images/step5-02.png)

## Try it

You need at least one contact in Addressbook to link to.

1. **Admin > Clear cache/register hooks.** The hook is new, and nothing works until it
   is registered.
2. Open **Example** and click **Add**. The popup still opens - now through the link
   registry rather than a hard-coded URL.
3. Enter a hostname, then open the **Links** tab. Choose *Addressbook*, type part of a
   contact's name, pick it and press **Link**.
4. Press **Save**. The link had nowhere to go until the host existed; it is stored now.
5. Reopen that host. The Links tab lists the contact.
6. Open the same contact in **Addressbook** and look at its links: your host is there,
   under the name `link_title()` gave it.
7. Back in Example, delete the host. The link disappears from the contact as well.

### Troubleshooting

**`egw.open() app "example" NOT defined in link registry!`** - the hook is not registered.
*Admin > Clear cache/register hooks*, then reload the page. If it survives that, the server
is still holding the old registry: restart php-fpm.

**The link dialog finds no hosts** - `link_query()` is what it calls. Check the app-name and
method in `search_link()`'s `query` match a real public method on `Bo`.

**Links made before the first save are lost** - `link_to` is being preserved, or `to_id` is
not reaching `save()`. Both ends are in `Ui.php` above.

**Changes in `.xet` files do not show up** - templates are cached. *Admin > Clear
cache/register hooks*.

--> continue to [step 6](https://github.com/EGroupware/example/tree/step6), which adds
client-side code in `app.ts`
