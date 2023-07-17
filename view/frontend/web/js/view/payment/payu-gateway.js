/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';

        let config = window.checkoutConfig.payment,
            creditCard = 'payu_gateway_creditcard',
            discoveryMiles = 'payu_gateway_discovery_miles';

        if (config[creditCard].isActive && !config[creditCard].isEnterprise) {
            rendererList.push(
                {
                    type: creditCard,
                    component: 'PayU_Gateway/js/view/payment/method-renderer/default'
                },
            );
        }

        if (config[creditCard].isActive && config[creditCard].isEnterprise) {
            rendererList.push(
                {
                    type: creditCard,
                    component: 'PayU_Gateway/js/view/payment/method-renderer/cc-form'
                },
            );
        }

        if (config[discoveryMiles].isActive) {
            rendererList.push(
                {
                    type: 'payu_gateway_discovery_miles',
                    component: 'PayU_Gateway/js/view/payment/method-renderer/default'
                },
            )
        }

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
