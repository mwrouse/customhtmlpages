{if isset($page)}
    {if isset($page->content)}
        {assign var=content value=$page->content}
        {include file="eval:($content)"}
    {/if}
{/if}