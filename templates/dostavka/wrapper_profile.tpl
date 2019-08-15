{addcss file="bread_crumps.css"}
{addcss file="profile.css"}

{extends file="%THEME%/wrapper.tpl"}
{block name="content"}
{$shop_config=ConfigLoader::byModule('shop')}
{$route_id=$router->getCurrentRoute()->getId()}
    <div class="box profile prof">
        {* Хлебные крошки *}
        {moduleinsert name="\Main\Controller\Block\BreadCrumbs"}
        <p class="prof_title">{t}Личный кабинет{/t}</p>
        <div class="prof__box">
            <!--  -->
            <section class="prof__left">
                <a href="{$router->getUrl('users-front-profile')}" class="prof__left_link {if $route_id=='users-front-profile'}act{/if}">Профиль</a>
                <a href="{$router->getUrl('shop-front-myorders')}" class="prof__left_link {if in_array($route_id, ['shop-front-myorders', 'shop-front-myorderview'])}act{/if}">Мои заказы</a>
                {if $shop_config.return_enable}
                <a href="{$router->getUrl('shop-front-myproductsreturn')}" class="prof__left_link {if $route_id=='shop-front-myproductsreturn'}act{/if}">Мои возвраты</a>
                {/if}
                {* {if $shop_config.use_personal_account}
                <a href="{$router->getUrl('shop-front-mybalance')}" class="prof__left_link {if $route_id=='shop-front-mybalance'}act{/if}">Лицевой счет</a>
                {/if} *}
                {* {if ModuleManager::staticModuleExists('support')}
                <a href="{$router->getUrl('support-front-support')}" class="prof__left_link {if $route_id=='support-front-support'}act{/if}">Уведомления <span></span></a>
                {/if} *}
                {if ModuleManager::staticModuleExists('partnership')}
                    {static_call var="is_partner" callback=['Partnership\Model\Api', 'isUserPartner'] params=$current_user.id}
                    {if $is_partner}
                    <a href="{$router->getUrl('partnership-front-profile')}" class="prof__left_link {if $route_id=='partnership-front-profile'}act{/if}">Профиль партнера</a>
                    {/if}
                {/if}
                <a href="{$router->getUrl('users-front-auth', ['Act' => 'logout'])}" class="prof__left_link">Выйти</a>
                <div class="prof__btn-box">
                    <a href="/" class="prof__btn-link">
                        <img src="/templates/dostavka/resource/img/dostavka/icons/icon_btns_type_1.svg" alt="" class="prof__btn-icon">
                        <div>
                            <p class="prof__btn-title">Как мы работаем</p>
                            <p class="prof__btn-label">Заказ, доствка и оплата</p>
                        </div>
                    </a>
                    <a href="/" class="prof__btn-link">
                        <img src="/templates/dostavka/resource/img/dostavka/icons/icon_btns_type_2.svg" alt="" class="prof__btn-icon">
                        <div>
                            <p class="prof__btn-title">Выгодные покупки</p>
                            <p class="prof__btn-label">Акции, скидки</p>
                        </div>
                    </a>
                    <a href="/" class="prof__btn-link">
                        <img src="/templates/dostavka/resource/img/dostavka/icons/icon_btns_type_3.svg" alt="" class="prof__btn-icon">
                        <div>
                            <p class="prof__btn-title">Свяжитесь с нами</p>
                            <p class="prof__btn-label">Вопросы и предложения</p>
                        </div>
                    </a>
                </div>
            </section>
            <!--  -->
            <section class="prof__right">
                {$app->blocks->getMainContent()}
            </section>
            <!--  -->
        </div>
    </div>
{/block}