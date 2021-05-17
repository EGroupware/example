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
            template: this.egw.webserverUrl + '/example/templates/default/calculator.xet',
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
    ExampleApp.prototype.calculatorNumber = function (_node, _widget) {
        var value = this.et2.getValueById('value');
        value += _widget.id.substr(4); // id: "num_0"
        this.et2.setValueById('value', value);
    };
    /**
     * Act on Clear button
     *
     * @param _node
     * @param _widget
     */
    ExampleApp.prototype.calculatorClear = function (_node, _widget) {
        this.et2.setValueById('value', '');
    };
    return ExampleApp;
}(egw_app_1.EgwApp));
app.classes.example = ExampleApp;
//# sourceMappingURL=app.js.map