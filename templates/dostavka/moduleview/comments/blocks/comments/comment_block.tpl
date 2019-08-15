{addjs file="{$mod_js}comments.js" basepath="root"}
<!--  -->
<div class="prod_mobile_bar_bottom" id="show_reviews">
    <p class="prod_mobile_bar_bottom_title">Отзывы о товаре</p>
    <p class="prod_mobile_bar_bottom_count">{count($commentlist)}</p>
</div>
<!--  -->
<div class="commentBlock comment_block">
    <div class="commentFormBlock open">
        {if $total}
            <ul class="commentList">
                {$list_html}
            </ul>
        {else}
            <div class="noComments">
                <p class="comment_label_1">{t}нет отзывов{/t}</p>
            </div>
        {/if}
        {if $mod_config.need_authorize == 'Y' && $is_auth}
            <form method="POST" class="formStyle" action="#comments">  
                <div class="comment_box_1">
                    <p class="comment_title">Напишите Ваш отзыв</p>                                             
                    <div class="rating comment_rating">
                        <input class="inp_rate" type="hidden" name="rate" value="{$comment.rate}">                     
                        <div class="starsBlock comment_stars">
                            <i class="comment_star"></i>
                            <i class="comment_star"></i>
                            <i class="comment_star"></i>
                            <i class="comment_star"></i>
                            <i class="comment_star"></i>
                        </div>
                        <span class="desc" style="display: none">{$comment->getRateText()}</span>
                    </div>
                </div>
                <!--  -->
                {$this_controller->myBlockIdInput()}
                <textarea name="message" class="comment_message">{$comment.message}</textarea>
                {if $already_write}<div class="already comment_warning">{t}Разрешен один отзыв на товар, предыдущий отзыв будет заменен{/t}</div>{/if}
                
                <div class="comment_box_2">
                    <input type="text" name="user_name" value="{$comment.user_name}" class="comment_name">
                    <input type="submit" value="{t}Оставить отзыв{/t}" class="comment_submit">
                </div>

                {if !$is_auth}
                    <div class="formLine captcha">
                        <div class="fieldName">{$comment->__captcha->getTypeObject()->getFieldTitle()}</div>
                        {$comment->getPropertyView('captcha')}
                    </div>
                {/if}
            </form>
        {/if}
    </div>
    {if $paginator->total_pages > $paginator->page}
        <a data-pagination-options='{ "appendElement":".commentList" }' data-href="{$router->getUrl('comments-block-comments', ['_block_id' => $_block_id, 'cp' => $paginator->page+1, 'aid' => $aid])}" class="button white oneMore ajaxPaginator">{t}еще комментарии...{/t}</a>
    {/if}
</div>

<script type="text/javascript">
    $(function() {
        $('.commentBlock').comments({
            rate:'.rating',
            stars: '.starsBlock i',
            rateDescr: '.rating .desc'
        });
    });
</script>