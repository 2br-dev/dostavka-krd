<section class="catalog_screen">
    <div class="catalog_lift_wrap">
        <div class="catalog_btns_wrap">
            <button type="1" class="catalog_btn_1 catalog_btn_category_1 catalog_btn_active"></button>
            <button type="2" class="catalog_btn_1 catalog_btn_category_2"></button>
            <button type="3" class="catalog_btn_1 catalog_btn_category_3"></button>
            <button type="4" class="catalog_btn_1 catalog_btn_category_4"></button>
            <button type="5" class="catalog_btn_1 catalog_btn_category_5"></button>
            <button type="6" class="catalog_btn_1 catalog_btn_category_6"></button>
            <button type="7" class="catalog_btn_1 catalog_btn_category_7"></button>
        </div>
    </div>
    <!--  -->
    <div class="catalog_body_wrap">
        <div type="1" class="catalog_section catalog_section_title_type_1">
            <a href="/catalog/bytovaya-himiya/" class="catalog_section_title">
                <p class="catalog_section_title_label">Бытовая химия</p>
            </a>
            {moduleinsert name="\Catalog\Controller\Block\TopProducts" indexTemplate='blocks/topproducts/top_products.tpl' dirs="lp-bytovaya-himiya" pageSize="5"}
        </div>    
        <div class="catalog_section_spacer"></div>
        <!--  -->
        <div type="2" class="catalog_section catalog_section_title_type_2">
            <a href="/catalog/detskie-tovary/" class="catalog_section_title">
                <p class="catalog_section_title_label">Детские товары</p>
            </a>
            {moduleinsert name="\Catalog\Controller\Block\TopProducts" indexTemplate='blocks/topproducts/top_products.tpl' dirs="lp-detskie-tovary" pageSize="5"}
        </div>
        <div class="catalog_section_spacer"></div>
        <!--  -->
        <div type="3" class="catalog_section catalog_section_title_type_3">
            <a href="/catalog/sredstva-gigieny/" class="catalog_section_title">
                <p class="catalog_section_title_label">Средства гигиены</p>
            </a>
            {moduleinsert name="\Catalog\Controller\Block\TopProducts" indexTemplate='blocks/topproducts/top_products.tpl' dirs="lp-sredstva-gigieny" pageSize="5"}
        </div>
        <div class="catalog_section_spacer"></div>
        <!--  -->
        <div type="4" class="catalog_section catalog_section_title_type_4">
            <a href="/catalog/zootovary/" class="catalog_section_title">
                <p class="catalog_section_title_label">Зоотовары</p>
            </a>
            {moduleinsert name="\Catalog\Controller\Block\TopProducts" indexTemplate='blocks/topproducts/top_products.tpl' dirs="lp-zootovary" pageSize="5"}
        </div>
        <div class="catalog_section_spacer"></div>
        <!--  -->
        <div type="5" class="catalog_section catalog_section_title_type_5">
            <a href="/catalog/kosmetika-i-uhod/" class="catalog_section_title">
                <p class="catalog_section_title_label">Косметика и уход</p>
            </a>
            {moduleinsert name="\Catalog\Controller\Block\TopProducts" indexTemplate='blocks/topproducts/top_products.tpl' dirs="lp-kosmetika-i-uhod" pageSize="5"}
        </div>
        <div class="catalog_section_spacer"></div>
        <!--  -->
        <div type="6" class="catalog_section catalog_section_title_type_6">
            <a href="/catalog/produkty/" class="catalog_section_title">
                <p class="catalog_section_title_label">Продукты</p>
            </a>
            {moduleinsert name="\Catalog\Controller\Block\TopProducts" indexTemplate='blocks/topproducts/top_products.tpl' dirs="lp-produkty" pageSize="5"}
        </div>
        <div class="catalog_section_spacer"></div>
        <!--  -->
        <div type="7" class="catalog_section catalog_section_title_type_7">
            <a href="/catalog/tovary-dlya-doma/" class="catalog_section_title">
                <p class="catalog_section_title_label">Товары для дома</p>
            </a>
            {moduleinsert name="\Catalog\Controller\Block\TopProducts" indexTemplate='blocks/topproducts/top_products.tpl' dirs="lp-tovary-dlya-doma" pageSize="5"}
        </div>
        <!--  -->
    </div>
</section>