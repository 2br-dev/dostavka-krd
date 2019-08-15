$(function(){


    var postStep = function(url){

        var form = $(".crud-list-form");

        $.ajaxQuery({
            url: url,
            type: "post",
            dataType: "json",
            data: form.serialize(),
            success: function(response) {
                if(response.retry)
                {
                    setTimeout(function(){postStep(url);}, 0);
                }
            },
            error: function(aa, textStatus, errorThrown){
                alert(errorThrown);
            }
        });

    };

    $("a.button.fixSelected").click(function(){
        
        var url = $(this).data('url');
        var form = $(".crud-list-form");

        if($('.chk input:checked', form).length >0)
        {
            if(confirm(lang.t('Вы действительно хотите восстановить файлы?')))
            {
                postStep(url);
            }
        }
    });

});
