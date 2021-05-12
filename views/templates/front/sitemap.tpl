{if count($tree) > 0}<ul>{/if}

{foreach from=$tree item=parent}
    {if !strpos($parent->meta_title, '}')} {* Do not include pages with smarty in the meta title since it won't be resolved *}
        <li>
            <a href="{$parent->url}" class="textlink-nostyle">{$parent->meta_title}</a>
            {if count($parent->children) > 0}
                {include file=$templatePath tree=$parent->children templatePath=$templatePath}
            {/if}
        </li>
    {/if}
{/foreach}

{if count($tree) > 0}</ul>{/if}