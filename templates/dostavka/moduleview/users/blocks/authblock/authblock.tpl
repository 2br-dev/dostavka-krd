{if $is_auth}
    <a href="{$router->getUrl('users-front-profile')}" class="h_btn_1 h_icon_profile"></a>
{else}
    {assign var=referer value=urlencode($url->server('REQUEST_URI'))}
    <a href="{$router->getUrl('users-front-auth', ['referer' => $referer])}" class="h_btn_1 h_icon_profile inDialog"></a>
{/if}