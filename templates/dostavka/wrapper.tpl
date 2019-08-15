{addcss file="header.css"}
{addcss file="nav.css"}
{addcss file="auth.css"}
{addcss file="footer.css"}
{addjs  file="header.js"}
{addjs  file="%catalog%/jquery.favorite.js"}

<!-- определяет текущую палитру сайта -->
{assign var="palette" value="none"}
{if $smarty.get.palette == "1" || $smarty.get.palette == "2" || $smarty.get.palette == "3" || $smarty.get.palette == "4" || $smarty.get.palette == "5" || $smarty.get.palette == "6" || $smarty.get.palette == "7"}
    {assign var="palette" value=$smarty.get.palette}
{/if}

{$palette = 0}
{$uri = explode('/', trim($smarty.server.REQUEST_URI, '/'))}
{$dir = array_shift($uri)}
{if $dir === 'catalog'}
    {$alias = array_shift($uri)}
    {$palette = \DostavkaApi\Model\DostavkaApiApi::getCategoryPaletteByAlias($alias)}
{/if}
{if $dir === 'product'}
    {$alias = array_shift($uri)}
    {$palette = \DostavkaApi\Model\DostavkaApiApi::getProductPaletteByAlias($alias)}
{/if}

{* {$palette = 0}
{if $router->getCurrentRoute()->getId() == 'catalog-front-product'}
    {$palette = $router->getCurrentRoute()->getExtra('product')->getMainDir()->getDirPalette()}
{/if}
{if $router->getCurrentRoute()->getId() == 'catalog-front-listproducts'}
    {$palette = $router->getCurrentRoute()->getExtra('category')->getDirPalette()}
{/if} *}

<!--  -->
{* {moduleinsert name="\Catalog\Controller\Block\Category"} *}
<main class="main palette_{$palette}">
    <header class="header">
        <div class="screen h_flex">
            <a href ="/" class="h_wrap_1">
                <img src="{$THEME_IMG}/dostavka/header_logo_{$palette}.png" alt="" class="h_logo">
            </a>
            <div class="h_wrap_2">
                <!-- поиск -->
                {moduleinsert name="\Catalog\Controller\Block\SearchLine"}
                <!-- /поиск -->
                {moduleinsert name="\Users\Controller\Block\AuthBlock"}
                {* <a href="/compare/" class="h_btn_1 h_icon_stat"></a> *}
                <a href="/favorite/" class="h_btn_1 h_icon_favor"></a>
                {moduleinsert name="\Shop\Controller\Block\Cart"}
            </div>
        </div>
    </header>
    <!--  -->
    <div class="header_spacer"></div>
    <!--  -->
    <nav class="top_nav">
        <div class="screen tn_wrap_1">
            {moduleinsert name="\Catalog\Controller\Block\Category" indexTemplate='blocks/category/nav_category.tpl'}
            <!-- mobile -->
            <div class="tn_wrap_2">
                <button class="tn_btn" id="call_menu"></button>
                <!-- поиск -->
                {moduleinsert name="\Catalog\Controller\Block\SearchLine"}
                <!-- /поиск -->
            </div>
            <!-- каталог -->
            {moduleinsert name="\Catalog\Controller\Block\Category"}
            <!-- /каталог -->
            <!-- каталог mobile -->
            {moduleinsert name="\Catalog\Controller\Block\Category"  indexTemplate='blocks/category/nav_category_mobile.tpl'}
            <!-- /каталог mobile -->
        </div>
    </nav>
    <!--  -->
    {* Данный блок будет переопределен у наследников данного шаблона *}
    {block name="content"}{/block}
    <!--  -->
    <footer class="footer">
        <div class="screen f_flex">
            <div class="f_col">
                <p class="f_title">Компания</p>
                <div class="f_wrap_1">
                    <a href="/" class="f_link_1">Главная</a>
                    <a href="/" class="f_link_1">Доставка и оплата</a>
                    <a href="/" class="f_link_1">Акции, скидки</a>
                    <a href="/" class="f_link_1">Личный кабинет</a>
                    <a href="/" class="f_link_1">Задать вопрос</a>
                    <a href="/" class="f_link_1">Контакты</a>
                </div>
            </div>
            <div class="f_wrap_3">
                <div class="f_col_2">
                    <p class="f_title">Мы в социальных сетях</p>
                    <div class="f_wrap_2">
                        <a href="#" class="f_link_2 f_inst"></a>
                        <a href="#" class="f_link_2 f_vk"></a>
                        <a href="#" class="f_link_2 f_fb"></a>
                    </div>
                </div>
                <div class="f_col_2">
                    <p class="f_title">Телефон</p>
                    <a href="tel: 8-800-888-80-08" class="f_link_3">8-800-888-80-08</a>
                </div>
            </div>
        </div>
    </footer>
    <!-- menu_overlay -->
    <div class="menu_overlay" id="menu_overlay"></div>
    <!-- /menu_overlay -->
</main>

<script>
    console.log("%c2Br", "background:#fff100 ; color: black; font-size: 48px; padding: 50px 40px");
    console.log("%cСоздано в агенстве 2-br.ru, 2019", "color:#fff100 ; background: black; font-size: 14px;");
</script>