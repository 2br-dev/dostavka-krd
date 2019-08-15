<h2>Ваш IP: {$ip}</h2>
<p>{t}Ниже представлены результаты определения города по вашему IP адресу. Если вы заметили, что координаты успешно определены, но филиал определен неверно, то следует проверить корректность заполнения географических координат у каждого филиала.{/t}</p>
<div class="notice">
    {if $geo_coordinates.lat || $geo_coordinates.lng }
        <p>{t lat=$geo_coordinates.lat lng=$geo_coordinates.lng}Координаты (широта, долгота): <b>%lat, %lng</b>{/t}</p>
    {else}
        <p>{t}Координаты не определены сервисом ip-геолокации{/t}</p>
    {/if}

    {if $geo_city}
        {* Необязательная опция geoIP сервисов *}
        <p>{t geo_city=$geo_city}Город от GeoIP сервиса: <b>%geo_city</b>{/t}</p>
    {/if}

    {if $affiliate}
        {t name=$affiliate.title id=$affiliate.id}Найденный филиал (филиал определяется по близости координат): <b>%name (%id)</b>{/t}
    {else}
        <p>{t}Филиал не определен{/t}</p>
    {/if}
</div>