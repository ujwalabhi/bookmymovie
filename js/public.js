(function ($) {
    "use strict";
    $(function () {
        $("#bap-empty-cart-dialog").hide();
        $("#bap-cart-form-dialog").hide();


        var scheme_id = $('#scheme').data('scheme-id'),
            event_id = $('#scheme').data('event-id'),
            checkoutSubmitHandler = function () {

                var checkoutFirstName = $('#checkout-first-name'),
                    checkoutLastName = $('#checkout-last-name'),
                    checkoutEmail = $('#checkout-email'),
                    checkoutPhone = $('#checkout-phone'),
                    checkoutNotes = $('#checkout-notes');

                if (checkoutFirstName.val().length == 0) {
                    checkoutFirstName.parents(".field").addClass('error')
                } else {
                    checkoutFirstName.parents(".field").removeClass('error')
                }

                if (checkoutLastName.val().length == 0) {
                    checkoutLastName.parents(".field").addClass('error')
                } else {
                    checkoutLastName.parents(".field").removeClass('error')
                }

                if (checkoutEmail.val().length == 0 || !validateEmail(checkoutEmail.val())) {
                    checkoutEmail.parents(".field").addClass('error')
                } else {
                    checkoutEmail.parents(".field").removeClass('error')
                }

                if (checkoutPhone.val().length == 0 || !validatePhone(checkoutPhone.val())) {
                    checkoutPhone.parents(".field").addClass('error')
                } else {
                    checkoutPhone.parents(".field").removeClass('error')
                }

                if (checkoutFirstName.val().length == 0 ||
                    checkoutLastName.val().length == 0 ||
                    checkoutEmail.val().length == 0 || !validateEmail(checkoutEmail.val()) ||
                    checkoutPhone.val().length == 0 || !validatePhone(checkoutPhone.val())) {

                    return false;
                }

                var dialog = this,
                    data = {
                        action: 'checkout',
                        first_name: checkoutFirstName.val(),
                        last_name: checkoutLastName.val(),
                        email: checkoutEmail.val(),
                        phone: checkoutPhone.val(),
                        notes: checkoutNotes.val(),
                        scheme_id: scheme_id,
                        event_id: event_id
                    };

                $(dialog).hide();
                $.blockUI();

                $.post(bmm_object.ajaxurl, data, function (response) {

                    if (response.error == 'limit') {
                        $('#scheme-warning-message').html(response.error_message);
                        $("#scheme-warning-message").dialog('open');
                    } else {
                        var currLocation = window.location.href;
                        if (currLocation.indexOf('?') != -1) {
                            window.location = currLocation + '&s_msg=1';
                        } else {
                            window.location = currLocation + '?s_msg=1';
                        }

                        refreshSchemeAndCartCallback(response.content);
                    }

                    $.unblockUI();
                }, 'json');
            };

        addCellEventHandlers();


        $(document).tooltip({
            tooltipClass: 'bap-tooltip',
            items: '.scheme-cell',
            position: {
                my: "left top+5"
            },
            content: function () {
                return $('#tooltip-scheme-place-' + $(this).data('cell')).html();
            }
        });

        $(document).on('click', '#scheme-container-visibility', function (e) {
            e.preventDefault();

            var self = $(this);

            $('#scheme-container').toggle();

            if ($('#scheme-container').is(':visible')) {
                self.attr('data-visible', 1);
                self.text(bmm_object.loc_strings.scheme_hide_text);
            } else {
                self.attr('data-visible', 0);
                self.text(bmm_object.loc_strings.scheme_show_text);
            }
        });


        function refreshScheme() {

            if (!bookAPLaceEventBookingOpen) {
                return;
            }
            $("#bap-empty-cart-dialog").hide();
            $("#bap-cart-form-dialog").hide();
            $("#cart-checkout").show();
            var data = {
                action: 'refresh_scheme',
                scheme_id: scheme_id,
                event_id: event_id
            };

            $.post(bmm_object.ajaxurl, data, function (response) {
                $('#scheme-container').empty().append(response);
                if (!bookAPLaceEventBookingOpen) {
                    $('#shopping-cart-container').empty();
                    $('#shopping-cart-controls-container').empty();
                }
            });

        }

        function addCellEventHandlers() {
            $(document).on('mouseenter', '.scheme-place-available', function () {
                $("#bap-empty-cart-dialog").hide();
                $("#bap-cart-form-dialog").hide();
                $("#cart-checkout").show();

                var self = $(this),
                    placeId = self.data('cell'),
                    isCellSelected = self.toggleClass('scheme-cell-selected').hasClass('scheme-cell-selected');

                toggleAllCellsOfPlace(placeId, isCellSelected);

            });

            $(document).on('mouseleave', '.scheme-place-available', function () {
                $("#bap-empty-cart-dialog").hide();
                $("#bap-cart-form-dialog").hide();
                $("#cart-checkout").show();
                var self = $(this),
                    placeId = self.data('cell'),
                    isCellSelected = self.toggleClass('scheme-cell-selected').hasClass('scheme-cell-selected');

                toggleAllCellsOfPlace(placeId, isCellSelected);

            });

            $(document).on('click', '.scheme-place-available', function () {

                $.blockUI();

                var data = {
                    action: 'add_to_cart',
                    scheme_id: scheme_id,
                    event_id: event_id,
                    place_id: $(this).data('place-id'),
                    seat_id: $(this).data('cell'),
                };


                $.post(bmm_object.ajaxurl, data, function (response) {


                    if (response == '0') {
                        refreshScheme();
                        $.unblockUI({
                            onUnblock: function () {
                                $("#scheme-warning-message").html(bap_object.loc_strings.places_in_cart_cant_add);
                                $("#scheme-warning-message").dialog('open');
                            }
                        });
                    } else if (response == 'limit') {
                        refreshScheme();
                        $.unblockUI({
                            onUnblock: function () {
                                $("#scheme-warning-message").html(bap_object.loc_strings.places_in_cart_limit);
                                $("#scheme-warning-message").dialog('open');
                            }
                        });
                    } else {
                        refreshSchemeAndCartCallback(response);
                        $.unblockUI();
                    }

                });

                return false;

            });

            $(document).on('click', '.delete_from_cart', function () {

                $.blockUI();

                var data = {
                    action: 'delete_from_cart',
                    scheme_id: scheme_id,
                    event_id: event_id,
                    place_id: $(this).data('place-id'),
                    seat_id: $(this).data('seat-id'),
                };

                $.post(bmm_object.ajaxurl, data, function (response) {
                    refreshSchemeAndCartCallback(response);
                    $.unblockUI();
                });

                return false;

            });
        }

        function refreshSchemeAndCartCallback(response) {
            $("#bap-empty-cart-dialog").hide();
            $("#bap-cart-form-dialog").hide();

            var schemeContainerVisibility = $('#scheme-container-visibility').data('visible');

            $('#scheme-container').replaceWith($(response).find('#scheme-container'));
            if (schemeContainerVisibility) {
                $('#scheme-container').toggle();
            }

            $('#shopping-cart-container').replaceWith($(response).find('#shopping-cart-container'));

        }

        function toggleAllCellsOfPlace(placeId, isCellsOfPlaceSelected) {
            $(".scheme-place-" + placeId).each(function () {
                var cell = $(this);
                if (isCellsOfPlaceSelected) {
                    cell.addClass('scheme-cell-selected');
                } else {
                    cell.removeClass('scheme-cell-selected');
                }
            });
            clearmsg();
        }

        $("#cart-checkout").unbind('click').click(function (e) {
            e.preventDefault();

            if ($('.bap-place-in-cart').length) {
                $("#bap-cart-form-dialog").show();
            } else {
                $("#bap-empty-cart-dialog").show();
            }
            $("#cart-checkout").hide();
            return false;
        });

        $('#checkout-cancle-button').unbind('click').click(function (e) {
            e.preventDefault();
            $("#bap-cart-form-dialog").hide();
            $("#bap-empty-cart-dialog").hide();
            $("#cart-checkout").show();

        });

        $("#emptydialogbutton").unbind('click').click(function (e) {
            e.preventDefault();
            $("#bap-empty-cart-dialog").hide();
            $("#cart-checkout").show();
            return false;
        });

        $("#checkout-button").unbind('click').click(function (e) {
            e.preventDefault();
            checkoutSubmitHandler();
        });


        function validateEmail(email) {
            var pattern = /^([^\s@]+@[^\s@]+\.[^\s@]+)$/;
            return pattern.test(email);
        }

        function validatePhone(phone) {
            var pattern = /^\d+$/;
            return pattern.test(phone);
        }

        function clearmsg(){
            $("#payment-success").hide();
            $("#payment-error").hide();
        }

        setInterval(clearmsg,10000);

    });
}(jQuery));