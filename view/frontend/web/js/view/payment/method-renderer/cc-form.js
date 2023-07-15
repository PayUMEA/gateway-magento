/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

/*browser:true*/
/*global define*/
define(
    [
        'underscore',
        'jquery',
        'Magento_Payment/js/view/payment/cc-form',
        'Magento_Checkout/js/model/quote',
        'PayU_Gateway/js/validator',
        'Magento_Ui/js/model/messageList',
        'PayU_Gateway/js/view/payment/validator-handler',
        'domReady!'
    ],
    function (
        _,
        $,
        Component,
        quote,
        validator,
        globalMessageList,
        validatorManager
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'PayU_Gateway/payment/cc-form',
                active: false,
                code: 'payu_gateway_creditcard',
                lastBillingAddress: null
            },

            /**
             * @returns {exports.initialize}
             */
            initialize: function () {
                const self = this;

                self._super();

                return self;
            },

            /**
             * Set list of observable attributes
             *
             * @returns {exports.initObservable}
             */
            initObservable: function () {
                validator.setConfig(window.checkoutConfig.payment[this.getCode()]);
                this._super().observe(['active']);

                return this;
            },

            /**
             * Get payment name
             *
             * @returns {String}
             */
            getCode: function () {
                return this.code;
            },

            /**
             * Check if payment is active
             *
             * @returns {Boolean}
             */
            isActive: function () {
                const active = this.getCode() === this.isChecked();

                this.active(active);

                return active;
            },

            /**
             * Get list of available CC types
             *
             * @returns {Object}
             */
            getCcAvailableTypes: function () {
                let availableTypes = validator.getAvailableCardTypes(),
                    billingAddress = quote.billingAddress(),
                    billingCountryId;

                this.lastBillingAddress = quote.shippingAddress();

                if (!billingAddress) {
                    billingAddress = this.lastBillingAddress;
                }

                billingCountryId = billingAddress.countryId;

                if (billingCountryId && validator.getCountrySpecificCardTypes(billingCountryId)) {
                    return validator.collectTypes(
                        availableTypes,
                        validator.getCountrySpecificCardTypes(billingCountryId)
                    );
                }

                return availableTypes;
            },

            /**
             * Action to place order
             * @param {String} key
             */
            placeOrder: function (key) {
                const self = this;

                if (key) {
                    return self._super();
                }
                // place order on success validation
                validatorManager.validate(self, function () {
                    return self.placeOrder('parent');
                }, function (err) {

                    if (err) {
                        self.showError(err);
                    }
                });

                return false;
            },

            /**
             * Returns state of place order button
             *
             * @returns {Boolean}
             */
            isButtonActive: function () {
                return this.isActive() && this.isPlaceOrderActionAllowed();
            },

            /**
             * Get full selector name
             *
             * @param {String} field
             * @returns {String}
             * @private
             */
            getSelector: function (field) {
                return '#' + this.getCode() + '_' + field;
            },

            /**
             * Add invalid class to field.
             *
             * @param {String} field
             * @returns void
             * @private
             */
            addInvalidClass: function (field) {
                $(this.getSelector(field)).addClass('braintree-hosted-fields-invalid');
            },

            /**
             * Remove invalid class from field.
             *
             * @param {String} field
             * @returns void
             * @private
             */
            removeInvalidClass: function (field) {
                $(this.getSelector(field)).removeClass('braintree-hosted-fields-invalid');
            },

            /**
             * Validate current credit card type.
             *
             * @returns {Boolean}
             * @private
             */
            validateCardType: function () {
                const cardFieldName = 'cc_number';

                this.removeInvalidClass(cardFieldName);

                if (this.selectedCardType() === null || !this.isValidCardNumber) {
                    this.addInvalidClass(cardFieldName);

                    return false;
                }

                return true;
            },

            /**
             * Show error message
             *
             * @param {String} errorMessage
             * @private
             */
            showError: function (errorMessage) {
                globalMessageList.addErrorMessage({
                    message: errorMessage
                });
            },

            /** Returns payment image path */
            getPaymentMethodImageSrc: function() {
                return window.checkoutConfig.payment[this.getCode()].imageSrc;
            }
        });
    }
);
