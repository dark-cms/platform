/* global define */
define(['jquery', 'underscore', 'backbone', 'routing', 'oro/navigation', 'oro/translator', 'oro/mediator',
    'oro/messenger', 'oro/error', 'oro/widget-manager', 'oro/modal', 'oro/datagrid/action-launcher'],
function($, _, Backbone, routing, Navigation, __, mediator, messenger, error, widgetManager, Modal, ActionLauncher) {
    'use strict';

    /**
     * Abstract action class. Subclasses should override execute method which is invoked when action is running.
     *
     * Triggers events:
     *  - "preExecute" before action is executed
     *  - "postExecute" after action is executed
     *
     * @export  oro/datagrid/abstract-action
     * @class   oro.datagrid.AbstractAction
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        /** @property {Function} */
        launcherPrototype: ActionLauncher,

        /** @property {Object} */
        launcherOptions: undefined,

        /** @property {String} */
        name: null,

        /** @property {oro.datagrid.Grid} */
        datagrid: null,

        /** @property {string} */
        route: null,

        /** @property {Object} */
        route_parameters: null,

        /** @property {Boolean} */
        confirmation: false,

        /** @property {String} */
        frontend_type: null,

        /** @property {Object} */
        frontend_options: null,

        /** @property {string} */
        identifierFieldName: 'id',

        messages: {},

        dispatched: false,

        /** @property {Object} */
        defaultMessages: {
            confirm_title: __('Execution Confirmation'),
            confirm_content: __('Are you sure you want to do this?'),
            confirm_ok: __('Yes, do it'),
            success: __('Action was successfully performed.'),
            error: __('Action was not performed.'),
            empty_selection: __('Please, select item to perform action.')
        },

        /**
         * Initialize view
         *
         * @param {Object} options
         * @param {Object} [options.launcherOptions] Options for new instance of launcher object
         */
        initialize: function(options) {
            options = options || {};

            if (!options.datagrid) {
                throw new TypeError("'datagrid' is required");
            }
            this.datagrid = options.datagrid;

            _.defaults(this.messages, this.defaultMessages);

            if (options.launcherOptions) {
                this.launcherOptions = _.extend({}, this.launcherOptions, options.launcherOptions);
            }

            this.launcherOptions = _.extend({
                action: this
            }, this.launcherOptions);

            Backbone.View.prototype.initialize.apply(this, arguments);
        },

        /**
         * Creates launcher
         *
         * @param {Object} options Launcher options
         * @return {oro.datagrid.ActionLauncher}
         */
        createLauncher: function(options) {
            options = options || {};
            if (_.isUndefined(options.icon) && !_.isUndefined(this.icon)) {
                options.icon = this.icon;
            }
            _.defaults(options, this.launcherOptions);
            return new (this.launcherPrototype)(options);
        },

        /**
         * Run action
         */
        run: function() {
            var options = {
                doExecute: true
            };
            this.trigger('preExecute', this, options);
            if (options.doExecute) {
                this.execute();
                this.trigger('postExecute', this, options);
            }
        },

        /**
         * Execute action
         */
        execute: function() {
            var eventName = this.getEventName();
            mediator.once(eventName, this.executeConfiguredAction, this);
            this._confirmationExecutor(
                _.bind(
                    function() {mediator.trigger(eventName, this);},
                    this
                )
            );
        },

        getEventName: function() {
            return 'grid_action_execute:' + this.datagrid.name + ':' + this.name;
        },

        executeConfiguredAction: function(action) {
            if (action.frontend_type == 'ajax') {
                this._handleAjax(action);
            } else if (action.frontend_type == 'redirect') {
                this._handleRedirect(action);
            } else if (widgetManager.isSupportedType(action.frontend_type)) {
                this._handleWidget(action);
            }
        },

        _confirmationExecutor: function(callback) {
            if (this.confirmation) {
                this.getConfirmDialog(callback).open();
            } else {
                callback();
            }
        },

        _handleWidget: function(action) {
            if (action.dispatched) {
                return;
            }
            action.frontend_options.url = action.frontend_options.url || this.getLinkWithParameters();
            action.frontend_options.title = action.frontend_options.title || this.label;
            widgetManager.createWidget(action.frontend_type, action.frontend_options).render();
        },

        _handleRedirect: function(action) {
            if (action.dispatched) {
                return;
            }
            var url = action.getLinkWithParameters(),
                navigation = Navigation.getInstance();
            if (navigation) {
                navigation.processRedirect({
                    fullRedirect: false,
                    location: url
                });
            } else {
                location.href = url;
            }
        },

        _handleAjax: function(action) {
            if (action.dispatched) {
                return;
            }
            action.datagrid.showLoading();
            $.ajax({
                url: action.getLink(),
                data: action.getActionParameters(),
                context: action,
                dataType: 'json',
                error: action._onAjaxError,
                success: action._onAjaxSuccess
            });
        },

        _onAjaxError: function(jqXHR, textStatus, errorThrown) {
            error.dispatch(null, jqXHR);
            this.datagrid.hideLoading();
        },

        _onAjaxSuccess: function(data, textStatus, jqXHR) {
            this.datagrid.hideLoading();
            this.datagrid.collection.fetch();

            var defaultMessage = data.successful ? this.messages.success : this.messages.error,
                message = data.message || defaultMessage;
            if (message) {
                messenger.notificationFlashMessage(data.successful ? 'success' : 'error', message);
            }
        },

        /**
         * Get action url
         *
         * @return {String}
         * @private
         */
        getLink: function(parameters) {
            if (_.isUndefined(parameters)) {
                parameters = {};
            }
            return routing.generate(
                this.route,
                _.extend(
                    this.route_parameters,
                    parameters
                )
            );
        },

        /**
         * Get action url with parameters added.
         *
         * @returns {String}
         */
        getLinkWithParameters: function() {
            return this.getLink(this.getActionParameters());
        },

        /**
         * Get action parameters
         *
         * @returns {Object}
         */
        getActionParameters: function() {
            return {};
        },

        /**
         * Get view for confirm modal
         *
         * @return {oro.Modal}
         */
        getConfirmDialog: function(callback) {
            return new Modal({
                title: this.messages.confirm_title,
                content: this.messages.confirm_content,
                okText: this.messages.confirm_ok
            }).on('ok', callback);
        }
    });
});
