$(function(){
    var widget_div = $('.widget-content[data-update-block-id="antivirus-widget-stateinfo"]');

    var isIntensive = function()
    {
        var int = $('.stateinfo', widget_div).data('intensive');
        return int == '1';
    };

    var xhr = null;

    var update = function() {

        if(xhr != null) xhr.abort();

        xhr = $.ajax({
            dataType: 'json',
            url: widget_div.find('.stateinfo').data('refreshUrl'),
            success: function (data) {
                if (data.html) {
                    widget_div.html(data.html).trigger('new-content');
                }
            }
        });
    };

    widget_div.on('new-content', function(){
        setTimeout(update, isIntensive() ? 500 : 10 * 1000);
    });

    setTimeout(update, isIntensive() ? 500 : 10 * 1000);

});
