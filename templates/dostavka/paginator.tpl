
<div class="p_bottom">
    <div class="pageSize pag">
        {t}Показывать по{/t}
        <div class="ddList">
            <span class="value">{$page_size}</span>
            <ul>
                {foreach $items_on_page as $item}
                <li class="{if $page_size==$item} act{/if}"><a href="{$this_controller->api->urlMakeCatalogParams(['pageSize' => $item])}">{$item}</a></li>
                {/foreach}
            </ul>
        </div>
    </div>
    
    {if $paginator->total_pages>1}
        {$pagestr = t('Страница %page', ['page' => $paginator->page])}
        {if $paginator->page > 1 && !substr_count($app->title->get(), $pagestr)}
            {$app->title->addSection($pagestr, 0, 'after')|devnull}
            {$caonical = implode('', ['<link rel="canonical" href="', $SITE->getRootUrl(true), substr($paginator->getPageHref(1),1), '"/>'])}
            {$app->setAnyHeadData($caonical)|devnull}
        {/if}
        <div class="paginator">
        {hook name="catalog-list_products:options" title="{t}Просмотр категории продукции:параметры отображения{/t}"}
        {/hook}
            <div class="pages">
                {if $paginator->page>1}
                        <a href="{$paginator->getPageHref($paginator->page-1)}" class="prev" title="{t}предыдущая страница{/t}"></a>
                    {/if}
                {foreach $paginator->getPageList() as $page}
                    <a href="{$page.href}" data-page="{$page.n}" {if $page.act}class="active"{/if}>{$page.n}</a>
                {/foreach}
                {if $paginator->page < $paginator->total_pages}
                    <a href="{$paginator->getPageHref($paginator->page+1)}" class="next" title="{t}следующая страница{/t}"></a>
                {/if}
            </div>
        </div>
    {/if}
</div>
