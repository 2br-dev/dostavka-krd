//////////////////////////////////////////////////////////////////////////
// | url: / | main slider |
//
$(document).ready(() => {
    $('#main-slider').slick({
        infinite: true,
        speed: 300,
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: true,
        autoplay: true,
        autoplaySpeed: 3000,
        dots: true
    })
})
//
//////////////////////////////////////////////////////////////////////////
// | url: / | переключатель каталога |
//
$(document).ready(() => {
    $(document).on('click', '.catalog_btn_1', scrollToCategory)
    $(window).scroll(scroll)
})
// верхние границы блоков
function getPositionBlocks() {
    let array = []
    //
    $.each($('.catalog_section'), (key, block) => {
        array.push({
            type: $(block).attr('type'),
            top: $(block).offset().top,
            bottom: $(block).offset().top + $(block).height()
        }) 
    })
    //
    return array
}
// 
function getPositionBtns() {
    let obj = {}
    //
    $.each($('.catalog_btn_1'), (key, block) => {
        obj[$(block).attr('type')] = {
            node: block,
            position: $(block).offset().top + ($(block).height() / 2)
        }
    })
    //
    return obj
}
//
function scrollToCategory() {
    const CATEGORY_TYPE    = $(this).attr('type')
    const CATEGORY_SECTION = $(`.catalog_section[type=${CATEGORY_TYPE}]`)
    // // 
    let size = 104
    if($(window).width() <= 1000 && $(window).width() > 768) {
        size = 145
    } else if($(window).width() <= 768) {
        size = 92
    }
    console.log(size);
    
    
    if($(window).width() > 1000) {
        const BTN_POSITION = $(this).position().top
        const CATEGORY_SECTION_TOP = CATEGORY_SECTION.offset().top
        $('html').animate({scrollTop: CATEGORY_SECTION_TOP - BTN_POSITION - size}, 100)
    } else {
        $('html').animate({scrollTop: $(CATEGORY_SECTION).offset().top - size}, 100)
    }
    //
    setTimeout(scroll, 100)
}
//
function scroll() {
    let size = 169
    if($(window).width() <= 768) {
        size = 92
    } else if($(window).width() <= 1000) {
        size = 145
    }
    //
    const SCROLL_POSITION = $(window).scrollTop() + size
    //
    // класс подсветки кнопки
    const CLASS_ACTIVE = 'catalog_btn_active'
    const POSITION_BLOCKS = getPositionBlocks()
    const POSITION_BTNS = getPositionBtns()   
    //
    if($(window).width() > 1000) {
        let activeType = 0
        POSITION_BLOCKS.map(block => {
            if(block.top <= POSITION_BTNS[block.type].position && block.bottom >= POSITION_BTNS[block.type].position) {
                activeType = block.type
            }
        })
        //
        POSITION_BLOCKS.map(block => {
            if(block.type === activeType) {
                $('.catalog_btn_1').removeClass(CLASS_ACTIVE)
                $(POSITION_BTNS[block.type].node).addClass(CLASS_ACTIVE)
            }
        })
    } else {
        POSITION_BLOCKS.map(block => {
            if(SCROLL_POSITION >= block.top && SCROLL_POSITION <= block.bottom && !$(POSITION_BTNS[block.type].node).hasClass(CLASS_ACTIVE)) {
                $('.catalog_btn_1').removeClass(CLASS_ACTIVE)
                $(POSITION_BTNS[block.type].node).addClass(CLASS_ACTIVE)
            }
        })
    }


}
//
