var XTRvotes = Class.create();
var XTR_votes;

XTRvotes.prototype = Object.extend(new _modulePrototype(), 
{
    initialize: function()
    {
        this.destructable = true;
        this.module_name = 'votes';
        this.current_edit_id = null;
        
        this.tree = null;
        this.init();
    },

    tree_object_clicked: function(itemid)
    {
        object_type = this.tree.getNodeParam(itemid, 'obj_type');

        switch(object_type)
        {
            case "_VOTES":
                this.show_edit_votes(itemid);
                break;

            case "_VOTESGROUP":
                this.edit_category(itemid);
                break;            

            default: 
                return false;
        }
    },

    group_dialog: function()
    {
        columns = $H({image: ' ', name: _lang_common['name']});
        _columnsHeadersWidth = new Array('20px', '150px');
        _columnsInterpretAs = $H({image: 'IMAGE'});
        _images = $H({group: 'img/tree/folderClosed.gif'});
            
        if(arguments[0])
        {
            xlist_name = arguments[0];
        }else{            
            xlist_name = "xlist";
        }

        return xlist = new XTRxlist(xlist_name, this.connector, {
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

    add_category:function()
    {            
        XTR_main.show_loading();               
        
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'add_category',true)); 
        this.validation = new Validation('add_category',{immediate: true});            
        XTR_main.hide_loading();
    },
    
    on_tree_drag: function(idNode, idTo, sibling, idTreeFrom, idTreeTo)
    {

        this.connector.execute({changeAncestor :{id: idNode, ancestor: idTo, relative:sibling}});

        if(this.connector.result['dragOK']) {
            return true;
        }
        else {
            return false;
        }
    },
        
    save_edited_category:function()
    {  
        if(!this.validation.validate())
            return void(0);
        
        formdata = (xoad.html.exportForm('edit_category'));
        
        this.connector.execute({save_edited_category :{id:this.current_node, data:formdata}});

        if(this.connector.result.is_saved) {
            XTR_main.hide_loading();                              
            this.tree.refreshItem(1);                        
            this.tabs.createTabNode({id: 't_firstpage', name: _lang_common['info'], temporal: true }, 'top', true);
            $('pw-edit').update(_lang_common['group_success_saved']);
            return true;
        }                        
    },
    
    
    save_category:function()
    {  
        if(!this.validation.validate())
            return void(0); 
        
        formdata = (xoad.html.exportForm('add_category'));
        this.connector.execute({save_category :{data:formdata}});
        if(this.connector.result.is_saved)
        {
            XTR_main.hide_loading();                              
            this.tree.refreshItem(1);                        
            this.tabs.createTabNode({id: 't_firstpage', name: _lang_common['info'], temporal: true }, 'top', true);
            $('pw-edit').update(_lang_common['group_success_saved']);
        }                        
    },
    
    edit_category:function(itemId)
    {
            XTR_main.load_module_tpls(this.module_name, new Array('edit_category'));            
            this.connector.execute({load_category :{id:itemId}});         
            this.current_node = itemId;   
            this.tabs.createTabNode({id: 't_edit', name: _lang_common['editing'], temporal: true}, 'top', true);
            XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'edit_category',true));                                                   
            xoad.html.importForm('edit_category',this.connector.result.category_data.category); 
            this.validation = new Validation('edit_category',{immediate: true});     

    },     
    
    add_votes: function()
    {            
        
        
        XTR_main.load_module_tpls(this.module_name, new Array('add_votes','fields'));              
        selected = this.tree.getSelectedItemId();
        params = true;
            
        if(selected) {
            if(this.tree.get_objtype(selected)=='_VOTESGROUP') {
                  params = {group_id:selected};
            }
        }
        this.connector.execute({load_initial_votes_data :params});            
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'add_votes'));                                       
                
        xoad.html.importForm('add_votes',this.connector.result.votes_data);                                     
        this.validation = new Validation('add_votes',{immediate: true}); 
            
                new Control.DatePicker('date1', { locale:'ru',use24hrs: true,timePicker: true, timePickerAdjacent: true});
                new Control.DatePicker('date2', { locale:'ru',use24hrs: true,timePicker: true, timePickerAdjacent: true});  
            
        this.add_variant();
            
        
    },
        
        
    parse_variants:function(field_id)
    {
        var fields = $(field_id).getElementsByClassName('field');  
		fields = $A(fields); 
        var arr = new Array();
        var count = 1;
        fields.each(function(el){
            var id = el.id.split('_')[1];
            var value = $('var_'+id).value;
            arr.push({'ord':count,'value':value,'var_id':id});
            count++;
        });
        return arr;        
    },
    
    add_variant:function()
    {
        XTR_main.show_loading();
        var num =  $('variants').getElementsByClassName('field').length+1;
        var syntax = /(^|.|\r|\n)(\{%F:\s*(\w+)\s*%})/;
        var a = new Array();
        a = {ord: num,id:num};
        var t = new Template(XTR_main.get_tpl(this.module_name, 'fields'), syntax);
        var parsed = t.evaluate(a);
        new Insertion.Bottom('variants', parsed); 
        XTR_main.hide_loading();
        Sortable.create('variants',{tag:'div', constraint:false});
        this.validation1 = new Validation('vars_form', {immediate: true});
    },
        
    variant_delete:function(elem)
    {
        XTR_main.show_loading(); 
        elem.up().remove();
        XTR_main.hide_loading();
    },
    
    show_edit_votes: function(itemId)
    {    

        this.tabs.createTabNode({id: 't_edit',name: _lang_common['editing'],temporal: true}, 'top', true);
        this.connector.execute({load_votes :{id:itemId},load_vote_info :{id:itemId}});

        this.current_node = itemId;               
        XTR_main.load_module_tpls(this.module_name, new Array('edit_votes','fields'));                             
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'edit_votes'));
        var syntax = /(^|.|\r|\n)(\{%F:\s*(\w+)\s*%})/;
        var t = new Template(XTR_main.get_tpl(this.module_name, 'fields'), syntax);
        var parsed = '';
        this.connector.result.variants.each(function(f) {
            parsed += t.evaluate(f);
        });

                new Control.DatePicker('date1', { locale:'ru',use24hrs: true,timePicker: true, timePickerAdjacent: true});
                new Control.DatePicker('date2', { locale:'ru',use24hrs: true,timePicker: true, timePickerAdjacent: true});  
        
        xoad.html.importForm('edit_votes',this.connector.result.votes_data);
        $('variants').innerHTML = parsed;                      
        Sortable.create('variants',{tag:'div',constraint:false});
        this.validation1 = new Validation('vars_form',{immediate: true});

        this.current_edit_id = itemId;           

        this.validation = new Validation('edit_votes', {immediate : true});
        
    },

   save_votes:function()
   {
        if(!this.validation.validate()||!this.validation1.validate())
            return void(0);
        if($('variants').getElementsByClassName('field').length<2) {
            alert(_lang_votes['in_voting_should_be_at_least_2_variants_answer']);
            return void(0);
        }
        
        var add_votes = xoad.html.exportForm('add_votes'); 
        this.connector.execute({save_votes :{main:add_votes,variants:this.parse_variants('variants')}});

        this.tree.refreshItem(add_votes.category);
        this.tabs.createTabNode({id: 't_firstpage', name: _lang_common['info'], temporal: true}, 'top', true);
        $('pw-edit').update(_lang_votes['voting_successfully_saved']);
                                     
   },

    save_edited_votes:function()
    {
        if(!this.validation.validate()||!this.validation1.validate())
            return void(0);
            
        if($('variants').getElementsByClassName('field').length < 2) {
            alert(_lang_votes['in_voting_should_be_at_least_2_variants_answer']);
            return void(0);
        }    
        main =  xoad.html.exportForm('edit_votes');
        this.connector.execute({save_edited_votes:{main:main,id:this.current_edit_id,variants:this.parse_variants('variants')}});
               
        if(this.connector.result.is_saved) {
            this.tabs.createTabNode({id: 't_firstpage', name: _lang_common['info'], temporal: true}, 'top', true)
            $('pw-edit').update(_lang_votes['form_success_saved']);
        }
        
        this.tree.refreshItem(this.tree.getParentId(this.current_edit_id));  
        
    },
    
    build_interface: function()
    {
                
        toggle_main_menu(true);                     
        if(!this.tree) {        
                  
                  menu = new dhtmlXMenuObject();
                  menu.renderAsContextMenu();
                  menu.addNewChild(menu.topId, 0, "delete",_lang_common['delete'], false,'','',this.delete_obj.bind(this));                           
                  menu.addNewChild(menu.topId, 0, "refresh", _lang_common['refresh'], false,'','',function(id,zid){this.tree.refreshItem(zid);}.bind(this));            
                  menu.addNewChild(menu.topId, 0, "add_price",_lang_votes['new_voting'], false,'','',this.add_votes.bind(this));                       
                  menu.addNewChild(menu.topId, 0, "add_pricegroup",_lang_common['add_group'], false,'','',this.add_category.bind(this));           
                          
                        $('tp-tree-window').appendChild(new Element('div',{id: this.module_name+"_treebox", className:'treebox'}));
                        this.tree = new dhtmlXTreeObject(this.module_name+"_treebox", 'auto', 'auto', "0");
                        this.tree.setImagePath("/xres/ximg/green/");          
                        this.tree.enableDragAndDrop(1);
                        this.tree.enableMultiselection(1);
                        this.tree.setDataMode("json");
                        //this.tree.setObjImages({_VOTESGROUP:'folderClosed.gif'});
                        
                        this.tree.enableContextMenu(menu);    
                        this.tree.setOnDblClickHandler(this.tree_object_clicked.bind(this)); 
                        this.tree.setDragHandler(this.on_tree_drag.bind(this));
                        this.tree.setXMLAutoLoading("tree_xml_server.php?tree=votes_container");
                        this.tree.loadJSON("tree_xml_server.php?tree=votes_container&id=0",function(){
                            this.tree.refreshItem(1);                  
                            }.bind(this));        
                    
                //this.connector.execute({get_tree_inheritance : true});            
              //this.tree.setInheritanceArr(this.connector.result.tree_inheritance);
        }else {
            $(this.module_name+"_treebox").show();
        } 



        var oTabs = [					
            {id:'t_firstpage',name: _lang_common['info'],temporal:true},
            {id:'t_addcontent',name: _lang_votes['add_voting'], callback: this.add_votes.bind(this)},
            {id:'t_addgroup',name: _lang_common['add_group'], callback: this.add_category.bind(this)},                 
        ]

        this.tabs = new XTRFabtabs('bookmarks',oTabs);  
        this.first_start();
        


    },

    first_start:function()
    {                  
        
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'forms_first',true));        
        this.tabs.makeActiveById('t_firstpage');             
    },
    
    get_action_properties:function(_action)
    {
        
        this.connector.module_name = 'forms';                   
        this.connector.execute({get_action_properties :{Action:_action},load_initial_votes_data : true});                  
        if(this.connector.result.action_properties) {
            $('action_properties').update(this.connector.lct.action_properties);                               
        }else {
            $('action_properties').update(_lang_common['properties_are_absent']);
        }
                   
        xoad.html.importForm('tune_actions', this.connector.result.action_properties_form);
        xoad.html.importForm('tune_actions', this.connector.result.votes_data);   
                   
        if(this.connector.result.xlist)
        {
            columns = $H({image:' ', name:_lang_common['name']});
            _columnsHeadersWidth = new Array('20px','150px');
            _columnsInterpretAs = $H({image:'IMAGE'});
            _images = $H({group:'img/tree/folderClosed.gif', page:'img/tree/page.gif'});
            var Xpop = new XTRpop('startXXlist', null, {position:'bottom', delta_x:-272, delta_y:-60});                        
           
            xlist = new XTRxlist(Xpop.tool_tip.id,this.connector,{
                resultSource:'showvotesName',
                resultIDSource:'votesId',
                columnsHeaders:columns,
                tableId:'dialogtable',
                columnsHeadersWidth:_columnsHeadersWidth,
                columnsInterpretAs:_columnsInterpretAs,
                images:_images,
                className:'dialog-table'
            });
            
            this.validation = new Validation('tune_actions', {immediate : true}); 
            //Передача контекста Xpop
            xlist.connectXpop(Xpop);
        }
                    
        this.validation = new Validation('tune_actions', {immediate: true}); 
    
  },
            
  destructor:function()
  {
      $(this.module_name+"_treebox").hide();
      XTR_main.set_rightside();
      
      this.tabs.destructor();
  }   
});