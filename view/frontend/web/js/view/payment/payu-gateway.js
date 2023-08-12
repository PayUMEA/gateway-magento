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
            fasta = 'payu_gateway_fasta',
            ebucks = 'payu_gateway_ebucks',
            eftPro = 'payu_gateway_eft_pro',
            equitel = 'payu_gateway_equitel',
            payflex = 'payu_gateway_payflex',
            mobicred = 'payu_gateway_mobicred',
            moreTyme = 'payu_gateway_more_tyme',
            airtelMoney = 'payu_gateway_airtel_money',
            capitecPay = 'payu_gateway_capitec_pay',
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
                    type: discoveryMiles,
                    component: 'PayU_Gateway/js/view/payment/method-renderer/default'
                },
            )
        }

        if (config[ebucks].isActive) {
            rendererList.push(
                {
                    type: ebucks,
                    component: 'PayU_Gateway/js/view/payment/method-renderer/default'
                }
            )
        }

        if (config[eftPro].isActive) {
            rendererList.push(
                {
                    type: eftPro,
                    component: 'PayU_Gateway/js/view/payment/method-renderer/default'
                }
            )
        }

        if (config[mobicred].isActive) {
            rendererList.push(
                {
                    type: mobicred,
                    component: 'PayU_Gateway/js/view/payment/method-renderer/default'
                }
            )
        }

        if (config[payflex].isActive) {
            rendererList.push(
                {
                    type: payflex,
                    component: 'PayU_Gateway/js/view/payment/method-renderer/default'
                }
            )
        }

        if (config[airtelMoney].isActive) {
            rendererList.push(
                {
                    type: airtelMoney,
                    component: 'PayU_Gateway/js/view/payment/method-renderer/default'
                }
            )
        }

        if (config[capitecPay].isActive) {
            rendererList.push(
                {
                    type: capitecPay,
                    component: 'PayU_Gateway/js/view/payment/method-renderer/default'
                }
            )
        }

        if (config[equitel].isActive) {
            rendererList.push(
                {
                    type: equitel,
                    component: 'PayU_Gateway/js/view/payment/method-renderer/default'
                }
            )
        }

        if (config[fasta].isActive) {
            rendererList.push(
                {
                    type: fasta,
                    component: 'PayU_Gateway/js/view/payment/method-renderer/default'
                }
            )
        }

        if (config[moreTyme].isActive) {
            rendererList.push(
                {
                    type: moreTyme,
                    component: 'PayU_Gateway/js/view/payment/method-renderer/default'
                }
            )
        }

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
