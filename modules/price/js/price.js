var XTRprice = Class.create();
var XTR_price;


XTRprice.prototype = Object.extend(new _modulePrototype(), 
    {
    initialize: function()
        {
        this.destructable = true;
        this.module_name='price';
        this.current_edit_id=null;
        this.current_node = 1;
        this.connector=null;
        this.leaves_obj_type=new Array('_PRICE');  
        this.tree=null;
        this.init();
        },
    //m
    tree_object_clicked: function(itemid)
        {
        object_type = this.tree.getNodeParam(itemid, 'obj_type');

        switch (object_type)
            {
            case "_PRICE":
                this.show_edit_price(itemid);
                this.current_node = itemid;

                break;

            case "_PRICEGROUP":
                this.edit_category(itemid);
                this.current_node = itemid;

            break;
            case "_ROOT":
                this.show_edit_root();
                this.current_node = itemid;
            break;            

            default: return false;
            }
        },
    //m

    group_dialog: function()
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

            _images=$H({
                group:'xres/ximg/tree/folderClosed.gif',
                page:'xres/ximg/tree/page.gif'
            });
            
            if(arguments[0])
            {
                xlist_name=arguments[0];
            }else{            
                xlist_name="xlist";
            }
            
            if(Object.isUndefined(arguments[1]))
            {fcall='load_xlist_data';}else{fcall=arguments[1];}
               
               if(Object.isUndefined(arguments[2]))
            {startwith=1}else{startwith=arguments[2];}
            
            
        return xlist = new XTRxlist(xlist_name, this.connector,
            {
            permanent: true,
            resultSource: 'ParentCategory',
            serverCallFunc:fcall,
            resultIDSource: 'ParentCategoryId',
            columnsHeaders: columns,
            tableId: 'dialogtable',
            startWithAncestor: startwith,
            columnsHeadersWidth: _columnsHeadersWidth,
            columnsInterpretAs: _columnsInterpretAs,
            images: _images,
            className: 'dialog-table'
            });
        },    

    show_edit_root: function() {
        this.connector.execute({load_root_data:true});
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'edit_root',true));        
        this.current_node = 1;
        xoad.html.importForm('edit_root', this.connector.result.root_data);
    },
    
    save_edit_root:function(el)
    {
           Validation.remove('is-none-uniq');    
           this.validation = new Validation('edit_root', {immediate: true});
                
        if(!this.validation.validate())return void(0);
        
        XTR_main.show_loading();
        formdata = xoad.html.exportForm('edit_root');
        $('edit_root').disable();
        this.connector.execute({save_edited_root :{id:this.current_node,root_data: formdata}});        
        $('edit_root').enable();           
        XTR_main.set_result(_lang_common['saved'],el);                  
        //this.tree.refreshItem(1);
        this.reload();
        XTR_main.hide_loading();
        
      
      },    

    add_category:function()
    {
            actionList = new Array();     
            XTR_main.show_loading();               
            XTR_main.load_module_tpls(this.module_name, new Array('add_category'));            
            actionList.add_category = true;                                                
            this.connector.execute(actionList);         
            XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'add_category'));                                
            xoad.html.importForm('add_category',this.connector.result.category_data); 
            this.validation = new Validation('add_category',{immediate: true});            
            
            var Xpop = new XTRpop('startXlist', null, { position: 'bottom', delta_x: -203, delta_y: 0 });
            xlist = this.group_dialog(Xpop.tool_tip.id,'load_xlist_data_folders',1);
            xlist.connectXpop(Xpop);
            XTR_main.hide_loading();

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
             /*
             var Xpop = new XTRpop('startXlist', null,
                    {
                    position: 'bottom',
                    delta_x: -203,
                    delta_y: 0
                    });

             xlist = this.page_dialog(Xpop.tool_tip.id);
             xlist.connectXpop(Xpop);
             */
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

    save_edited_category:function()
    {  
        if(!this.validation.validate())return void(0);        
        formdata=xoad.html.exportForm('edit_category');        
        this.connector.execute({save_edited_category :{id:this.current_node,data:formdata}});
        if(this.connector.result.is_saved)
                        {        
                        //this.tree.refreshItem(1);                        
                        this.tree.refreshItem(this.tree.getParentId(this.current_edit_id));  
                        this.tabs.createTabNode(
                                {id: 't_firstpage',name: _lang_common['info'],temporal: true}, 'top', true);
                                $('pw-edit').update(_lang_common['group_success_saved']);
                     }                        

    },
    
    
    save_category:function()
    {  
      
      if(!this.validation.validate())return void(0); 
      formdata=xoad.html.exportForm('add_category');
      this.connector.execute({save_category :{data:formdata}});
                        if(this.connector.result.is_saved)
                        {                        
                         //this.tree.refreshItem(1);                        
                         //this.tree.refreshItem(add_price.category);
                         this.tree.refreshItem(this.tree.getParentId(this.current_edit_id));  
                         this.tabs.createTabNode(
                            {
                            id: 't_firstpage',
                            name: _lang_common['info'],
                            temporal: true
                            }, 'top', true);
                            $('pw-edit').update(_lang_common['group_success_saved']);
         
                        }                                
    },
    
    edit_category:function(itemId)
    {            
            XTR_main.load_module_tpls(this.module_name, new Array('edit_category'));            
            this.connector.execute({load_category :{id:itemId}});         
            this.current_node = itemId;   
            this.tabs.createTabNode({id: 't_edit',name: _lang_common['editing'],temporal: true}, 'top', true);
            XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'edit_category'));                                                   
            xoad.html.importForm('edit_category',this.connector.result.category_data); 
            this.validation = new Validation('edit_category',{immediate: true});     
    },     
    
    
    //m
    add_price: function()
        {            
            XTR_main.load_module_tpls(this.module_name, new Array('add_price'));              
            selected=this.tree.getSelectedItemId();
            params=true;
            
            if(selected)
            {
                if(this.tree.get_objtype(selected)=='_PRICEGROUP')
                {
                  params={group_id:selected};
                }
            }            
            this.connector.execute({load_initial_price_data:params});            
            XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'add_price'));                                                       
            xoad.html.importForm('add_price',this.connector.result.price_data);                                     
            
            
            var Xpop = new XTRpop('startXlist', null, { position: 'bottom', delta_x: -203, delta_y: 0 });
            xlist = this.group_dialog(Xpop.tool_tip.id,'load_xlist_data_folders',1);
            xlist.connectXpop(Xpop);
            
            this.validation = new Validation('add_price',{immediate: true}); 
            XTR_main.hide_loading();
            
            
            

        }
        ,
        
        //m                
    show_edit_price: function(itemId)
        {    
            
            this.tabs.createTabNode({id: 't_edit',name: _lang_common['editing'],temporal: true}, 'top', true);
            actionList = new Array();
                //recieve tpl from server        
            if (!XTR_main.tpl_exists(this.module_name, 'edit_price'))
                {
                    actionList.tpl_price_edit = true;            
                }
            
            actionList.load_price ={price_id:itemId};
            
            
            this.connector.execute(actionList)
            
                this.current_node = itemId;               
                XTR_main.load_module_tpls(this.module_name, new Array('edit_price'));                
                XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'edit_price'));                                   
                xoad.html.importForm('edit_price',this.connector.result.price_data);                                
                this.current_edit_id=itemId;           
                
                //validation part
                this.validation = new Validation('edit_price', {immediate : true});
        },
        
        
       change_category:function(sid)
       {            
            this.connector.execute({load_tpl:{id:sid}})
            select_option($('Template'),this.connector.result.default_tpl);               
       }
       ,

   
   //m
       save_price:function()
         {   
            if(!this.validation.validate())return void(0);
            var add_price = xoad.html.exportForm('add_price');        
            this.connector.execute({save_price :{main:add_price}});
              
              if(this.connector.result.is_saved)
              {
                            //this.tree.refreshItem(add_price.category);
                            this.tree.refreshItem(this.tree.getParentId(this.current_edit_id));  
                             this.tabs.createTabNode(
                                {
                                id: 't_firstpage',
                                name:_lang_common['info'],
                                temporal: true
                                }, 'top', true)
                                $('pw-edit').update(_lang_price['price_success_saved']);
                                
              }

       },
    
    
    delete_obj: function(b,item_id)
        {
        checked = this.tree.getAllChecked()

        if (checked.length > 1)
            {
            result = confirm(_lang_common['you_really_wish_to_remove_this_objects']);
            item_id = checked;
            }

        else
            {
            result = confirm(_lang_common['you_really_wish_to_remove_this_object']);
            }

            if (result)
                {
                files = confirm(_lang_price['delete_files_of_prices_from_a_server']);                
                this.connector.execute({delete_obj :{id: item_id,files:files}});

                if (Object.isArray( this.connector.result.deleted))
                    {
                    
                        for (i = 0; i < this.connector.result.deleted.length; i++)
                            {
                            this.tree.deleteItem( this.connector.result.deleted[i]);
                            }
                        $('pw-edit').update(_lang_price['prices_are_removed_successfully']);
                        }
                    
                    }
        },
    
    
    save_edited_price:function()
    {    
        
        main = xoad.html.exportForm('edit_price');                     
        this.connector.execute({save_edited_price:{main:main,id:this.current_edit_id}});
        if(this.connector.result.is_saved){
        this.tabs.createTabNode(
                            {
                            id: 't_firstpage',
                            name: _lang_common['info'],
                            temporal: true
                            }, 'top', true)
                            $('pw-edit').update(_lang_price['price_success_saved']);        
                            if (this.connector.result.dragOK) {
                                alert('reinit!');
                            }

                        }
                     
                        this.tree.refreshItem(this.tree.getParentId(this.current_edit_id));  


    },
    

    reload: function() {
        
        if (this.tree) this.tree.destructor();
        this.tree = null;
        this.tree = new dhtmlXTreeObject(this.module_name+"_treebox", 'auto', 'auto', "0");
        this.tree.enableDragAndDrop(1);                        
        this.tree.setImagePath("/xres/ximg/green/");          
        this.tree.enableContextMenu(menu);       
                
        this.tree.setOnDblClickHandler(this.tree_object_clicked.bind(this));
        this.tree.setDataMode("json");
                
        this.tree.setXMLAutoLoading("tree_xml_server.php?tree=price_container");
        this.tree.loadJSON("tree_xml_server.php?tree=price_container&id=0",function(){this.tree.refreshItem(1);}.bind(this));        
        this.tree.enableDragAndDrop(true);        
        this.tree.setDragBehavior('complex');
        this.tree.setDragHandler(this.on_tree_drag.bind(this));
    },
    
    build_interface: function()
        {

        //parent tree
        toggle_main_menu(true);
        
        if(!this.tree)
        {
            $('tp-tree-window').appendChild(new Element('div',{id: this.module_name+"_treebox",className:'treebox'}));
            menu = new dhtmlXMenuObject();
            menu.renderAsContextMenu();
            menu.addNewChild(menu.topId, 0, "delete",_lang_common['delete'], false,'','',this.delete_obj.bind(this));                           
            menu.addNewChild(menu.topId, 0, "refresh", _lang_common['refresh'], false,'','',function(id,zid){this.tree.refreshItem(zid);}.bind(this));            
            menu.addNewChild(menu.topId, 0, "add_price",_lang_price['add_price'], false,'','', this.add_price.bind(this));                       
            menu.addNewChild(menu.topId, 0, "add_pricegroup",_lang_common['add_group'], false,'','',this.add_category.bind(this));           
            menu.addNewChild(menu.topId, 0, "edit_rights",_lang_pages['set_access_rights'], false,'','',this.access.bind(this));           
            
            this.reload();
            }
            else{
                $(this.module_name+"_treebox").show();
            }
         
         var oTabs = [                    
                    {id:'t_firstpage',name: _lang_common['info'],temporal:true},
                    {id:'t_addcontent',name: _lang_price['add_price'], callback: this.add_price.bind(this)},
                    {id:'t_addgroup',name: _lang_common['add_group'], callback: this.add_category.bind(this)}                    
                ]
                
                            
                this.tabs=new XTRFabtabs('bookmarks',oTabs);  
                this.first_start();          
        },
        
    
        first_start:function()
            {                  
                   XTR_main.load_module_tpls(this.module_name, new Array('price_first')); 
                   XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name,'price_first'));                            
                   this.tabs.makeActiveById('t_firstpage');             
            
            },
    
            get_action_properties:function(_action,prefix)    
            {

                 
                if (prefix!='action' && prefix!='secondary') {
                    destination_prefix = 'action';
                    defaultAction = prefix;
                }
                else if (prefix=='secondary'){
                    destination_prefix = prefix;
                    defaultAction = null;    
                } else{


                    destination_prefix = 'action';
                    defaultAction = null;    
                } 

                   //! тут есть странности    load_initial_price_data:true               
                   this.connector.execute({get_action_properties:{Action:_action},load_initial_price_data:true});                  
                   
                   if(this.connector.result.action_properties)
                   {
                    $('action_properties').update(this.connector.lct.action_properties);                               
                   }else{
                    $('action_properties').update(_lang_common['properties_are_absent']);
                   }
                   
                    xoad.html.importForm('tune_actions',this.connector.result.action_properties_form);
                    xoad.html.importForm('tune_actions',this.connector.result.price_data);   
                    
                    if (this.connector.result.xlist) {
                        
                            var Xpop = new XTRpop('startXlist', null, { position: 'bottom', delta_x: -203, delta_y: 0 });
                            
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

                                _images=$H({
                                    group:'xres/ximg/tree/folderClosed.gif',
                                    page:'xres/ximg/tree/page.gif'
                                });
                                
                                xlist = new XTRxlist(Xpop.tool_tip.id, this.connector,
                                {
                                permanent: true,
                                resultSource: 'categoryName',
                                serverCallFunc:'load_xlist_data_folders',
                                resultIDSource: 'category',
                                columnsHeaders: columns,
                                tableId: 'dialogtable',
                                startWithAncestor: 1,
                                columnsHeadersWidth: _columnsHeadersWidth,
                                columnsInterpretAs: _columnsInterpretAs,
                                images: _images,
                                className: 'dialog-table'
                                });                    
                                        
                                xlist.connectXpop(Xpop);                    

                        this.validation=new Validation('tune_actions', {immediate : true});    
                     }
                   
                    
                     if (defaultAction){
                        this.get_action_properties(defaultAction,'secondary');    
                        }               
    
                    this.validation=new Validation('tune_actions', {immediate : true}); 
                   
  
                   
            },
            

          destructor:function()
             {
            
                $(this.module_name+"_treebox").hide();   
                XTR_main.set_rightside_eform();
                
                this.tabs.destructor();
          
             
             }   

    }
     );
