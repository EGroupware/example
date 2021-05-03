/**
 * EGroupware - Example
 *
 * @link: https://www.egroupware.org
 * @package Example
 * @author Hadi Nategh <hn-At-egroupware.org>
 * @copyright (c) 2019 by Hadi Nategh <hn-At-egroupware.org>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 */

/*egw:uses
	/api/js/jsapi/egw_app.js
 */

import 'jquery';
import 'jqueryui';
import '../jsapi/egw_global';
import '../etemplate/et2_types';

import { EgwApp } from '../../api/js/jsapi/egw_app';
import {et2_createWidget} from "../../api/js/etemplate/et2_core_widget";
import {et2_dialog} from "../../api/js/etemplate/et2_widget_dialog";

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
			{text: this.egw.lang("close"), id: "close", image: "close"},
			{text: this.egw.lang("edit"), id: "edit", image: "edit"}
		];
		var self = this;

		et2_createWidget("dialog",{
			callback: function(button){
				if (button == 'edit')
				{
					this.egw.open(values.content.host_id, self.appname, 'view');
				}
			}.bind(this),
			title: 'view host',
			buttons: buttons,
			type: et2_dialog.PLAIN_MESSAGE,
			template: this.egw.webserverUrl+'/example/templates/default/edit.xet',
			value: values
		});
	}
}

app.classes.example = ExampleApp;