$(function() {
    // Tabs initialization
    if ($('#myTab a').length) {
        $('#myTab a').click(function (e) {
            e.preventDefault();
            $(this).tab('show');
        });
    }
});