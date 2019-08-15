<!--  -->
{addcss file="favorite.css"}
<!--  -->
{addjs file="jquery.changeoffer.js"}
{$shop_config=ConfigLoader::byModule('shop')}
{$check_quantity=$shop_config.check_quantity}
{$list = $this_controller->api->addProductsDirs($list)}

<div id="favorite" class="productList fav_box">
    <p class="fav_title">{t}Избранные товары{/t}</p>
    {if $list}    
        {* <div class="sortLine">
            <div class="viewAs">
                <a href="{urlmake viewAs="table"}" class="viewAs table{if $view_as == 'table'} act{/if}" rel="nofollow"></a>
                <a href="{urlmake viewAs="blocks"}" class="viewAs blocks{if $view_as == 'blocks'} act{/if}" rel="nofollow"></a>
            </div>
            <div class="pageSize">
                {t}Показывать по{/t}
                <div class="ddList">
                    <span class="value">{$page_size}</span>
                    <ul>
                        {foreach $items_on_page as $item}
                            <li class="{if $page_size==$item} act{/if}"><a href="{urlmake pageSize=$item}">{$item}</a></li>
                        {/foreach}
                    </ul>
                </div>
            </div>
        </div> *}

        {include file="favorite_page.tpl"}

    {else}
        <div class="noProducts fav_none">
            <p class="fav_none_text">{t}У вас нет избранных товаров{/t}</p>
        </div>
    {/if}
</div>