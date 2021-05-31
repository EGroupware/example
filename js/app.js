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
            function (d, b) { for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        if (typeof b !== "function" && b !== null)
            throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
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
    ExampleApp.prototype.calculatorUpdateDisplay = function (_node, _widget) {
        // cant use this.et2 in dialog
        var et2 = _widget.getInstanceManager().widgetContainer;
        et2.setValueById('currentOperand', this.currentOperand);
        et2.setValueById('previousOperand', this.previousOperand + this.operator);
    };
    ExampleApp.prototype.calculatorNumber = function (_node, _widget) {
        // cant use this.et2 in dialog
        var et2 = _widget.getInstanceManager().widgetContainer;
        this.currentOperand = et2.getValueById('currentOperand');
        var value = _widget.id.substr(4);
        //Only 1 '.' allowed
        if (value === '.' && this.currentOperand.includes('.')) //includes is red underlined but works?
            return;
        this.currentOperand += value;
        this.calculatorUpdateDisplay(_node, _widget);
    };
    ExampleApp.prototype.chooseOperation = function (_node, _widget) {
        var et2 = _widget.getInstanceManager().widgetContainer;
        var value = et2.getValueById('currentOperand');
        this.currentOperand = value;
        console.log("this.currentOperand: " + this.currentOperand);
        var operator_id = _widget.id;
        var operator;
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
            this.calculatorUpdateDisplay(_node, _widget);
        }
        else if (this.currentOperand === '') {
            this.calculatorUpdateDisplay(_node, _widget); //so it displays the operator
            return;
        }
        else
            this.calculate(_node, _widget);
    };
    ExampleApp.prototype.calculate = function (_node, _widget) {
        //Change strings to numbers
        var previousOperand = parseFloat(this.previousOperand);
        var currentOperand = parseFloat(this.currentOperand);
        //Fix issue: '' has no number it can convert to.
        if (previousOperand == NaN) {
            return;
        }
        if (currentOperand == NaN) {
            return;
        }
        //computes current and previous based on operator and set previosuValue to that value
        var result;
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
        //update the Display to show result on Screen
        this.calculatorUpdateDisplay(_node, _widget);
    };
    /**
     * Act on Clear button
     *
     * @param _node
     * @param _widget
     */
    ExampleApp.prototype.calculatorClear = function (_node, _widget) {
        this.currentOperand = '';
        this.previousOperand = '';
        this.operator = '';
        this.calculatorUpdateDisplay(_node, _widget);
    };
    return ExampleApp;
}(egw_app_1.EgwApp));
app.classes.example = ExampleApp;
//# sourceMappingURL=app.js.map