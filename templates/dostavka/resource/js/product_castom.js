$(document).ready(() => {
    $(document).on('click', '.prod_img_link', function() {
        if($(window).width() <= 768) {
            return 0;
        }
        //
        const ACTIVE_IMG_MINI = 'prod_img_link_active'
        const SRC_IMG_MINI = $(this).attr('url-href')
        const SRC_IMG_FB = $(this).attr('url-href-fb')
        const IMG_MAIN = $('.prod_img_main')
        const FB = $('#item_fb')
        //
        if($(this).hasClass(ACTIVE_IMG_MINI)) {
            return 0;
        }
        //
        $(`.${ACTIVE_IMG_MINI}`).removeClass(ACTIVE_IMG_MINI)
        $(this).addClass(ACTIVE_IMG_MINI)
        //
        FB.attr('href', SRC_IMG_FB)
        IMG_MAIN.attr('src', SRC_IMG_MINI)
    })
})
//////////////////////////////////////////////////////////////////
$(document).ready(() => {
    $(document).on('click', '#show_reviews', function() {
        $(this).hide()
        $('.comment_block').slideDown(200)        
    })
})
//////////////////////////////////////////////////////////////////
{
    $(document).ready(() => {

        $(document).on('click', '.BTN', edit)
        

        const MIN_AMOUNT = Number($('#amount').val())
    

        function edit() {
            const classes = {
                plus: 'INC',
                minus: 'DEC'
            }
    
            const nodes = {
                amount: $('#amount'),
                amountMask: $('#amount_mask')
            }
    
            const values = {
                step: Number($(this).attr('data-amount-step')),
                amount: Number(nodes.amount.val())
            }
            
            let value = values.amount
            if($(this).hasClass(classes.minus)) {
                value -= values.step
                if(value === 0 || value < MIN_AMOUNT) {
                    value = values.amount
                }
            } else if($(this).hasClass(classes.plus)) {
                value += values.step
            }
            
            nodes.amount.val(value)
            nodes.amountMask.html(`${value} шт.`)
        }
    })
}