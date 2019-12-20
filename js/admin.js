(function ($) {
    "use strict";
    $(function () {

        var placeName = $("#scheme-place-name"),
            placeDescription = $("#scheme-place-description"),
            placePrice = $("#scheme-place-price"),
            scheme_id = $('#scheme').data('scheme-id'),
            buttonsObj;


        $('#scheme-container').tooltip({
            items: '.scheme-cell',
            position: {
                my: "left top+5"
            },
            content: function () {
                return $('#tooltip-scheme-place-' + $(this).data('place-id')).html();
            }
        });


    });
}(jQuery));