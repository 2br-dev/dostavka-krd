
<div class="formbox" >
            
    <div class="rs-tabs" role="tabpanel">
        <ul class="tab-nav" role="tablist">
                    <li class=" active"><a data-target="#catalog-property-item-tab0" data-toggle="tab" role="tab">{$elem->getPropertyIterator()->getGroupName(0)}</a></li>
                    <li class=""><a data-target="#catalog-property-item-tab1" data-toggle="tab" role="tab">{$elem->getPropertyIterator()->getGroupName(1)}</a></li>
        
        </ul>
        <form method="POST" action="{urlmake}" enctype="multipart/form-data" class="tab-content crud-form">
            <input type="submit" value="" style="display:none"/>
                        <div class="tab-pane active" id="catalog-property-item-tab0" role="tabpanel">
                                                                                                                                    {include file=$elem.__id->getRenderTemplate() field=$elem.__id}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        
                                            <table class="otable">
                                                                                                                                                        
                                <tr>
                                    <td class="otitle">{$elem.__title->getTitle()}&nbsp;&nbsp;{if $elem.__title->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__title->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__title->getRenderTemplate() field=$elem.__title}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__alias->getTitle()}&nbsp;&nbsp;{if $elem.__alias->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__alias->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__alias->getRenderTemplate() field=$elem.__alias}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__type->getTitle()}&nbsp;&nbsp;{if $elem.__type->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__type->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__type->getRenderTemplate() field=$elem.__type}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__description->getTitle()}&nbsp;&nbsp;{if $elem.__description->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__description->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__description->getRenderTemplate() field=$elem.__description}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__unit->getTitle()}&nbsp;&nbsp;{if $elem.__unit->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__unit->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__unit->getRenderTemplate() field=$elem.__unit}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__unit_export->getTitle()}&nbsp;&nbsp;{if $elem.__unit_export->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__unit_export->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__unit_export->getRenderTemplate() field=$elem.__unit_export}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__name_for_export->getTitle()}&nbsp;&nbsp;{if $elem.__name_for_export->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__name_for_export->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__name_for_export->getRenderTemplate() field=$elem.__name_for_export}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__parent_id->getTitle()}&nbsp;&nbsp;{if $elem.__parent_id->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__parent_id->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__parent_id->getRenderTemplate() field=$elem.__parent_id}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__int_hide_inputs->getTitle()}&nbsp;&nbsp;{if $elem.__int_hide_inputs->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__int_hide_inputs->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__int_hide_inputs->getRenderTemplate() field=$elem.__int_hide_inputs}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__hidden->getTitle()}&nbsp;&nbsp;{if $elem.__hidden->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__hidden->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__hidden->getRenderTemplate() field=$elem.__hidden}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__no_export->getTitle()}&nbsp;&nbsp;{if $elem.__no_export->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__no_export->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__no_export->getRenderTemplate() field=$elem.__no_export}</td>
                                </tr>
                                
                                                            
                        </table>
                                                </div>
                        <div class="tab-pane" id="catalog-property-item-tab1" role="tabpanel">
                                                                                                            {include file=$elem.____property_values__->getRenderTemplate() field=$elem.____property_values__}
                                                                                                
                                                </div>
            
        </form>
    </div>
    </div>