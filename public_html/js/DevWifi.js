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
    if($("#inputMac").length)
        $("#inputMac").mask("hh:hh:hh:hh:hh:hh");
});