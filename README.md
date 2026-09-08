# Example app for EGroupware development

1. Step: [a minimal app](https://github.com/EGroupware/example/tree/step1)
2. Step: [an edit dialog](https://github.com/EGroupware/example/tree/step2)
3. Step: [storing hosts in the database](https://github.com/EGroupware/example/tree/step3)

## 4. Step: a list of hosts

The app gets its front page: a list you can sort, search and act on, with the edit dialog
opening from it as a popup.

![step4-01 The host list](https://raw.githubusercontent.com/wiki/EGroupware/example/images/step4-01.png)

Lists are built with `et2-nextmatch`. You give it a template describing one row, and a
server method to fetch rows; it handles paging, sorting, searching, selection and the
context menu.

### templates/default/index.xet

Three templates in one file. The last one is the page:

```xml
<template id="example.index">
	<template template="example.index.header" slot="main-header"></template>
	<et2-nextmatch id="nm" template="example.index.rows"></et2-nextmatch>
</template>
```

`slot="main-header"` puts our toolbar in the application header - the bar with the app name
in it - rather than above the list. That is where the Add button and the search box live:

```xml
<et2-toolbar id="toolbar">
	<et2-button-icon statustext="Add" id="add" image="add" slot="prefix"
		onclick="egw(window).openPopup(egw::link('/index.php','menuaction=example.EGroupware\\Example\\Ui.edit'),'640','480','_blank','example',null,true); return false;"
		noSubmit="true"></et2-button-icon>
	<template template="api.search-button" slot="prefix"></template>
</et2-toolbar>
```

`noSubmit="true"` stops the button submitting the form as well as running its `onclick`.

`example.index.rows` describes the header row and one data row:

```xml
<row class="th">
	<et2-nextmatch-sortheader label="Name" id="host_name"/>
	<et2-nextmatch-header label="Description" id="host_description"/>
</row>
<row class="$class">
	<et2-description id="host_name"></et2-description>
	<et2-description id="host_description"></et2-description>
</row>
```

The header row decides what each column can do. `et2-nextmatch-sortheader` makes a column
sortable; `et2-nextmatch-header` is a plain label. There are filtering headers too -
`et2-nextmatch-header-account` is used here for the Creator and Modified-by columns, and
anything that filters also appears in the list's filter panel.

In the data row, a widget's `id` is the row field it shows: `id="host_name"` displays that
row's `host_name`. That holds however deeply the widget sits, so a column stacking two
values needs nothing beyond the ids:

```xml
<et2-vbox>
	<et2-date-time id="host_created" readonly="true"></et2-date-time>
	<et2-select-account id="host_creator" readonly="true"></et2-select-account>
</et2-vbox>
```

Everything other than that value is set with an expression. `$field` reads a field from the
row, `$[parent.child]` reads a nested one, and `@field` looks up an entry by key. They work in
any attribute - the row's own `class="$class"` is the same mechanism. `value` is an ordinary
property, so an expression can set it as well, which is what to reach for when a widget shows
a field other than the one its id names.

Rows are rendered inside the grid's own shadow DOM, which ordinary application CSS cannot
reach, so row styling is loaded into it explicitly:

```xml
<et2-styles src="rows.css"></et2-styles>
```

### src/Ui.php

`get_rows()` is what the list calls for data. It gets the query - search text, sort order,
which page - and answers the rows plus the total number matching, which the pager needs.
`Api\Storage` does the work:

```php
public function get_rows($query, ?array &$rows=null, ?array &$readonlys=null)
{
	if (empty($query['csv_export']))
	{
		Api\Cache::setSession(Bo::APP, 'index', $query);
	}

	return $this->bo->get_rows($query, $rows, $readonlys);
}
```

Storing the query in the session gives two things: the user's sorting and search survive a
reload, and `action()` below can re-apply exactly the query the list was showing.

`csv_export` is set on calls that are not really listings, and their query must not be
stored - [step 10](https://github.com/EGroupware/example/tree/step10) runs into one.

It is deliberately **not** in `$public_functions`. The list reaches it through the
template's own exec-id, so it never needs to be callable from a URL.

`index()` describes the list rather than drawing it:

```php
$content = ['nm' => array_merge($nm, [
	'get_rows'            => Bo::APP.'.'.self::class.'.get_rows',
	'no_filter'           => true,
	'no_filter2'          => true,
	'no_cat'              => true,
	'row_id'              => 'host_id',
	'row_modified'        => 'host_modified',
	'actions'             => $this->get_actions(),
	'placeholder_actions' => ['add'],
])];
```

`row_id` names the field identifying a row, which is what selection and actions work with.
`row_modified` lets the list notice changed rows. `no_filter`, `no_filter2` and `no_cat` say
we are not offering those standard filters, so they are left out of the filter panel.
`placeholder_actions` is what the context menu offers when the list is empty.

The session gives back only what the *user* changed - sorting, searching. Everything the app
owns is merged fresh on top, so a session stored by an older version of the app cannot
pin an out-of-date `get_rows` or action list.

### Actions

`get_actions()` returns the context menu. *Edit* and *Add* are URLs opened as popups; the
`$id` in the edit URL is replaced with the id of the row you clicked:

```php
'edit' => [
	'caption' => 'Edit',
	'default' => true,
	'allowOnMultiple' => false,
	'url' => 'menuaction=example.'.self::class.'.edit&host_id=$id',
	'popup' => '640x480',
	'group' => $group=0,
],
```

`'default' => true` makes it what double-clicking a row does. `allowOnMultiple` keeps it out
of the menu when several rows are selected.

*Delete* has no URL, so it submits back to `index()`, which hands it to `action()`:

```php
if ($select_all)
{
	$selected = array_column($this->bo->search($query['search'] ?? '', ['host_id'],
		'', '', '%', false, 'OR', false, $query['col_filter'] ?? []), 'host_id');
}
```

That is what "select all" has to mean: every host matching the current search, not only the
rows the browser happened to have loaded. `$start=false` returns them unpaged. The query
comes from the session rather than from the request, so the browser cannot widen a
select-all by sending filters it never displayed.

### The edit dialog becomes a popup

Opened from the list, `edit()` now has an opener to talk to:

```php
Api\Framework::refresh_opener(lang('Entry saved.'), Bo::APP,
	$this->bo->data['host_id'], empty($content['host_id']) ? 'add' : 'edit');

if ($button === 'save')
{
	Api\Framework::window_close();	// does NOT return
}
```

`refresh_opener()` tells the list which row changed and how, so it updates that row instead
of reloading everything. *Save* then closes the popup; *Apply* stays open, which is the only
difference between them. `window_close()` does not return, so nothing after it runs.

*Cancel* is meaningful again now that there is a window to close.

## Try it

1. Open **Example**. The list shows your hosts, newest change first.
2. Click **Add** in the app header. The edit dialog opens as a popup.
3. Save it - the popup closes and the new host is in the list, without reloading the page.
4. Double-click a row to edit it. Change the description and press **Apply**: the list
   updates behind the popup, which stays open.
5. Click a column heading - `#`, Name, Created - to sort by it.
6. Right-click a row, choose **Delete**, confirm. The message counts what went.
7. Type in the search box; the list narrows. Reload the page: your search is still there.

### Troubleshooting

**The list is empty but there are rows in the table** - open the browser console. A row
template that fails to build reports there, not on the page, and leaves an empty list.

**A column shows nothing** - the widget's `id` has to match a field name from `get_rows()`
exactly. Log a row in the browser console to see what the list actually received.

**Changes in `.xet` files do not show up** - templates are cached. *Admin > Clear
cache/register hooks*.

--> continue to [step 5](https://github.com/EGroupware/example/tree/step5), which links hosts
to entries in other applications
