{if !empty($recommended)}
    <p class="prod_sample_main_title">Похожие товары</p>
    <div class="slid">
        <div class="slid__body" id="slid1">
            {foreach $recommended as $product}
            <div class="slid__item">
                <div class="slid__item_body">
                    <a href="{$product->getUrl()}" class="prod_sample_img_box">
                        {$main_image=$product->getMainImage()}
                        <img src="{$main_image->getUrl(160,160, axy)}" alt="{$main_image.title|default:"{$product.title}"}" class="prod_sample_img">
                    </a>
                    <a href="{$product->getUrl()}" class="slid__item_title">{$product.title}</a>
                    <div class="prod_sample_rating">
                        {$rating = $product->getRatingBall()}
                        {for $star=1 to 5}
                        {if $star <= $rating}
                        <button class="prod_sample_rating_star prod_sample_rating_star_full"></button>
                        {else}
                        <button class="prod_sample_rating_star"></button>
                        {/if}
                        {/for}
                    </div>
                    <div class="prod_sample_bottom">
                        <p class="prod_sample_price">{$product->getCost()} р.</p>
                        <a class="prod_sample_basket addToCart noShowCart" data-href="{$router->getUrl('shop-front-cartpage', ["add" => $product.id])}" data-add-text=""></a>
                    </div>
                </div>
            </div>
            {/foreach}
        </div>
    </div>
{/if}

<script>
    $(document).ready(() => {
        $('#slid1').slick({
            infinite: true,
            speed: 300,
            slidesToShow: 3,
            slidesToScroll: 1,
            arrows: false,
            autoplay: true,
            autoplaySpeed: 3000,
            responsive: [
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1,
                    }
                },
            ]
        })
    }) 
</script>