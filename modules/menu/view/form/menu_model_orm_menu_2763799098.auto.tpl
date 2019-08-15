
<div class="formbox" >
            
    <div class="rs-tabs" role="tabpanel">
        <ul class="tab-nav" role="tablist">
                    <li class=" active"><a data-target="#menu-menu-tab0" data-toggle="tab" role="tab">{$elem->getPropertyIterator()->getGroupName(0)}</a></li>
                    <li class=""><a data-target="#menu-menu-tab1" data-toggle="tab" role="tab">{$elem->getPropertyIterator()->getGroupName(1)}</a></li>
        
        </ul>
        <form method="POST" action="{urlmake}" enctype="multipart/form-data" class="tab-content crud-form">
            <input type="submit" value="" style="display:none"/>
                        <div class="tab-pane active" id="menu-menu-tab0" role="tabpanel">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        
                                            <table class="otable">
                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__title->getTitle()}&nbsp;&nbsp;{if $elem.__title->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__title->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__title->getRenderTemplate() field=$elem.__title}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__hide_from_url->getTitle()}&nbsp;&nbsp;{if $elem.__hide_from_url->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__hide_from_url->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__hide_from_url->getRenderTemplate() field=$elem.__hide_from_url}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__alias->getTitle()}&nbsp;&nbsp;{if $elem.__alias->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__alias->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__alias->getRenderTemplate() field=$elem.__alias}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__parent->getTitle()}&nbsp;&nbsp;{if $elem.__parent->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__parent->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__parent->getRenderTemplate() field=$elem.__parent}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__public->getTitle()}&nbsp;&nbsp;{if $elem.__public->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__public->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__public->getRenderTemplate() field=$elem.__public}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__typelink->getTitle()}&nbsp;&nbsp;{if $elem.__typelink->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__typelink->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__typelink->getRenderTemplate() field=$elem.__typelink}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__affiliate_id->getTitle()}&nbsp;&nbsp;{if $elem.__affiliate_id->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__affiliate_id->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__affiliate_id->getRenderTemplate() field=$elem.__affiliate_id}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__partner_id->getTitle()}&nbsp;&nbsp;{if $elem.__partner_id->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__partner_id->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__partner_id->getRenderTemplate() field=$elem.__partner_id}</td>
                                </tr>
                                
                                                            
                        </table>
                                                </div>
                        <div class="tab-pane" id="menu-menu-tab1" role="tabpanel">
                                                                                                                                                                                                
                                            <table class="otable">
                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__mobile_public->getTitle()}&nbsp;&nbsp;{if $elem.__mobile_public->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__mobile_public->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__mobile_public->getRenderTemplate() field=$elem.__mobile_public}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__mobile_image->getTitle()}&nbsp;&nbsp;{if $elem.__mobile_image->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__mobile_image->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__mobile_image->getRenderTemplate() field=$elem.__mobile_image}</td>
                                </tr>
                                
                                                            
                        </table>
                                                </div>
            
        </form>
    </div>
    </div>