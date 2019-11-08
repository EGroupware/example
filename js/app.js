/**
 * EGroupware - Example
 *
 * @link http://www.egroupware.org
 * @package Example
 * @author Hadi Nategh <hn-At-egroupware.org>
 * @copyright (c) 2019 by Hadi Nategh <hn-At-egroupware.org>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id$
 */

app.classes.example = AppJS.extend(
{
	// app name
	appname: 'example',

	/**
	 * app js initialization stage
	 */
	init: function()
	{
		this._super.apply(this, arguments);
	},

	/**
	 * et2 object is ready to use
	 *
	 * @param {object} et2
	 * @param {string} name
	 */
	et2_ready: function(et2,name)
	{
		// call parent
		this._super.apply(this, arguments);
	},

	view: function(_action, _selected) {
		var row_id = _selected[0].id;

		var values = {
			content: egw.dataGetUIDdata(row_id).data,
			readonlys: {
				'__ALL__':true
			}
		};
		var buttons = [
			{text: this.egw.lang("close"), id: "close", image: "close"},
			{text: this.egw.lang("edit"), id:"edit", image: "edit"}
		];
		var self = this;

		et2_createWidget("dialog",{
			callback: function(button){
				if (button == 'edit')
				{
					egw.open(values.content.host_id, self.appname, 'view');
				}
			},
			title: 'view host',
			buttons: buttons,
			type: et2_dialog.PLAIN_MESSAGE,
			template: egw.webserverUrl+'/example/templates/default/edit.xet',
			value: values
		});

	}
});