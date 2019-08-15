
<div class="formbox" >
                
        <form method="POST" action="{urlmake}" enctype="multipart/form-data" class="crud-form">
            <input type="submit" value="" style="display:none">
            <div class="notabs">
                                                                                                            
                                                                                            
                                                                                            
                                                                                            
                                                                                            
                                                                                            
                                                                                            
                                                                                            
                                                                                            
                                                                                            
                                                                                            
                    
                
                
                                    <table class="otable">
                                                                                                                    
                                <tr>
                                    <td class="otitle">{$elem.__title->getTitle()}&nbsp;&nbsp;{if $elem.__title->getHint() != ''}<a class="help-icon" title="{$elem.__title->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__title->getRenderTemplate() field=$elem.__title}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__full_title->getTitle()}&nbsp;&nbsp;{if $elem.__full_title->getHint() != ''}<a class="help-icon" title="{$elem.__full_title->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__full_title->getRenderTemplate() field=$elem.__full_title}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__domains->getTitle()}&nbsp;&nbsp;{if $elem.__domains->getHint() != ''}<a class="help-icon" title="{$elem.__domains->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__domains->getRenderTemplate() field=$elem.__domains}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__folder->getTitle()}&nbsp;&nbsp;{if $elem.__folder->getHint() != ''}<a class="help-icon" title="{$elem.__folder->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__folder->getRenderTemplate() field=$elem.__folder}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__language->getTitle()}&nbsp;&nbsp;{if $elem.__language->getHint() != ''}<a class="help-icon" title="{$elem.__language->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__language->getRenderTemplate() field=$elem.__language}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__default->getTitle()}&nbsp;&nbsp;{if $elem.__default->getHint() != ''}<a class="help-icon" title="{$elem.__default->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__default->getRenderTemplate() field=$elem.__default}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__update_robots_txt->getTitle()}&nbsp;&nbsp;{if $elem.__update_robots_txt->getHint() != ''}<a class="help-icon" title="{$elem.__update_robots_txt->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__update_robots_txt->getRenderTemplate() field=$elem.__update_robots_txt}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__redirect_to_main_domain->getTitle()}&nbsp;&nbsp;{if $elem.__redirect_to_main_domain->getHint() != ''}<a class="help-icon" title="{$elem.__redirect_to_main_domain->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__redirect_to_main_domain->getRenderTemplate() field=$elem.__redirect_to_main_domain}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__redirect_to_https->getTitle()}&nbsp;&nbsp;{if $elem.__redirect_to_https->getHint() != ''}<a class="help-icon" title="{$elem.__redirect_to_https->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__redirect_to_https->getRenderTemplate() field=$elem.__redirect_to_https}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__theme->getTitle()}&nbsp;&nbsp;{if $elem.__theme->getHint() != ''}<a class="help-icon" title="{$elem.__theme->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__theme->getRenderTemplate() field=$elem.__theme}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__is_closed->getTitle()}&nbsp;&nbsp;{if $elem.__is_closed->getHint() != ''}<a class="help-icon" title="{$elem.__is_closed->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__is_closed->getRenderTemplate() field=$elem.__is_closed}</td>
                                </tr>
                                                            
                        
                    </table>
                            </div>
        </form>
    </div>