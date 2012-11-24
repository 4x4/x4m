var XTRcomments = Class.create();
var XTR_comments;

XTRcomments.prototype = Object.extend(new _modulePrototype(), 
    {
	initialize : function() {
		this.destructable = true;
		this.module_name = 'comments';        
        this.tree=null;
        this.connector=null;
        this.leaves_obj_type=new Array('_TREAD'); 
        this.init();

	},
	// m
	tree_object_clicked : function(itemid) {

    object_type = this.tree.getNodeParam(itemid, 'obj_type');
    	switch (object_type) {

			case "_TREAD" :
				this.gridview(itemid);

				break;
			default :
				return false;
		}
	},
	// m

    


	save_tread : function() {
		formdata = xoad.html.exportForm('add_tread');	
		this.connector.execute({save_tread :{data : formdata}});
		if (this.connector.result.saved) {
			this.tree.refreshItem(1);
		    XTR_main.set_result(_lang_comments['tread_success_saved']);   
            this.first_start();
		}
	},

	// m
	show_add_tread : function() 
    {
		
		XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'add_tread',true));
        Validation.add('is-none-uniq', _lang_pages['value_of_this_field_is_not_unique_enter_other_value'],this.non_uniq_entry.bind(this));
        this.validation = new Validation('add_tread',{immediate: true});
		
	},
    
    
    save_edited_tread:function()
    {
        formdata = xoad.html.exportForm('edit_tread');    
        this.connector.execute({save_tread:{id : this.current_node,data : formdata}});
        
    },
    
    non_uniq_entry: function(v)
        {
            this.connector.execute({check_uniq :{basic:$('treadName').value}});
            b = this.connector.result.uniq;
            return b
        },
        
    edit_tread:function(kid,id)
    {        
        
        this.connector.execute({show_edit_tread:{id : id}});
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'edit_tread',true));
        xoad.html.importForm('edit_tread', this.connector.result.tread_data);
        this.current_node = id;
    }
    ,
    
	// m
    del_obj:function()
    {
        this.delete_obj_grid(this.cobjects_gridlist);    
    },
    
    
    del_comment : function() {

        id=this.gridlist.getSelectedRowId(1);
        if(id.length>0)
        {                
            if (!confirm(_lang_comments['you_really_wish_to_remove_this_comment'])) 
            {
                return false;
            }
            
            
            this.connector.execute({delete_comments:{id :id}});
            if (this.connector.result.deleted)
            {
               this.gridlist.deleteSelectedRows();
            }
        }
    },
    
    switch_comment :function(id,cid,state) 
    {
        this.connector.execute({switch_comment:{id : id,state:state}});
    
    },

    doOnCellEdit:function(stage, rowId, cellInd)
    {
            if (stage == 2) 
            {
                var cellObj = this.gridlist.cellById(rowId, cellInd);
                
                if(cellInd==3)
                {
                    this.connector.execute({save_comment_part:{part:'Header',id:rowId,text:cellObj.getValue()}});                
                }
                
                if(cellInd==4)
                {
                    this.connector.execute({save_comment_part:{part:'Message',id :rowId,text:cellObj.getValue()}});                
                }
            }
            return true;    
    },          
          
    view_comments_external:function(id,module)
    {
        this.connector.execute({get_comment_by_module:{id:id,module:module}});                
        
        if(this.connector.result.id)
        {
            this.view_comments(this.connector.result.id);
        }
        
    },


    new_comments:function(id)
    {
                       XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'show_tread',true),'b');
                        menu = new dhtmlXMenuObject();
                        menu.renderAsContextMenu();
                        menu.addNewChild(menu.topId, 0, "delete",_lang_common['delete'], false,'','',this.del_comment.bind(this));
                        
                        this.gridlist = new dhtmlXGridObject('t-container');   
                        this.gridlist.selMultiRows = true;
                        this.gridlist.setImagePath("xres/ximg/grid/imgs/");
                        this.gridlist.setHeader('id,'+_lang_common['date']+','+_lang_comments['comment_object']+','+_lang_comments['UserName']+
                        ','+_lang_comments['Header']+','+_lang_comments['Message']+','+_lang_common['active']);
                        
                        this.gridlist.setInitWidths("70,80,100,150,200,*,70");
                        this.gridlist.setColAlign("center,center,center,center,center,left");
                        this.gridlist.attachEvent("onCheckbox",this.switch_comment.bind(this));  
                        this.gridlist.attachHeader("#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#select_filter");
                        this.gridlist.setSelectFilterLabel(5,_select_filter_yes_no);
                        this.gridlist.setColTypes("ro,ro,ro,ro,ed,txt,ch");
                        this.gridlist.setColSorting("int,date_rus,,str,str,str,str");
                        this.gridlist.enableAutoWidth(true);
                        this.gridlist.attachEvent("onEditCell", this.doOnCellEdit.bind(this));
                        this.gridlist.enableContextMenu(menu);  
                        this.gridlist.init();
                        this.gridlist.setSkin("modern");

                        this.connector.execute({new_comments_table:true});            
                        if(this.connector.result.data_set)
                        {
                           this.gridlist.parse(this.connector.result.data_set,"xjson")
                        }
    },         
    
    view_comments:function(id)
    {
                        this.comments_win= XTR_main.dhxWins.createWindow("comments", 20, 10, 1100, 730);
                        this.comments_win.centerOnScreen();
                        this.comments_win.setText(_lang_comments['comments_list']);
                        this.comments_win.attachHTMLString(XTR_main.get_tpl(this.module_name, 'view_comments',true));
            
            
                        menu = new dhtmlXMenuObject();
                        menu.renderAsContextMenu();
                        menu.addNewChild(menu.topId, 0, "delete",_lang_common['delete'], false,'','',this.del_comment.bind(this));
                        
                        
                        this.gridlist = this.comments_win.attachGrid();
                        this.gridlist.selMultiRows = true;
                        this.gridlist.setImagePath("xres/ximg/grid/imgs/");
                        this.gridlist.setHeader('id,'+_lang_common['date']+','+_lang_comments['UserName']+','+_lang_comments['Header']+','+
                        _lang_comments['Message']+','+_lang_common['active']);
                        this.gridlist.setInitWidths("70,100,150,200,*,70");
                        this.gridlist.setColAlign("center,center,center,center,left");
                        this.gridlist.attachEvent("onCheckbox",this.switch_comment.bind(this));  
                        this.gridlist.attachHeader("#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#select_filter");
                        this.gridlist.setSelectFilterLabel(5,_select_filter_yes_no);
                        this.gridlist.setColTypes("ro,ro,ro,ed,txt,ch");
                        this.gridlist.setColSorting("int,date_rus,str,str,str");
                        this.gridlist.enableAutoWidth(true);
                        this.gridlist.attachEvent("onEditCell", this.doOnCellEdit.bind(this));

    
                        
                        //this.gridlist.enablePaging(true, 30, 8, "pagingArea", true, "recinfoArea");
                        
                        //this.gridlist.attachEvent("onRowDblClicked", this.show_edit_news.bind(this));
                        //this.gridlist.attachEvent("onCheckbox",this.switch_news.bind(this)); 
                        this.gridlist.enableContextMenu(menu);  
                        this.gridlist.init();
                        this.gridlist.setSkin("modern");

                        this.connector.execute({comments_table:{id:id}});            
                        if(this.connector.result.data_set)
                        {
                           this.gridlist.parse(this.connector.result.data_set,"xjson")
                        }
    },

    switch_cobject :function(id,cid,state) 
    {
        this.connector.execute({switch_cobject:{id : id,state:state}});
    
    },
            
    gridview:function(id)
        {

                   
        
           
                    this.tabs.createTabNode(
                        {
                        id: 'tedit_group',
                        name: _lang_common['editing'],
                        temporal: true
                        }, 'top', true);
                    
                    menu = new dhtmlXMenuObject();
                    menu.renderAsContextMenu();
                    menu.addNewChild(menu.topId, 0, "delete",_lang_common['delete'], false,'','',this.del_obj.bind(this));
               
                   // menu.attachEvent("onClick",this.view_comments.bind(this));
       
                    this.cobjects_gridlist = new dhtmlXGridObject('t-container');   
                    this.cobjects_gridlist.selMultiRows = true;
                    this.cobjects_gridlist.setImagePath("xres/ximg/grid/imgs/");
                    this.cobjects_gridlist.setHeader('id,'+_lang_comments['comment_object']+','+_lang_comments['module']+','+_lang_common['active']);
                    this.cobjects_gridlist.setInitWidths("100,*,150,90");
                    this.cobjects_gridlist.setColAlign("right,left,left,left");
                    this.cobjects_gridlist.attachHeader("#text_filter,#text_filter,#text_filter,#text_filter");
                    this.cobjects_gridlist.setColTypes("ro,ro,ro,ch");
                    this.cobjects_gridlist.attachEvent("onCheckbox",this.switch_cobject.bind(this));  
                    this.cobjects_gridlist.setColSorting("int,str,str,int");
                    this.cobjects_gridlist.enableAutoWidth(true);
                    this.cobjects_gridlist.setMultiLine(false);
                    
                    this.cobjects_gridlist.attachEvent("onRowDblClicked", this.view_comments.bind(this));
                    this.cobjects_gridlist.enableContextMenu(menu);  
                    this.cobjects_gridlist.init();
                    this.cobjects_gridlist.setSkin("modern");
               
                    this.connector.execute({cobject_table:{id:id}});            
                    if(this.connector.result)
                    {
                        this.cobjects_gridlist.parse(this.connector.result.data_set,"xjson")
                    }
        },  
        
        
        
	first_start : function() 
    {
	    XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'new_comments',true))
		this.tabs.makeActiveById('t_new_comments');
	},

	// m
	build_interface : function() {

        toggle_main_menu(true);
        
		if (!this.tree){
            
            
            menu = new dhtmlXMenuObject();
            menu.renderAsContextMenu();
            menu.addNewChild(menu.topId, 0, "delete",_lang_common['delete'], false,'','',this.delete_obj.bind(this));                           
            menu.addNewChild(menu.topId, 0, "edit",_lang_common['edit'], false,'','',this.edit_tread.bind(this));                           
            menu.addNewChild(menu.topId, 0, "refresh", _lang_common['refresh'], false,'','',function(id,zid){this.tree.refreshItem(zid);}.bind(this));            
            menu.addNewChild(menu.topId, 0, "add_tread",_lang_comments['add_tread'], false,'','', this.show_add_tread.bind(this));                       
            tree_node = new Element('div',{id: this.module_name+"_treebox",className:'treebox'});
            $('tp-tree-window').appendChild(tree_node);
            
            this.tree = new dhtmlXTreeObject(this.module_name+"_treebox", 'auto', 'auto', "0");
            this.tree.enableDragAndDrop(1);                        
            this.tree.setImagePath("/xres/ximg/green/");          
            this.tree.enableContextMenu(menu);       
            this.tree.setOnDblClickHandler(this.tree_object_clicked.bind(this));        
            this.tree.setDragHandler(this.on_tree_drag.bind(this)); 
            this.tree.setDataMode("json");
            
            this.tree.setXMLAutoLoading("tree_xml_server.php?tree=comments_container");
            this.tree.loadJSON("tree_xml_server.php?tree=comments_container&id=0",function(){
                    this.tree.refreshItem(1);                  
                }.bind(this));        
            this.tree.setDragHandler(this.on_tree_drag.bind(this));
            //this.connector.execute({get_tree_inheritance :true});
             //  this.tree.setInheritanceArr(this.connector.result.tree_inheritance);            
            }
            else{
                
                $(this.module_name+"_treebox").show();
            
            }
                
		var oTabs = [{
			id : 't_new_comments',
			name : _lang_comments['new_comments'],
			callback : this.new_comments.bind(this)
            
		}, {
			id : 't_add_tread',
			name : _lang_comments['add_tread'],
			callback : this.show_add_tread.bind(this)
		}
		]

		this.tabs = new XTRFabtabs('bookmarks', oTabs);
		XTR_main.set_rightside();
        this.first_start();
		   

	},

	destructor : function() {

		$(this.module_name + "_treebox").hide();
		this.tabs.destructor();
		XTR_main.set_rightside('');
	}


});