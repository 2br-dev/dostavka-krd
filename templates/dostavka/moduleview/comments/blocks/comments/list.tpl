{if !empty($commentlist)}
    <p class="clist_main_title">Отзывы о товаре</p>
    {foreach $commentlist as $comment}
        {* <li {$comment->getDebugAttributes()}>
            <div class="info">
                <span class="date">{$comment.dateof|dateformat:"@date @time"}</span>
                <span class="name">{$comment.user_name}</span>
                <p class="starsSection" title="{$comment->getRateText()}"><span class="stars"><i class="mark{$comment.rate}"></i></span></p>
            </div>
            <div class="comment">
                <i class="corner"></i>
                <p>{$comment.message|nl2br}</p>
            </div>
        </li> *}
        <!--  -->
        <div class="clist">
            <div class="clist_top">
                <p class="clist_name">{$comment.user_name}</p>
                <div class="clist_rating">
                    {$rating = $comment.rate}
                    {for $star=1 to 5}
                    {if $star <= $rating}
                    <button class="clist_star clist_star_full"></button>
                    {else}
                    <button class="clist_star"></button>
                    {/if}
                    {/for}
                </div>
            </div>
            <P class="clist_text">{$comment.message|nl2br}</P>
        </div>
        <!--  -->
    {/foreach}
{/if}