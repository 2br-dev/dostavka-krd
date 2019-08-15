
<div class="formbox" >
            
    <div class="rs-tabs" role="tabpanel">
        <ul class="tab-nav" role="tablist">
                    <li class=" active"><a data-target="#catalog-dir-tab0" data-toggle="tab" role="tab">{$elem->getPropertyIterator()->getGroupName(0)}</a></li>
                    <li class=""><a data-target="#catalog-dir-tab1" data-toggle="tab" role="tab">{$elem->getPropertyIterator()->getGroupName(1)}</a></li>
                    <li class=""><a data-target="#catalog-dir-tab2" data-toggle="tab" role="tab">{$elem->getPropertyIterator()->getGroupName(2)}</a></li>
                    <li class=""><a data-target="#catalog-dir-tab3" data-toggle="tab" role="tab">{$elem->getPropertyIterator()->getGroupName(3)}</a></li>
                    <li class=""><a data-target="#catalog-dir-tab4" data-toggle="tab" role="tab">{$elem->getPropertyIterator()->getGroupName(4)}</a></li>
                    <li class=""><a data-target="#catalog-dir-tab6" data-toggle="tab" role="tab">{$elem->getPropertyIterator()->getGroupName(6)}</a></li>
                    <li class=""><a data-target="#catalog-dir-tab7" data-toggle="tab" role="tab">{$elem->getPropertyIterator()->getGroupName(7)}</a></li>
                    <li class=""><a data-target="#catalog-dir-tab8" data-toggle="tab" role="tab">{$elem->getPropertyIterator()->getGroupName(8)}</a></li>
                    <li class=""><a data-target="#catalog-dir-tab9" data-toggle="tab" role="tab">{$elem->getPropertyIterator()->getGroupName(9)}</a></li>
                    <li class=""><a data-target="#catalog-dir-tab10" data-toggle="tab" role="tab">{$elem->getPropertyIterator()->getGroupName(10)}</a></li>
        
        </ul>
        <form method="POST" action="{urlmake}" enctype="multipart/form-data" class="tab-content crud-form">
            <input type="submit" value="" style="display:none"/>
                        <div class="tab-pane active" id="catalog-dir-tab0" role="tabpanel">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        
                                            <table class="otable">
                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__name->getTitle()}&nbsp;&nbsp;{if $elem.__name->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__name->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__name->getRenderTemplate() field=$elem.__name}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__alias->getTitle()}&nbsp;&nbsp;{if $elem.__alias->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__alias->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__alias->getRenderTemplate() field=$elem.__alias}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__public->getTitle()}&nbsp;&nbsp;{if $elem.__public->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__public->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__public->getRenderTemplate() field=$elem.__public}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__is_label->getTitle()}&nbsp;&nbsp;{if $elem.__is_label->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__is_label->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__is_label->getRenderTemplate() field=$elem.__is_label}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__image->getTitle()}&nbsp;&nbsp;{if $elem.__image->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__image->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__image->getRenderTemplate() field=$elem.__image}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__weight->getTitle()}&nbsp;&nbsp;{if $elem.__weight->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__weight->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__weight->getRenderTemplate() field=$elem.__weight}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__xml_id->getTitle()}&nbsp;&nbsp;{if $elem.__xml_id->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__xml_id->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__xml_id->getRenderTemplate() field=$elem.__xml_id}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__palette_type->getTitle()}&nbsp;&nbsp;{if $elem.__palette_type->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__palette_type->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__palette_type->getRenderTemplate() field=$elem.__palette_type}</td>
                                </tr>
                                
                                                            
                        </table>
                                                </div>
                        <div class="tab-pane" id="catalog-dir-tab1" role="tabpanel">
                                                                                                            {include file=$elem.____property__->getRenderTemplate() field=$elem.____property__}
                                                                                                
                                                </div>
                        <div class="tab-pane" id="catalog-dir-tab2" role="tabpanel">
                                                                                                                            
                                            <table class="otable">
                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__description->getTitle()}&nbsp;&nbsp;{if $elem.__description->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__description->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__description->getRenderTemplate() field=$elem.__description}</td>
                                </tr>
                                
                                                            
                        </table>
                                                </div>
                        <div class="tab-pane" id="catalog-dir-tab3" role="tabpanel">
                                                                                                                                                                                                                                                                    
                                            <table class="otable">
                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__meta_title->getTitle()}&nbsp;&nbsp;{if $elem.__meta_title->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__meta_title->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__meta_title->getRenderTemplate() field=$elem.__meta_title}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__meta_keywords->getTitle()}&nbsp;&nbsp;{if $elem.__meta_keywords->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__meta_keywords->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__meta_keywords->getRenderTemplate() field=$elem.__meta_keywords}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__meta_description->getTitle()}&nbsp;&nbsp;{if $elem.__meta_description->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__meta_description->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__meta_description->getRenderTemplate() field=$elem.__meta_description}</td>
                                </tr>
                                
                                                            
                        </table>
                                                </div>
                        <div class="tab-pane" id="catalog-dir-tab4" role="tabpanel">
                                                                                                                                                                                                                                                                                                                                        
                                            <table class="otable">
                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__product_meta_title->getTitle()}&nbsp;&nbsp;{if $elem.__product_meta_title->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__product_meta_title->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__product_meta_title->getRenderTemplate() field=$elem.__product_meta_title}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__product_meta_keywords->getTitle()}&nbsp;&nbsp;{if $elem.__product_meta_keywords->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__product_meta_keywords->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__product_meta_keywords->getRenderTemplate() field=$elem.__product_meta_keywords}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__product_meta_description->getTitle()}&nbsp;&nbsp;{if $elem.__product_meta_description->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__product_meta_description->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__product_meta_description->getRenderTemplate() field=$elem.__product_meta_description}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__in_list_properties_arr->getTitle()}&nbsp;&nbsp;{if $elem.__in_list_properties_arr->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__in_list_properties_arr->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__in_list_properties_arr->getRenderTemplate() field=$elem.__in_list_properties_arr}</td>
                                </tr>
                                
                                                            
                        </table>
                                                </div>
                        <div class="tab-pane" id="catalog-dir-tab6" role="tabpanel">
                                                                                                                            
                                            <table class="otable">
                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__export_name->getTitle()}&nbsp;&nbsp;{if $elem.__export_name->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__export_name->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__export_name->getRenderTemplate() field=$elem.__export_name}</td>
                                </tr>
                                
                                                            
                        </table>
                                                </div>
                        <div class="tab-pane" id="catalog-dir-tab7" role="tabpanel">
                                                                                                            {include file=$elem.___recomended_->getRenderTemplate() field=$elem.___recomended_}
                                                                                                
                                                </div>
                        <div class="tab-pane" id="catalog-dir-tab8" role="tabpanel">
                                                                                                            {include file=$elem.___concomitant_->getRenderTemplate() field=$elem.___concomitant_}
                                                                                                
                                                </div>
                        <div class="tab-pane" id="catalog-dir-tab9" role="tabpanel">
                                                                                                                                                                                                                                                                    
                                            <table class="otable">
                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__mobile_background_color->getTitle()}&nbsp;&nbsp;{if $elem.__mobile_background_color->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__mobile_background_color->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__mobile_background_color->getRenderTemplate() field=$elem.__mobile_background_color}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__mobile_tablet_background_image->getTitle()}&nbsp;&nbsp;{if $elem.__mobile_tablet_background_image->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__mobile_tablet_background_image->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__mobile_tablet_background_image->getRenderTemplate() field=$elem.__mobile_tablet_background_image}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__mobile_tablet_icon->getTitle()}&nbsp;&nbsp;{if $elem.__mobile_tablet_icon->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__mobile_tablet_icon->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__mobile_tablet_icon->getRenderTemplate() field=$elem.__mobile_tablet_icon}</td>
                                </tr>
                                
                                                            
                        </table>
                                                </div>
                        <div class="tab-pane" id="catalog-dir-tab10" role="tabpanel">
                                                                                                                            
                                            <table class="otable">
                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__tax_ids->getTitle()}&nbsp;&nbsp;{if $elem.__tax_ids->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__tax_ids->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__tax_ids->getRenderTemplate() field=$elem.__tax_ids}</td>
                                </tr>
                                
                                                            
                        </table>
                                                </div>
            
        </form>
    </div>
    </div>