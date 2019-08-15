{addcss file="checkout.css"}
{addcss file="bread_crumps.css"}

{extends file="%THEME%/wrapper.tpl"}
{block name="content"}
<div class="cout_main">
    <div class="cout_screen">
        {* {moduleinsert name="\Main\Controller\Block\Breadcrumbs"} *}
        <div class="cout_conteiner">
            <div class="cout_col_left">
                {$app->blocks->getMainContent()}
            </div>
            <div class="cout_col_right">
                <div class="right_col">
                    <div class="right_icon_box">
                        <img src="{$THEME_IMG}/dostavka/icons/icon_cart_1.svg" alt="" class="right_img">
                    </div>
                    <p class="right_title">Доставка</p>
                    <p class="right_text">При заказе от 1000 рублей в пределах Краснодара доставка бесплатная.</p>
                    <p class="right_text">При заказе до 1000 рублей стоимость доставки в пределах Краснодара составляет 200 рублей.</p>
                    <div class="right_spacer"></div>
                </div>
                <!--  -->
                <div class="right_col">
                    <div class="right_icon_box">
                        <img src="{$THEME_IMG}/dostavka/icons/icon_cart_2.svg" alt="" class="right_img">
                    </div>
                    <p class="right_title">Доставка</p>
                    <p class="right_text">Заказы, сформированные до 15:00, будут доставляться на следующий день с 15:00 до 22:00, а после 15:00 - через день с 9:00 до 15-00. Желательное время доставки можно указать в комментарии при оформлении заказа.</p>
                    <div class="right_spacer"></div>
                </div>
                <!--  -->
                <div class="right_col">
                    <div class="right_icon_box">
                        <img src="{$THEME_IMG}/dostavka/icons/icon_cart_3.svg" alt="" class="right_img">
                    </div>
                    <p class="right_title">Доставка</p>
                    <p class="right_text">Вы можете оплатить заказ одним из четырех способов: </p>
                    <p class="right_text">Наличными курьеру</p>
                    <p class="right_text">Картой курьеру</p>
                    <p class="right_text">Безналичным расчетом по выставленному счету</p>
                    <p class="right_text">Оплата на сайте</p>
                </div>
                <!--  -->
                <div class="cart_row">
                    <p class="right_text_2">Подробнее об условиях доставки и оплаты Вы можете</p>
                    <a href="" class="right_link">прочитать тут</a>
                </div>    
            </div>
        </div>
    </div>
</div>
{/block}