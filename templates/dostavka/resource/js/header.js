// nav_menu
$(document).ready(() => {
    $(document).on('mouseenter', '.tn_nav_item', showNavMenu)
    $(document).on('mouseenter', '.withount_child', showNavMenu)
    $(document).on('mouseenter', '.header, #menu_overlay', hideNavMenu)
    $(document).on('scroll', window, hideNavMenu)
    $(window).resize(function(){
        hideNavMenu()
    })
})
// 
function showNavMenu() {
    const OVERLAY = $('#menu_overlay')
    const CATEGORY = $(this)
    const MENU_ID = $(CATEGORY).attr('target_menu')
    const TARGET_MENU = $(`#${MENU_ID}`)
    const ACTIVE_MENU_ITEM = $(`.tn_nav_item_active`)
    // если соответсвующее меню уже открыто
    if($(TARGET_MENU).attr('state') === 'show') {
        return -1
    }
    // если элемент не меняется в данный момент
    if($(CATEGORY).attr('ready') === 'off') {
        return -1
    }
    hideNavMenu(1)
    // 
    if(!$(CATEGORY).hasClass(`tn_nav_item_active`)) {
        $(ACTIVE_MENU_ITEM).addClass('tn_nav_item_active_hide')
    }
    $(TARGET_MENU).attr('state', 'show')
    $(CATEGORY).attr('ready', 'off')
    $(CATEGORY).addClass('tn_nav_item_show')
    $(OVERLAY).fadeIn(200)
    $(TARGET_MENU).slideDown(200, () => {
        $(CATEGORY).attr('ready', 'on')
    })
}
//
function hideNavMenu(categ = null) {
    const ACTIVE_MENU_ITEM = $(`.tn_nav_item_active`)
    const MENU = $('.tn_menu[state="show"]')
    let CATEGORY = document.getElementsByClassName('tn_nav_item_show')[0]
    const OVERLAY = $('#menu_overlay')
    $(CATEGORY).removeClass('tn_nav_item_show')
    $(ACTIVE_MENU_ITEM).removeClass('tn_nav_item_active_hide')
    $(MENU).attr('state', 'hide')
    $(MENU).hide()
    if(categ !== 1) {
        $(OVERLAY).fadeOut(200)
    }
}
//
//////////////////////////////////////////////////////////////////////////
// мобильное меню
//
$(document).ready(() => {
    $(document).on('click', '#call_menu', toggleMenu)
    $(document).on('click', '#mobile_menu .tn_mobile_row', callSubMenu)
    $(document).on('click', '#mobile_menu_sub .tn_mobile_top', backToMenu)  
})
//
function toggleMenu() {
    const OVERLAY = $('#mobile_overlay')
    const MENU = $('#mobile_menu')
    const SUB_MENU = $('#mobile_menu_sub')
    //   
    if($(SUB_MENU).hasClass('mobile_menu_show')) {
        $('body').css('overflow', 'auto')
        $(OVERLAY).css('display', 'none')
        $(SUB_MENU).addClass('mobile_menu_hide')
        setTimeout(() => {
            $(SUB_MENU).removeClass('mobile_menu_show')
            $(SUB_MENU).removeClass('mobile_menu_hide')
        }, 200)
    } else if($(MENU).hasClass('mobile_menu_show')) {
        $('body').css('overflow', 'auto')
        $(OVERLAY).css('display', 'none')
        $(MENU).addClass('mobile_menu_hide')
        setTimeout(() => {
            $(MENU).removeClass('mobile_menu_show')
            $(MENU).removeClass('mobile_menu_hide')
        }, 200)
    } else {
        $('body').css('overflow', 'hidden')
        $(OVERLAY).css('display', 'block')
        $(MENU).addClass('mobile_menu_show')
    }
}
//
function callSubMenu() {
    const SUB_CATEGORY = $(this).attr('category')
    const SUB_MENU = $('#mobile_menu_sub')
    const MENU = $('#mobile_menu')
    // 
    $('#mobile_menu_sub .mobile_active').removeClass('mobile_active')
    $(`#mobile_menu_sub .tn_menu_mobile_wrap[subCategory=${SUB_CATEGORY}]`).addClass('mobile_active')
    //
    $(MENU).removeClass('mobile_menu_show')  
    $(SUB_MENU).addClass('mobile_menu_show')
}
// 
function backToMenu() {
    const MENU = $('#mobile_menu')
    const SUB_MENU = $('#mobile_menu_sub')

    $(MENU).addClass('mobile_menu_show')  
    $(SUB_MENU).removeClass('mobile_menu_show')
}