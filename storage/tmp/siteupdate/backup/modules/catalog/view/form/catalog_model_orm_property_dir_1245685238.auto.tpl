
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
                                    <td class="otitle">{$elem.__hidden->getTitle()}&nbsp;&nbsp;{if $elem.__hidden->getHint() != ''}<a class="help-icon" title="{$elem.__hidden->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__hidden->getRenderTemplate() field=$elem.__hidden}</td>
                                </tr>
                                                            
                        
                    </table>
                            </div>
        </form>
    </div>