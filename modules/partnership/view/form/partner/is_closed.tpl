{include file=$elem.__is_closed->getOriginalTemplate() field=$elem.__is_closed}
<span class="m-l-5 close-partner-message" {if !$elem.is_closed}style="display:none"{/if}>
    {include file=$elem.__close_message->getOriginalTemplate() field=$elem.__close_message}
</span>

<script lang="JavaScript">
    $(function() {
        $('input[name="is_closed"]').change(function() {
            $('.close-partner-message').toggle( $(this).is(':checked') );
        });
    });
</script>