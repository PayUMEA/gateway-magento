
require([
    "jquery"
], function ($) {
    $("body").on('click', '#payu_gateway_txn_status', function (e) {
        $.ajax({
            showLoader: true,
            url: window.PayUGatewayAjaxCheck,
            type: "POST",
            data: {
                form_key: window.PayUGatewayAjaxFormKey,
                order_id: window.PayUGatewayAjaxOrderId
            },
            dataType: 'json'
        }).done(function (response) {
            $("#payu_gateway_txn_data").html('<pre>' + JSON.stringify(response.data, undefined, 2) + '</pre>')
        });
    });
});
