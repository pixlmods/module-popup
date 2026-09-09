/**
 * Copyright © Pixl Mods. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Ui/js/form/form',
    'Magento_Ui/js/modal/confirm',
    'mage/translate'
], function (Form, confirm, $t) {
    'use strict';

    return Form.extend({
        /**
         * Delete popup
         *
         * @param {String} url
         */
        deletePopup: function (url) {
            confirm({
                title: $t('Delete Popup'),
                content: $t('Are you sure you want to delete this popup?'),
                actions: {
                    confirm: function () {
                        window.location.href = url;
                    }
                }
            });
        }
    });
});
