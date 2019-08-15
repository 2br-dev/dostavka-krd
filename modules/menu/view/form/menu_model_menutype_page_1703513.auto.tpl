

                                            
    


                                    
            <tr>
                <td class="otitle">{$elem.__link_template->getTitle()}&nbsp;&nbsp;{if $elem.__link_template->getHint() != ''}<a class="help-icon" title="{$elem.__link_template->getHint()|escape}">?</a>{/if}
                </td>
                <td>{include file=$elem.__link_template->getRenderTemplate() field=$elem.__link_template}</td>
            </tr>
                    
    
