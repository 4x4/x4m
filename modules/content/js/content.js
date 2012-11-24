var XTRcontent = Class.create();
var XTR_content;

XTRcontent.prototype = Object.extend(new _modulePrototype(), 
    {

    initialize: function()
        {
        this.destructable = true;
        this.module_name='content';
        this.current_edit_id=null;
        this.leaves_obj_type=new Array('_CONTENT');  
        this.tree=null;
        this._extra={show_news_interval:'Category'};
        this.init();
        
        },
        
        
        
        
    action_by_obj_type:function(itemid,object_type)
    {   
        
        switch (object_type)
            {
            case "_CONTENT":
                this.show_edit_content(itemid);

                break;

            case "_CONTENTGROUP":
                this.edit_category(itemid);

            break;            

            default: return false;
            }
    },
    //m
    tree_object_clicked: function(itemid)
        {
            this.current_object_type = this.tree.getRowAttribute(itemid,"obj_type");                     
            this.action_by_obj_type(itemid,this.current_object_type);
        },
    //m
    add_category:function()
    {
            
            XTR_main.load_module_tpls(this.module_name, new Array('add_category'));                        
            this.connector.execute({add_category : true});         
            XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'add_category'));                                
            xoad.html.importForm('add_category',this.connector.result.category_data); 
            this.validation = new Validation('add_category',{immediate: true});            
            

    },
    save_edited_category:function()
    {  
        if(!this.validation.validate())return void(0);

            formdata=xoad.html.exportForm('edit_category');
            add_tunes=xoad.html.exportForm('add_tunes');
            outerLink=xoad.html.exportForm('OuterLink');   
            this.connector.execute({save_edited_category:{id:this.current_node, data:formdata, tunes:add_tunes, outerLink:outerLink}});    
            if(this.connector.result.is_saved)
            {                                                  
                            this.tree.refreshItem(1);                        
                            
            this.tabs.createTabNode(
                    {
                    id: 't_firstpage',
                    name: _lang_common['info'],
                    temporal: true
                    }, 'top', true);
                    
                    XTR_main.set_result(_lang_common['group_success_saved']);  
                       }
                
    },
    
    get_extra_field:function(action)
    {
        
        
    },
    
    save_category:function()
    {  
      
        if(!this.validation.validate())return void(0); 
        //XTR_main.show_loading();
        formdata=(xoad.html.exportForm('add_category'));        
        this.connector.execute({save_category:{data:formdata}});
        
        if(this.connector.result.is_saved)
         {               
                //XTR_main.hide_loading();                              
                this.tree.refreshItem(1);                        
                this.tabs.createTabNode({id: 't_firstpage',
                                            name: _lang_common['info'],
                                            temporal: true
                                            }, 'top', true);
                   
                   this.first_start();
                   XTR_main.set_result(_lang_common['group_success_saved']);  
                        
                }                        
               
               
    },
    
    edit_category:function(itemId)
    {
            XTR_main.load_module_tpls(this.module_name, new Array('edit_category','outer_link'));                        
            this.connector.execute({load_category :{id:itemId}});        
            this.current_node = itemId;   
            this.tabs.createTabNode({id: 't_edit',name: _lang_common['editing'],temporal: true}, 'top', true);
            
            XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'edit_category'));                  
            //$('slotzcontainer').update((XTR_main.get_tpl(this.module_name, 'outer_link')));
                                                                                           
            xoad.html.importForm('edit_category', this.connector.result.category_data);
            xoad.html.importForm('OuterLink', this.connector.result.outerLink); 
            xoad.html.importForm('add_tunes', this.connector.result.tunes); 
            this.validation = new Validation('edit_category',{immediate: true});    
    },     
    

    //m
    add_content: function()
        {            
                XTR_main.load_module_tpls(this.module_name, new Array('add_content'));
     
                this.current_node = this.tree.getSelectedId();
                params={group_id:this.current_node};           
             
                this.connector.execute({add_content:params});            
                XTR_main.set_rightside_eform(XTR_main.get_tpl('content', 'add_content'));                                                       
                xoad.html.importForm('add_content', this.connector.result.content_data); 
                $('innerfields').update(this.connector.result.fields);                                     
                this.validation = new Validation('add_content',{immediate: true});             
        }
        ,
        
        
    show_content_extra:function(params)
    {
        this.show_edit_content(params.contentId);
    },
        //m                
    show_edit_content: function(itemId)
        {    
            this.tabs.createTabNode({id: 't_edit',name: _lang_common['editing'],temporal: true}, 'top', true);            
            actionList = new Array();
                //recieve tpl from server        
            if (!XTR_main.tpl_exists(this.module_name, 'edit_content'))
                {
                    actionList.tpl_content_edit = true;            
                }
                 actionList.load_content={content_id:itemId};
            
                this.connector.execute(actionList);            
                this.current_node = itemId;               
                XTR_main.cache_mass_tpl('content',$H(this.connector.lct));                              
                XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'edit_content'));                  
                xoad.html.importForm('edit_content',this.connector.result.content_data);
                xoad.html.importForm('add_tunes',this.connector.result.tunes);
                $('innerfields').update(this.connector.result.fields);       
                
                xoad.html.importForm('inner_fields',this.connector.result.content_data.fields_data);                                      
                this.current_edit_id=itemId;            
                //validation part
                this.validation = new Validation('edit_content', {immediate : true});

             
        },
        
        
       change_category:function(sid)
       {
            this.connector.execute({load_tpl:{id:sid}});
            select_option($('Template'),this.connector.result.default_tpl);   
       }
       ,
       
        
      change_fields:function(selected_tpl)
      {      
        inner_cache=xoad.html.exportForm('inner_fields');      
        this.connector.execute({parse_content_tpl:{tpl_file:selected_tpl,isruntime:true}});        
        $('innerfields').update(this.connector.result.fields);    
        
        inner_cache=xoad.html.importForm('inner_fields',inner_cache);        
        
      
      },  

       save_content:function()
         {
   
        if(!this.validation.validate())return void(0);
          inner= xoad.html.exportForm('inner_fields');                
          main=  xoad.html.exportForm('add_content');
          this.connector.execute({save_content:{main:main,inner_fields:inner}})
          if(this.connector.result.is_saved)
                        {
                         this.tree.refreshItem(main.category);
                         this.tabs.createTabNode(
                         {
                            id: 't_firstpage',
                            name: _lang_common['info'],
                            temporal: true
                         }, 'top', true)
                            
                            this.first_start();
                            XTR_main.set_result(_lang_content['article_success_saved']);  
                        }
          
            
       },
    
 
    save_edited_content:function()
    {
        inner=  xoad.html.exportForm('inner_fields');                
        main=  xoad.html.exportForm('edit_content');
        add_tunes=xoad.html.exportForm('add_tunes');
        
        
        this.connector.execute({save_edited_content :{main:main, tunes:add_tunes, inner_fields:inner, id:this.current_edit_id}});
                           if(this.connector.result.is_saved){
                                         this.tabs.createTabNode(
                                        {id: 't_firstpage',
                                        name: _lang_common['info'],
                                        temporal: true
                                        }, 'top', true);
                        }
                        this.tree.refreshItem(this.tree.getParentId(this.current_edit_id));  
                        XTR_main.set_result(_lang_content['article_success_saved']);  
               
                
       

    },
    
    dynXLS:function(id)
     {
                                            
            this.connector.execute({content_table:{id:id}});            
     
            if(this.connector.result)
                {
                    this.tree.json_dataset=this.connector.result.data_set;
                }
         return true;
     },
     
     
      generate_link:function()
      {
          trans_rus_lat('Name','basic',250,true,true);
      },
        
    
    //m
    build_interface: function()
        {
        
            
    toggle_main_menu(true);    
      if(!this.tree)
        {
            tree_node = new Element('div',{id: this.module_name+"_treebox",className:'gridbox'});
            $('tp-tree-window').appendChild(tree_node);
            menu = new dhtmlXMenuObject();
            menu.renderAsContextMenu();
            menu.addNewChild(menu.topId, 0, "refresh", _lang_common['refresh'], false,'','',function(id,zid){this.tree.refreshItem(zid);}.bind(this));                           menu.addNewChild(menu.topId, 0, "delete",_lang_common['delete'], false,'','',this.delete_obj_treegrid.bind(this));                           
            menu.addNewChild(menu.topId, 0, "copyn",_lang_common['copy'], false,'','',this.copy_obj_treegrid.bind(this));                           
            menu.addNewChild(menu.topId, 0, "pasten",_lang_common['paste'], false,'','',this.paste_obj_treegrid.bind(this));                           
            
            menu.addNewChild(menu.topId, 0, "add_content",_lang_content['add_article'], false,'','',this.add_content.bind(this));
            menu.addNewChild(menu.topId, 0, "add_group", _lang_common['add_group'], false,'','',this.add_category.bind(this));
             menu.addNewChild(menu.topId, 0, "component_location", _lang_common['component_location'], false,'','',this.get_component_location.bind(this));             
            
            this.tree = new dhtmlXGridObject(this.module_name+"_treebox");
            this.tree.selMultiRows = true;
            this.tree.imgURL = "/xres/ximg/green/";
            this.tree.setHeader(_lang_content['content_groups']+','+_lang_content['templates']);
            this.tree.setInitWidths("280,*");
            this.tree.setColAlign("left,left");
            this.tree.setColTypes("tree,ed");
            this.tree.enableDragAndDrop(true);
            this.tree.enableEditEvents(false,false,true);
            this.tree.attachEvent("onDrag",this.on_treegrid_drag.bind(this));
            this.tree.setDragBehavior('complex');
            this.tree.enableMultiselect(true);
            this.tree.enableContextMenu(menu);
            this.tree.attachHeader("#text_search,#text_search");
            this.tree.init();
            this.tree.kidsXmlFile=1;
            this.tree.attachEvent("onDynXLS",this.dynXLS.bind(this));
            this.tree.setSkin("dhx_skyblue");
            this.tree.attachEvent("onRowDblClicked", this.tree_object_clicked.bind(this));
            this.connector.execute({content_table:{id:0}});            
            if(this.connector.result)
                    {                                       
                        this.tree.parse(this.connector.result.data_set,"xjson")
                    }


             this.connector.execute({get_tree_inheritance:true})
             //this.tree.setInheritanceArr(this.connector.result.tree_inheritance); 
             this.tree.refreshItem(1);           
        }
        
         var oTabs = [					
                    {id:'t_firstpage',name: _lang_common['info'],temporal:true},
                    {id:'t_addcontent',name: _lang_content['add_article'], callback: this.add_content.bind(this)},
                    {id:'t_addgroup',name: _lang_common['add_group'], callback: this.add_category.bind(this)}                 
					
				]
          
         this.tabs=new XTRFabtabs('bookmarks',oTabs);          
         
         if(this.tree)
         {
              $(this.module_name+"_treebox").show();     
               this.state_restore();
         }
        
            
         
        },
        get_loc_double_click:function(id)
        {
            
            XTR_main.load_module('pages','normal'); 
            pages=XTR_main.called_modules['XTR_pages']; 
            pages.action_by_obj_type(id,'_PAGE');  
        } ,
        
           get_component_location:function(mid,id)
           {
                         XTR_main.set_rightside_eform();
                        $('pw-edit').update(); $('pw-edit').className='pw-edit-b';
                        $('pw-edit').insert('<div id="t-container"></div>');
                         this.gridlist = new dhtmlXGridObject('t-container');   
                        this.gridlist.selMultiRows = true;
                        this.gridlist.setImagePath("xres/ximg/grid/imgs/");
                        this.gridlist.setHeader('id,'+_lang_search['title']+','+_lang_search['link']);
                        this.gridlist.setInitWidths("70,500,200");
                        this.gridlist.setColAlign("center,left");
                        this.gridlist.attachEvent("onRowDblClicked", this.get_loc_double_click.bind(this));                                                                                    
                        this.gridlist.attachHeader("#text_filter,#text_filter,#text_filter");
                        this.gridlist.setColTypes("ro,ro,ro");
                        this.gridlist.enableAutoWidth(true);
                        this.gridlist.setMultiLine(true);
 
                        this.gridlist.init();
                        this.gridlist.setSkin("modern");
                        this.connector.execute({get_component_location:{id:id}}); 
                        if(this.connector.result)
                        {
                           this.gridlist.parse(this.connector.result,"xjson");
                        }else{                                   
                            XTR_main.set_result(_lang_common['nothing_found']); 
                        }  
           },
           
        first_start:function()
            {                  
                   XTR_main.load_module_tpls(this.module_name, new Array('content_first'));    
                   XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name,'content_first'));        
                   this.tabs.makeActiveById('t_firstpage');             
            
            },
            
            get_action_properties:function(_action,prefix)
            {
                    defaultAction = null;       
                    if (prefix!='action' && prefix!='secondary') {
                        destination_prefix = 'action';
                        defaultAction = prefix;
                    }
                    else if (prefix=='secondary'){
                        destination_prefix = prefix;
                        defaultAction = null;    
                    }
	            else {
		            destination_prefix = 'action';
            	            defaultAction = null; 	
	            }     
                   
                   this.connector.execute({get_action_properties :{Action:_action}});                  
                   
                   if(this.connector.result.action_properties)
                   {
                    $(destination_prefix+'_properties').update(this.connector.lct.action_properties);                               
                   }else{
                    $(destination_prefix+'_properties').update(_lang_common['options_not_found']);
                   }

                   xoad.html.importForm('tune_actions',this.connector.result.action_properties_form);
                   
                   if(this.connector.result.xlist)
                   {
                     
                      columns=$H({image:' ',name:'Имя'});
                     _columnsHeadersWidth=new Array('20px','150px');
                     _columnsInterpretAs=$H({image:'IMAGE'});
                     _images=$H({
                        group:'xres/ximg/tree/folderClosed.gif',
                        page:'xres/ximg/tree/page.gif'
                        });
 	                   
                        var Xpop = new XTRpop('startXXlist',null,{position:'bottom',delta_x:-272,delta_y:-60});
                         xlist = new XTRxlist(Xpop.tool_tip.id,this.connector,
                       {
                        resultSource:'showContentName',
                        resultIDSource:'contentId',
                        columnsHeaders:columns,
                        tableId:'dialogtable',
                        columnsHeadersWidth:_columnsHeadersWidth,
                        columnsInterpretAs:_columnsInterpretAs,
                        images:_images,
                        className:'dialog-table'
                       }
                       );
                       xlist.connectXpop(Xpop);             
                        
                       }
                    if (defaultAction){this.get_action_properties(defaultAction,'secondary');}
                    this.validation=new Validation('tune_actions', {immediate : true}); 
                                          
                   
            }


  ,      
  destructor:function()
     {
          XTR_main.set_rightside();
          $(this.module_name+"_treebox").hide();   
          this.tabs.destructor();
     }   

    });
