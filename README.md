# Example app for EGroupware development

1. Step: [a minimal app](https://github.com/EGroupware/example/tree/step1)
2. Step: [an edit dialog](https://github.com/EGroupware/example/tree/step2)
3. Step: [storing hosts in the database](https://github.com/EGroupware/example/tree/step3)
4. Step: [a list of hosts](https://github.com/EGroupware/example/tree/step4)
5. Step: [linking hosts to other entries](https://github.com/EGroupware/example/tree/step5)

## 6. Step: code that runs in the browser

Everything so far has been server-side: PHP decides, the browser draws. This step adds the
app's own JavaScript class, and uses it for a *View* action that shows a host in a dialog
without opening the edit popup or asking the server for anything.

![step6-01 The view dialog](https://raw.githubusercontent.com/wiki/EGroupware/example/images/step6-01.png)

### js/app.ts

Every application may have one class, extending `EgwApp`. The framework creates a single
instance of it and puts it in `app.example`, which is how templates and actions reach these
methods.

```ts
import {EgwApp} from "../../api/js/jsapi/egw_app";
import {Et2Dialog} from "../../api/js/etemplate/Et2Dialog/Et2Dialog";
// egw and app are globals - no import needed

class ExampleApp extends EgwApp
{
	constructor()
	{
		super('example');
	}
}

app.classes.example = ExampleApp;
```

The constructor takes no arguments and passes the application name to `super()`.

### The view action

`view()` is handed the action and the rows it ran on, like any action callback:

```ts
view(_action, _selected)
{
	// The list already holds every row it has fetched, so viewing one needs no
	// request: ask the client-side store for the row by the id the list uses.
	const rowId = _selected[0].id;			// "example::123"
	const values = {
		content: this.egw.dataGetUIDdata(rowId).data,
		readonlys: {__ALL__: true}
	};

	const dialog = new Et2Dialog(this.egw);
	dialog.transformAttributes({
		title: this.egw.lang('View host'),
		// the edit dialog's own template, shown read-only
		template: this.egw.link('/example/templates/default/edit.xet'),
		type: Et2Dialog.PLAIN_MESSAGE,
		// button_id is what the dialog reports back to the callback, and a custom
		// button may use any non-numeric string for it
		buttons: [
			{button_id: 'close', label: this.egw.lang('Close'), id: 'close', image: 'close'},
			{button_id: 'edit', label: this.egw.lang('Edit'), id: 'edit', image: 'edit'}
		],
		callback: (_button) =>
		{
			if (_button === 'edit')
			{
				this.egw.open(values.content.host_id, 'example', 'edit');
			}
		},
		width: 400,
		value: values
	});
	// adding it to the document is what opens it
	document.body.appendChild(dialog);
}
```

Three things worth pulling out.

**The data is already here.** The list keeps every row it fetched in a client-side store, so
`dataGetUIDdata()` answers immediately and the dialog opens with no round trip.

**The dialog reuses the edit template**, handed `readonlys: {__ALL__: true}` to show it
read-only. That is why the buttons in `edit.xet` gained `hideOnReadonly="true"` - Save,
Apply, Cancel and Delete are meaningless here, and hide themselves rather than needing a
second template.

**`button_id` is what comes back** to the callback. Set it on any custom button; the
built-in `Et2Dialog.BUTTONS_*` sets already have theirs.

[The whole file on this branch](https://github.com/EGroupware/example/blob/step6/js/app.ts)

### Building it

`app.ts` is TypeScript, and the framework loads `js/app.min.js`. **Until it is built, nothing
in this step happens** - `app.example` does not exist, and the action silently does nothing.
From the EGroupware root:

```bash
npm run build
```

Rollup finds the app by itself: any top-level `<app>/js/app.ts` is picked up, which is one
reason an application lives beside `api/` rather than anywhere else. While working on
client-side code, two more are worth knowing:

```bash
npm run build:watch    # rebuild whenever a source file changes
npm run typecheck      # type errors without building
```

The result, `js/app.min.js`, is a build artifact and is not committed - this branch
`.gitignore`s it, so you build it yourself after checking out.

### src/Ui.php

The action that calls it. Actions so far have been URLs; this one names a method:

```php
'view' => [
	'caption' => 'View',
	'default' => true,
	'allowOnMultiple' => false,
	// not a URL: this one runs a method on our own app object
	'onExecute' => 'javaScript:app.example.view',
	'group' => $group=0,
],
```

`javaScript:` followed by a path from `app` is resolved in the browser and called with the
action and the selection. Making *View* the default means double-clicking a row opens the
dialog; *Edit* keeps its popup and is one click further away in the context menu.

## Try it

1. Run `npm run build` in the EGroupware root, then reload with a forced refresh so the
   browser takes the new file. A plain reload often will not: use the browser's
   *empty cache and hard reload*.
2. Open **Example** and double-click a row. The view dialog opens, filled in, with only
   *Close* and *Edit* on it.
3. Press **Edit** - the edit popup opens on that host.
4. Right-click a row: *View* and *Edit* are both offered now.

### Troubleshooting

**Double-clicking does nothing, and the console says nothing** - `js/app.min.js` has not been
built. Run `npm run build`; check `app.example` exists in the browser console afterwards.

**It builds, but the browser keeps running the old code** - `app.min.js` is cached under an
unchanging name, so a normal reload can serve the previous build. Use *empty cache and hard
reload*, which is the browser's own, not EGroupware's.

**`app.example` exists but `view` throws** - the constructor must take no arguments and pass
the application name to `super()` itself.

**The dialog opens empty** - the template path is wrong, or the id in the `.xet` does not
match. Check the network tab for `edit.xet`.

**A dialog button does nothing** - it needs a `button_id`; that is the value the callback is
given.

--> continue to [step 7](https://github.com/EGroupware/example/tree/step7), which adds custom
fields
