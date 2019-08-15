{if count($partner->getNonFormErrors())>0}
    <div class="pageError">
        {foreach from=$partner->getNonFormErrors() item=item}
        <p>{$item}</p>
        {/foreach}
    </div>
{/if}    

{if $result}
    <div class="formResult success">{$result}</div>
{/if}

<form method="POST" enctype="multipart/form-data">
    {$this_controller->myBlockIdInput()}
    <div class="formSection">
        <span class="formSectionTitle">{t}Настройки партнерского сайта{/t}</span>
    </div>
    <div class="changePasswordFrame">
        <table class="formTable">
            <tr>
                <td class="key">{t alias="Партнёрский модуль - Увеличение стоимости в %"}Увеличение<br> стоимости, в %{/t}</td>
                <td class="value">
                    {$partner->getPropertyView('price_inc_value')}
                    <div class="help">{t}Число от 0 до 100{/t}</div>
                </td>    
            </tr>        
            <tr>
                <td class="key">{t}Логотип{/t}</td>
                <td class="value">
                    {$partner->getPropertyView('logo')}
                    <div class="help">{t}Изображение размером 206x25 px в форматах GIF, PNG, JPG{/t}</div>
                </td>    
            </tr>
            <tr>
                <td class="key">{t}Слоган{/t}</td>
                <td class="value">
                    {$partner->getPropertyView('slogan')}
                </td>    
            </tr>            
            <tr>
                <td class="key">{t}Контактная информация{/t}</td>
                <td class="value">
                    <textarea style="width:100%; height:200px" name="contacts">{$partner.contacts}</textarea>
                </td>    
            </tr>            
        </table>        
    </div>

    <button type="submit" class="formSave">{t}Сохранить{/t}</button>
</form>