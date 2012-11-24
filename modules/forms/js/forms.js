var XTRforms = Class.create();
//var XTR_forms;


XTRforms.prototype = Object.extend(new _modulePrototype(), 
{
    initialize: function()
    {
        this.destructable = true;
        this.module_name = 'forms';
        this.current_node = null;
        this.tree = null;
        this.connector = null;
        this._extra = {show_forms:'formsId'};    
        this.leaves_obj_type = new Array('_FORM');          
        this.init();
    },

    tree_object_clicked: function(itemid)
    {
        object_type = this.tree.getNodeParam(itemid, 'obj_type');

        switch (object_type)
        {
            case "_FORMS":
                this.edit_form(itemid);
                break;
            case "_FORMSGROUP":
                this.edit_category(itemid);
                break;            
            default: return false;
                break;
        }
    },


    group_dialog: function()
    {
        columns = $H({
            image: ' ',
            name: _lang_common['name']
        });

        _columnsHeadersWidth = new Array('20px', '150px');
        _columnsInterpretAs = $H({
            image: 'IMAGE'
        });

        _images = $H({
            group: 'xres/ximg/tree/folderClosed.gif',
            page:'xres/ximg/tree/page.gif'
        });
            
            if(arguments[0]){
                xlist_name = arguments[0];
            } else {            
                xlist_name = "xlist";
            }
            
        return xlist = new XTRxlist(xlist_name, this.connector,
        {
            resultSource: 'showGroup',
            resultIDSource: 'showGroupId',
            columnsHeaders: columns,
            tableId: 'dialogtable',
            startWithAncestor: 1,
            columnsHeadersWidth: _columnsHeadersWidth,
            columnsInterpretAs: _columnsInterpretAs,
            images: _images,
            className: 'dialog-table'
        });
    },
    
    on_tree_drag: function(idNode, idTo, sibling, idTreeFrom, idTreeTo)
    {
        this.connector.execute({changeAncestor:{id:idNode, ancestor:idTo, relative:sibling}});
            if (this.connector.result['dragOK']){
                return true;
            } else {
                return false;
            }
    },
    
    
    
    
    show_forms_extra:function(params)
    {
        this.connector.execute({get_obj_type:{formsId:params.formsId}});
            if(obj_type = this.connector.result.obj_type){
                switch (obj_type){
                    case '_FORMSGROUP':
                        this.edit_category(params.formsId);
                    break;
                    case '_FORMS':
                        this.edit_form(params.formsId);
                    break;
                }
            }    
    },
    
    
    add_category:function()
    {
        
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'add_category',true));
        this.validation = new Validation('add_category',{immediate: true});
    },
    
    save_category:function()
    {        
        if(!this.validation.validate()) return void(0);
            
        formdata = (xoad.html.exportForm('add_category'));        
        this.connector.execute({save_category:{data:formdata}});
            if(this.connector.result.is_saved){
                this.tree.refreshItem(1);
                XTR_main.set_result(_lang_common['group_success_saved']);
            }        
    },
    
    edit_category:function(itemId)
    {
        
        this.connector.execute({edit_category:{id:itemId}});
        this.current_node = itemId;
        this.tabs.createTabNode({id:'t_edit',name: _lang_common['editing'],temporal: true}, 'top', true);
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'edit_category',true));
        xoad.html.importForm('edit_category',this.connector.result.category_data);
        this.validation = new Validation('edit_category',{immediate: true});
    },
        
    save_edited_category:function()
    {
        if(!this.validation.validate()) return void(0);        
        formdata = (xoad.html.exportForm('edit_category'));        
        this.connector.execute({save_edited_category :{id:this.current_node,data:formdata}});
            if(this.connector.result.is_saved)
            {
                this.tree.refreshItem(1);
                XTR_main.set_result(_lang_common['group_success_saved']);
            }
    },     
    
    add_form: function()
    {
        this.current_node = this.tree.getSelectedItemId();
        params = true;
            if(this.current_node){
                if( this.tree.getNodeParam(this.current_node, 'obj_type') == '_FORMSGROUP')
                {
                    params = {group_id:this.current_node};
                }
            }
        this.connector.execute({add_form:params});            
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'add_form',true));                                    
        xoad.html.importForm('add_form', this.connector.result.categories);                          
        this.validation = new Validation('add_form',{immediate: true});
        $('use_captcha').observe('click', function(event){$('captcha_settings').toggle(); $('captcha_settings').previous(0).toggle();});
        $$('select.type-textfield').invoke('observe', 'change', this.additional_failure_message.bind(this));
    },    
    
    
    save_form:function()
    {
        if(!this.validation.validate()){return void(0);}
        var add_form = xoad.html.exportForm('add_form');
        var fields = this.get_form_fields();
            if(!fields){return void(0);} 
        this.connector.execute({save_form:{main:add_form, fields:fields}});
        
            if(this.connector.result.is_saved){
                if(this.current_node){this.tree.refreshItem(this.tree.getParentId(this.current_node));}
                else{this.tree.refreshItem(add_form.category);}
                XTR_main.set_result(_lang_forms['form_success_saved']);
            }
    },    
    
    
    block_options_hide: function(div_id)
    {
        if(div_id){        
            $$(div_id + ' div.additional_options, ' + div_id + ' div.compulsory_to_fill').each(function(element){element.hide();});
        } else {
            $$('div.additional_options').each(function(element){element.hide();});
            $$('div.compulsory_to_fill').each(function(element){if(!element.previous(0).down(1).checked){element.hide();}});
        }
    },
    
    block_options_toggle: function(div_id)
    {
        div_id = (div_id) ? div_id : '';
        $$(div_id + ' a.additional_options').invoke('observe', 'click', function(){this.next(0).toggle();});
        $$(div_id + ' input.compulsory_to_fill').invoke('observe', 'click', function(){this.ancestors()[1].next(0).toggle();});
    },
    
    additional_failure_message: function(evt, elem)
    {
        if(evt && !elem) elem = Event.element(evt);
        else if(!evt && elem) elem = elem;
        
        afmli = elem.next(1).down(4);
        options = $H({text:'hide',email:'show',url:'show',hidden:'hide',numerical:'show'});
            if(options.get(elem.getValue()) == 'show') afmli.show();
            else afmli.hide();
    },

                      
    edit_form: function(itemId)
    {
        this.tabs.createTabNode({id: 't_edit', name: _lang_common['editing'], temporal: true}, 'top', true);
        actionList = {};
        
            if (!XTR_main.tpl_exists(this.module_name, 'edit_form')){
                actionList.tpl_form_edit = true;          
            }
        this.connector.execute({edit_form:{form_id:itemId}});
        this.current_node = itemId;
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'edit_form',true));
        xoad.html.importForm('edit_form',this.connector.result.form_data);
        xoad.html.importForm('xls_upload',this.connector.result.form_data);
        $('fields').insert(this.connector.result.fields);
        this.validation = new Validation('edit_form', {immediate : true});
        Sortable.create('fields',{tag:'div',constraint:false,dropOnEmpty:true});
        self = this;
        $$('select.type-textfield').each(function(elem){self.additional_failure_message(null, elem);});       
        this.block_options_hide();
        this.block_options_toggle();
            if(!$('use_captcha').checked){$('captcha_settings').hide(); $('captcha_settings').previous(0).toggle();}
                $('use_captcha').observe('click', function(event){$('captcha_settings').toggle(); $('captcha_settings').previous(0).toggle();});
            $$('select.type-textfield').invoke('observe', 'change', this.additional_failure_message.bind(this));
    },
    
    
    save_edited_form:function()
    {
        if(!this.validation.validate()){return void(0);}                
        main = xoad.html.exportForm('edit_form');
        fields = this.get_form_fields();
            if(!fields) {return void(0);}
        this.connector.execute({save_edited_form:{main:main, id:this.current_node, fields:fields}});
        
            if(this.connector.result.is_saved){
                this.tree.refreshItem(this.tree.getParentId(this.current_node));
                XTR_main.set_result(_lang_forms['form_success_saved']);
            }
    },

    
    field_delete:function(elem)
    {
            
            flid = elem.next(3).getValue();
            if(flid){if(this.delete_obj(null,flid)){elem.ancestors()[1].remove();}}
            else {elem.ancestors()[1].remove();}
    },
    
    
    get_form_fields:function()
    {
        var fields = new Array();
        var orders = Sortable.sequence('fields','div');
            orders.each(function(val, k){fields[k] = xoad.html.exportForm('field_' + val);});

        return (fields) ? fields : false;
    },
    

    add_field:function()
    {
        var type = $('typef').value;
        var div_id;
        
            if (!XTR_main.tpl_exists(this.module_name,type)){
                this.connector.execute({tpl_fields_edit:{type:type}});
                var flds = this.connector.lct;
                XTR_main.cache_tpl('forms', flds[0]['field'], flds[0]['text']);
            };
            
        var num =  $$('div.field-form').length;
        var syntax = /(^|.|\r|\n)(\{%F:\s*(\w+)\s*%})/;
        var t = new Template(XTR_main.get_tpl(this.module_name, type), syntax);
        var a = {num: num};
        var parsed = t.evaluate(a);
        new Insertion.Top('fields', parsed);
        Sortable.create('fields',{tag:'div',constraint:false,dropOnEmpty:true});
        this.validation = new Validation('fields_form',{immediate: true});
        div_id = '#'+type+'_'+num;
        this.block_options_hide(div_id);
        this.block_options_toggle(div_id);
        $$('select.type-textfield').invoke('observe', 'change', this.additional_failure_message.bind(this));
    },
    
    
    
    in_archive:function(evt)
    {
            if (!confirm(_lang_forms['you_really_wish_to_put_in_archive_this_message'])){return false;}
        id = this.gridlist.getSelectedRowId(1);            
        this.connector.execute({in_archive_msg:{id:id}});
            if (this.connector.result.isArch)
            {
               this.gridlist.deleteSelectedRows();
               XTR_main.set_result(_lang_forms['messages_moved_to_archive']);
            }
    },
    
    
    delete_msg:function(evt)
    {
            if (!confirm(_lang_forms['you_really_wish_to_remove_this_message'])){return false;}
        id = this.gridlist.getSelectedRowId(1);            
        this.connector.execute({delete_msg:{id:id}});
            if (this.connector.result.isDel)
            {
               this.gridlist.deleteSelectedRows();
               XTR_main.set_result(_lang_forms['messages_deleted']);
            }
    },
    
    
    read_msg:function(id, state){                       
        this.connector.execute({read_msg:{id:id, state:state}});
            //if(this.connector.result.read){}
    },
    
    
    find_email_address:function()
    {
       var html_txt = $('formcontent').innerHTML;
       var email_addr;
            html_txt = html_txt.gsub(/([\w]+)@((?:[-a-z0-9]+\.)+[a-z]{2,})/, function(match){
                email_addr = match[0];
                return '<a href="javascript:void(0)" class="reply">' + email_addr + '</a>';
            });
       $('formcontent').update(html_txt);
       return email_addr;
    },
    
    
    reply_msg:function(reply_to)
    {
       module = this.module_name;
       reply_to = {'reply_to':reply_to};
       $$('a.reply').invoke('observe', 'click', function(){
            tpl = XTR_main.get_tpl(module, 'form_reply', true);
            $('forms_modal_container').update(tpl);
       });
    },
    
    
    show_selected_message:function(id)
    {
       this.connector.execute({open_selected_message:{id:id}});
       this._win = XTR_main.dhxWins.createWindow("prop_editor", 20, 10, 600, 600);
       this._win.centerOnScreen();
       this._win.setText(_lang_forms['incoming_message']);
       this._win.attachHTMLString(XTR_main.get_tpl(this.module_name, 'form_content',true));
            if(this.connector.result.message){
                $('formcontent').update(this.connector.result.message);
                var reply_to = this.find_email_address();
                this.reply_msg(reply_to);
                this.read_msg(id, true);
            }
    },
    
    
    first_start:function()
    {
       XTR_main.load_module_tpls(this.module_name, new Array('forms_first'));
       this.tabs.makeActiveById('t_new_forms');
    },
    
    
    show_archive:function()
    {
        this.show_messages(1);
    },
    
    
    show_messages:function(status)
    {
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'forms_first'),'b');
        $('pw-edit').insert('<div id="t-container"></div>');
                    
        menu = new dhtmlXMenuObject();
        menu.renderAsContextMenu();
        menu.addNewChild(menu.topId, 0, "inArchive",_lang_forms['in_archive'], false,'','',this.in_archive.bind(this));
        menu.addNewChild(menu.topId, 1, "delete",_lang_common['delete'], false,'','',this.delete_msg.bind(this));

        this.gridlist = new dhtmlXGridObject('t-container');
        this.gridlist.setImagePath("xres/ximg/grid/imgs/");
        this.gridlist.selMultiRows = true;  
        this.gridlist.setHeader('ID,'+_lang_common['date']+','+_lang_forms['form_name']+','+_lang_forms['read']);
        this.gridlist.setInitWidths("70,160,*,100"); 
        this.gridlist.setColAlign("center,left,left,center");
        this.gridlist.setColTypes("ed,ed,ed,ch");
        this.gridlist.setColSorting("int,str,str,str");
        this.gridlist.enableAutoWidth(true);
        this.gridlist.setMultiLine(true);
        //this.gridlist.enablePaging(true, 30, 8, "pagingArea", true, "recinfoArea");
        this.gridlist.attachEvent("onRowDblClicked", this.show_selected_message.bind(this));
        this.gridlist.attachEvent("onCheckbox",this.read_msg.bind(this));
        this.gridlist.enableContextMenu(menu);  
        this.gridlist.customGroupFormat = function(text, count){return text + ", (<b>" + count + "</b>)";};
        this.gridlist.init();
        this.gridlist.setSkin("modern");
        
            this.connector.execute({load_messages:{archive:status}});            
                if(this.connector.result.data_set){
                    this.gridlist.parse(this.connector.result.data_set,"xjson")
                    this.gridlist.groupBy(2);
                }
    },
        

    
    show_incoming_messages:function()
    {
        this.show_messages(0)               
    },         
    
    
    build_interface: function()
    {
        //parent tree
        toggle_main_menu(true);     
        
        if(!this.tree){        
           tree_node = new Element('div',{id:this.module_name+"_treebox", className:'treebox'});
            $('tp-tree-window').appendChild(tree_node);
            this.tree = new dhtmlXTreeObject(this.module_name + "_treebox", 'auto', 'auto', "0");
            this.tree.setImagePath("/xres/ximg/green/");
            this.tree.enableDragAndDrop(1);
            this.tree.setDataMode("json");
            this.tree.setDragBehavior("complex");      
            this.tree.enableMultiselection(1);   
            this.tree.setOnDblClickHandler(this.tree_object_clicked.bind(this));
            this.tree.setDragHandler(this.on_tree_drag.bind(this)); 
            
            this.tree.setXMLAutoLoading("tree_xml_server.php?tree=forms_container");
            this.tree.loadJSON("tree_xml_server.php?tree=forms_container&id=0");            
            
            this.tree.setDragHandler(this.on_tree_drag.bind(this));
            
            menu = new dhtmlXMenuObject();
            menu.renderAsContextMenu();            
            menu.addNewChild(menu.topId, 0, "refresh", _lang_common['refresh'], false,'','',function(id,zid){this.tree.refreshItem(zid);}.bind(this));
            menu.addNewChild(menu.topId, 0, "delete",_lang_common['delete'], false,'','',this.delete_obj.bind(this));                           
            menu.addNewChild(menu.topId, 0, "copyn",_lang_common['copy'], false,'','',this.copy_obj.bind(this));                           
            menu.addNewChild(menu.topId, 0, "pasten",_lang_common['paste'], false,'','',this.paste_obj.bind(this));                                       
            this.tree.enableContextMenu(menu);

            
            this.connector.execute({get_tree_inheritance:true});                   
//            this.tree.setInheritanceArr(this.connector.result.tree_inheritance);
        } else {
            $(this.module_name + "_treebox").show();
        }
        

         var oTabs = [                                        
                    {id:'t_addform',name: _lang_forms['add_form'], callback: this.add_form.bind(this)},
                    {id:'t_addgroup',name: _lang_common['add_group'], callback: this.add_category.bind(this)},
                    {id:'t_new_forms',name: _lang_common['new'],callback: this.show_incoming_messages.bind(this)},
                    {id:'t_archiv_forms',name: _lang_forms['archive'], callback: this.show_archive.bind(this)}
                ]
 
         this.tabs = new XTRFabtabs('bookmarks',oTabs); 
         this.tree.refreshItem(1);  
         this.first_start();
         
    },
    
    
    get_action_properties:function(_action)
    {
       this.connector.module_name = 'forms';
       this.connector.execute({get_action_properties :{Action:_action},load_initial_forms_data:true});
       
            if(this.connector.result.action_properties){
                $('action_properties').update(this.connector.lct.action_properties);
            } else {
                $('action_properties').update(_lang_common['properties_are_absent']);
            }
            
       xoad.html.importForm('tune_actions',this.connector.result.action_properties_form);
       xoad.html.importForm('tune_actions',this.connector.result.forms_data);
       
            if(this.connector.result.xlist){
                columns = $H({
                    image:' ',name:_lang_common['name']});
                    _columnsHeadersWidth = new Array('20px','150px');
                    _columnsInterpretAs = $H({image:'IMAGE'});
                    _images=$H({
                        group:'xres/ximg/tree/folderClosed.gif',
                        page:'xres/ximg/tree/page.gif'
                    });
                    
                    var Xpop = new XTRpop('startXXlist',null,{position:'bottom',delta_x:-272,delta_y:-60});
                    xlist = new XTRxlist(Xpop.tool_tip.id, this.connector,
                    {
                        permanent:true,
                        resultSource:'showformsName',
                        resultIDSource:'formsId',
                        columnsHeaders:columns,
                        tableId:'dialogtable',
                        columnsHeadersWidth:_columnsHeadersWidth,
                        columnsInterpretAs:_columnsInterpretAs,
                        images:_images,
                        className:'dialog-table'
                    });
                    
                    xlist.connectXpop(Xpop);
            }
            
       this.validation = new Validation('tune_actions', {immediate : true});
    },
    
    
    destructor:function()
    {
       $(this.module_name + "_treebox").hide();
       XTR_main.set_rightside();
       //this.menuObj.stopIt();
       this.tabs.destructor();
    }
});