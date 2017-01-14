{**
* Tabular products filter: module for PrestaShop 1.4
*
* @author     zapalm <zapalm@ya.ru>
* @copyright (c) 2010, zapalm
* @link      http://prestashop.modulez.ru/en/frontend-features/12-tabular-products-filter-for-prestashop.html The module's homepage
* @license   http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
*}

<!-- Tabular products filter -->
<div class="block products_block ztfilter-content">
    <form action="{$handler}" method="post" name="ztable" class="ztfilter-fm">
    <div class="ztfilter-header">{l s='Products filter. Choose a category' mod='ztfilter'}:{$cat_tree}</div>
    <table class="std ztfilter-table">
        <thead>
            <tr>
                <th class="first_item ztfilter-table">
                    <select class="ztfilter-sel-column" onchange="document.forms.ztable.submit()" name="flt_product" id="flt_product">
                        <option value="0" class="ztfilter-opt-color">{l s='Product' mod='ztfilter'}</option>
                        {foreach from=$products key='id' item='p'}
                            <option value="{$id}" {if isset($smarty.post.flt_product) && $smarty.post.flt_product == $id}selected="selected"{/if}>{$p}</option>
                        {/foreach}
                    </select>
                </th>

                {foreach from=$attr_groups key='group' item='attrs'}
                    <th class="ztfilter-table item">
                        {assign var="sel_name" value="flt_`$attr_group_ids.$group`"}
                        <select class="ztfilter-sel-column" name="{$sel_name}" onchange="document.forms.ztable.submit()">
                            <option value="0" class="ztfilter-opt-color">{$group}</option>
                            {foreach from=$attrs key='a' item='id'}
                                {if $a!='is_color_group'}
                                    <option value="{$id}" {if isset($smarty.post.$sel_name) && $smarty.post.$sel_name == $id}selected="selected"{/if}>{$a}</option>
                                {/if}
                            {/foreach}
                        </select>
                    </th>
                {/foreach}

                {if $conf.ZTFILTER_COL_PRICE}
                    <th class="item ztfilter-table">{l s='Price' mod='ztfilter'}</th>
                {/if}

                {if $conf.ZTFILTER_COL_REF}
                    <th class="item ztfilter-table">{l s='Ref.' mod='ztfilter'}</th>
                {/if}

                {if $conf.ZTFILTER_COL_MAN}
                    <th class="item ztfilter-table">{l s='Manufacturer' mod='ztfilter'}</th>
                {/if}

                <th class="last_item"><img title="{l s='Detailed' mod='ztfilter'}" alt="{l s='Detailed' mod='ztfilter'}" src="{$icon_dir}magnify.gif"></th>
                <input name="groups_keys" type="hidden" value="{$groups_keys}">
            </tr>
        </thead>
        <tbody>
            {foreach from=$attr_vals item='attr_val' key='attr_key' name='combs'}
                <tr>
                    {if $smarty.foreach.combs.iteration <= $smarty.post.flt_limit OR $smarty.post.flt_limit == 0}
                        <td>{$attr_val.name}</td>
                        {foreach from=$attr_groups key='group' item='attr_group'}
                            {if $attr_group.is_color_group && isset($attr_val.color_val) && $attr_val.color_val}
                                <td><div class="ztfilter-color-pick" style="background: {$attr_val.color_val};" title="{if isset($attr_val.$group) && $attr_val.$group}{$attr_val.$group}{/if}">{if file_exists($col_img_dir|cat:$attr_val.color_attribute_id|cat:'.jpg')}<img src="{$img_col_dir}{$attr_val.color_attribute_id}.jpg" alt="{if isset($attr_val.$group) && $attr_val.$group}{$attr_val.$group}{/if}" width="20" height="20">{/if}</div></td>
                            {else}
                                <td>{if isset($attr_val.$group) && $attr_val.$group}{$attr_val.$group}{/if}</td>
                            {/if}
                        {/foreach}
                        {if $conf.ZTFILTER_COL_PRICE}
                            <td>{$attr_val.price}</td>
                        {/if}

                        {if $conf.ZTFILTER_COL_REF}
                            <td>{$attr_val.reference}</td>
                        {/if}

                        {if $conf.ZTFILTER_COL_MAN}
                            <td>{$attr_val.manufacturer}</td>
                        {/if}
                        <td><a href="{$base_dir}product.php?id_product={$attr_val.id_product}&id_product_attribute={$attr_key}"><img src="{$icon_dir}next.gif" /></a></td>
                    {/if}
                </tr>
            {/foreach}
        </tbody>
    </table>
    <div class="ztfilter-footer">{l s='Product show limit' mod='ztfilter'}:
        <select class="ztfilter-sel-column" name="flt_limit" onchange="document.forms.ztable.submit()">
            {foreach from=$product_limit key='id' item='l'}
                <option value="{$l}" {if $smarty.post.flt_limit == $l}selected="selected"{/if}>{$l}</option>
            {/foreach}
            <option value="0" {if $smarty.post.flt_limit == 0}selected="selected"{/if}>{l s='Show all' mod='ztfilter'}</option>
        </select>
    </div>
    </form>
</div>
<!-- /Tabular products filter -->
