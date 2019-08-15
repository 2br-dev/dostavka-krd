
<div class="formbox" >
                
        <form method="POST" action="{urlmake}" enctype="multipart/form-data" class="crud-form">
            <input type="submit" value="" style="display:none">
            <div class="notabs">
                                                                                                            
                                                                                            
                                                                                            
                                                                                            
                                                                                            
                                                                                            
                                                                                            
                                                                                            
                                                                                            
                                                                                            
                                                                                            
                                                                                            
                                                                                            
                                                                                            
                    
                
                
                                    <table class="otable">
                                                                                                                    
                                <tr>
                                    <td class="otitle">{$elem.__type->getTitle()}&nbsp;&nbsp;{if $elem.__type->getHint() != ''}<a class="help-icon" title="{$elem.__type->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__type->getRenderTemplate() field=$elem.__type}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__aid->getTitle()}&nbsp;&nbsp;{if $elem.__aid->getHint() != ''}<a class="help-icon" title="{$elem.__aid->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__aid->getRenderTemplate() field=$elem.__aid}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.____url__->getTitle()}&nbsp;&nbsp;{if $elem.____url__->getHint() != ''}<a class="help-icon" title="{$elem.____url__->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.____url__->getRenderTemplate() field=$elem.____url__}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__dateof->getTitle()}&nbsp;&nbsp;{if $elem.__dateof->getHint() != ''}<a class="help-icon" title="{$elem.__dateof->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__dateof->getRenderTemplate() field=$elem.__dateof}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__user_id->getTitle()}&nbsp;&nbsp;{if $elem.__user_id->getHint() != ''}<a class="help-icon" title="{$elem.__user_id->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__user_id->getRenderTemplate() field=$elem.__user_id}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.____url_user__->getTitle()}&nbsp;&nbsp;{if $elem.____url_user__->getHint() != ''}<a class="help-icon" title="{$elem.____url_user__->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.____url_user__->getRenderTemplate() field=$elem.____url_user__}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__user_name->getTitle()}&nbsp;&nbsp;{if $elem.__user_name->getHint() != ''}<a class="help-icon" title="{$elem.__user_name->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__user_name->getRenderTemplate() field=$elem.__user_name}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__message->getTitle()}&nbsp;&nbsp;{if $elem.__message->getHint() != ''}<a class="help-icon" title="{$elem.__message->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__message->getRenderTemplate() field=$elem.__message}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__moderated->getTitle()}&nbsp;&nbsp;{if $elem.__moderated->getHint() != ''}<a class="help-icon" title="{$elem.__moderated->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__moderated->getRenderTemplate() field=$elem.__moderated}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__rate->getTitle()}&nbsp;&nbsp;{if $elem.__rate->getHint() != ''}<a class="help-icon" title="{$elem.__rate->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__rate->getRenderTemplate() field=$elem.__rate}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__help_yes->getTitle()}&nbsp;&nbsp;{if $elem.__help_yes->getHint() != ''}<a class="help-icon" title="{$elem.__help_yes->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__help_yes->getRenderTemplate() field=$elem.__help_yes}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__help_no->getTitle()}&nbsp;&nbsp;{if $elem.__help_no->getHint() != ''}<a class="help-icon" title="{$elem.__help_no->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__help_no->getRenderTemplate() field=$elem.__help_no}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__ip->getTitle()}&nbsp;&nbsp;{if $elem.__ip->getHint() != ''}<a class="help-icon" title="{$elem.__ip->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__ip->getRenderTemplate() field=$elem.__ip}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__useful->getTitle()}&nbsp;&nbsp;{if $elem.__useful->getHint() != ''}<a class="help-icon" title="{$elem.__useful->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__useful->getRenderTemplate() field=$elem.__useful}</td>
                                </tr>
                                                            
                        
                    </table>
                            </div>
        </form>
    </div>