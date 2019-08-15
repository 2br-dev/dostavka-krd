

                                            
    


                                    
            <tr>
                <td class="otitle">{$elem.__content->getTitle()}&nbsp;&nbsp;{if $elem.__content->getHint() != ''}<a class="help-icon" title="{$elem.__content->getHint()|escape}">?</a>{/if}
                </td>
                <td>{include file=$elem.__content->getRenderTemplate() field=$elem.__content}</td>
            </tr>
                    
    
