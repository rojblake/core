{include file='settings_admin_menu.tpl'}
<div class="z-admincontainer">
    <div class="z-adminpageicon">{img modname="core" src="error.gif" set="icons/large" __alt="Modify Config"}</div>
    <h2>{gt text="Error settings"}</h2>
    <form class="z-form" action="{modurl modname=Settings type=admin func=updateerrorhandling}" method="post">
        <div>
            <input type="hidden" name="authid" value="{insert name="generateauthkey" module=Settings}" />
            <fieldset>
                <legend>{gt text="Error messages"}</legend>
                <div class="z-formrow">
                    <label>{gt text="Reporting level"}</label>
                    <div class="z-formlist">
                        <input id="errordisplay2" type="radio" name="errorsettings_errordisplay" value="2" {if $development neq 1}disabled="disabled"{/if} {if $errordisplay eq 2}checked="checked"{/if} />
                        <label for="errordisplay2">{gt text="Display messages for all notices, warnings and errors (system must be in development mode)"}</label>
                    </div>
                    <div class="z-formlist">
                        <input id="errordisplay1" type="radio" name="errorsettings_errordisplay" value="1" {if $errordisplay eq 1}checked="checked"{/if} />
                        <label for="errordisplay1">{gt text="Display messages for errors only"}</label>
                    </div>
                    <div class="z-formlist">
                        <input id="errordisplay0" type="radio" name="errorsettings_errordisplay" value="0" {if $errordisplay eq 0}checked="checked"{/if} />
                        <label for="errordisplay0">{gt text="Suppress all error messages"}</label>
                    </div>
                </div>
            </fieldset>
            <fieldset>
                <legend>{gt text="Error logging"}</legend>
                <div class="z-formrow">
                    <label>{gt text="Errors to log"}</label>
                    <div class="z-formlist">
                        <input id="errorlog2" type="radio" name="errorsettings_errorlog" value="2" {if $errorlog eq 2}checked="checked"{/if} />
                        <label for="errorlog2">{gt text="Log all notices, warnings and errors"}</label>
                    </div>
                    <div class="z-formlist">
                        <input id="errorlog1" type="radio" name="errorsettings_errorlog" value="1" {if $errorlog eq 1}checked="checked"{/if} />
                        <label for="errorlog1">{gt text="Log errors only"}</label>
                    </div>
                    <div class="z-formlist">
                        <input id="errorlog0" type="radio" name="errorsettings_errorlog" value="0" {if $errorlog eq 0}checked="checked"{/if} />
                        <label for="errorlog0">{gt text="No error logging"}</label>
                    </div>
                </div>
                <div class="z-formrow">
                    <label>{gt text="Error log type"}</label>
                    <div class="z-formlist">
                        <input id="errorlogtype0" type="radio" name="errorsettings_errorlogtype" value="0" {if $errorlogtype eq 0}checked="checked"{/if} />
                        <label for="errorlogtype0">{gt text="Use the PHP system log configured within 'php.ini'"}</label>
                    </div>
                    <div class="z-formlist">
                        <input id="errorlogtype1" type="radio" name="errorsettings_errorlogtype" value="1" {if $errorlogtype eq 1}checked="checked"{/if} />
                        <label for="errorlogtype1">{gt text="Send error e-mail messages to"}</label>
                        <input type="text" id="errorsettings_errormailto" name="errorsettings_errormailto" size="15" maxlength="50" value="{$errormailto}" />
                    </div>
                    <div class="z-formlist">
                        <input id="errorlogtype2" type="radio" name="errorsettings_errorlogtype" value="2" {if $errorlogtype eq 2}checked="checked"{/if} />
                        <label for="errorlogtype2">{gt text="Write to module-specific log file"}</label>
                    </div>
                    <div class="z-formlist">
                        <input id="errorlogtype3" type="radio" name="errorsettings_errorlogtype" value="3" {if $errorlogtype eq 3}checked="checked"{/if} />
                        <label for="errorlogtype3">{gt text="Write to global log file"}</label>
                    </div>
                </div>
            </fieldset>
            <div class="z-buttons z-formbuttons">
                {button src="button_ok.gif" set="icons/extrasmall" __alt="Save" __title="Save" __text="Save"}
                <a href="{modurl modname=Settings type=admin}" title="{gt text="Cancel"}">{img modname="core" src="button_cancel.gif" set="icons/extrasmall" __alt="Cancel" __title="Cancel"} {gt text="Cancel"}</a>
            </div>
        </div>
    </form>
</div>
