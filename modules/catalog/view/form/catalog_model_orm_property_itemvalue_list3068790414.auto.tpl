{$app->autoloadScripsAjaxBefore()}
<div class="virtual-form" data-has-validation="true" data-action="/cp/catalog-block-propertyvaluesblock/?ido=edit">
    <div class="crud-form-error"></div>
    <input type="hidden" name="value_id" value="{$elem.id}">
    <input type="hidden" name="prop_id" value="{$elem.prop_id}">
    <input type="hidden" name="prop_type" value="{$elem.prop_type}">
    <table class="table-inline-edit">
                                                    
                <tr>
                    <td class="key">{$elem.__value->getTitle()}&nbsp;&nbsp;{if $elem.__value->getHint() != ''}<a class="help-icon" title="{$elem.__value->getHint()|escape}">?</a>{/if} </td>
                    <td>{include file=$elem.__value->getRenderTemplate() field=$elem.__value}</td>
                </tr>
                                                            
                <tr>
                    <td class="key">{$elem.__alias->getTitle()}&nbsp;&nbsp;{if $elem.__alias->getHint() != ''}<a class="help-icon" title="{$elem.__alias->getHint()|escape}">?</a>{/if} </td>
                    <td>{include file=$elem.__alias->getRenderTemplate() field=$elem.__alias}</td>
                </tr>
                            
        
            <tr>
                <td class="key"></td>
                <td><a class="btn btn-success virtual-submit">Сохранить</a>
                <a class="btn btn-default cancel">Отмена</a>
                </td>
            </tr>
    </table>
</div>
{$app->autoloadScripsAjaxAfter()}