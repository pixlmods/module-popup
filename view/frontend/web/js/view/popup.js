/**
 * Copyright © Pixl Mods. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'jquery/ui',
    'Magento_Ui/js/modal/modal',
    'PixlMods_Popup/js/actions/send-event'
], function ($, ui, modal, sendEvent) {
    'use strict';

    $.widget('mage.pixlmodsPopup', {
        options: {
            popups: [],
            trackEvents: true,
            eventUrl: '',
            dayCookieLifetime: 1,
            cookiePrefix: 'pixlmods_popup_',
            sessionPrefix: 'pixlmods_popup_session_',
            modalOptions: {
                modalClass: 'pixlmods-popup-modal',
                type: 'popup',
                responsive: true,
                innerScroll: true,
                clickableOverlay: true
            }
        },

        /**
         * @inheritdoc
         */
        _create: function () {
            this._shown = false;
            this._eligiblePopups = this._getEligiblePopups();

            if (!this._eligiblePopups.length) {
                return;
            }

            this._armTriggers();
        },

        /**
         * Popups still eligible under their own frequency rule,
         * sorted by priority (lower number = higher priority).
         *
         * @returns {Array}
         * @private
         */
        _getEligiblePopups: function () {
            var self = this;

            return (this.options.popups || [])
                .filter(function (popup) {
                    return self._isFrequencyAllowed(popup);
                })
                .sort(function (a, b) {
                    return a.priority - b.priority;
                });
        },

        /**
         * Arms every eligible popup's own trigger.
         *
         * @private
         */
        _armTriggers: function () {
            this._eligiblePopups.forEach(this._armTrigger.bind(this));
        },

        /**
         * Routes a popup to its trigger-specific arming method.
         *
         * @param {Object} popup
         * @private
         */
        _armTrigger: function (popup) {
            var delayMs = (popup.display_delay || 0) * 1000;

            switch (popup.trigger_type) {
                case 'on_load':
                    this._armOnLoad(popup, delayMs);
                    break;

                case 'exit_intent':
                    this._armExitIntent(popup, delayMs);
                    break;

                case 'scroll':
                    this._armScroll(popup, delayMs);
                    break;

                case 'time_on_page':
                    this._armTimeOnPage(popup, delayMs);
                    break;

                case 'click_element':
                    this._armClickElement(popup, delayMs);
                    break;

                default:
                    break;
            }
        },

        /**
         * @param {Object} popup
         * @param {Number} delayMs
         * @private
         */
        _armOnLoad: function (popup, delayMs) {
            var self = this;

            setTimeout(function () {
                self._showPopup(popup);
            }, delayMs);
        },

        /**
         * Fires when the cursor leaves through the top of the viewport
         * (classic "about to close the tab" signal).
         *
         * @param {Object} popup
         * @param {Number} delayMs
         * @private
         */
        _armExitIntent: function (popup, delayMs) {
            var self = this;

            setTimeout(function () {
                self._on(document, {
                    'mouseleave': function (e) {
                        if (e.clientY <= 0) {
                            self._showPopup(popup);
                        }
                    }
                });
            }, delayMs);
        },

        /**
         * @param {Object} popup
         * @param {Number} delayMs
         * @private
         */
        _armScroll: function (popup, delayMs) {
            var self = this,
                threshold = parseFloat(popup.trigger_value) || 0;

            setTimeout(function () {
                var checkScroll = function () {
                    var scrollable = document.documentElement.scrollHeight - window.innerHeight,
                        scrolledPct = scrollable > 0 ? window.scrollY / scrollable * 100 : 100;

                    if (scrolledPct >= threshold) {
                        self._showPopup(popup);
                    }
                };

                self._on($(window), {
                    'scroll': checkScroll
                });

                // Covers pages short enough to already satisfy the threshold on arrival.
                checkScroll();
            }, delayMs);
        },

        /**
         * @param {Object} popup
         * @param {Number} delayMs
         * @private
         */
        _armTimeOnPage: function (popup, delayMs) {
            var self = this,
                seconds = parseFloat(popup.trigger_value) || 0;

            setTimeout(function () {
                self._showPopup(popup);
            }, delayMs + seconds * 1000);
        },

        /**
         * @param {Object} popup
         * @param {Number} delayMs
         * @private
         */
        _armClickElement: function (popup, delayMs) {
            var self = this,
                selector = popup.trigger_value,
                events = {};

            if (!selector) {
                return;
            }

            events['click ' + selector] = function (e) {
                e.preventDefault();
                self._showPopup(popup);
            };

            setTimeout(function () {
                self._on(document, events);
            }, delayMs);
        },

        /**
         * Displays the popup's content inside a Magento modal.
         * Only the first popup to reach here in this page view is shown;
         * everything armed after that becomes a no-op.
         *
         * @param {Object} popup
         * @private
         */
        _showPopup: function (popup) {
            var self = this,
                $content,
                $modal;

            if (this._shown || !this._isFrequencyAllowed(popup)) {
                return;
            }

            this._shown = true;
            this._sendEvent(popup, 'view');

            $content = $('<div>', { 'class': 'pixlmods-popup-modal-content' })
                .html(this._decodeContent(popup.content));

            // Any link/button clicked inside the popup's own content counts as a conversion
            // (CTA click), reported once per popup display.
            $content.on('click', 'a, button, input[type="submit"], input[type="button"]', function () {
                self._sendEvent(popup, 'conversion');
            });

            $modal = $('<div>').append($content);

            $modal.modal($.extend(true, {}, this.options.modalOptions, {
                closed: this._onModalClosed.bind(this, popup, $modal)
            }));

            $modal.modal('openModal');
        },

        /**
         * Sends popup analytics event.
         *
         * @param {Object} popup
         * @param {String} type
         * @private
         */
        _sendEvent: function (popup, type) {
            if (!this.options.eventUrl) {
                return;
            }

            if (!popup || !popup.id) {
                return;
            }

            switch (type) {
                case 'view':
                    sendEvent.view(this.options.eventUrl, popup.id);
                    break;

                case 'close':
                    sendEvent.close(this.options.eventUrl, popup.id);
                    break;

                case 'conversion':
                    sendEvent.conversion(this.options.eventUrl, popup.id);
                    break;

                default:
                    sendEvent.send(this.options.eventUrl, {
                        popup_id: popup.id,
                        event_type: type
                    });
                    break;
            }
        },

        /**
         * @param {Object} popup
         * @param {jQuery} $modal
         * @private
         */
        _onModalClosed: function (popup, $modal) {
            this._sendEvent(popup, 'close');
            this._markAsShown(popup);

            $modal.remove();
        },

        /**
         * @param {String} encoded base64
         * @returns {String}
         * @private
         */
        _decodeContent: function (encoded) {
            try {
                return decodeURIComponent(escape(window.atob(encoded)));
            } catch (e) {
                return '';
            }
        },

        /**
         * @param {Object} popup
         * @returns {Boolean}
         * @private
         */
        _isFrequencyAllowed: function (popup) {
            switch (popup.frequency) {
                case 'session':
                    return sessionStorage.getItem(this.options.sessionPrefix + popup.id) === null;

                case 'day':
                case 'once':
                    return this._getCookie(this.options.cookiePrefix + popup.id) === null;

                case 'always':
                default:
                    return true;
            }
        },

        /**
         * Persists the "already shown" state according to the popup's frequency.
         *
         * @param {Object} popup
         * @private
         */
        _markAsShown: function (popup) {
            switch (popup.frequency) {
                case 'session':
                    sessionStorage.setItem(this.options.sessionPrefix + popup.id, '1');
                    break;

                case 'day':
                    this._setCookie(this.options.cookiePrefix + popup.id, '1', this.options.dayCookieLifetime);
                    break;

                case 'once':
                    this._setCookie(this.options.cookiePrefix + popup.id, '1', 365);
                    break;

                default:
                    break;
            }
        },

        /**
         * @param {String} name
         * @param {String} value
         * @param {Number} days
         * @private
         */
        _setCookie: function (name, value, days) {
            var expires = '',
                date;

            if (days) {
                date = new Date();
                date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
                expires = '; expires=' + date.toUTCString();
            }

            document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/; SameSite=Lax';
        },

        /**
         * @param {String} name
         * @returns {String|null}
         * @private
         */
        _getCookie: function (name) {
            var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));

            return match ? decodeURIComponent(match[1]) : null;
        }
    });

    // Standard Magento x-magento-init entry point: applies the widget
    // to the resolved element with the config passed in the layout/template.
    return function (config, element) {
        $(element).pixlmodsPopup(config);
    };
});
