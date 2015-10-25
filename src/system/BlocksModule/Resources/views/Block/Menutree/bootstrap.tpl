<nav class="navbar navbar-default" role="navigation">
    <div class="container">
        <div class="menutree collapse navbar-collapse" id="menutree-navbar-collapse-{$blockinfo.bid}">
            {menutree data=$menutree_content id='menu'|cat:$blockinfo.bid class='nav navbar-nav' ext=true bootstrap=true extopt='first,last,single,dropdown,childless,dropdown-menu'}
            {if $menutree_editlinks}
            <ul class="nav navbar-nav navbar-right">
                <li><a class="fa fa-plus" href="{route name='zikulablocksmodule_admin_modify' bid=$blockinfo.bid addurl=1}#menutree_tabs" title="{gt text='Add the current URL as new link in this block'}"></a></li>
                <li><a class="fa fa-pencil" href="{route name='zikulablocksmodule_admin_modify' bid=$blockinfo.bid fromblock=1}" title="{gt text='Edit this block'}"></a></li>
            </ul>
            {/if}
        </div>
    </div>
</nav>