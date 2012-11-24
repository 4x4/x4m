var XTRusers = Class.create();  
var XTR_users;


XTRusers.prototype =Object.extend(new _modulePrototype(), 
    {
    initialize: function()
        {
        this.destructable = true;
        this.current_node = null;
        this.module_name = 'users';        
        this.tree=null;
        this.leaves_obj_type=new Array('_USERS');    
        this.init();
        },

        
        tree_object_clicked: function(itemid)
        {
        object_type = this.tree.getNodeParam(itemid, 'obj_type');

        switch (object_type)
            {
            case "_USERS":
                this.show_edit_user(itemid);

                break;

            case "_USERSGROUP":
                this.show_edit_usergroup(itemid);

                break;


            default: return false;
            }
        },
    //m

      
        show_new_user:function()
        {
            
            
            this.connector.execute({show_new_user:true });                                
            XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'add_user',true));
            xoad.html.importForm('user_new', this.connector.result.user_new);
            
            Validation.add('is-none-user-uniq', _lang_common['user_with_such_name_already_exists'], this.non_uniq_user.bind(this));
            
            this.validation = new Validation('user_new',
                {
                immediate: true
                });
        
        },
        
  
        
        non_uniq_user:function()
        {
            this.connector.execute({check_uniq :{username: v}});
            return this.connector.result.uniq;
            
        
        },
        
        
        save_new_user:function()
        {
        
        
          if(!this.validation.validate())return void(0);
            formdata = xoad.html.exportForm('user_new');
            this.connector.execute({save_new_user :{data:formdata}});
            
            this.tabs.createTabNode(
                {
                id: 't_firstpage',
                name: _lang_common['info'],
                temporal: true
                }, 'top', true);

            this.tree.refreshItem(this.tree.getParentId(this.current_node));                        
            this.first_start();
            XTR_main.set_result(_lang_common['user_success_saved']);
        
        } ,
        
        
        save_user:function()
        {
        
        
          if(!this.validation.validate())return void(0);
            formdata = xoad.html.exportForm('user_edit');            
            this.connector.execute({save_user :{id:this.current_node,data:formdata}});                        
            this.tree.refreshItem(this.tree.getParentId(this.current_node));                        
            XTR_main.set_result(_lang_common['user_success_saved']);
        
        } ,
        
        
        
        show_new_usergroup:function(_parentId)
        {
            
            
            
            this.connector.execute({show_new_usergroup:true});         
            
            XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'add_user_group',true));
            if(!Object.isUndefined(this.connector.result.roles_schemes))
            {   
             $('roles').update(this.roles_shemes_builder($H(this.connector.result.roles_schemes)));
            }
            
            this.validation = new Validation('group_new',
                {
                immediate: true
                });                                      
        } ,
        
        
        
        save_usergroup:function()
        {
        
            if(!this.validation.validate())return void(0);
            formdata = xoad.html.exportForm('group_new');
            rolesformdata = $H(xoad.html.exportForm('group_roles'));    
            rolesformdata.each
            (
                function(pair)
                    {
                       if(!pair[1]){rolesformdata.unset(pair[0]);}
                    }            
            ) 
            if(this.current_node!=null)
            {id=this.current_node;this.current_node=null;}else{id=false;}
            
            rolesformdata=rolesformdata.toObject();
            
            this.connector.execute({save_usergroup :
                {
                 id:id,     
                 data:formdata,
                 rolesdata:rolesformdata 
                }});
            
            this.tabs.createTabNode(
                {
                id: 't_firstpage',
                name: _lang_common['info'],
                temporal: true
                }, 'top', true);

            this.tree.refreshItem(this.tree.getParentId(this.current_node));            
            this.first_start();
            XTR_main.set_result(_lang_common['group_users_success_saved']);
  
        
        },

       show_edit_user:function(userId)
       {
        this.connector.execute({load_user_data :{userId:userId}});
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name,'edit_user',true));
        
        xoad.html.importForm('user_edit', this.connector.result.user);
         
        this.current_node = userId;
        this.tabs.createTabNode(
            {
            id: 'tedit_user',
            name: _lang_common['editing'],
            temporal: true
            }, 'top', true);
              Validation.add('is-none-user-uniq', _lang_common['user_with_such_name_already_exists'], this.non_uniq_user.bind(this));
            
            this.validation = new Validation('user_edit',
                {
                immediate: true
                });
                
        
       },
       
       
       roles_shemes_builder:function(rs)
        {
          html='<ul class="chk">';   
          rs.each(
          function(pair)
          {                                                                             
          html+='<li><input type="checkbox" id="_'+pair[0]+'" name="_'+pair[0]+'" value="'+pair[0]+'"> '+pair[1]+'</li>';                         
          }
                                    );                       
                        html+='</ul>';   
                        return html;
        },
          
        get_permissions:function(selector)
        {
                role_id=e.options[e.selectedIndex].value;
                this.connector.execute({get_permissions:true});            
        },
        
        
       switch_permissions:function(id,cid,state)
       {
         
          this.connector.execute({set_permission:{attribute:cid,scheme_id:id,module:this.perms_module,obj_id:this.perms_id,state:state}});             
           
           
       },
       
       show_permissions:function(id,module)
        {
            this.permission_win = XTR_main.dhxWins.createWindow("permission_win", 20, 10, 650, 330);
            this.permission_win.centerOnScreen();
            this.permission_win.setText(_lang_common['permissions']);

            this.perms_id=id;
            this.perms_module=module;
            
                    this.gridlist= this.permission_win.attachGrid();
                    
                    this.gridlist.setImagePath("xres/ximg/grid/imgs/");
                    this.gridlist.setHeader('id,scheme,read,write,delete,deep');
                    this.gridlist.setInitWidths("70,*,70,70,70,70");
                    this.gridlist.attachEvent("onCheckbox",this.switch_permissions.bind(this)); 
                    this.gridlist.setColAlign("left,left,center,center,center,center");                    
                    this.gridlist.setColTypes("ro,ro,ch,ch,ch,ch");
                    this.gridlist.setColSorting("int,str,str,str,str,str");
                    this.gridlist.init();
                    this.gridlist.setSkin("modern");
               
               
                this.connector.execute({show_permissions:{id:id,module:module}});            
                
                if(this.connector.result)
                {
                    this.gridlist.parse(this.connector.result.data_set,"xjson")
                }
            
             
        },
          
       show_edit_usergroup:function(id)
       {
            
            this.cuurent_node=id;
            
            this.connector.execute({show_edit_group :{id:id}});
            XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name,'add_user_group',true)); 
            
            if(!Object.isUndefined(this.connector.result.roles_schemes))
            {   
             $('roles').update(this.roles_shemes_builder($H(this.connector.result.roles_schemes)));
             xoad.html.importForm('group_new', this.connector.result.group_data);
             xoad.html.importForm('group_roles', this.connector.result.group_roles.Roles);
            }
            
            this.current_node = id;
            this.tabs.createTabNode(
                {
                id: 'tedit_user',
                name: _lang_common['editing'],
                temporal: true
                }, 'top', true);

                
          this.validation = new Validation('group_new',
                            {
                            immediate: true
                            }); 
       
       },

       


first_start: function()
        {
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'users_first',true));
        this.tabs.makeActiveById('t_firstpage');
        },
        

build_interface: function()
        {
        
        toggle_main_menu(true);     
        if(!this.tree)
        {            
            menu = new dhtmlXMenuObject();
            menu.renderAsContextMenu();
            menu.addNewChild(menu.topId, 0, "add_user",_lang_fusers['new_user'], false,'','',this.show_new_user.bind(this));           
            menu.addNewChild(menu.topId, 0, "add_category",_lang_common['add_category'], false,'','', this.show_new_usergroup.bind(this));                       
            menu.addNewChild(menu.topId, 0, "refresh", _lang_common['refresh'], false,'','',function(id,zid){this.tree.refreshItem(zid);}.bind(this));
            menu.addNewChild(menu.topId, 0, "delete",_lang_common['delete'], false,'','',this.delete_obj.bind(this));                           
        
                                   
            $('tp-tree-window').appendChild(new Element('div', {id : this.module_name + "_treebox",className : 'treebox'}));
            
            this.tree = new dhtmlXTreeObject(this.module_name + "_treebox",'auto', 'auto', "0", "xres/ximg/tree/");            
            this.tree.setImagePath("/xres/ximg/green/");
            this.tree.setDataMode("json");
            this.tree.setOnDblClickHandler(this.tree_object_clicked.bind(this));     
            this.tree.enableMultiselection(1);             
            this.tree.enableContextMenu(menu);
            this.tree.setXMLAutoLoading("tree_xml_server.php?tree=users_container");        
            this.tree.loadJSON("tree_xml_server.php?tree=users_container&id=0",function(){
                this.tree.refreshItem(1);                  
            }.bind(this));

        }else{
            
            $(this.module_name+"_treebox").show();        
        }

        //закладки
        var oTabs =
            [
            {
            id: 't_firstpage',
            name:_lang_common['info'],
            temporal: true
            },
            {
            id: 't_addpage',
            name:_lang_common['new_user'],
            callback: this.show_new_user.bind(this)
            },
            {
                id: 't_addgroup',
                name: _lang_common['add_group'],
                callback: this.show_new_usergroup.bind(this) 
            },
            
            {
                id: 't_roles',
                name: _lang_users['schemes_roles'],
                callback: this.roles_table.bind(this) 
            }            

            ]
        
            this.tabs = new XTRFabtabs('bookmarks', oTabs);                    
     
                //this.connector.execute({get_tree_inheritance : true});        
                //this.tree.setInheritanceArr(this.connector.result.tree_inheritance);
                this.first_start();


        },

        
        save_role:function()
        {
            if(!this.validation.validate())return void(0);
               
             formdata = xoad.html.exportForm('roles_editor');
             maccess = xoad.html.exportForm('maccess');
             
             if(!Object.isUndefined(this.current_role))
               {
                actionList={save_role :{formdata:formdata,
                                        maccess:maccess,
                                        id:this.current_role}}
                
                this.current_role=null;
               }else{
                
                   actionList    ={save_role :{formdata:formdata,maccess:maccess}}
                   
               }
               this.connector.execute(actionList);   
               this.roles_table();
        
        },
        


        add_role:function()
        {
                
                XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'roles_editor',true));
                this.connector.execute({edit_role:true});
                if(this.connector.result!=null)
                {
                $('maccess').update(this.module_access_builder($H(this.connector.result.maccess)));
                            this.validation = new Validation('roles_editor',
                            {
                            immediate: true
                            });    
        }
        },
        
        
        module_access_builder:function(rs)
        {
          html='<ul class="chk">';   
          rs.each(
          function(pair)
          {                                                                             
                html+='<li><input type="checkbox" name="'+pair[0]+'" value="'+pair[1]+'"> '+module_name(pair[0])+'</li>';                         
          }
          );                       
          html+='</ul>';   
          return html;
        },
                
  
            
        del_role:function(evt)
        {
            this.delete_obj_grid(this.gridlist,'del_role');
        },
        
        edit_role:function(id)
        {
        
            XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'roles_editor',true));
            this.connector.execute({edit_role:{id:id}});
            this.current_role=id;
            $('maccess').update(this.module_access_builder($H(this.connector.result.maccess)));
            xoad.html.importForm('maccess', this.connector.result.maccess);
            xoad.html.importForm('roles_editor', this.connector.result.roles_editor);               
            this.validation = new Validation('roles_editor',
                            {
                                immediate: true
                            }); 
                    
        },
        
        roles_table:function()
    {
        XTR_main.load_module_tpls(this.module_name, new Array('roles_table','roles_editor','roles_add_role')); 
        
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'roles_table',true),'b');
       
                    $('pw-edit').insert('<div id="t-container"></div>');
                    menu = new dhtmlXMenuObject();
                    menu.renderAsContextMenu();
                    menu.addNewChild(menu.topId, 0, "delete",_lang_common['delete'], false,'','',this.del_role.bind(this));
//                    menu.attachEvent("onClick",this.gridclick.bind(this));

                    this.gridlist = new dhtmlXGridObject('t-container');   
                    this.gridlist.selMultiRows = true;
                    this.gridlist.setImagePath("xres/ximg/grid/imgs/");
                    this.gridlist.setHeader('id,'+_lang_users['name_schemes_roles']);
                    this.gridlist.setInitWidths("50,*");
                    this.gridlist.setColAlign("right,left");
                    this.gridlist.attachHeader("#text_filter,#text_filter");
                    this.gridlist.setColTypes("ed,ed");
                    this.gridlist.setColSorting("int,str");
                    this.gridlist.enableAutoWidth(true);
                    this.gridlist.enableDragAndDrop(true);
                    this.gridlist.attachEvent("onRowDblClicked", this.edit_role.bind(this));
                    this.gridlist.enableContextMenu(menu);  
                    this.gridlist.init();
                    this.gridlist.setSkin("modern");
               
                this.connector.execute({roles_table:true});            
                if(this.connector.result)
                {
                    this.gridlist.parse(this.connector.result.data_set,"xjson")
                }
        
         }, 
         get_action_properties:function(_action,prefix)
            {
                    if(!Object.isUndefined(prefix))
                    {                           
                        destination_prefix=prefix;
                    
                    }else{
                    
                        destination_prefix='action'
                    }
                   
                   this.connector.execute({get_action_properties :{Action:_action}});                  
                   
                   if(this.connector.result.action_properties)
                   {
                    $(destination_prefix+'_properties').update(this.connector.lct.action_properties);                               
                   }else{
                    $(destination_prefix+'_properties').update('Свойства отсутствуют');
                   }
                   if(this.connector.result.action_signals)
                   {
                    $(destination_prefix+'_signals').update(this.connector.lct.action_signals);                               
                   }else
                   {
                    $(destination_prefix+'_signals').update('Сигналы отсутствуют');
                   }
                    xoad.html.importForm('tune_actions',this.connector.result.action_properties_form);   
                    if(this.connector.result.xlist)
                    {   
                         var Xpop = new XTRpop('startXXlist',null,{position:'bottom',delta_x:-272,delta_y:-60});
                         gd=this.group_dialog(Xpop.tool_tip.id);
                         gd.connectXpop(Xpop); 
                    } 
                    this.validation=new Validation('tune_actions', {immediate : true}); 

            },
           
                    
        
    destructor: function()
        {
        $(this.module_name+"_treebox").hide();
        this.tabs.destructor();        
        XTR_main.set_rightside();
        }
    });