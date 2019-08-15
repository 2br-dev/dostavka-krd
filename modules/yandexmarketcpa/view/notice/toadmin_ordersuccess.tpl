{extends file="%alerts%/notice_template.tpl"}
{block name="content"}
    <h1>Уважаемый, администратор!</h1>
    <p>{t domain={$url->getDomainStr() order_num={$data->order.order_num}} }На сайте %domain подтверждён заказ N %order_num{/t}.
    <a href="{$router->getAdminUrl('edit', ["id" => $data->order.id], 'shop-orderctrl', true)}">{t}Перейти к заказу{/t}</a></p>
{/block}