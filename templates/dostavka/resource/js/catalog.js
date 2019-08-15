$(document).ready(() => {
    $(document).on('click', document, toggleDDL)
})
// 
function toggleDDL(e) {
    if($(window).width() > 768) {
        const elem = $('.sort .ddList .value')
        if(!elem.is(e.target)) {
            $('.sort .ddList ul').slideUp(100)
        } else {
            $('.sort .ddList ul').slideToggle(100)
        }
        // 
        const elem2 = $('.pageSize .ddList .value')
        if(!elem2.is(e.target)) {
            $('.pageSize .ddList ul').slideUp(100)
        } else {           
            $('.pageSize .ddList ul').slideToggle(100)
        }        
    }
}
////////////////////////////////////////////////////
$(document).ready(() => {
    $(document).on('click', document, toggleMobileFilterSort)
})
// 
function toggleMobileFilterSort(e) {
    if($(window).width() <= 768) {
        const elem = $('#call_filter')
        const elem2 = $('#call_sort')
        // 
        if(elem.is(e.target)) {
            $('.sortLine').hide()
            $(elem2).removeClass('catal_filter_panel_btn_active')
            $('.catal_left').slideToggle(100)
        }
        // 
        if(elem2.is(e.target)) {
            $('.catal_left').hide()
            if($(elem2).hasClass('catal_filter_panel_btn_active')) {
                $(elem2).removeClass('catal_filter_panel_btn_active')
            } else {
                $(elem2).addClass('catal_filter_panel_btn_active')
            }
            $('.sortLine').slideToggle(100)
        }   
        //
        const btn1 = $('.catal_filter_btn_1')  
        // 
        if(btn1.is(e.target)) {
            $('.sortLine').hide()
            $(elem2).removeClass('catal_filter_panel_btn_active')
            $('.catal_left').slideToggle(100)
        }  
    }
}
/////////////////////////////////////////////////////
$(document).ready(() => {
    $( window ).resize(function() {
        if($(window).width() <= 768) {
            $('.product_main_rows').removeClass('product_main_rows')
        }
    })
})