/**
* Copyright © Pixl Mods. All rights reserved.
* See COPYING.txt for license details.
 */
define([
    'jquery',
    'Magento_Ui/js/modal/modal'
], function ($, modal) {
    'use strict'

    const popupID = '#pixlmods-popup'

    return function () {
        const options = {
            type: 'popup',
            responsive: true,
            clickableOverlay: false,
            autoOpen: true,
            modalClass: 'pixlmods-popup',
            buttons: []
        }

        const popup = modal(options, $(popupID))
    }
})
