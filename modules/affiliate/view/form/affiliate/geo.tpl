{addjs file="//api-maps.yandex.ru/2.1/?lang=ru_RU" basepath="root"}
{addjs file="jquery.ui/jquery.autocomplete.js" basepath="common"}
{addjs file="%affiliate%/selectpoint.js"}

<p><input type="checkbox" name="use_geo" id="useGeo" {if $elem.coord_lat !== null || $elem.coord_lng !== null}checked{/if}><label for="useGeo">{t}Включить геопозицию{/t}</label></p>
<div class="selectPoint" data-coord-lat="{$elem.coord_lat}" data-coord-lng="{$elem.coord_lng}">
    <div class="yaAdressField">
        <input type="text" class="autocomplete" placeholder="{t}Поиск точки по адресу{/t}" size="70" autocomplete="off"> 
    </div>

    <div class="geoCoorContainer">
        <div id="ardessResults">
            {* Сюда будут вставлены результаты гео-ответа от Yandex *}
        </div>
    </div>

    {* Подключение карты *}
    <div id="map" class="yaMap"></div>
    <div class="latLng">
        {$elem.__coord_lat->getTitle()} {include file=$elem.__coord_lat->getRenderTemplate() field=$elem.__coord_lat}
        &nbsp;&nbsp; {$elem.__coord_lng->getTitle()} {include file=$elem.__coord_lng->getRenderTemplate() field=$elem.__coord_lng}
    </div>
</div>

<script type="text/javascript">
    $.allReady(function() {
        $.selectPoint();
    });
</script>