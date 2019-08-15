
<!--  -->
{assign var=catalog_config value=$this_controller->getModuleConfig()} 
<div class="oneClickWrapper">
    {if $success}
        <div class="authorization reserveForm">
            <div class="f__head">
                <p class="f__title">Заказ принят</p>
            </div>
            <!--  -->
            <div class="f__body">
                <p class="f__text f__text-bold">Артикул: {$product.barcode}</p>
                <p class="f__text">{$product.title}</p>
                <p class="f__text-2">В ближайшее время с Вами свяжется наш менеджер</p>
            </div>
        </div>    
    {else}
        <form enctype="multipart/form-data" method="POST" action="{$router->getUrl('catalog-front-oneclick',["product_id"=>$product.id])}" class="authorization formStyle reserveForm">
            {$this_controller->myBlockIdInput()}
           <input type="hidden" name="product_name" value="{$product.title}"/>
           <input type="hidden" name="offer_id" value="{$offer_fields.offer_id}">
           {hook name="catalog-oneclick:form" title="{t}Купить в один клик:форма{/t}"} 
           <!--  -->
           <!--  -->
           <!--  -->
           <div class="oc__wrap">
               <p class="oc__title">Купить в один клик</p>
           </div>
           <div class="oc_body">
               <p class="oc__text">Оставьте Ваши данные и наш консультант с вами свяжется.</p>
               <!--  -->
               {if $error_fields}
                   <div class="oc__errors">
                   {foreach $error_fields as $error_field}
                       {foreach $error_field as $error}
                            <p class="oc__error">{$error}</p>
                       {/foreach}
                   {/foreach}
                   </div>
               {/if}
               <!--  -->
               <div class="formLine oc__row">
                    <input type="text" class="inp {if $error_fields}has-error{/if}" value="{if $request->request('name','string')}{$request->request('name','string')}{else}{$click.user_fio}{/if}" maxlength="100" name="name" placeholder="Ваше имя">
                </div>
                <div class="formLine oc__row">
                    <input type="text" class="inp {if $error_fields}has-error{/if}" value="{if $request->request('phone','string')}{$request->request('phone','string')}{else}{$click.user_phone}{/if}" maxlength="20" name="phone" placeholder="Ваш телефон">
                </div>
                <!--  -->
                {if !$is_auth}
                    <div class="formLine captcha">
                        <label class="fieldName">{$click->__kaptcha->getTypeObject()->getFieldTitle()}</label>
                        {$click->getPropertyView('kaptcha')}
                    </div>
                {/if}
                <!--  -->
                {if $CONFIG.enable_agreement_personal_data}
                    <div class="oc__row">
                        {include file="%site%/policy/agreement_phrase.tpl" button_title="{t}Купить{/t}"}
                    </div>
                {/if}
                <!--  -->
                <div class="oc__row oc__row-submit">
                    <button class="oc__submit" type="submit">Отправить</button>
                </div>
           </div>
           {/hook}
        </form>
    {/if}
</div>



