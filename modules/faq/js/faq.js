var XTRfaq = Class.create();
var XTR_faq;


XTRfaq.prototype =Object.extend(new _modulePrototype(), 
    {    
    initialize: function()
        {
        this.destructable = true;
        this.module_name='faq';
        this.current_edit_id=null;
        this.current_node=null;
        this.current_folder=null;
        this.current_foldername=null;
        this.current_group=null;
        this.current_groupname=null;
        
        this.tree=null;
        this.leaves_obj_type=new Array('_FAQGROUP'); 
        this.init();

        },
    //m
    tree_object_clicked: function(itemid)
        {
        object_type = this.tree.getNodeParam(itemid, 'obj_type');
         
        switch (object_type)
            {
            case "_ROOT":
                this.current_node=itemid;
                this.show_edit_root();
                break;
                
                
            case "_FAQ":
                this.current_node=itemid;
                this.show_edit_question(itemid);
                this.current_node=itemid;
                break;

            case "_FAQGROUP":
                this.current_node=itemid;
                this.show_questions(itemid);
                this.current_group=itemid;
                this.current_groupname=this.tree.getItemText(itemid);;
                break;            

            case "_FAQFOLDER":
                this.current_node=itemid;
                this.edit_folder(itemid);
                this.current_folder=itemid;
                this.current_foldername=this.tree.getItemText(itemid);;
                break;
                
                
            default: return false;
            }
        },
    //m

     group_dialog: function()
        {

            columns = $H( { image: ' ', name: _lang_common['name'] });
            _columnsHeadersWidth = new Array('20px', '150px');
            _columnsInterpretAs = $H( { image: 'IMAGE' });
            _images=$H({ group:'xres/ximg/tree/folderClosed.gif', page:'xres/ximg/tree/page.gif' });
            
            if(Object.isUndefined(arguments[0])) { xlist_name="xlist";}else{ xlist_name=arguments[0];}
            if(Object.isUndefined(arguments[1])) {fcall='load_xlist_fuser';}else{fcall=arguments[1];}
            if(Object.isUndefined(arguments[2])) {startwith=1}else{startwith=arguments[2];}
            if(Object.isUndefined(arguments[3])) {anobj='fuser'}else{anobj=arguments[3];}
            if(Object.isUndefined(arguments[4])) {anobj_id='fuser_id'}else{anobj_id=arguments[4];}
            if(Object.isUndefined(arguments[5])) {dial='dialogtable'}else{dial=arguments[5];}
            
            return xlist = new XTRxlist(xlist_name, this.connector,
                {
                    permanent: true,
                    resultSource: anobj,
                    serverCallFunc:fcall,
                    resultIDSource: anobj_id,
                    columnsHeaders: columns,
                    tableId: dial,
                    startWithAncestor: startwith,
                    columnsHeadersWidth: _columnsHeadersWidth,
                    columnsInterpretAs: _columnsInterpretAs,
                    images: _images,
                    className: 'dialog-table',
                    include_root_in_selection:true,
                    usebackoff:1
                });
        },    
 
    non_uniq_entry:function(v)
        {    
            this.connector.execute({check_uniq:{basic:v}});
            
            if(!this.connector.result.uniq){   
                if(this.current_node!=this.connector.result.id)
                {
                    return false; 
                }
            }
                return  true;
        },  
        
    edit_item:function(action, itemid) {
        
        var object_type = this.tree.get_objtype(itemid);
        switch (object_type)
            {
            case "_ROOT":
                this.current_node=itemid;
                this.current_folder=itemid;
                this.current_foldername=this.tree.getItemText(itemid);;                
                this.show_edit_root();
                break;
                
            case "_FAQFOLDER":
                this.current_node=itemid;
                this.edit_folder(itemid);
                this.current_folder=itemid;
                this.current_foldername=this.tree.getItemText(itemid);;
                break;                

            case "_FAQGROUP":
                this.current_node=itemid;
                this.edit_category(itemid,itemid);
                this.current_group=itemid;
                this.current_groupname=this.tree.getItemText(itemid);;
                break;            
                
            case "_FAQ":
                this.current_node=itemid;
                this.show_edit_question(itemid);
                this.current_node=itemid;
                break;
                
                
            default: return false;
            }        
      
    },

/*------------ ROOT*/        

    show_edit_root:function() {
        XTR_main.load_module_tpls(this.module_name, new Array('edit_root'));                        
        this.connector.execute({load_root: {}});         
        this.tabs.createTabNode({id: 't_edit',name: _lang_common['editing'],temporal: true}, 'top', true);
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'edit_root'));                                                   
        xoad.html.importForm('edit_root',this.connector.result.root_data.params); 
        Validation.add('is-uniq', _lang_news['link_is_not_uniq'],this.non_uniq_entry.bind(this));
        this.validation = new Validation('edit_root',{immediate: true});                 
    },   

    save_edited_root:function() {  
        if(!this.validation.validate())return void(0);
        formdata=(xoad.html.exportForm('edit_root'));
        this.connector.execute({save_edited_root :{data:formdata}});
        if(this.connector.result.is_saved) {
            this.tree.refreshItem(0);
            XTR_main.set_result(_lang_common['category_success_saved']);
        }
    },
        
        
/*------------ FOLDER*/

    add_folder:function() {            
        XTR_main.load_module_tpls(this.module_name, new Array('add_folder'));         
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'add_folder')); 
        Validation.add('is-uniq', _lang_news['link_is_not_uniq'],this.non_uniq_entry.bind(this));
        this.current_node = null;   
        this.validation = new Validation('add_folder',{immediate: true});            
    },
    

    save_edited_folder:function(){ 
        if(!this.validation.validate())return void(0);
        formdata=(xoad.html.exportForm('edit_folder'));
        this.connector.execute({save_edited_folder :{id:this.current_folder,data:formdata}});
        if(this.connector.result.is_saved) {
            this.tree.refreshItem(this.getSelectedParent(this.current_folder));                                
            XTR_main.set_result(_lang_common['category_success_saved']);
        }
    },
    
    
    save_folder:function() {  
        if(!this.validation.validate())return void(0); 
        XTR_main.show_loading();        
        formdata=(xoad.html.exportForm('add_folder'));        
        this.connector.execute({save_folder:{data:formdata}})                
        if(this.connector.result.is_saved) {                                                         
            this.tree.refreshItem(1);                                
            XTR_main.set_result(_lang_common['category_success_saved']);                    
        }                        
        XTR_main.hide_loading();
    },
    
    edit_folder:function(itemId) {
        XTR_main.load_module_tpls(this.module_name, new Array('edit_folder'));                        
        this.connector.execute({load_folder: {id:itemId}});         
        this.tabs.createTabNode({id: 't_edit',name: _lang_common['editing'],temporal: true}, 'top', true);
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'edit_folder'));                                                   
        xoad.html.importForm('edit_folder',this.connector.result.folder_data.params); 
        Validation.add('is-uniq', _lang_news['link_is_not_uniq'],this.non_uniq_entry.bind(this));
        this.validation = new Validation('edit_folder',{immediate: true});                 
    },    
        
/*------------ CATEGORY*/        
        
    add_category:function() {            
        XTR_main.load_module_tpls(this.module_name, new Array('add_category'));         
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'add_category')); 
        Validation.add('is-uniq', _lang_news['link_is_not_uniq'],this.non_uniq_entry.bind(this));
        this.current_node = null;
        this.connector.execute({new_category_info: {}});
        xoad.html.importForm('add_category',this.connector.result); 
        if (this.current_folder) {
            xoad.html.importForm('add_category',{"ParentCategory":this.current_foldername, "ParentCategoryId":this.current_folder});
        }
        this.validation = new Validation('add_category',{immediate: true});
        
    },

    edit_category:function(k,itemId) {
        XTR_main.load_module_tpls(this.module_name, new Array('edit_category'));                        
        this.connector.execute({load_category: {id:itemId}});         
        this.current_node = itemId;   
        this.tabs.createTabNode({id: 't_edit',name: _lang_common['editing'],temporal: true}, 'top', true);
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'edit_category'));                                                   
        
        xoad.html.importForm('edit_category',this.connector.result.answer_templates); 
        xoad.html.importForm('edit_category',this.connector.result.category_data.category.params); 
        Validation.add('is-uniq', _lang_news['link_is_not_uniq'],this.non_uniq_entry.bind(this));
        this.validation = new Validation('edit_category',{immediate: true});                 
    },    

    save_edited_category:function() {  
        if(!this.validation.validate())return void(0);
        formdata=(xoad.html.exportForm('edit_category'));
        this.connector.execute({save_edited_category :{id:this.current_node,data:formdata}});
        if(this.connector.result.is_saved){
            this.tree.refreshItem(1);                                
            XTR_main.set_result(_lang_common['category_success_saved']);
            xoad.html.importForm('edit_category',{"count":this.connector.result.count});
        }
    },
    
    save_category:function(k,itemId) {  
      
        if(!this.validation.validate())return void(0); 
        XTR_main.show_loading();        
        formdata=(xoad.html.exportForm('add_category'));        
        this.connector.execute({save_category:{data:formdata}})                
        if(this.connector.result.is_saved) {                                                         
            this.tree.refreshItem(1);                                
            XTR_main.set_result(_lang_common['category_success_saved']);                    
            this.current_group=this.connector.result.id;
            this.edit_category(this.current_group,this.current_group);
        }                        
        XTR_main.hide_loading();
    },
    
/*------------ QUESTION*/
    add_question: function()
        {            
            
            XTR_main.load_module_tpls(this.module_name, new Array('add_question'));
            XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'add_question'));
            
            var Xpop = new XTRpop('startXlist', null, { position: 'bottom', delta_x: -203, delta_y: 0, className : 'dialogtable', donotdestroy:true });
            var xlist = this.group_dialog(Xpop.tool_tip.id,'load_xlist_data',1, 'categoryAlias', 'category','dialogtable');
            xlist.connectXpop(Xpop);             
            
            if (this.current_group) {
                xoad.html.importForm('add_question',{"category":this.current_group,"categoryAlias":this.current_groupname});
            }
            
            this.validation = new Validation('add_question',{immediate: true});
         
        }
        ,

   //m
       save_question:function()
         {
            if(!this.validation.validate()){return void(0)};
            
            this.connector.execute({save_question:{main:xoad.html.exportForm('add_question')}});                            
            if (this.connector.result.is_saved){XTR_main.set_result(_lang_faq['question_success_saved']);}   
         
       },

                            
    show_edit_question: function(id)
        {    
            this.tabs.createTabNode({id: 't_edit',name: _lang_common['editing'],temporal: true}, 'top', true);            
            XTR_main.load_module_tpls(this.module_name, new Array('edit_question'));
            XTR_main.load_module_tpls(this.module_name, new Array('edit_question'));            
            XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'edit_question'));                                  
            var Xpop = new XTRpop('startXlist', null, { position: 'bottom', delta_x: -203, delta_y: 0, className : 'dialogtable', donotdestroy:true });
            var xlist = this.group_dialog(Xpop.tool_tip.id,'load_xlist_data',1, 'categoryAlias', 'category','dialogtable');
            xlist.connectXpop(Xpop);             
            
            
            this.connector.execute({load_question_data:{id:id}});
            xoad.html.importForm('edit_question',this.connector.result.faq_data);                                  
            new Control.DatePicker('date', { locale:'ru',use24hrs: true,timePicker: true, timePickerAdjacent: true});
            
            this.validation = new Validation('edit_question', {immediate : true});
            

            
             
        },
   
    
    save_edited_question:function()
    {   
        if(!this.validation.validate()){return void(0)}; 
        this.connector.execute({save_edited_question:xoad.html.exportForm('edit_question')});
        if (this.connector.result.is_saved){XTR_main.set_result(_lang_faq['question_success_saved']);}   
    },
    
    
    
    delete_question : function() 
    {
        id=this.gridlist.getSelectedRowId(1);
        if(id.length>0)
        {                
            if  (!confirm(_lang_faq['you_are_assured_what_wish_to_remove_this_question']))
            {
                return false;
            }
            this.connector.execute({delete_question:{id:id}});
            if (this.connector.result.isDel)
            {
                this.gridlist.deleteSelectedRows();
            }
        }
    },


    getSelectedParent:function(itemid)
    {
        if (itemid) selected=itemid;
        else selected=this.tree.getSelectedRowId();
        if (!selected) {  return 1; }
        return this.tree.getParentId(selected);
     },


    
    build_tree: function(){
        this.tree = null;
        this.tree = new dhtmlXTreeObject(this.module_name + "_treebox",'auto', 'auto', "0", "xres/ximg/tree/");            
        this.tree.setImagePath("/xres/ximg/green/");
        this.tree.setDataMode("json");
        this.tree.enableContextMenu(menu);
        this.tree.setOnDblClickHandler(this.tree_object_clicked.bind(this));     
        this.tree.enableDragAndDrop(1);
        this.tree.attachEvent("onDrag",this.on_tree_drag.bind(this));
        this.tree.setDragBehavior('complex');
        this.tree.enableMultiselection(1);                         
        this.tree.setXMLAutoLoading("tree_xml_server.php?tree=faq_container");        
        this.tree.loadJSON("tree_xml_server.php?tree=faq_container&id=0");
    },
    
    
    //m
    build_interface: function()
        {
        //parent tree
        if(!this.tree){
            tree_node = new Element('div',{id: this.module_name+"_treebox",className:'treebox'});
            $('tp-tree-window').appendChild(tree_node);
            toggle_main_menu(true);                
                
            menu = new dhtmlXMenuObject();
            menu.renderAsContextMenu();
            menu.addNewChild(menu.topId, 0, "add_question", _lang_faq['add_question'], false, '', '', this.add_question.bind(this));
            menu.addNewChild(menu.topId, 0, "add_category", _lang_common['add_group'], false, '', '', this.add_category.bind(this));
            menu.addNewChild(menu.topId, 0, "edit_category", _lang_common['edit'], false, '', '', this.edit_item.bind(this));
                
            menu.addNewChild(menu.topId, 0, "delete", _lang_common['delete'], false, '', '', this.delete_obj.bind(this));
            this.build_tree();
        }
        else{            
            $(this.module_name+"_treebox").show();        
        } 
        


         this.connector.execute({get_module_options:true});                
         this.options=this.connector.result.options;
         var oTabs = [					
                    {id:'t_firstpage',name: _lang_common['info'],callback: this.first_start.bind(this),temporal:true},
                    {id:'t_addcontent',name: _lang_faq['add_question'], callback: this.add_question.bind(this)},
                    {id:'t_addgroup',name: _lang_common['add_group'], callback: this.add_category.bind(this)},
                    {id:'t_addfolder',name: _lang_common['add_folder'], callback: this.add_folder.bind(this)},
                    {id:'t_newquestions',name: _lang_faq['new_questions'], callback: this.show_new_questions.bind(this)}
				]
          this.tabs=new XTRFabtabs('bookmarks',oTabs);  
          this.first_start();
          toggle_main_menu(true);     
        },
        

        
    
        first_start:function()
            {                  
                this.show_questions_list(0,2);
            },
   
    

    switch_question :function(id,cid,state) 
    {
        this.connector.execute({switch_question:{id : id,state:state}});
    
    },    
    
    show_questions_list:function(id,active) {
           
        XTR_main.load_module_tpls(this.module_name, new Array('faq_first'));
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'faq_first'));
        this.tabs.createTabNode( { id: 'tedit_page', name: _lang_faq['section questions'], temporal: true }, 'top', true);
        menu = new dhtmlXMenuObject();
        menu.renderAsContextMenu();
        menu.addNewChild(menu.topId, 0, "refresh", _lang_common['delete'], false,'','',this.delete_question.bind(this));                               
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'umenu_list',true),'b');
                    
        $('pw-edit').insert('<div id="t-container"></div>');
        this.gridlist = new dhtmlXGridObject('t-container');   
        this.gridlist.selMultiRows = true;                             
        this.gridlist.setImagePath("xres/ximg/grid/imgs/");
        this.gridlist.setHeader('id,'+_lang_common['date']+','+_lang_common['user']+','+_lang_faq['question']+','+_lang_common['active']);
        this.gridlist.setInitWidths("70,120,130,*,80");
        this.gridlist.setColAlign("center,center,center,left,center");
        this.gridlist.attachHeader("#text_filter,#text_filter,#text_filter,#text_filter,#select_filter");
        this.gridlist.setColTypes("ed,ed,ed,ed,ch");
        this.gridlist.setColSorting("int,date_rus,str,str,int");
        this.gridlist.setSelectFilterLabel(4,_select_filter_yes_no);
        this.gridlist.attachEvent("onRowDblClicked", this.show_edit_question.bind(this));
        this.gridlist.attachEvent("onCheckbox",this.switch_question.bind(this));  
        this.gridlist.enableContextMenu(menu);  
        this.gridlist.init();
        this.gridlist.setSkin("modern");
        if (active==2) {
            this.connector.execute({load_questions_data:{activity:active}});                                
            var i = 0;
            if ( this.connector.result.data_set){
                for ( key in this.connector.result.data_set.rows) {
                    if (!key) continue;
                    i=this.connector.result.data_set.rows[key];
                    i.data[4]=0;
                }
            }
        }
        else {  this.connector.execute({load_questions_data:{id:id,activity:active}}); }
                    
        if(this.connector.result.data_set) {
            this.gridlist.parse(this.connector.result.data_set,"xjson")
        }   
    },
    
    
    show_questions:function(id)
    {    
        this.show_questions_list(id,0);
        this.current_node = this.tree.getSelectedItemId();
    },
    
    switch_state: function(evt){
        
        elt = Event.element(evt);
        rowid=elt.up('tr').rowid;
        state=!parseInt(elt.params.get('active'));
        this.connector.execute({switch_state : {id : rowid,state:state}});
        elt.params.get('active')=state?'1':'0';
        if (this.connector.result.switched)
        {
           if(state)
           {
            elt.removeClassName('nt-no-activ');
            elt.addClassName('nt-activ');
           }else{
            elt.removeClassName('nt-activ');
            elt.addClassName('nt-no-activ');

        }

        }        
    },
    
    
    show_new_questions:function()
    {    
            this.show_questions_list(0,2);  
    },    

    get_action_properties:function(_action,prefix){
        if (prefix != 'action' && prefix != 'secondary') {
            destination_prefix = 'action';
            defaultAction = prefix;
        }
        else if (prefix == 'secondary') {
            destination_prefix = prefix;
            defaultAction = null;
        } else {
            destination_prefix = 'action';
            defaultAction = null;
        }

        this.connector.execute({ get_action_properties: { Action: _action } });

        if (this.connector.result.action_properties) { 
            $(destination_prefix + '_properties').update(this.connector.lct.action_properties);
        } else {
            $(destination_prefix + '_properties').update('Свойства отсутствуют');
        }
        
        xoad.html.importForm('tune_actions', this.connector.result.action_properties_form);                
                   
        if (this.connector.result.xlist){
                      
            var xlistfolders = $('startXlistFolders');
            var xlistgroups = $('startXlistGroup');
            var xlistroot = $('startXlistRoot');
                    
            if (xlistfolders) {
                var XpopFolders = new XTRpop('startXlistFolders', null, { position: 'bottom', delta_x: -203, delta_y: 0, donotdestroy:true });
                var xlistFolders = this.group_dialog(XpopFolders.tool_tip.id,'load_xlist_folder',1, 'folderAlias', 'folder','dialogtable');
                xlistFolders.connectXpop(XpopFolders);                    
            }
            if (xlistgroups){
                var XpopAlbum = new XTRpop('startXlistGroup', null, { position: 'bottom', delta_x: -203, delta_y: 0, donotdestroy:true });
                var xlistAlbum = this.group_dialog(XpopAlbum.tool_tip.id,'load_xlist_data',1, 'categoryAlias', 'category','dialogtable2');
                xlistAlbum.connectXpop(XpopAlbum); 
            }
            if (xlistroot){
                var XpopRoot = new XTRpop('startXlistRoot', null, { position: 'bottom', delta_x: -203, delta_y: 0, donotdestroy:true });
                var xlistRoot = this.group_dialog(XpopRoot.tool_tip.id,'load_xlist_folder',1, 'rootAlias', 'root','dialogtable3');
                xlistRoot.connectXpop(XpopRoot); 
            }
                    
            this.validation=new Validation('tune_actions', {immediate : true});
        }
                    
        if (defaultAction) { this.get_action_properties(defaultAction, 'secondary'); }
                    
      },

  
  destructor:function() {
    $(this.module_name+"_treebox").hide();
    this.tabs.destructor();
  }   

});