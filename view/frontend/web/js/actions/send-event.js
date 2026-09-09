/**
 * Copyright © Pixl Mods. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery'
], function ($) {
    'use strict';

    return {
        /**
         * Send popup event
         *
         * @param {String} url
         * @param {Object} data
         * @returns {*}
         */
        send: function (url, data) {
            return $.ajax({
                url: url,
                type: 'POST',
                data: data
            });
        },

        /**
         * Register popup view
         *
         * @param {String} url
         * @param {Number} popupId
         */
        view: function (url, popupId) {
            return this.send(url, {
                popup_id: popupId,
                event_type: 'view'
            });
        },

        /**
         * Register popup close
         *
         * @param {String} url
         * @param {Number} popupId
         */
        close: function (url, popupId) {
            return this.send(url, {
                popup_id: popupId,
                event_type: 'close'
            });
        },

        /**
         * Register popup conversion
         *
         * @param {String} url
         * @param {Number} popupId
         */
        conversion: function (url, popupId) {
            return this.send(url, {
                popup_id: popupId,
                event_type: 'conversion'
            });
        }
    };
});
