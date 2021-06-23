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
import {et2_button} from "../../api/js/etemplate/et2_widget_button";
import { widget } from 'jquery';

class ExampleApp extends EgwApp
{	
	
	// app name
	readonly appname = 'example';
	
	//Some variables for calculator
	public previousOperand : string;
	public currentOperand : string;
	public operator : string;

	/**
	 * app js initialization stage
	 */
	constructor(appname: string)
	{
		super(appname);

		this.previousOperand = '';
		this.currentOperand = '';
		this.operator = '';
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

	/**
	 * Open calculator dialog
	 *
	 * @param _node
	 * @param _widget
	 */

	
	calculator(_node : HTMLButtonElement, _widget : et2_button)
	{
		et2_createWidget("dialog",{
			callback: function(button){
				
			}.bind(this),
			title: 'Calculator',
			buttons: [],
			type: et2_dialog.PLAIN_MESSAGE,
			template: this.et2.getArrayMgr('content').getEntry('calculator_tpl'),
			value: {
				content: {}
			}
		});
	}

	/**
	 * Act on number buttons with id "num_N"
	 *
	 * @param _node
	 * @param _widget
	 */

	calculatorUpdateDisplay(_node : HTMLButtonElement, _widget : et2_button) {
			
		// cant use this.et2 in dialog
		const et2 = _widget.getInstanceManager().widgetContainer;
		et2.setValueById('currentOperand', this.currentOperand);
		et2.setValueById('previousOperand', this.previousOperand + this.operator);
	}

	//Apend Number to currentOperand
	calculatorNumber(_node : HTMLButtonElement, _widget : et2_button) {	

		// cant use this.et2 in dialog
		const et2 = _widget.getInstanceManager().widgetContainer;
		this.currentOperand = et2.getValueById('currentOperand');
		
		let value = _widget.id.substr(4);

		//Only 1 '.' allowed.
		if (value === '.' && this.currentOperand.includes('.')) //includes is red underlined but works?
			return;

		this.currentOperand += value;

		this.calculatorUpdateDisplay(_node,_widget);
	}		

	//Choose a operation
	chooseOperation(_node : HTMLButtonElement, _widget : et2_button) {

		const et2 = _widget.getInstanceManager().widgetContainer;
		let value = et2.getValueById('currentOperand');

		this.currentOperand = value;
		console.log("this.currentOperand: " + this.currentOperand);

		let operator_id = _widget.id;
		let operator;

		switch (operator_id) {
			case 'add':
				operator = '+';
				break;
			case 'sub':
				operator = '-';
				break;
			case 'mul':
				operator = '*';
				break;
			case 'div':
				operator = '/';
				break;
			default:
				console.log("not an allowed operator");
				return;	
		}

		this.operator = operator;

		//previousOperand stores the value and current operand is reset
		if (this.previousOperand === '') {

			this.previousOperand = this.currentOperand;
			this.currentOperand = '';
			this.calculatorUpdateDisplay(_node,_widget);
		}
		else if(this.currentOperand === '') { 
			this.calculatorUpdateDisplay(_node,_widget); //so it displays the operator
			return;
		}
		else this.calculate(_node, _widget);
	}

	calculate (_node : HTMLButtonElement, _widget : et2_button) {

		//Change strings to numbers
		let previousOperand = parseFloat(this.previousOperand);
		let currentOperand = parseFloat(this.currentOperand);

		//Fix issue: '' has no number it can convert to.
		if(previousOperand == NaN) {
			return
		}
		if(currentOperand == NaN) {
			return
		}

		//computes current and previous based on operator and set previousValue to that value
		let result;
		switch (this.operator) {
			case "+":
				result = previousOperand + currentOperand;
				break;
			case "-":
				result = previousOperand - currentOperand;
				break;
			case "*":
				result = previousOperand * currentOperand;
				break;
			case "/":
				result = previousOperand / currentOperand;
				break;
			default:
				console.log("Operation failed");
				return;
		}

		this.currentOperand = '';
		this.previousOperand = result.toString();
		this.operator = '';
	
		//update the Display to show result on Screen
		this.calculatorUpdateDisplay(_node, _widget);
	}
	

	/**
	 * Act on Clear button
	 *
	 * @param _node
	 * @param _widget
	 */
	calculatorClear(_node : HTMLButtonElement, _widget : et2_button)
	{	
		this.currentOperand = '';
		this.previousOperand = '';
		this.operator = '';
		this.calculatorUpdateDisplay(_node, _widget);
	}	
}

app.classes.example = ExampleApp;

