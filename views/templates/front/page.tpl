{if isset($page)}
    {if isset($page.content)}
        {eval var=$page.content}
    {/if}
{/if}