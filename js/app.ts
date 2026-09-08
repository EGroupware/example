/**
 * EGroupware - Example - Javascript UI
 *
 * @link https://www.egroupware.org
 * @package example
 * @author Hadi Nategh <hn-AT-egroupware.org>
 * @author Nathan Gray <ng-AT-egroupware.org>
 * @copyright (c) 2019-26 by Hadi Nategh <hn-AT-egroupware.org>
 * @license https://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 */

import {EgwApp} from "../../api/js/jsapi/egw_app";
import {Et2Dialog} from "../../api/js/etemplate/Et2Dialog/Et2Dialog";
// egw and app are globals - no import needed

/**
 * UI for the example application
 *
 * The framework creates one instance per application and puts it in app.example,
 * which is how a template or an action reaches these methods.
 */
class ExampleApp extends EgwApp
{
	/**
	 * Constructor
	 */
	constructor()
	{
		super('example');
	}

	/**
	 * Show a host without opening the edit popup
	 *
	 * Runs as the list's "view" action, so it is handed the action and the rows it
	 * was run on.
	 *
	 * @param _action the action that ran, its id is "view"
	 * @param _selected the selected rows
	 */
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
}

app.classes.example = ExampleApp;
