{if isset($categoryspecial_name) && isset($categoryspecial_is_special)}
<div class="categoryspecial--container">
    <strong>{$categoryspecial_name}</strong>
    {if $categoryspecial_is_special}
        {l s='is special category' mod='categoryspecial'}.
    {else}
        {l s='is not a special category' mod='categoryspecial'}.
    {/if}
</div>
{/if}