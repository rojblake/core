// Copyright Zikula Foundation 2010 - license GNU/LGPLv3 (or at your option, any later version).
Zikula.define("AdminPanel");Zikula.AdminPanel.labels={clickToEdit:Zikula.__("Right-click down arrows to edit tab name"),edit:Zikula.__("Edit category"),remove:Zikula.__("Delete category"),makeDefault:Zikula.__("Make default category"),saving:Zikula.__("Saving")};Zikula.AdminPanel.setupNotices=function(){var b={headerSelector:"strong",headerClassName:"z-systemnoticeheader z-panel-indicator",effectDuration:0.5},a=$$("#z-developernotices ul:first");if($("z-securityanalyzer")){b.active=[0]}Zikula.AdminPanel.noticesPanels=new Zikula.UI.Panels("admin-systemnotices",b);if(a[0]){a[0].removeClassName("z-hide")}};Zikula.AdminPanel.Tab=Class.create({initialize:function(b,a){if(a){this.tab=this.createTab(a)}else{this.tab=$(b)}this.id=/admintab_(\d+)/.exec(this.tab.identify())[1];this.attachActionsMenu();this.attachModulesMenu();this.attachEditor();Droppables.add(this.tab.down("a"),{accept:"draggable",hoverclass:"ajaxhover",onDrop:function(c,d){Zikula.AdminPanel.Module.getModule(c).move(d)}});this.tab.store("tab",this)},createTab:function(c){var b=new Element("a",{href:c.url}).update(c.name),d=new Element("span",{"class":"z-admindrop"}).update("&nbsp;"),a=new Element("li",{id:"admintab_"+c.id,"class":"admintab",style:"z-index: 0"}).insert(b).insert(d);$("addcat").insert({before:a});return a},attachActionsMenu:function(){var a=this.tab.down("span"),c=new Zikula.UI.ContextMenu(a,{animation:false}),b=this;c.addItem({label:Zikula.AdminPanel.labels.edit,callback:function(d){b.editor.enterEditMode()}});c.addItem({label:Zikula.AdminPanel.labels.remove,callback:function(d){b.deleteTab()}});c.addItem({label:Zikula.AdminPanel.labels.makeDefault,callback:function(d){b.setTabDefault()}});this.actionsMenu=c},attachModulesMenu:function(){var b=this.tab.down("span"),a=Zikula.AdminPanel.Tab.menusData[this.id]?Zikula.AdminPanel.Tab.menusData[this.id].items:[],c=new Zikula.UI.ContextMenu(b,{leftClick:true,animation:false});a.each(function(d){c.addItem({label:d.menutext,moduleId:d.id,callback:function(){window.location=d.menutexturl}})});this.modulesMenu=c},attachEditor:function(){var a=this.tab.down("a"),b=this;this.editor=new Ajax.InPlaceEditor(a,"ajax.php?module=Admin&type=ajax&func=editCategory",{clickToEditText:Zikula.AdminPanel.labels.clickToEdit,savingText:Zikula.AdminPanel.labels.saving,externalControl:"admintabs-none",externalControlOnly:true,rows:1,cols:a.innerHTML.length,submitOnBlur:true,okControl:false,cancelControl:false,ajaxOptions:Zikula.Ajax.Request.defaultOptions(),onFormCustomization:function(c,d){$(d).observe("keypress",function(e){if(e.keyCode===Event.KEY_RETURN){e.stop();e.element().blur()}})},onEnterEditMode:function(c,d){b.originalText=a.innerHTML},callback:function(c,d){return{name:d,cid:b.id}},onComplete:function(e,c){e=Zikula.Ajax.Response.extend(e);if(!e.isSuccess()){a.update(b.originalText);Zikula.showajaxerror(e.getMessage());return}var d=e.getData();a.update(d.response)}})},deleteTab:function(){var a={cid:this.id};new Zikula.Ajax.Request("ajax.php?module=Admin&type=ajax&func=deleteCategory",{parameters:a,onComplete:this.deleteTabResponse.bind(this)})},deleteTabResponse:function(a){if(!a.isSuccess()){Zikula.showajaxerror(a.getMessage());return}this.tab.remove();Zikula.AdminPanel.Tab.removeTab(this.id)},setTabDefault:function(){var a={cid:this.id};new Zikula.Ajax.Request("ajax.php?module=Admin&type=ajax&func=defaultCategory",{parameters:a,onComplete:this.setTabDefaultResponse.bind(this)})},setTabDefaultResponse:function(a){if(!a.isSuccess()){Zikula.showajaxerror(a.getMessage())}}});Object.extend(Zikula.AdminPanel.Tab,{tabs:{},menusData:{},getTab:function(a){a=$(a);var b;if(a.nodeName==="LI"&&a.hasClassName("admintab")){b=a}else{b=a.up("li.admintab")}return b?$(b).retrieve("tab"):null},removeTab:function(a){delete this.tabs[a];this.setupSortable()},init:function(){this.menusData=$("admintabs-menuoptions").getValue().unescapeHTML().evalJSON();this.setupSortable();var a=function(b){b.preventDefault();b.element().stopObserving("click",a)};Draggables.addObserver({onStart:function(c,b,d){if(b.element.down("a")!=undefined){b.element.down("a").stopObserving("click",a);setTimeout(function(){b.element.down("a").observe("click",a)},200)}}});$("admintabs").select("li.admintab").each(function(c){var b=new Zikula.AdminPanel.Tab(c);this.tabs[b.id]=b}.bind(this));this.setupForm()},setupSortable:function(){Sortable.destroy("admintabs");Sortable.create("admintabs",{tag:"li",constraint:"horizontal",onUpdate:function(b){var a=Sortable.serialize("admintabs");new Zikula.Ajax.Request("ajax.php?module=Admin&type=ajax&func=sortCategories",{parameters:a,onComplete:function(c){if(!c.isSuccess()){Zikula.showajaxerror(c.getMessage())}}})},only:["admintab","active"]})},setupForm:function(){this.addTabLink=$("addcatlink");this.addTabForm=$("ajaxNewCatHidden").removeClassName("z-hide").hide();this.addTabLink.observe("click",this.addTabShowForm.bindAsEventListener(this));this.addTabForm.down("form").observe("submit",this.addTabSave.bindAsEventListener(this));this.addTabForm.down("a.cancel").observe("click",this.addTabHideForm.bindAsEventListener(this));this.addTabForm.down("a.save").observe("click",this.addTabSave.bindAsEventListener(this));return this},addTabShowForm:function(a){if(a){a.stop()}this.addTabLink.hide();this.addTabForm.show();return this},addTabHideForm:function(a){if(a){a.stop()}this.addTabLink.show();this.addTabForm.hide().down("form").reset();return this},addTabSave:function(c){c.stop();var a=this.addTabForm.down("[name=name]").getValue();if(a===""){Zikula.showajaxerror(Zikula.__("You must enter a name for the new category"));return this}this.addTabHideForm();var b={name:a};new Zikula.Ajax.Request("ajax.php?module=Admin&type=ajax&func=addCategory",{parameters:b,onComplete:this.addTabResponse.bind(this)});return this},addTabResponse:function(a){if(!a.isSuccess()){Zikula.showajaxerror(a.getMessage());this.addTabHideForm();return this}var b=a.getData(),c=new Zikula.AdminPanel.Tab(null,b);this.tabs[c.id]=c;Zikula.AdminPanel.Tab.setupSortable();return this}});Zikula.AdminPanel.Module=Class.create({initialize:function(a){this.module=$(a);this.id=(/module_(\d+)/).exec($(this.module).identify())[1];this.attachMenu();this.module.store("module",this)},attachMenu:function(){var a=this.module.down("input.modlinks").getValue().unescapeHTML().evalJSON()||[],b;if(a.size()>0){b=new Zikula.UI.ContextMenu(this.module.down(".module-context"),{leftClick:true,animation:false});a.each(function(c){b.addItem({label:c.text,callback:function(){window.location=c.url}})});this.menu=b}},move:function(b){var a={modid:this.id,cat:Zikula.AdminPanel.Tab.getTab(b).id};new Zikula.Ajax.Request("ajax.php?module=Admin&type=ajax&func=changeModuleCategory",{parameters:a,onComplete:this.moveResponse.bind(this)})},moveResponse:function(b){if(!b.isSuccess()){Zikula.showajaxerror(b.getMessage());return}var c=b.getData();if(c.parentCategory==c.oldCategory){return this}Zikula.AdminPanel.Tab.tabs[c.parentCategory].modulesMenu.addItem({label:c.name,moduleId:c.id,callback:function(){window.location=c.url}});var a=Zikula.AdminPanel.Tab.tabs[c.oldCategory].modulesMenu.items;a.each(function(e,d){if(e.moduleId==c.id){a.splice(d,1)}});this.module.remove();Zikula.AdminPanel.Module.removeModule(this.id)}});Object.extend(Zikula.AdminPanel.Module,{modules:{},getModule:function(a){return $(a).retrieve("module")},removeModule:function(a){delete this.modules[a];this.setupSortable()},init:function(){this.setupSortable();$$(".z-adminiconcontainer").each(function(b){var a=new Zikula.AdminPanel.Module(b);this.modules[a.id]=a}.bind(this))},setupSortable:function(){if($$(".z-adminiconcontainer").size()===0){return}Sortable.destroy("modules");Sortable.create("modules",{tag:"div",constraint:"",only:["z-adminiconcontainer"],handle:"z-dragicon",onUpdate:function(b){var a=Sortable.serialize("modules");new Zikula.Ajax.Request("ajax.php?module=Admin&type=ajax&func=sortModules",{parameters:a,onComplete:function(c){if(!c.isSuccess()){Zikula.showajaxerror(c.getMessage())}}})}})}});Zikula.AdminPanel.init=function(){Zikula.AdminPanel.setupNotices();Zikula.AdminPanel.Tab.init();Zikula.AdminPanel.Module.init()};document.observe("dom:loaded",Zikula.AdminPanel.init);