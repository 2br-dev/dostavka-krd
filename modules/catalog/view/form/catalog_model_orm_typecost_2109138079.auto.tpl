
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
                                    <td class="otitle">{$elem.__type->getTitle()}&nbsp;&nbsp;{if $elem.__type->getHint() != ''}<a class="help-icon" title="{$elem.__type->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__type->getRenderTemplate() field=$elem.__type}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__round->getTitle()}&nbsp;&nbsp;{if $elem.__round->getHint() != ''}<a class="help-icon" title="{$elem.__round->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__round->getRenderTemplate() field=$elem.__round}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__old_cost->getTitle()}&nbsp;&nbsp;{if $elem.__old_cost->getHint() != ''}<a class="help-icon" title="{$elem.__old_cost->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__old_cost->getRenderTemplate() field=$elem.__old_cost}</td>
                                </tr>
                                                            
                        
                    </table>
                            </div>
        </form>
    </div>