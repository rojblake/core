{*  $Id$  *}
{include file='theme_admin_menu.tpl'}
<div class="z-admincontainer">
    {include file="theme_admin_modifymenu.tpl"}
    <h2>{gt text="Colour palettes"}</h2>
    {pageaddvar name="javascript" value="system/Theme/includes/picker.js"}
    <form class="z-form" id="theme_modify_palette" action="{modurl modname="Theme" type="admin" func="updatepalettes"}" method="post" enctype="application/x-www-form-urlencoded">
        <div>
            <input type="hidden" name="authid" value="{insert name="generateauthkey" module=Theme}" />
            <input type="hidden" name="themename" value="{$themename|safetext}" />

            {if $palettes}
                {foreach from=$palettes item=palette key=palettename}
                <fieldset>
                    <legend>{$palettename|safetext}</legend>
                    <div class="z-formrow">
                        <label><strong>{gt text="Name"}</strong></label>
                        <span><strong>{gt text="Value"}</strong></span>
                    </div>
                    {foreach from=$palette item=color key=name}
                    <div class="z-formrow">
                        <label>{$name|safetext}</label>
                        <div>
                            <input size="6" maxlength="7" type="text" name="palettes[{$palettename}][{$name}]" value="{$color|safetext}" />
                            <a href="javascript:TCP.popup(document.forms['theme_modify_palette'].elements['{$palettename}[{$name}]'])">{img class=theme_colorpicker_image modname=core set=icons/extrasmall src=colors.gif}</a>
                        </div>
                        <div class="z-formnote" style="background-color:{$color};">&nbsp;</div>
                    </div>
                    {/foreach}
                </fieldset>
                {/foreach}
            {/if}

            <fieldset>
                <legend>{gt text="Create new palette"}</legend>
                <div class="z-formrow">
                    <label for="theme_palettename">{gt text="Name"}</label>
                    <input id="theme_palettename" name="palettename" size="30" />
                </div>
                <div class="z-formrow">
                    <label>bgcolor</label>
                    <span>
                        <input name="bgcolor" size="7" /><a href="javascript:TCP.popup(document.forms['theme_modify_palette'].elements['bgcolor'])">{img class=theme_colorpicker_image modname=core set=icons/extrasmall src=colors.gif}</a>
                    </span>
                </div>
                <div class="z-formrow">
                    <label>color1</label>
                    <span>
                        <input name="color1" size="7" /><a href="javascript:TCP.popup(document.forms['theme_modify_palette'].elements['color1'])">{img class=theme_colorpicker_image modname=core set=icons/extrasmall src=colors.gif}</a>
                    </span>
                </div>
                <div class="z-formrow">
                    <label>color2</label>
                    <span>
                        <input name="color2" size="7" /><a href="javascript:TCP.popup(document.forms['theme_modify_palette'].elements['color2'])">{img class=theme_colorpicker_image modname=core set=icons/extrasmall src=colors.gif}</a>
                    </span>
                </div>
                <div class="z-formrow">
                    <label>color3</label>
                    <span>
                        <input name="color3" size="7" /><a href="javascript:TCP.popup(document.forms['theme_modify_palette'].elements['color3'])">{img class=theme_colorpicker_image modname=core set=icons/extrasmall src=colors.gif}</a>
                    </span>
                </div>
                <div class="z-formrow">
                    <label>color4</label>
                    <span>
                        <input name="color4" size="7" /><a href="javascript:TCP.popup(document.forms['theme_modify_palette'].elements['color4'])">{img class=theme_colorpicker_image modname=core set=icons/extrasmall src=colors.gif}</a>
                    </span>
                </div>
                <div class="z-formrow">
                    <label>color5</label>
                    <span>
                        <input name="color5" size="7" /><a href="javascript:TCP.popup(document.forms['theme_modify_palette'].elements['color5'])">{img class=theme_colorpicker_image modname=core set=icons/extrasmall src=colors.gif}</a>
                    </span>
                </div>
                <div class="z-formrow">
                    <label>color6</label>
                    <span>
                        <input name="color6" size="7" /><a href="javascript:TCP.popup(document.forms['theme_modify_palette'].elements['color6'])">{img class=theme_colorpicker_image modname=core set=icons/extrasmall src=colors.gif}</a>
                    </span>
                </div>
                <div class="z-formrow">
                    <label>color7</label>
                    <span>
                        <input name="color7" size="7" /><a href="javascript:TCP.popup(document.forms['theme_modify_palette'].elements['color7'])">{img class=theme_colorpicker_image modname=core set=icons/extrasmall src=colors.gif}</a>
                    </span>
                </div>
                <div class="z-formrow">
                    <label>color8</label>
                    <span>
                        <input name="color8" size="7" /><a href="javascript:TCP.popup(document.forms['theme_modify_palette'].elements['color8'])">{img class=theme_colorpicker_image modname=core set=icons/extrasmall src=colors.gif}</a>
                    </span>
                </div>
                <div class="z-formrow">
                    <label>sepcolor</label>
                    <span>
                        <input name="sepcolor" size="7" /><a href="javascript:TCP.popup(document.forms['theme_modify_palette'].elements['sepcolor'])">{img class=theme_colorpicker_image modname=core set=icons/extrasmall src=colors.gif}</a>
                    </span>
                </div>
                <div class="z-formrow">
                    <label>text1</label>
                    <span>
                        <input name="text1" size="7" /><a href="javascript:TCP.popup(document.forms['theme_modify_palette'].elements['text1'])">{img class=theme_colorpicker_image modname=core set=icons/extrasmall src=colors.gif}</a>
                    </span>
                </div>
                <div class="z-formrow">
                    <label>text2</label>
                    <span>
                        <input name="text2" size="7" /><a href="javascript:TCP.popup(document.forms['theme_modify_palette'].elements['text2'])">{img class=theme_colorpicker_image modname=core set=icons/extrasmall src=colors.gif}</a>
                    </span>
                </div>
                <div class="z-formrow">
                    <label>link</label>
                    <span>
                        <input name="link" size="7" /><a href="javascript:TCP.popup(document.forms['theme_modify_palette'].elements['link'])">{img class=theme_colorpicker_image modname=core set=icons/extrasmall src=colors.gif}</a>
                    </span>
                </div>
                <div class="z-formrow">
                    <label>vlink</label>
                    <span>
                        <input name="vlink" size="7" /><a href="javascript:TCP.popup(document.forms['theme_modify_palette'].elements['vlink'])">{img class=theme_colorpicker_image modname=core set=icons/extrasmall src=colors.gif}</a>
                    </span>
                </div>
                <div class="z-formrow">
                    <label>hover</label>
                    <span>
                        <input name="hover" size="7" /><a href="javascript:TCP.popup(document.forms['theme_modify_palette'].elements['hover'])">{img class=theme_colorpicker_image modname=core set=icons/extrasmall src=colors.gif}</a>
                    </span>
                </div>
            </fieldset>
            <div class="z-buttons z-formbuttons">
                {button src=button_ok.gif set=icons/extrasmall __alt="Save" __title="Save" __text="Save"}
                <a href="{modurl modname=Theme type=admin func=pageconfigurations themename=$themename}" title="{gt text="Cancel"}">{img class=theme_colorpicker_image modname=core src=button_cancel.gif set=icons/extrasmall __alt="Cancel" __title="Cancel"} {gt text="Cancel"}</a>
            </div>
        </div>
    </form>
</div>
