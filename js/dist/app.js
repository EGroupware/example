"use strict";
/**
 * EGroupware - Example
 *
 * @link: https://www.egroupware.org
 * @package Example
 * @author Hadi Nategh <hn-At-egroupware.org>
 * @copyright (c) 2019 by Hadi Nategh <hn-At-egroupware.org>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 */
var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
Object.defineProperty(exports, "__esModule", { value: true });
/*egw:uses
    /api/js/jsapi/egw_app.js
 */
require("jquery");
require("jqueryui");
require("../jsapi/egw_global");
require("../etemplate/et2_types");
var egw_app_1 = require("../../api/js/jsapi/egw_app");
var et2_core_widget_1 = require("../../api/js/etemplate/et2_core_widget");
var et2_widget_dialog_1 = require("../../api/js/etemplate/et2_widget_dialog");
var ExampleApp = /** @class */ (function (_super) {
    __extends(ExampleApp, _super);
    /**
     * app js initialization stage
     */
    function ExampleApp(appname) {
        var _this = _super.call(this, appname) || this;
        // app name
        _this.appname = 'example';
        _this.previousOperand = '';
        _this.currentOperand = '';
        _this.operator = '';
        _this.result = '';
        return _this;
    }
    /**
     * et2 object is ready to use
     *
     * @param {object} et2 object
     * @param {string} name template name et2_ready is called for eg. "example.edit"
     */
    ExampleApp.prototype.et2_ready = function (et2, name) {
        // call parent
        _super.prototype.et2_ready.apply(this, arguments);
    };
    /**
     * View a host / example entry
     *
     * @param {object} _action action object, attribute id contains the name of the action
     * @param {array} _selected array with selected rows, attribute id containers the row-id
     */
    ExampleApp.prototype.view = function (_action, _selected) {
        var row_id = _selected[0].id; // "example::123"
        var values = {
            content: this.egw.dataGetUIDdata(row_id).data,
            readonlys: {
                '__ALL__': true
            }
        };
        var buttons = [
            { text: this.egw.lang("close"), id: "close", image: "close" },
            { text: this.egw.lang("edit"), id: "edit", image: "edit" }
        ];
        var self = this;
        et2_core_widget_1.et2_createWidget("dialog", {
            callback: function (button) {
                if (button == 'edit') {
                    this.egw.open(values.content.host_id, self.appname, 'view');
                }
            }.bind(this),
            title: 'view host',
            buttons: buttons,
            type: et2_widget_dialog_1.et2_dialog.PLAIN_MESSAGE,
            template: this.egw.webserverUrl + '/example/templates/default/edit.xet',
            value: values
        });
    };
    /**
     * Open calculator dialog
     *
     * @param _node
     * @param _widget
     */
    ExampleApp.prototype.calculator = function (_node, _widget) {
        et2_core_widget_1.et2_createWidget("dialog", {
            callback: function (button) {
            }.bind(this),
            title: 'Calculator',
            buttons: [],
            type: et2_widget_dialog_1.et2_dialog.PLAIN_MESSAGE,
            template: this.et2.getArrayMgr('content').getEntry('calculator_tpl'),
            value: {
                content: {}
            }
        });
    };
    /**
     * Act on number buttons with id "num_N"
     *
     * @param _node
     * @param _widget
     */
    ExampleApp.prototype.calculatorNumber = function (_node, _widget, result) {
        // Reference to output line
        // cant use this.et2 in dialog
        var et2 = _widget.getInstanceManager().widgetContainer;
        var value = et2.getValueById('value');
        console.log("previousOperand: " + this.previousOperand);
        if (result == '') {
            value += _widget.id.substr(4); // id: "num_0" -> substr(4) returns 0
            this.currentOperand += value;
            et2.setValueById('value', value);
            console.log("currentOperand: " + this.currentOperand);
            console.log(typeof this.currentOperand);
        }
        else {
            et2.setValueById('value', result);
            result = '';
        }
    };
    ExampleApp.prototype.chooseOperation = function (operator_id) {
        //Check for operator
        //if previous operand is empty
        //set previousOperand equel to currentOperand
        //clear currentperand
        //if previous operand is NOT empty
        //call calculate function
        switch (operator_id) {
            case 'add':
                this.operator = '+';
                break;
            case 'sub':
                this.operator = '-';
                break;
            case 'mul':
                this.operator = '*';
                break;
            case 'div':
                this.operator = '/';
                break;
            default:
                console.log("not an allowed operator");
                return;
        }
        if (this.previousOperand = '') {
            this.previousOperand = this.currentOperand;
            this.currentOperand = '';
        }
        else {
            this.calculate();
        }
    };
    ExampleApp.prototype.calculate = function () {
        //needs 3 parameteres, currentoperand - previousOperand - operator
        var previousOperand = parseFloat(this.previousOperand);
        var currentOperand = parseFloat(this.currentOperand);
        //computes current and previous based on operator and set previosuValue to that value
        var result;
        switch (this.operator) {
            case "+":
                result = previousOperand + currentOperand; //maybe need to convert variables to floats 
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
                console.log("Not an allowed operator");
                return;
        }
        this.currentOperand = '';
        this.previousOperand = result.toString();
        //update the Display to show result on Screen
        this.calculatorNumber(this.previousOperand); //don't know what to put in here
    };
    /**
     * Act on Clear button
     *
     * @param _node
     * @param _widget
     */
    ExampleApp.prototype.calculatorClear = function (_node, _widget) {
        var et2 = _widget.getInstanceManager().widgetContainer;
        et2.setValueById('value', '');
    };
    return ExampleApp;
}(egw_app_1.EgwApp));
app.classes.example = ExampleApp;
