<!--  -->
<form method="GET" class="query on search" action="{$router->getUrl('catalog-front-listproducts', [])}" id="queryBox">
    <input type="text" class="search_field input query{*if !$param.hideAutoComplete*} {*autocomplete*}{*/if*}" name="query" value="{$query}" autocomplete="off" data-source-url="{$router->getUrl('catalog-block-searchline', ['sldo' => 'ajaxSearchItems', _block_id => $_block_id])}" placeholder="">
    <input type="submit" class="search_btn submit" value="" title="">
</form>