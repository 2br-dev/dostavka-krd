
<div class="formbox" >
            
    <div class="rs-tabs" role="tabpanel">
        <ul class="tab-nav" role="tablist">
                    <li class=" active"><a data-target="#catalog-warehouse-tab0" data-toggle="tab" role="tab">{$elem->getPropertyIterator()->getGroupName(0)}</a></li>
                    <li class=""><a data-target="#catalog-warehouse-tab1" data-toggle="tab" role="tab">{$elem->getPropertyIterator()->getGroupName(1)}</a></li>
                    <li class=""><a data-target="#catalog-warehouse-tab2" data-toggle="tab" role="tab">{$elem->getPropertyIterator()->getGroupName(2)}</a></li>
        
        </ul>
        <form method="POST" action="{urlmake}" enctype="multipart/form-data" class="tab-content crud-form">
            <input type="submit" value="" style="display:none"/>
                        <div class="tab-pane active" id="catalog-warehouse-tab0" role="tabpanel">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    {include file=$elem.__coor_x->getRenderTemplate() field=$elem.__coor_x}
                                                                                                                        {include file=$elem.__coor_y->getRenderTemplate() field=$elem.__coor_y}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        
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
                                    <td class="otitle">{$elem.__group_id->getTitle()}&nbsp;&nbsp;{if $elem.__group_id->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__group_id->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__group_id->getRenderTemplate() field=$elem.__group_id}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__image->getTitle()}&nbsp;&nbsp;{if $elem.__image->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__image->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__image->getRenderTemplate() field=$elem.__image}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__description->getTitle()}&nbsp;&nbsp;{if $elem.__description->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__description->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__description->getRenderTemplate() field=$elem.__description}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__adress->getTitle()}&nbsp;&nbsp;{if $elem.__adress->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__adress->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__adress->getRenderTemplate() field=$elem.__adress}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__phone->getTitle()}&nbsp;&nbsp;{if $elem.__phone->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__phone->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__phone->getRenderTemplate() field=$elem.__phone}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__work_time->getTitle()}&nbsp;&nbsp;{if $elem.__work_time->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__work_time->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__work_time->getRenderTemplate() field=$elem.__work_time}</td>
                                </tr>
                                
                                                                                                                                                                                                                                                    
                                <tr>
                                    <td class="otitle">{$elem.__default_house->getTitle()}&nbsp;&nbsp;{if $elem.__default_house->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__default_house->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__default_house->getRenderTemplate() field=$elem.__default_house}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__public->getTitle()}&nbsp;&nbsp;{if $elem.__public->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__public->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__public->getRenderTemplate() field=$elem.__public}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__checkout_public->getTitle()}&nbsp;&nbsp;{if $elem.__checkout_public->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__checkout_public->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__checkout_public->getRenderTemplate() field=$elem.__checkout_public}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__dont_change_stocks->getTitle()}&nbsp;&nbsp;{if $elem.__dont_change_stocks->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__dont_change_stocks->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__dont_change_stocks->getRenderTemplate() field=$elem.__dont_change_stocks}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__use_in_sitemap->getTitle()}&nbsp;&nbsp;{if $elem.__use_in_sitemap->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__use_in_sitemap->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__use_in_sitemap->getRenderTemplate() field=$elem.__use_in_sitemap}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__xml_id->getTitle()}&nbsp;&nbsp;{if $elem.__xml_id->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__xml_id->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__xml_id->getRenderTemplate() field=$elem.__xml_id}</td>
                                </tr>
                                
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__affiliate_id->getTitle()}&nbsp;&nbsp;{if $elem.__affiliate_id->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__affiliate_id->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__affiliate_id->getRenderTemplate() field=$elem.__affiliate_id}</td>
                                </tr>
                                
                                                            
                        </table>
                                                </div>
                        <div class="tab-pane" id="catalog-warehouse-tab1" role="tabpanel">
                                                                                                                                                                                                                                                                    
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
                        <div class="tab-pane" id="catalog-warehouse-tab2" role="tabpanel">
                                                                                                                            
                                            <table class="otable">
                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__yandex_market_point_id->getTitle()}&nbsp;&nbsp;{if $elem.__yandex_market_point_id->getHint() != ''}<a class="help-icon" data-placement="right" title="{$elem.__yandex_market_point_id->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__yandex_market_point_id->getRenderTemplate() field=$elem.__yandex_market_point_id}</td>
                                </tr>
                                
                                                            
                        </table>
                                                </div>
            
        </form>
    </div>
    </div>