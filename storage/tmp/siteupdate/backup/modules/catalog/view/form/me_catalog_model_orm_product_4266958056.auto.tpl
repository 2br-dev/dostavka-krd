
    <div class="me_info">
        {t}Выбрано элементов:{/t} <strong>{$param.sel_count}</strong>
    </div>
	{if count($param.ids)==0}
		<div class="me_no_select">
            {t}Для группового редактирования необходимо отметить несколько элементов.{/t}
		</div>
	{else}


<div class="formbox" >
                
    <div class="rs-tabs" role="tabpanel">
        <ul class="tab-nav" role="tablist">
                            <li class=" active"><a data-target="#catalog-product-tab0" data-toggle="tab" role="tab">{$elem->getPropertyIterator()->getGroupName(0)}</a></li>
                            <li class=""><a data-target="#catalog-product-tab1" data-toggle="tab" role="tab">{$elem->getPropertyIterator()->getGroupName(1)}</a></li>
                            <li class=""><a data-target="#catalog-product-tab2" data-toggle="tab" role="tab">{$elem->getPropertyIterator()->getGroupName(2)}</a></li>
                            <li class=""><a data-target="#catalog-product-tab3" data-toggle="tab" role="tab">{$elem->getPropertyIterator()->getGroupName(3)}</a></li>
                            <li class=""><a data-target="#catalog-product-tab4" data-toggle="tab" role="tab">{$elem->getPropertyIterator()->getGroupName(4)}</a></li>
                            <li class=""><a data-target="#catalog-product-tab5" data-toggle="tab" role="tab">{$elem->getPropertyIterator()->getGroupName(5)}</a></li>
                            <li class=""><a data-target="#catalog-product-tab8" data-toggle="tab" role="tab">{$elem->getPropertyIterator()->getGroupName(8)}</a></li>
            
        </ul>
        <form method="POST" action="{urlmake}" enctype="multipart/form-data" class="tab-content crud-form">
            <input type="submit" value="" style="display:none">
                        <div class="tab-pane active" id="catalog-product-tab0" role="tabpanel">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                
                                            <table class="otable">
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-title" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__title->getName()}" {if in_array($elem.__title->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-title">{$elem.__title->getTitle()}</label>&nbsp;&nbsp;{if $elem.__title->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__title->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__title->getRenderTemplate(true) field=$elem.__title}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-short_description" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__short_description->getName()}" {if in_array($elem.__short_description->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-short_description">{$elem.__short_description->getTitle()}</label>&nbsp;&nbsp;{if $elem.__short_description->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__short_description->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__short_description->getRenderTemplate(true) field=$elem.__short_description}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-description" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__description->getName()}" {if in_array($elem.__description->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-description">{$elem.__description->getTitle()}</label>&nbsp;&nbsp;{if $elem.__description->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__description->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__description->getRenderTemplate(true) field=$elem.__description}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-barcode" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__barcode->getName()}" {if in_array($elem.__barcode->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-barcode">{$elem.__barcode->getTitle()}</label>&nbsp;&nbsp;{if $elem.__barcode->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__barcode->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__barcode->getRenderTemplate(true) field=$elem.__barcode}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-weight" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__weight->getName()}" {if in_array($elem.__weight->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-weight">{$elem.__weight->getTitle()}</label>&nbsp;&nbsp;{if $elem.__weight->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__weight->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__weight->getRenderTemplate(true) field=$elem.__weight}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-dateof" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__dateof->getName()}" {if in_array($elem.__dateof->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-dateof">{$elem.__dateof->getTitle()}</label>&nbsp;&nbsp;{if $elem.__dateof->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__dateof->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__dateof->getRenderTemplate(true) field=$elem.__dateof}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-excost" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__excost->getName()}" {if in_array($elem.__excost->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-excost">{$elem.__excost->getTitle()}</label>&nbsp;&nbsp;{if $elem.__excost->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__excost->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__excost->getRenderTemplate(true) field=$elem.__excost}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-num" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__num->getName()}" {if in_array($elem.__num->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-num">{$elem.__num->getTitle()}</label>&nbsp;&nbsp;{if $elem.__num->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__num->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__num->getRenderTemplate(true) field=$elem.__num}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-amount_step" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__amount_step->getName()}" {if in_array($elem.__amount_step->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-amount_step">{$elem.__amount_step->getTitle()}</label>&nbsp;&nbsp;{if $elem.__amount_step->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__amount_step->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__amount_step->getRenderTemplate(true) field=$elem.__amount_step}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-unit" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__unit->getName()}" {if in_array($elem.__unit->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-unit">{$elem.__unit->getTitle()}</label>&nbsp;&nbsp;{if $elem.__unit->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__unit->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__unit->getRenderTemplate(true) field=$elem.__unit}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-min_order" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__min_order->getName()}" {if in_array($elem.__min_order->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-min_order">{$elem.__min_order->getTitle()}</label>&nbsp;&nbsp;{if $elem.__min_order->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__min_order->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__min_order->getRenderTemplate(true) field=$elem.__min_order}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-max_order" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__max_order->getName()}" {if in_array($elem.__max_order->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-max_order">{$elem.__max_order->getTitle()}</label>&nbsp;&nbsp;{if $elem.__max_order->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__max_order->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__max_order->getRenderTemplate(true) field=$elem.__max_order}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-public" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__public->getName()}" {if in_array($elem.__public->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-public">{$elem.__public->getTitle()}</label>&nbsp;&nbsp;{if $elem.__public->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__public->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__public->getRenderTemplate(true) field=$elem.__public}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-no_export" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__no_export->getName()}" {if in_array($elem.__no_export->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-no_export">{$elem.__no_export->getTitle()}</label>&nbsp;&nbsp;{if $elem.__no_export->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__no_export->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__no_export->getRenderTemplate(true) field=$elem.__no_export}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-xdir" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__xdir->getName()}" {if in_array($elem.__xdir->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-xdir">{$elem.__xdir->getTitle()}</label>&nbsp;&nbsp;{if $elem.__xdir->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__xdir->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__xdir->getRenderTemplate(true) field=$elem.__xdir}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-maindir" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__maindir->getName()}" {if in_array($elem.__maindir->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-maindir">{$elem.__maindir->getTitle()}</label>&nbsp;&nbsp;{if $elem.__maindir->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__maindir->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__maindir->getRenderTemplate(true) field=$elem.__maindir}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-xspec" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__xspec->getName()}" {if in_array($elem.__xspec->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-xspec">{$elem.__xspec->getTitle()}</label>&nbsp;&nbsp;{if $elem.__xspec->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__xspec->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__xspec->getRenderTemplate(true) field=$elem.__xspec}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-reservation" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__reservation->getName()}" {if in_array($elem.__reservation->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-reservation">{$elem.__reservation->getTitle()}</label>&nbsp;&nbsp;{if $elem.__reservation->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__reservation->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__reservation->getRenderTemplate(true) field=$elem.__reservation}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-brand_id" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__brand_id->getName()}" {if in_array($elem.__brand_id->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-brand_id">{$elem.__brand_id->getTitle()}</label>&nbsp;&nbsp;{if $elem.__brand_id->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__brand_id->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__brand_id->getRenderTemplate(true) field=$elem.__brand_id}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-simage" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__simage->getName()}" {if in_array($elem.__simage->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-simage">{$elem.__simage->getTitle()}</label>&nbsp;&nbsp;{if $elem.__simage->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__simage->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__simage->getRenderTemplate(true) field=$elem.__simage}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-rating" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__rating->getName()}" {if in_array($elem.__rating->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-rating">{$elem.__rating->getTitle()}</label>&nbsp;&nbsp;{if $elem.__rating->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__rating->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__rating->getRenderTemplate(true) field=$elem.__rating}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-group_id" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__group_id->getName()}" {if in_array($elem.__group_id->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-group_id">{$elem.__group_id->getTitle()}</label>&nbsp;&nbsp;{if $elem.__group_id->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__group_id->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__group_id->getRenderTemplate(true) field=$elem.__group_id}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-sku" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__sku->getName()}" {if in_array($elem.__sku->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-sku">{$elem.__sku->getTitle()}</label>&nbsp;&nbsp;{if $elem.__sku->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__sku->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__sku->getRenderTemplate(true) field=$elem.__sku}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-sortn" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__sortn->getName()}" {if in_array($elem.__sortn->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-sortn">{$elem.__sortn->getTitle()}</label>&nbsp;&nbsp;{if $elem.__sortn->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__sortn->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__sortn->getRenderTemplate(true) field=$elem.__sortn}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-payment_subject" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__payment_subject->getName()}" {if in_array($elem.__payment_subject->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-payment_subject">{$elem.__payment_subject->getTitle()}</label>&nbsp;&nbsp;{if $elem.__payment_subject->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__payment_subject->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__payment_subject->getRenderTemplate(true) field=$elem.__payment_subject}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-disallow_manually_add_to_cart" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__disallow_manually_add_to_cart->getName()}" {if in_array($elem.__disallow_manually_add_to_cart->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-disallow_manually_add_to_cart">{$elem.__disallow_manually_add_to_cart->getTitle()}</label>&nbsp;&nbsp;{if $elem.__disallow_manually_add_to_cart->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__disallow_manually_add_to_cart->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__disallow_manually_add_to_cart->getRenderTemplate(true) field=$elem.__disallow_manually_add_to_cart}</div></td>
                            </tr>
                            
                        </table>
                                                </div>
                        <div class="tab-pane" id="catalog-product-tab1" role="tabpanel">
                                                                                                            {include file=$elem.___property_->getRenderTemplate(true) field=$elem.___property_}
                                                                        
                                                </div>
                        <div class="tab-pane" id="catalog-product-tab2" role="tabpanel">
                                                                                                            {include file=$elem.___offers_->getRenderTemplate(true) field=$elem.___offers_}
                                                                                                                    
                                                </div>
                        <div class="tab-pane" id="catalog-product-tab3" role="tabpanel">
                                                                                                                                                                                            
                                            <table class="otable">
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-meta_title" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__meta_title->getName()}" {if in_array($elem.__meta_title->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-meta_title">{$elem.__meta_title->getTitle()}</label>&nbsp;&nbsp;{if $elem.__meta_title->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__meta_title->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__meta_title->getRenderTemplate(true) field=$elem.__meta_title}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-meta_keywords" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__meta_keywords->getName()}" {if in_array($elem.__meta_keywords->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-meta_keywords">{$elem.__meta_keywords->getTitle()}</label>&nbsp;&nbsp;{if $elem.__meta_keywords->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__meta_keywords->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__meta_keywords->getRenderTemplate(true) field=$elem.__meta_keywords}</div></td>
                            </tr>
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-meta_description" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__meta_description->getName()}" {if in_array($elem.__meta_description->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-meta_description">{$elem.__meta_description->getTitle()}</label>&nbsp;&nbsp;{if $elem.__meta_description->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__meta_description->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__meta_description->getRenderTemplate(true) field=$elem.__meta_description}</div></td>
                            </tr>
                            
                        </table>
                                                </div>
                        <div class="tab-pane" id="catalog-product-tab4" role="tabpanel">
                                                                                                            {include file=$elem.___recomended_->getRenderTemplate(true) field=$elem.___recomended_}
                                                                        
                                                </div>
                        <div class="tab-pane" id="catalog-product-tab5" role="tabpanel">
                                                                                                            {include file=$elem.___concomitant_->getRenderTemplate(true) field=$elem.___concomitant_}
                                                                        
                                                </div>
                        <div class="tab-pane" id="catalog-product-tab8" role="tabpanel">
                                                                                                    
                                            <table class="otable">
                                                        
                            <tr class="editrow">
                                <td class="ochk" width="20">
                                    <input id="me-catalog-product-tax_ids" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__tax_ids->getName()}" {if in_array($elem.__tax_ids->getName(), $param.doedit)}checked{/if}></td>
                                <td class="otitle">
                                    <label for="me-catalog-product-tax_ids">{$elem.__tax_ids->getTitle()}</label>&nbsp;&nbsp;{if $elem.__tax_ids->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__tax_ids->getHint()|escape}">?</a>{/if}
                                </td>
                                <td><div class="multi_edit_rightcol coveron"><div class="cover"></div>{include file=$elem.__tax_ids->getRenderTemplate(true) field=$elem.__tax_ids}</div></td>
                            </tr>
                            
                        </table>
                                                </div>
            
        </form>
    </div>
    </div>
{/if}