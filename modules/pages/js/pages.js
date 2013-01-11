
    
XTRpages.prototype = Object.extend(new _modulePrototype(), 
    {
    initialize: function()
        {
        this.destructable = true;
        this.current_node = null;
        this.module_name = 'pages';
        this.template_changed = false;
        this.connector=null;
        this.tree=null;
        this.leaves_obj_type=new Array('_PAGE');  
        this.init(); 
        
    
        },
    
    action_by_obj_type:function(itemid,object_type)
    {
        switch (object_type)
            {
            case "_PAGE":
                this.show_edit_page(itemid);
                break;
            case "_GROUP":
                this.show_edit_group(itemid);
                break;

            case "_ROOT":
                this.show_edit_root(itemid);
                break;
                                
           case "_LINK":
                this.show_edit_link(itemid);
                break;
            default: return false;
            }
    },
    
    tree_object_clicked: function(itemid)
        {
            this.current_object_type = this.tree.getRowAttribute(itemid,"obj_type");                     
            this.action_by_obj_type(itemid,this.current_object_type);
        
        },    
    //m

    group_dialog: function(xlist_name)
        {

        columns = $H(
            {
            image: ' ',
            name: _lang_common['name']
            });

        _columnsHeadersWidth = new Array('20px', '150px');
        _columnsInterpretAs = $H(
            {
            image: 'IMAGE'
            });

        _images = $H(
            {
            group: 'xres/ximg/tree/folderClosed.gif'
            });
            

            
        return xlist = new XTRxlist(xlist_name, this.connector,
            {
                permanent: true,
                resultSource: 'showGroup',
                resultIDSource: 'showGroupId',
                columnsHeaders: columns,
                tableId: 'dialogtable',
                startWithAncestor: 0,
                include_root_in_selection:true,
                columnsHeadersWidth: _columnsHeadersWidth,
                columnsInterpretAs: _columnsInterpretAs,
                images: _images,
                usebackoff:1,
                className: 'dialog-table'
            });
        },    
        
        
        
        page_dialog: function(xlist_name)
        {

        columns = $H(
            {
            image: ' ',
            name: _lang_common['name']
            });

        _columnsHeadersWidth = new Array('20px', '150px');
        _columnsInterpretAs = $H(
            {
            image: 'IMAGE'
            });

        _images = $H(
            {
                group: 'xres/ximg/tree/folderClosed.gif',
                page: 'xres/ximg/tree/page.gif'
            });
            
        
        return xlist = new XTRxlist(xlist_name, this.connector,
            {
            permanent: true,
            resultSource: 'Link',
            resultIDSource: 'LinkId',
            columnsHeaders: columns,
            tableId: 'dialogtable',
            startWithAncestor: 0,
            columnsHeadersWidth: _columnsHeadersWidth,
            columnsInterpretAs: _columnsInterpretAs,
            include_root_in_selection:false,
            serverCallFunc:'load_xlist_link_data', 
            images: _images,
            className: 'dialog-table'
            });
        },    
        
        
        
        
    show_edit_root: function()
        {
        
        
        //connector
        this.connector.execute({load_root_data:true,
                        get_cslotz:{node_id: 1,load_all_tpl_slotz:true, get_modules_only:true}   
        })

        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'pages_root_edit',true));        
        this.current_node = 1;
        this.xtr_slots = XTRSlotBox.getInstance("slotz");  
        this.xtr_slots.reinit("slotz");

        xoad.html.importForm('root_edit', this.connector.result.root_data);
        
        if(!this.xtr_slots.is_slotz_template_cached('_allslotz_'))
        {
            //слоты и модули
            this.connector.execute({get_cslotz:{node_id: 1,load_all_tpl_slotz:true}});        
            
            if(this.connector.result)this.xtr_slots.import_slotz('_allslotz_',this.connector.result.slotz);
        
        }
        else{this.xtr_slots.import_slotz('_allslotz_');}
            
            if(this.connector.result)this.xtr_slots.import_modules(this.connector.result.modules);        
        
        this.tabs.createTabNode(
            {
            id: 'tedit_page',
            name: _lang_common['editing'],
            temporal: true
            }, 'top', true);
        


        },
        
      save_edit_root:function(el)
      {

           Validation.remove('is-none-uniq');    
           
           this.validation = new Validation('root_edit',
            {
             immediate: true
            });
            
                
        if(!this.validation.validate())return void(0);
        
        formdata = xoad.html.exportForm('root_edit');
        $('root_edit').disable();
        
        _modules = this.xtr_slots.export_module_slotz();
        this.connector.execute({save_edited_root :{id:this.current_node,root_data: formdata,modules: _modules}});        
        $('root_edit').enable();           
        XTR_main.set_result(_lang_pages['site_properties_success_saved'],el);                  
        this.tree.refreshItem(1);      
        
      
      },  
        
        
       
    save_edited_group :function(el)
       {
           
           
        Validation.remove('is-none-uniq');    
        this.validation = new Validation('group_edit',
            {
            immediate: true
            });
        
        if(!this.validation.validate())return void(0);
        
        formdata = xoad.html.exportForm('group_edit');
        $('group_edit').disable();
        
        _modules = this.xtr_slots.export_module_slotz()
        this.connector.execute({save_edited_group :
            {
            id:this.current_node,
            group_data: formdata,
            modules: _modules
            }});
        
        $('group_edit').enable();
               
        
        this.tree.refreshItem(this.tree.getParentId(this.current_node));  
        XTR_main.set_result(_lang_common['group_success_saved'],el);         
        XTR_main.hide_loading();
       
       },
        
    show_edit_group: function(itemId)
        {
        
        XTR_main.load_module_tpls(this.module_name, new Array('pages_group_edit'));
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'pages_group_edit'));

        this.xtr_slots = XTRSlotBox.getInstance("slotz");
        this.xtr_slots.reinit("slotz");

        //connector
        this.connector.execute({load_group_data:{group_id: itemId},
                                 get_cslotz:{node_id: itemId,load_all_tpl_slotz:true, get_modules_only:true}     
                               });
        xoad.html.importForm('group_edit', this.connector.result.group_data);
                
        if(!this.xtr_slots.is_slotz_template_cached('_allslotz_'))
        {
            //слоты и модули
            this.connector.execute({get_cslotz:{node_id: itemId,load_all_tpl_slotz:true}});        
            this.xtr_slots.import_slotz('_allslotz_',this.connector.result.slotz);
        
        }else{this.xtr_slots.import_slotz('_allslotz_')}
        this.xtr_slots.import_modules(this.connector.result.modules);        


        this.current_node = itemId;
        
        this.tabs.createTabNode({id: 'tedit_page',name: _lang_common['editing'],temporal: true}, 'top', true);


        },
        
        
     save_edited_page:function(el)
     {          
         
         Validation.remove('is-none-uniq');
        this.validation = new Validation('page_edit',
            {
            immediate: true
            });

       if(!this.validation.validate())return void(0);
        
        formdata = xoad.html.exportForm('page_edit');
        $('page_edit').disable();
        
        _modules = this.xtr_slots.export_module_slotz()
        this.connector.execute({save_edited_page :{id:this.current_node,page_data: formdata,modules: _modules}});        
        $('page_edit').enable();             
        XTR_main.set_result(_lang_pages['page_success_saved']);   
        this.tree.refreshItem(this.tree.getParentId(this.current_node));    
        
     },   

        
    save_new_page: function(el)
        {
            if(!this.validation.validate())return void(0);
            
            formdata = xoad.html.exportForm('page_new');
            $('page_new').disable();
            
            _modules = this.xtr_slots.export_module_slotz();
            this.connector.execute({save_new_page:
                {
                page_data: formdata,
                modules: _modules
                }});
            this.tabs.createTabNode(
                {
                id: 't_firstpage',
                name: _lang_common['info'],
                temporal: true
                }, 'top', true);

            this.tree.refreshItem(this.tree.getParentId(formdata.showGroupId));
            this.first_start();
                     
            XTR_main.set_result(_lang_pages['page_success_saved'],el);

        },
        
    non_uniq_entry: function(v)
        {
        _anc = $('showGroupId').value;

        if (_anc > 0)
            {
            this.connector.execute({check_uniq :{anc: _anc,basic: v}});
            b = this.connector.result.uniq;
            return b
            }

        else
            return true;
        },
        
     getSelectedParent:function()
     {
        selected=this.tree.getSelectedRowId();
        if (!selected)
            {                
                return {id:1,caption:this.tree.getItemText(1)};
            }
        if (this.tree.getRowAttribute(selected,"obj_type")=='_GROUP')
        {
            return {id:selected,caption:this.tree.getItemText(selected)};
        }else
        {
                
            if(selected==1){
                parent=1;
                
            }else{
            parent=this.tree.getParentId(selected);             
            }
            return {id:parent,caption:this.tree.getItemText(parent)};
        }
            
     }
     ,   
        
    show_new_page: function()
        {
        this.tabs.makeActiveById('t_addpage');

        XTR_main.load_module_tpls(this.module_name, new Array('pages_new'));
    
        parent=this.getSelectedParent();                                            

        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'pages_new'));
        
        this.xtr_slots = XTRSlotBox.getInstance("slotz");
        this.xtr_slots.reinit("slotz");
                   
        
        this.connector.execute({show_new_pg : {parent_id:parent.id}});
        xoad.html.importForm('page_new', this.connector.result.page_data);
        xoad.html.importForm('page_new',{showGroupId:parent.id,showGroup:parent.caption});
        pd=this.connector.result.page_data;
        
        var Xpop = new XTRpop('startXlist', null,
            {
            position: 'bottom',
            delta_x: -203,
            delta_y: 0
            });
        
        xlist = this.group_dialog(Xpop.tool_tip.id);
        
        //Передача контекста Xpop
        xlist.connectXpop(Xpop);

        if((!Object.isUndefined(pd.initTemplate))&&(pd.initTemplate!=''))
        {
             XTR_pages.change_template(pd.initTemplate)
        }
        
        Validation.add('is-none-uniq', _lang_pages['value_of_this_field_is_not_unique_enter_other_value'],
            this.non_uniq_entry.bind(this));

        this.validation = new Validation('page_new',
            {
            immediate: true
            });
        },
  
    save_new_group: function()
        {
        if(!this.validation.validate())return void(0);
        XTR_main.show_loading();
        formdata = xoad.html.exportForm('group_new');
        $('group_new').disable();        
        _modules = this.xtr_slots.export_module_slotz();
        this.connector.execute({save_new_group :{group_data: formdata,modules: _modules}});

        if (this.connector.result.saved)
            {
            this.tabs.createTabNode({
                id: 't_firstpage',
                name: _lang_common['info'],
                temporal: true
                }, 'top', true);

                 this.tree.refreshItem(this.tree.getParentId(formdata.showGroupId));
                 this.first_start();
                 XTR_main.set_result(_lang_common['group_success_saved']);  
            
            }else{
          
            alert(_lang_common['save_error']);
            }
            

        XTR_main.hide_loading();
        },
        
        show_on_site:function(zid,id)
        {
        
            this.connector.execute({get_page_url:{id:id}});
            window.open(this.connector.result.link, '_blank', '');
        },
        
        
    show_new_group: function(_parentId)
        {
        //recieve tpl from server
        this.tabs.makeActiveById('t_addgroup');
        XTR_main.load_module_tpls(this.module_name, new Array('pages_newgroup'));
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'pages_newgroup'));
        
        parent=this.getSelectedParent();                                            
        
        
        this.xtr_slots = XTRSlotBox.getInstance("slotz");
        this.xtr_slots.reinit("slotz");
        
        this.connector.execute({show_new_pg:{parent_id:parent.id}});

         if(!this.xtr_slots.is_slotz_template_cached('_allslotz_'))
        {
            //слоты и модули
            this.connector.execute({get_cslotz:{load_all_tpl_slotz:true}});        
            this.xtr_slots.import_slotz('_allslotz_',this.connector.result.slotz);        
        }else{this.xtr_slots.import_slotz('_allslotz_')}
        
        

        xoad.html.importForm('group_new', this.connector.result.page_data);
        xoad.html.importForm('group_new',{showGroupId:parent.id,showGroup:parent.caption});

        var Xpop = new XTRpop('startXlist', null,
            {
            position: 'bottom',
            delta_x: -203,
            delta_y: 0
            });
        
        //слоты    
        xlist = this.group_dialog(Xpop.tool_tip.id);
        xlist.connectXpop(Xpop);

        this.validation = new Validation('group_new',
            {
            immediate: true
            });
        },
        
        
    show_tunes: function()
        {
        XTR_main.load_module_tpls(this.module_name, new Array('pages_tunes'));
        
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'pages_tunes'));
        },
        
        
    show_edit_page: function(itemId)
        {

        
        //recieve  edit_page  template from server       
        

        XTR_main.set_rightside_eform(XTR_main.get_tpl('pages', 'pages_edit',true));
        
        //recieve  page data
        
        this.xtr_slots = XTRSlotBox.getInstance("slotz");
        this.xtr_slots.reinit("slotz");
        this.connector.execute({load_page_data:{page_id: itemId},
                                get_cslotz:{node_id: itemId, get_modules_only:true} 
        }, null);
        
                
        //$('page_edit_link').href=this.connector.result.page_data.LinkTo;
        xoad.html.importForm('page_edit', this.connector.result.page_data);        
        
        template=this.connector.result.page_data.VTemplate;
        
         if(!this.xtr_slots.is_slotz_template_cached(template))
        {
            //слоты и модули
            this.connector.execute({get_cslotz:{node_id:itemId}}, null);        
            if(this.connector.result)
            this.xtr_slots.import_slotz(template,this.connector.result.slotz);        
            
            }else{this.xtr_slots.import_slotz(template);
        
        }
                    if(this.connector.result)this.xtr_slots.import_modules(this.connector.result.modules)

        this.current_node = itemId;

        this.tabs.createTabNode(
            {
            id: 'tedit_page',
            name:_lang_common['editing'],
            temporal: true
            }, 'top', true);

   
        
        },

    change_template: function(_tpl)
        {             
        modules = this.xtr_slots.export_module_slotz();                
        this.template_changed = true;
        this.xtr_slots.clear_slotz();        
        if(_tpl!=null){
          if(!this.xtr_slots.is_slotz_template_cached(_tpl))
        {
            //слоты и модули
            
            this.connector.execute({get_cslotz:{tpl_name:_tpl}});                        
            this.xtr_slots.import_slotz(_tpl,this.connector.result.slotz);                    
            }else{this.xtr_slots.import_slotz(_tpl);        
        }
             }        
        this.xtr_slots.import_modules(modules, true);        
        },
        
        
     dynXLS:function(id)
     {

            this.connector.execute({pages_table:{id:id}});            
            if(this.connector.result)
                {
                    this.tree.json_dataset=this.connector.result.data_set;
                }
         return true;
     },


    first_start: function()
        {
            XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'pages_first',true));
            this.tabs.makeActiveById('t_firstpage');
            
        },
        
        
  
   
   
//================== USER_MENU ======================= 
 
   
   create_user_menu: function()
        {
            XTR_main.load_module_tpls(this.module_name, new Array('umenu_add','uitem'));
            XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'umenu_add'));
            this.validation = new Validation('add_umenu',{immediate: true});
            tree1 = new dhtmlXTreeObject("treeboxbox_tree1", 'auto', 'auto', "0");            
            tree1.setImagePath("/xres/ximg/green/");          
            tree1.setDataMode("json");         
            tree1.setXMLAutoLoading("tree_xml_server.php?tree=page_container");
            tree1.loadJSON("tree_xml_server.php?tree=page_container&id=0");
            tree1.setOnDblClickHandler(XTR_pages.push_element);
            this.ids = new Array();
        },
        
   
   push_element: function(){
           if (1!=tree1.getSelectedItemId()){
            if (!XTR_pages.exists(tree1.getSelectedItemId())){
                var syntax = /(^|.|\r|\n)(\{%F:\s*(\w+)\s*%})/;
                var t = new Template(XTR_main.get_tpl('pages', 'uitem'), syntax);
                var repl = {'id':tree1.getSelectedItemId(),'text':tree1.getSelectedItemText()};
                var ins = t.evaluate(repl);
                XTR_pages.ids.push(repl.id);
                new Insertion.Bottom('umenu',ins);
                Sortable.create('umenu',{tag:'li',constraint:false});
            }
           }
   },
   
   exists: function(value){

        if (in_array(XTR_pages.ids,value)) return  true;

        return false;
        
   },
   
   delete_item: function(elt){
        elt.up().remove();
   },
   
   save_user_menu: function()
   {
   if(!this.validation.validate())return void(0);
        var elts  = $('umenu').getElementsByClassName('uitem');
        if (elts.length==0){ 
            alert(_lang_pages['in_menu_should_be_at_least_one_element']);
        }else{
            
            var data = xoad.html.exportForm('add_umenu');
            var elts  = $('umenu').getElementsByClassName('uitem'); 
            var ids   = Sortable.sequence('umenu','div');            
            this.connector.execute({save_umenu :{data:data,items:ids}});
                
                if(this.connector.result.is_saved)
                {
                    this.show_user_menus();
                    }  
                        XTR_main.hide_loading();                              
                
            }
        
        
        
   },
   
   
     
  
      delete_umenu : function() 
      {
          
        id=this.gridlist_umenu.getSelectedRowId(1);
        if(id.length>0)
        {                
            if (!confirm(_lang_pages['you_are_assured_what_wish_to_remove_the_chosen_menu']))return false;

            this.connector.execute({delete_umenu:{id:id}});
            if (this.connector.result.isDel)
            {
               this.gridlist_umenu.deleteSelectedRows();
            }
        }
    },
   
    show_user_menus:function()
    {    
         
                    menu = new dhtmlXMenuObject();
                    menu.renderAsContextMenu();
                    menu.addNewChild(menu.topId, 0, "delete",_lang_common['delete'], false,'','',this.delete_umenu.bind(this));                                      
                    XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'umenu_list',true),'b');
                    $('pw-edit').insert('<div id="t-container"></div>');
                    this.gridlist_umenu = new dhtmlXGridObject('t-container');   
                    this.gridlist_umenu.selMultiRows = true;
                    this.gridlist_umenu.setImagePath("xres/ximg/grid/imgs/");
                    this.gridlist_umenu.setHeader('id,name');
                    this.gridlist_umenu.setInitWidths("70,*");
                    this.gridlist_umenu.enableContextMenu(menu);
                    this.gridlist_umenu.setColAlign("center,left");
                    this.gridlist_umenu.attachHeader("#text_filter,#text_filter");
                    this.gridlist_umenu.setColTypes("ed,ed");
                    this.gridlist_umenu.setColSorting("int,str");
                    this.gridlist_umenu.enableAutoWidth(true);
                    this.gridlist_umenu.attachEvent("onRowDblClicked", this.show_edit_umenu.bind(this));
                    this.gridlist_umenu.enableContextMenu(menu);  
                    this.gridlist_umenu.init();
                    this.gridlist_umenu.setSkin("modern");
                    
                    this.connector.execute({menu_table:true});                                
                    
                    if(this.connector.result.data_set)
                    {
                        this.gridlist_umenu.parse(this.connector.result.data_set,"xjson")
                    }   
    },
    
    show_edit_umenu:function(id){
        
        this.connector.execute({load_edit_umenu :{id:id}});
        XTR_main.load_module_tpls(this.module_name, new Array('umenu_edit','uitem'));
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'umenu_edit'));
        this.validation = new Validation('edit_umenu',{immediate: true});
        
            tree1 = new dhtmlXTreeObject("treeboxbox_tree1", 'auto', 'auto', "0");            
            tree1.setImagePath("/xres/ximg/green/");          
            tree1.setDataMode("json");         
            tree1.setXMLAutoLoading("tree_xml_server.php?tree=page_container");
            tree1.loadJSON("tree_xml_server.php?tree=page_container&id=0");
            tree1.setOnDblClickHandler(XTR_pages.push_element);
            
        this.connector.result;
        var syntax = /(^|.|\r|\n)(\{%F:\s*(\w+)\s*%})/;
        var t = new Template(XTR_main.get_tpl(this.module_name, 'uitem'), syntax);
        var items = this.connector.result.items;
        var xxx = '';
        XTR_pages.ids = new Array();
        items.each(function(i){
            xxx+=t.evaluate(i);
            XTR_pages.ids.push(i.id);    
        });
        $('umenu').innerHTML = xxx;
        xoad.html.importForm('edit_umenu',this.connector.result.data);
        Sortable.create('umenu',{tag:'li',constraint:false});   
    },
    
    save_edited_umenu: function(){
   if(!this.validation.validate())return void(0);
        var elts  = $('umenu').getElementsByClassName('uitem');
        if (elts.length==0){ 
            alert(_lang_pages['in_menu_should_be_at_least_one_ element']);
        }else{
            
            var data = xoad.html.exportForm('edit_umenu');
            var elts  = $('umenu').getElementsByClassName('uitem'); 
            var ids   = Sortable.sequence('umenu','div');            
            this.connector.execute({update_umenu:{data:data,items:ids}})               
                           if(this.connector.result.is_saved)
                           {
                            this.show_user_menus();                     
                            }
                 
                        XTR_main.hide_loading();                              
        
        
            }
   },
    
    parse_menu: function(){
        XTR_main.load_module_tpls(this.module_name, new Array('umenu_edit'));
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'umenu_edit'));    
    
    },


  
    

    
//================== end of USER_MENU =======================

    save_new_link:function()
    {    
      if(!this.validation.validate())return void(0);
        
        formdata = xoad.html.exportForm('new_link');
        $('new_link').disable();
        this.connector.execute({save_new_link :{link_data: formdata}});
        this.tabs.createTabNode(
            {
            id: 't_firstpage',
            name: _lang_common['info'],
            temporal: true
            }, 'top', true);

        this.tree.refreshItem(this.tree.getParentId(formdata.showGroupId));
        this.first_start();
                 
        XTR_main.set_result(_lang_pages['link_success_saved']);
   
    },
    
    
    save_edited_link:function()
    {
     if(!this.validation.validate())return void(0);
        
        formdata = xoad.html.exportForm('link_edit');        
        this.tree.refreshItem(this.tree.getParentId(this.current_node));          
        this.connector.execute({save_edited_link :{id:this.current_node,link_data: formdata}}); 
        if(this.connector.result.saved)
        {                                                
            XTR_main.set_result(_lang_pages['link_success_saved']);
        }else{
            XTR_main.set_result(_lang_common['save_error']);
        }   
    }
    ,
    
    show_edit_link:function(itemId)
    {

        XTR_main.load_module_tpls(this.module_name, new Array('pages_edit_link'));

        //recieve  page data

        this.connector.execute({load_link:{link_id: itemId}});
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name,'pages_edit_link'));
        xoad.html.importForm('link_edit', this.connector.result.link_data);

         
         var Xpop = new XTRpop('startXlist', null,
            {
            position: 'bottom',
            delta_x: -203,
            delta_y: 0
            });
            
         xlist = this.page_dialog(Xpop.tool_tip.id);

        //Передача контекста Xpop
        xlist.connectXpop(Xpop);
        
        this.current_node = itemId;         
        this.tabs.createTabNode(
            {
            id: 'tedit_page',
            name: _lang_common['editing'],
            temporal: true
            }, 'top', true);

        this.validation = new Validation('link_edit',
            {
            immediate: true
            });

      

   
    },
 
 
 
     show_add_link:function()
     {

        this.tabs.makeActiveById('t_link');
        XTR_main.load_module_tpls(this.module_name, new Array('pages_new_link'));
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'pages_new_link'));

        
        var Xpop = new XTRpop('startXlist', null,
            {
            position: 'bottom',
            delta_x: -203,
            delta_y: 0
            });
        
        
        xlist = this.page_dialog(Xpop.tool_tip.id);                
        xlist.connectXpop(Xpop);
        
        var X_pop = new XTRpop('groupXlist', "xxlist",
            {
            position: 'bottom',
            delta_x: -203,
            delta_y: 0
            });
            
        xlist_g = this.group_dialog(X_pop.tool_tip.id);
        //Передача контекста Xpop
        xlist_g.connectXpop(X_pop);
       
       
       parent=this.getSelectedParent();                                
       xoad.html.importForm('new_link',{showGroupId:parent.id,showGroup:parent.caption});

        this.validation = new Validation('new_link',
            {
            immediate: true
            });

},


    access_groups_builder:function(rs)
        {
          html='<ul class="chk">';   
          rs.each(
          function(pair)
          {         
          if(pair[1].r){checked='checked'}else{checked='';}                                                                   
          html+='<li><input type="checkbox" '+checked+' id="_'+pair[0]+'" name="_'+pair[0]+'" value="'+pair[0]+'"> '+pair[1].Name+'</li>';                         
          
          }
                                    );                       
                        html+='</ul>';   
                        return html;
        },

     access:function(zid,id)
     {          
             
             this.connector.execute({get_access:{id:id}});             
             XTR_main.load_module_tpls(this.module_name, new Array('access'));   
             XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'access'));                  
             $('access').Name.value=this.tree.getItemText(id);             
             access_groups=$H(this.connector.result.access_groups);
             xoad.html.importForm('access',this.connector.result.access);             
             var Xpop = new XTRpop('startXlist', null,
                    {
                    position: 'bottom',
                    delta_x: -203,
                    delta_y: 0
                    });

             xlist = this.page_dialog(Xpop.tool_tip.id);
             xlist.connectXpop(Xpop);
             this.current_node=id;
             $('fusersgroups').update(this.access_groups_builder(access_groups));             
             
             
     },
     
     save_access_rights:function()          
     {
     
             
             groups=xoad.html.exportForm('fuserrights');
             access=xoad.html.exportForm('access');              
             this.connector.execute({set_access:{id:this.current_node,groups:groups,access:access}}, null);                                   
             XTR_main.set_result(_lang_pages['rights_saved']);  
             
     },
     
     switch_page : function(id,cid,state) 
     {                                
        this.connector.execute({switch_page:{id : id,state:state}});        
    },
 
 
    create_new_route:function()
    {
                    params=xoad.html.exportForm('add_route');        
                    this.connector.execute({create_new_route:params});                 
                    this.refresh_routes();
        
    },
    
    delete_route:function(kid,id)
    {
                    
        this.connector.execute({delete_route:{id:id}});                 
        this.gridlist_umenu.deleteSelectedRows(); 
        this.refresh_routes();
    
    }
    ,
    refresh_routes:function()
    {
                    this.connector.execute({routes_table:true});                                
                    
                    this.gridlist_umenu.clearAll();
                    
                    if(this.connector.result.data_set)
                    {
                        this.gridlist_umenu.parse(this.connector.result.data_set,"xjson")
                    }       
    },
    
    
    doOnCellEdit:function(stage, rowId, cellInd)
    {
        
            if (stage == 2) 
            {
                var cellObj = this.gridlist_umenu.cellById(rowId, cellInd);
                
                if(cellInd==1)
                {
                    this.connector.execute({save_route_part:{part:'from',id:rowId,text:cellObj.getValue()}});                
                }
                
                if(cellInd==2)
                {
                    this.connector.execute({save_route_part:{part:'to',id :rowId,text:cellObj.getValue()}});                
                }
            }
            return true;    
    },
    
    route_301_switch:function(id,cid,state)
    {
        this.connector.execute({route_301_switch:{id : id,state:state}});        
    },   
    
    
    show_routes:function()                                                        
    {
                    menu = new dhtmlXMenuObject();
                    menu.renderAsContextMenu();
                    menu.addNewChild(menu.topId, 0, "delete",_lang_common['delete'], false,'','',this.delete_route.bind(this));                                      
                    XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'routes',true),'b');
                    $('pw-edit').insert('<div id="t-container"></div>');
                    this.gridlist_umenu = new dhtmlXGridObject('t-container');   
                    this.gridlist_umenu.selMultiRows = true;
                    this.gridlist_umenu.setImagePath("xres/ximg/grid/imgs/");
                    this.gridlist_umenu.setHeader('id,'+_lang_pages['source']+','+_lang_pages['destination']+','+_lang_pages['301_redirect']);
                    this.gridlist_umenu.setInitWidths("70,200,*,120");
                    this.gridlist_umenu.enableContextMenu(menu);
                    this.gridlist_umenu.setColAlign("center,left,left,center");
                    this.gridlist_umenu.setColTypes("ed,ed,ed,ch");
                    this.gridlist_umenu.attachHeader("#text_filter,#text_filter,#text_filter,#select_filter");
                    this.gridlist_umenu.setSelectFilterLabel(3,_select_filter_yes_no);
                    this.gridlist_umenu.setColSorting("int,str,str,str");
                    this.gridlist_umenu.attachEvent("onCheckbox",this.route_301_switch.bind(this));
                    this.gridlist_umenu.enableAutoWidth(true);
                    this.gridlist_umenu.enableContextMenu(menu);  
                    this.gridlist_umenu.init();
                    this.gridlist_umenu.attachEvent("onEditCell", this.doOnCellEdit.bind(this));
                    this.gridlist_umenu.setSkin("modern");
                    this.refresh_routes();
    
    }
    ,
     build_interface: function()
        {
        //дерево
        
        toggle_main_menu(true);
        if(!this.tree)
        {            
            $('tp-tree-window').appendChild(new Element('div',{id: this.module_name+"_treebox",className:'gridbox'}));
            menu = new dhtmlXMenuObject();
            menu.renderAsContextMenu();
            menu.addNewChild(menu.topId, 0, "refresh", _lang_common['refresh'], false,'','',function(id,zid){this.tree.refreshItem(zid);}.bind(this));                       
            menu.addNewChild(menu.topId, 0, "delete",_lang_common['delete'], false,'','',this.delete_obj_treegrid.bind(this));                           
            menu.addNewChild(menu.topId, 0, "copyn",_lang_common['copy'], false,'','',this.copy_obj_treegrid.bind(this));                           
            menu.addNewChild(menu.topId, 0, "pasten",_lang_common['paste'], false,'','',this.paste_obj_treegrid.bind(this));                           
            menu.addNewChild(menu.topId, 0, "access",_lang_pages['set_access_rights'], false,'','',this.access.bind(this));                           
            menu.addNewChild(menu.topId, 0, "add_page",_lang_pages['add_page'], false,'','',this.show_new_page.bind(this));
            menu.addNewChild(menu.topId, 0, "add_group",_lang_common['add_group'], false,'','',this.show_new_group.bind(this));
            menu.addNewChild(menu.topId, 0, "show_on_site",_lang_pages['show_on_site'], false,'','',this.show_on_site.bind(this));
            
            this.tree = new dhtmlXGridObject(this.module_name+"_treebox");
            this.tree.selMultiRows = true;
            this.tree.imgURL = "/xres/ximg/green/";
            this.tree.setHeader(_lang_pages['page_name']+','+_lang_pages['link']+','+_lang_pages['no_display']);
            this.tree.setInitWidths("280,*,*");
            this.tree.setColAlign("left,left,center");
            this.tree.setColTypes("tree,ed,ch");
            this.tree.enableDragAndDrop(true);
            this.tree.enableEditEvents(false,false,true);
            this.tree.attachEvent("onDrag",this.on_treegrid_drag.bind(this));
            this.tree.setDragBehavior('complex-next');
            
            this.tree.setSelectFilterLabel(2,_select_filter_yes_no);

            this.tree.enableMultiselect(true);
            this.tree.enableContextMenu(menu);
            this.tree.attachHeader("#text_search,#text_search,#select_filter");
            this.tree.init();
            this.tree.kidsXmlFile=1;
            this.tree.attachEvent("onCheckbox",this.switch_page.bind(this)); 
            this.tree.attachEvent("onDynXLS",this.dynXLS.bind(this));
            this.tree.setSkin("dhx_skyblue");
            this.tree.attachEvent("onRowDblClicked", this.tree_object_clicked.bind(this));
            this.connector.execute({pages_table:{id:0}});            
            if(this.connector.result)
                    {
                        this.tree.parse(this.connector.result.data_set,"xjson")
                    }

            this.tree.openItem(1);
            if (XTR_main.tpl_exists('pages', 'pages_slot_editor')== false)
            {   
                this.connector.execute({get_tree_inheritance:true});
                XTR_main.cache_mass_tpl('pages', $H(this.connector.lct));
               // this.tree.setInheritanceArr(this.connector.result.tree_inheritance);
            }
            
        }
        
          
       
        var oTabs =
            [
            {
            id: 't_firstpage',
            name: _lang_common['info'],
            temporal: true
            },
            {
            id: 't_addpage',
            name: _lang_pages['add_page'],
            callback: this.show_new_page.bind(this)
            },
            {
            id: 't_addgroup',
            name: _lang_common['add_group'],
            callback: this.show_new_group.bind(this)
            },
            {
            id:'t_link',
            name: _lang_pages['add_link'],
            callback: this.show_add_link.bind(this)
            },
            {
            id:'t_umenu_list',
            name: _lang_pages['menu'],
            callback: this.show_user_menus.bind(this)
            },
            {
            id:'routes',
            name: _lang_pages['routes'],
            callback: this.show_routes.bind(this)
            }
            ]
       
        this.tabs = new XTRFabtabs('bookmarks', oTabs);
        if(this.tree){
            $(this.module_name+"_treebox").show();        
            this.state_restore();
        }
        
        
        
        },


    destructor: function()
        {
       // tree.destructor();
        
            $(this.module_name+"_treebox").hide();
            slot = null;            
            this.tabs.destructor();
            XTR_main.set_rightside();
        }
    }  );


    function m_func(state,id,tree,value)
         {
            if ((state==2)&&(value=="")) return false;
            return true;
         } 