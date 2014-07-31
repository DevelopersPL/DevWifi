$(function() {
    // Tabs initialization
    if ($('#myTab a').length) {
        $('#myTab a').click(function (e) {
            e.preventDefault();
            $(this).tab('show');
        });
    }

    // masked input
    $.mask.definitions['h'] = "[A-Fa-f0-9]";
    if ($('#inputMac, #DinputMac, #EinputMac').length)
        $('#inputMac, #DinputMac, #EinputMac').mask("hh:hh:hh:hh:hh:hh");


    if ($('#inputGrade').length)
        $('#inputGrade').mask("9?aa");

    // grade show/hide animation
    if ($( "#inputType" ).val() != 'u')
        $(".grade").hide();

    $( "#inputType" ).change(function() {
        if ($( this ).val() != 'u') {
            $(".grade").slideUp();
        } else {
            $(".grade").slideDown();
        }
    });

    // loading-state button
    $('form').submit(function () {
        if ($(this).valid()) {
            $('.submitbtn').button('loading');
            return true;
        }
    });

});
