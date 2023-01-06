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
            creditCard = 'payu_gateway_creditcard';

        if (config[creditCard].isActive && !config[creditCard].isEnterprise) {
            rendererList.push(
                {
                    type: creditCard,
                    component: 'PayU_Gateway/js/view/payment/method-renderer/creditcard'
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

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
