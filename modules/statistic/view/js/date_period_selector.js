$(function() {
    /**
     * Инициализация фильтров в графике статистики
     *
     */
    var initDateSelector = function() {
        /**
         * Инициализация селектора дат
         */
        $('.stat-date-range input[datefilter]', this).dateselector();

        /**
         * Переключение заранее заготовленных пресетов фильтров
         */
        $(".stat-date-range .date-presets a", this).off('click.date-range').on('click.date-range', function(){
            var context = $(this).closest('.stat-date-range');
            var from    = $(this).data('from');
            var to      = $(this).data('to');
            
            var input_from = $("input.from", context);
            var input_to   = $("input.to", context);
            
            $('input.hidden', context).remove();
            input_from.clone().attr('type', 'hidden').val(from).addClass('.hidden').appendTo(context);
            input_from.clone().attr('type', 'hidden').val(to).addClass('.hidden').appendTo(context);
            
            input_from.attr('disabled', true);
            input_to.attr('disabled', true);
            $("input[type=submit]", context).click();
        });

        /**
         * Переключение типа группировки
         */
        $(".stat-date-range .date-presets-groups a", this).off('click.date-groups').on('click.date-groups', function(){
            var context = $(this).closest('.stat-date-range');
            var wrapper = $(this).closest('.dropdown');
            var block_wrapper = $(this).closest('.updatable');
            var value   = $(this).data('value');

            if (value == 'week'){
                $('.graph', block_wrapper).addClass('weekly');
            }else{
                $('.graph', block_wrapper).removeClass('weekly');
            }

            $('input[type="hidden"]', wrapper).val(value);
            $("input[type=submit]", context).click();
        });
    };
    
    $.contentReady(initDateSelector);
});