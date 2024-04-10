# Example app for EGroupware development

#### 1. Step: [minimal "Hello World" app](https://github.com/EGroupware/example/tree/step1)
#### 2. Step: [an edit dialog](https://github.com/EGroupware/example/tree/step2)
#### 3. Step: [create a database table for data persistence](https://github.com/EGroupware/example/tree/step3)
#### 4. Step: [add UI to list hosts](https://github.com/EGroupware/example/tree/step4)
#### 5. Step: [Linking with other EGroupware entries and attaching files](https://github.com/EGroupware/example/tree/step5)
#### 6. Step: Client-side actions in TypeScript and eTemplate dialogs
We're adding now a dialog to display a host-entry without server-side interaction.
![step6-js-view](https://user-images.githubusercontent.com/972180/68548645-82845880-03ef-11ea-9fc5-3a4c6ea434a8.png)

* To do so we use the [edit.xet](https://github.com/EGroupware/example/tree/step6/templates/default/edit.xet) template from the previous steps, but use the data from our list, by adding a "view" action to our [Ui::get_actions()](https://github.com/EGroupware/example/tree/step6/src/Ui.php#L174) method using the "onExecute" attribute:
```
	protected function get_actions()
	{
		return [
			'view' => [
				'caption' => 'View',
				'default' => true,
				'allowOnMultiple' => false,
				'onExecute' => 'javaScript:app.example.view',
				'group' => $group=0,
			],
			'edit' => [
				'caption' => 'Edit',
// rest of the method is unchanged
```

* value "javaScript:app.example.view" references our client-side application object for the example app implemented by a new file [js/apps.js](https://github.com/EGroupware/example/tree/step6/js/app.js), which get's automatically loaded by the framework, if it exists:
```
class ExampleApp extends EgwApp
{
	// app name
	readonly appname = 'example';

	/**
	 * app js initialization stage
	 */
	constructor(appname: string)
	{
		super(appname);
	}

	/**
	 * et2 object is ready to use
	 *
	 * @param {object} et2 object
	 * @param {string} name template name et2_ready is called for eg. "example.edit"
	 */
	et2_ready(et2,name)
	{
		// call parent
		super.et2_ready.apply(this, arguments);
	}

```
The above et2_ready method get's called, once an eTemplate is fully loaded and can e.g. be used to modify it on client-side or register more handles. As the app object get shared by all views / templates of an app, you can use the ```name``` parameter of ```et2_ready``` to distinguish between them.

The ```view``` method get's called by our new view action:
```
	/**
	 * View a host / example entry
	 *
	 * @param {object} _action action object, attribute id contains the name of the action
	 * @param {array} _selected array with selected rows, attribute id containers the row-id
	 */
	view(_action, _selected)
	{
		var row_id = _selected[0].id;	// "example::123"

		var values = {
			content: this.egw.dataGetUIDdata(row_id).data,
			readonlys: {
				'__ALL__':true
			}
		};
		var buttons = [
			{label: this.egw.lang("close"), id: "close", image: "close"},
			{label: this.egw.lang("edit"), id: "edit", image: "edit"}
		];
		var self = this;

		// Pass egw in the constructor
		const dialog = new Et2Dialog(this.egw);

		// Set attributes.  They can be set in any way, but this is convenient.
		dialog.transformAttributes({
			callback: button => {
				if (button == 'edit')
				{
					this.egw.open(values.content.host_id, self.appname, 'view');
				}
			},
			title: 'view host',
			buttons: buttons,
			type: Et2Dialog.PLAIN_MESSAGE,
			template: this.egw.webserverUrl+'/example/templates/default/edit.xet',
			width: 400,
			value: values
		});
		// Add to DOM, dialog will auto-open
		document.body.appendChild(dialog);
	}
}

app.classes.example = ExampleApp;
```
--> [continue to step 7](https://github.com/EGroupware/example/tree/step7) by checking out branch ```step7``` in your workingcopy