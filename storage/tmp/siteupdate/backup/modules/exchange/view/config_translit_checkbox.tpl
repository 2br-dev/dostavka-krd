{include file=$field->getOriginalTemplate()}
<script>
$(function() {
    $('input[name="catalog_translit_on_update"]').change(function() {
        if (this.checked) $('input[name="catalog_translit_on_add"]').prop('checked', true);
    });
    $('input[name="catalog_translit_on_add"]').change(function() {
        if (!this.checked) $('input[name="catalog_translit_on_update"]').prop('checked', false);
    });    
});
</script>