
{*  *}
{$counterSubDir=1}
{*  *}
{foreach $dirlist as $dir}
{if $dir.fields.name != 'Лидеры продаж' && count($dir.child) > 0}
    <nav class="tn_menu" state="hide" id="category_{$counterSubDir}">
        <div class="tn_menu_wrap">
            {$longCol=false}
            {foreach $dir.child as $subDir}
                {*  *}
                {$checkClasses_YES = $subDir.fields.name == "Освежители воздуха" || $subDir.fields.name == "Уход за обувью и одеждой" || $subDir.fields.name == 'Для мам' || $subDir.fields.name == 'Стирка и мытье детских вещей' || $subDir.fields.name == 'Детская безопасность' || $subDir.fields.name == 'Уход за кожей ребенка' || $subDir.fields.name == 'Детское питание' || $subDir.fields.name == 'Салфетки, бумажная продукция' || $subDir.fields.name == 'Бритье и депиляция' || $subDir.fields.name == 'Защита полости рта' || $subDir.fields.name == 'Контрацептивы, тесты' || $subDir.fields.name == 'Подгузники и простыни для взрослых' || $subDir.fields.name == 'Уход за ногами' || $subDir.fields.name == 'Уход за телом'  || $subDir.fields.name == 'Подарочные наборы' || $subDir.fields.name == 'Элементы питания' || $subDir.fields.name == 'Лампочки' || $subDir.fields.name == 'Кухонные принадлежности' || $subDir.fields.name == 'Хлебобулочные изделия' || $subDir.fields.name == 'Консервированная продукция'}
                {$checkClasses_NO  = $subDir.fields.name != "Освежители воздуха" && $subDir.fields.name != "Уход за обувью и одеждой" && $subDir.fields.name != 'Для мам' && $subDir.fields.name != 'Стирка и мытье детских вещей' && $subDir.fields.name != 'Детская безопасность' && $subDir.fields.name != 'Уход за кожей ребенка' && $subDir.fields.name != 'Детское питание' && $subDir.fields.name != 'Салфетки, бумажная продукция' && $subDir.fields.name != 'Бритье и депиляция' && $subDir.fields.name != 'Защита полости рта' && $subDir.fields.name != 'Контрацептивы, тесты' && $subDir.fields.name != 'Подгузники и простыни для взрослых' && $subDir.fields.name != 'Уход за ногами' && $subDir.fields.name != 'Уход за телом'  && $subDir.fields.name != 'Подарочные наборы' && $subDir.fields.name != 'Элементы питания' && $subDir.fields.name != 'Лампочки' && $subDir.fields.name != 'Кухонные принадлежности' && $subDir.fields.name != 'Хлебобулочные изделия' && $subDir.fields.name != 'Консервированная продукция'}
                {*  *}
                {if $checkClasses_YES}
                    <div class="tn_menu_space"></div>
                    <a href="{$subDir.fields->getUrl()}?palette={$counterSubDir}" class="tn_menu_text_link tn_menu_link_bold">{$subDir.fields.name}</a>
                    
                    {foreach $subDir.child as $subSubDir}
                        <a href="{$subSubDir.fields->getUrl()}?palette={$counterSubDir}" class="tn_menu_text_link">{$subSubDir.fields.name}</a>
                    {/foreach}
                    {if $subDir.fields.name == 'Уход за кожей ребенка' || $subDir.fields.name == 'Контрацептивы, тесты' || $subDir.fields.name == 'Элементы питания'}
                        {$longCol=false}  
                    {else}
                        {$longCol=true}  
                    {/if} 
                {else}
                    {if $longCol}
                        </div>
                    {/if}
                {/if}
                {if $checkClasses_NO}
                    <div class="tn_menu_col">
                        <a href="{$subDir.fields->getUrl()}?palette={$counterSubDir}" class="tn_menu_text_link tn_menu_link_bold">{$subDir.fields.name}</a>
                        
                        {foreach $subDir.child as $subSubDir}
                            <a href="{$subSubDir.fields->getUrl()}?palette={$counterSubDir}" class="tn_menu_text_link">{$subSubDir.fields.name}</a>
                        {/foreach}
                    {$longCol=true}
                {/if}
            {/foreach}
            {$counterSubDir=$counterSubDir+1}

        </div>
    </nav>
{/if}
{/foreach}
