$.allReady(function() {
    $('body').on('submit', '.ya-form-test', function(e) {
        var _this = this;
        $.ajaxQuery({
            type: "POST",
            url: $(this).attr('action'),
            data: $(this).find('.request').val(),
            dataType: 'html',
            contentType : "application/json",
            success: function(response) {
                $(_this).find('.response').text(response);
                $(_this).find('.error').empty();
            },
            error: function(xhr, textStatus, errorThrown) {
                console.log(xhr, textStatus, errorThrown);
                $.rs.loading.hide();
                $(_this).find('.response').text(xhr.responseText);
                $(_this).find('.error').text( lang.t('Произошла ошибка: ') + xhr.status + ' ' + xhr.statusText );
            }
        });

        e.preventDefault();
    });
});