var XTRnews = Class.create();

var XTR_news;

XTRnews.prototype = Object.extend(new _modulePrototype(), 
    {
	initialize : function() {
		this.destructable = true;
		this.module_name = 'news';        
        this.tree=null;
        this.connector=null;
        this._extra={show_news_interval:'Category'};
        this.leaves_obj_type=new Array('_NEWS'); 
        this.init();

	},
	// m
	tree_object_clicked : function(itemid) {
	
        object_type = this.tree.getNodeParam(itemid, 'obj_type');

		switch (object_type) {
                                                  
			case "_NEWSGROUP" :
				this.show_category(itemid);

				break;
			default :
				return false;
		}
	},
	// m
	
    del_news : function() {
		id=this.gridlist.getSelectedRowId(1);
        if(id.length>0)
        {                
            if (!confirm(_lang_news['you_really_wish_to_remove_this_news'])) 
            {
			    return false;
		    }
            
            this.connector.execute({delete_news:{id :id}});
		    if (this.connector.result.is_del)
            {
			   this.gridlist.deleteSelectedRows();
		    }
        }
	},

	switch_news : function(id,cid,state) 
    {
        this.connector.execute({switch_news:{id : id,state:state}});	
    }

	,
	show_edit_news : function(id) 
    {
     
        this.current_node =id;
		this.connector.execute({show_edit:{id : this.current_node}});
        Validation.add('is-uniq', _lang_news['link_is_not_uniq'],this.non_uniq_entry.bind(this));
            
		XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'edit_news',true));
		xoad.html.importForm('edit_news', this.connector.result.news_data);
        xoad.html.importForm('add_tunes', this.connector.result.news_data);
        new Control.DatePicker('news_date', { locale:'ru',use24hrs: true,timePicker: true, timePickerAdjacent: true});
        this.validation = new Validation('edit_news',{immediate: true});
        this.validation = new Validation('add_tunes',{immediate: true});
            


	}
    ,
    show_news_interval_extra:function(params)
    {
        this.show_category(params.Category);        
    },
    
    view_comments:function(id,kid)
    {
        XTR_main.load_module('comments','silent');    
        XTR_comments.view_comments_external(kid,'news');
    },
    
    
	show_category : function(item_id) {
		
		XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'news_first'),'b');
		this.tabs.createTabNode({
					id : 'tedit_page',
					name : _lang_news['group_news'],
					temporal : true
				}, 'top', true);

                    menu = new dhtmlXMenuObject();
                    menu.renderAsContextMenu();
                    menu.addNewChild(menu.topId, 0, "delete",_lang_common['delete'], false,'','',this.del_news.bind(this));
                    menu.addNewChild(menu.topId, 0, "view_comments",_lang_common['view_comments'], false,'','',this.view_comments.bind(this));
                    
                        $('pw-edit').insert('<div id="t-container"></div><div id="pagingArea"></div>');
                        this.gridlist = new dhtmlXGridObject('t-container');   
                        this.gridlist.selMultiRows = true;
                        this.gridlist.setImagePath("xres/ximg/grid/imgs/");
                        this.gridlist.setHeader('id,'+_lang_common['date']+','+_lang_news['news']+','+_lang_common['active']);
                        this.gridlist.setInitWidths("70,120,*,70");
                        this.gridlist.setColAlign("center,center,left,center");
                        this.gridlist.attachHeader("#text_filter,#text_filter,#text_filter,#select_filter");
                        this.gridlist.setColTypes("ro,ro,ro,ch");
                        this.gridlist.setColSorting("int,date_rus,str,str");
                        this.gridlist.enableAutoWidth(true);
                        this.gridlist.setMultiLine(true);
                        this.gridlist.enablePaging(true, 30, 8, "pagingArea", true, "recinfoArea");
                        this.gridlist.attachEvent("onRowDblClicked", this.show_edit_news.bind(this));
                        this.gridlist.attachEvent("onCheckbox",this.switch_news.bind(this)); 
                        this.gridlist.enableContextMenu(menu);  
                        this.gridlist.init();
                        this.gridlist.setSelectFilterLabel(3,_select_filter_yes_no);
                        this.gridlist.enableDragAndDrop(true);
                        this.gridlist.setSkin("modern");
                        this.gridlist.rowToDragElement = function(id) 
                        {
                                if (this.cells(id, 2).getValue() != "");
                                    return this.cells(id, 2).getValue() + "/" + this.cells(id, 1).getValue();
                                    return this.cells(id, 1).getValue();
                                    }

    
                        this.gridlist.gridToTreeElement = function(tree,fakeID,gridID,treeID)
                        {
                            
                                this.connector.execute({change_news_category:{anc:treeID,id:gridID}});            
                                
                                if(this.connector.result.changed){
                                    
                                    XTR_main.set_result(_lang_news['news_moved']);
                                    return true;}else{
                                        
                                        return -1;}
                            
                        }.bind(this);


                    this.connector.execute({news_table:{id:item_id}});            
                    
                    if(this.connector.result.data_set)
                    {
                       this.gridlist.parse(this.connector.result.data_set,"xjson")
                    }
	},

	show_add_group : function() {

		XTR_main.load_module_tpls(this.module_name, new Array('add_category'));
		XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name,'add_category'));
		
	},

	save_group : function() {
		formdata = xoad.html.exportForm('add_category');	
		this.connector.execute({save_group :{group_data : formdata}});
		if (this.connector.result.saved) {
			this.tabs.createTabNode({
						id : 't_firstpage',
						name : _lang_common['info'],
						temporal : true
            }, 'top', true);

			this.tree.refreshItem(1);
			$('pw-edit').update(_lang_common['group_success_saved']);
			new Effect.Highlight($('pw-edit'));
		}
	},

	// m
	show_add_news : function() {
		_parentId = this.tree.getSelectedItemId();
		if ((_parentId > 0)&& (this.tree.getNodeParam(_parentId, 'objtype') != '_NEWSGROUP')) {
			            actionList={add_news :{'parent' : _parentId}};
		                } else {
			            actionList={add_news:true};
		}

        Validation.add('is-uniq', _lang_news['link_is_not_uniq'],
            this.non_uniq_entry.bind(this));
        
		this.connector.execute(actionList);
		XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'add_news',true));
		xoad.html.importForm('add_news', this.connector.result.add_news);

        new Control.DatePicker('news_date', { locale:'ru',use24hrs: true,timePicker: true, timePickerAdjacent: true});

        
        this.validation = new Validation('add_news',
            {
            immediate: true
            });

		
	},
    
    non_uniq_entry: function(v)
        {
            
            this.connector.execute({check_uniq :{basic: v}});
            if(this.connector.result.uniq==false)
            {
                return true    
            }else{
                
                if(this.connector.result.uniq==this.current_node)
                {
                    return true;
                    
                }else
                {
                    return false;
                }
            }
        }
         ,

	save_edited_news : function(){
        
        if(!this.validation.validate())return void(0);
		
        add_tunes = $H(xoad.html.exportForm('add_tunes'));
        formdata = $H(xoad.html.exportForm('edit_news'));
		formdata.set('id', this.current_node);
        formdata=formdata.merge(add_tunes);
        
		this.connector.execute({save_news :{news_data : formdata.toObject()}});
                    
                    
        
		if (this.connector.result.saved) {
			this.show_category(formdata.get('ctg_id'));
		}

	},

    edit_category:function(b,id)
    {        
        XTR_main.load_module_tpls(this.module_name, new Array('edit_news_category'));
        this.connector.execute({show_edit_category:{id : id}});
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'edit_news_category'));
        xoad.html.importForm('edit_news_category', this.connector.result.category_data);
        this.current_node = id;
    }
    ,
    
    save_edited_category:function()
    {                
        this.connector.execute({save_edited_category:{id :this.current_node,data:xoad.html.exportForm('edit_news_category') }});
           if(this.connector.result.is_save){
        XTR_main.set_result(_lang_common['category_success_saved']);
        }
    }
    ,
    
	save_news : function() {
        if(!this.validation.validate())return void(0); 
		formdata = $H(xoad.html.exportForm('add_news'));
        add_tunes = $H(xoad.html.exportForm('add_tunes'));
        formdata=formdata.merge(add_tunes);
		this.connector.execute({save_news:{news_data : formdata.toObject()}});
		
        if (this.connector.result.saved) 
        {
			this.show_category(formdata.get('ctg_id'));
		}
	},
	// m
	first_start : function() 
    {		
		XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'news_first',true));
		this.tabs.makeActiveById('t_firstpage');
	},

    rss_tunes:function()
    {
        
    },
    
	// m
	build_interface : function() {

        toggle_main_menu(true);
        
        if (!this.tree) {
            menu = new dhtmlXMenuObject();
            menu.renderAsContextMenu();
            menu.addNewChild(menu.topId, 0, "add_news",_lang_news['add_news'], false,'','',this.show_add_news.bind(this));           
            menu.addNewChild(menu.topId, 0, "add_category",_lang_common['add_category'], false,'','',this.show_add_group.bind(this));                       
            menu.addNewChild(menu.topId, 0, "edit_category",_lang_news['edit_category'], false,'','',this.edit_category.bind(this));           
            menu.addNewChild(menu.topId, 0, "delete",_lang_common['delete'], false,'','',this.delete_obj.bind(this));                           
            menu.addNewChild(menu.topId, 0, "rss",_lang_news['rss_tunes'], false,'','',this.rss_tunes.bind(this));                           
            menu.addNewChild(menu.topId, 0, "refresh", _lang_common['refresh'], false,'','',function(id,zid){this.tree.refreshItem(zid);}.bind(this));            
            tree_node = new Element('div', {id : this.module_name + "_treebox",className : 'treebox'});            
           
            $('tp-tree-window').appendChild(tree_node);
            this.tree = new dhtmlXTreeObject(this.module_name + "_treebox",'auto', 'auto', "0", "xres/ximg/tree/");            
            this.tree.setImagePath("/xres/ximg/green/");
            this.tree.setDataMode("json");
            this.tree.setOnDblClickHandler(this.tree_object_clicked.bind(this));     
            this.tree.enableMultiselection(1); 
            this.tree.enableDragAndDrop(true);             
            this.tree.enableContextMenu(menu);
            this.tree.setXMLAutoLoading("tree_xml_server.php?tree=news_container");        
            
            this.tree.loadJSON("tree_xml_server.php?tree=news_container&id=0",function(){
                this.tree.refreshItem(1);                  
            }.bind(this));
            this.connector.execute({get_module_options:true});
            this.options=this.connector.result.options;
        } else {
            $(this.module_name + "_treebox").show();
        }
        

        var oTabs = [{
            id : 't_firstpage',
            name : _lang_common['info'],
            temporal : true
        }, {
            id : 't_addnews',
            name : _lang_news['add_news'],
            callback : this.show_add_news.bind(this)
        }, {
            id : 't_addgroup',
            name : _lang_common['add_group'],
            callback : this.show_add_group.bind(this)
        }

        ]

        this.tabs = new XTRFabtabs('bookmarks', oTabs);
        XTR_main.set_rightside_eform();
        
        this.first_start();


    },

	destructor : function() {

		$(this.module_name + "_treebox").hide();
		this.tabs.destructor();

		XTR_main.set_rightside();
	}


});


        