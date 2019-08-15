$(document).ready(function(){
    /**
     * Открытие детальной информации с информацией по источнику
     */
    $("body").on('click', '.openSourceDetail', function(){
        $($(this).data('target')).show();
        $(this).hide();
        return false;
    });
});