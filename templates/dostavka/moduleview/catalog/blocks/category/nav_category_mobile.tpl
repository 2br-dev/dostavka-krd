<div class="tn_overlay" id="mobile_overlay"></div>
<!--  -->
<nav class="tn_menu_mobile" id="mobile_menu">
    <!-- категории -->
    <div class="tn_menu_mobile_wrap mobile_active">
        <div class="tn_mobile_top">
            <p class="tn_mobile_title">Каталог</p>
        </div>
        {$counter= 1}
        {foreach $dirlist as $dir}
        <div class="tn_mobile_row" category="{$counter}">
             <a class="tn_mobile_link">{$dir.fields.name}</a> 
            <button class="tn_mobile_btn"></button>
        </div>
        {$counter=$counter+1}
        {/foreach}
        {if false}
            <div class="tn_mobile_top">
                <a href="/auth/" class="tn_mobile_title">Войти</a>
            </div>
        {else}
            <div class="tn_mobile_top">
                <p class="tn_mobile_title">Мой аккаунт</a>
            </div>
            <div class="tn_mobile_top">
                <a href="{$router->getUrl('users-front-profile')}" class="tn_mobile_link">Профиль</a>
            </div>
            <div class="tn_mobile_top">
                <a href="" class="tn_mobile_link">Мои заказы</a>
            </div>
            <div class="tn_mobile_top">
                <a href="" class="tn_mobile_link">Уведомления</a>
            </div>
            <div class="tn_mobile_top">
                <a href="" class="tn_mobile_link">Пароль</a>
            </div>
            <div class="tn_mobile_top">
                <a href="/favorite/" class="tn_mobile_link">Избранное</a>
            </div>
            <div class="tn_mobile_top">
                <a href="/cart/" class="tn_mobile_link">Корзина</a>
            </div>
        {/if} 
        </div>
    </div>
</nav>
<!-- Подкатегории -->
<nav class="tn_menu_mobile" id="mobile_menu_sub">
    {$counter= 1}
    {foreach $dirlist as $dir}
    <div class="tn_menu_mobile_wrap" subCategory="{$counter}">
        <div class="tn_mobile_top">
            <button class="tn_mobile_btn tn_mobile_btn_revers"></button>
            <a class="tn_mobile_link">{$dir.fields.name}</a>
        </div>
        {foreach $dir.child as $subDir}
        <a href="{$subDir.fields->getUrl()}?palette={$counter}" class="tn_mobile_link_2">{$subDir.fields.name}</a>
        {/foreach}
    </div>
    {$counter=$counter+1}
    {/foreach}
</nav>
<!--  -->
