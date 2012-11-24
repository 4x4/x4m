var XTRbanners = Class.create();
var XTR_banners;

XTRbanners.prototype =Object.extend(new _modulePrototype(),
    {
    initialize: function()
        {
        this.destructable = true;
        this.module_name='banners';
        this.current_edit_id=null;
        this.leaves_obj_type=new Array('_BANNERS');  
        this.tree=null;
        this.init();
        },
    //m
    tree_object_clicked: function(itemid)
        {
        object_type = this.tree.getNodeParam(itemid, 'obj_type');

        switch (object_type)
            {
            case "_BANNERS":
                this.show_edit_banner(itemid);

                break;

            case "_BANNERSGROUP":
                this.edit_category(itemid);

            break;            

            default: return false;
            }
        },
    //m

    group_dialog2: function()
        {

        columns = $H(
            {
            image: ' ',
            name: 'Èìÿ'
            });

        _columnsHeadersWidth = new Array('20px', '150px');
        _columnsInterpretAs = $H(
            {
            image: 'IMAGE'
            });

        _images = $H(
            {
            group: 'img/tree/folderClosed.gif'
            });
            
            if(arguments[0])
            {
                xlist_name=arguments[0];
            }else{            
                xlist_name="xlist";
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
         

    add_category:function()
    {
            
            XTR_main.show_loading();               
            XTR_main.load_module_tpls(this.module_name, new Array('add_category'));                        
            this.connector.execute({add_category:true});         
            XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'add_category'));                                
            xoad.html.importForm('ecategory',this.connector.result.category_data); 
            this.validation = new Validation('ecategory',{immediate: true});            
            XTR_main.hide_loading();

    },
    
    
    save_category:function()
    {  
      
          if(!this.validation.validate())return void(0); 
        XTR_main.show_loading();
        formdata=(xoad.html.exportForm('ecategory'));
        this.connector.execute({save_category:{data:formdata}});        
                        if(this.connector.result.is_saved)
                        {
                            this.current_node = this.connector.result.is_saved;
                            XTR_main.hide_loading();                              
                            this.tree.refreshItem(1);                        
                            XTR_main.set_result(_lang_common['group_success_saved']);
                            $('action').href="javascript:XTR_banners.save_edited_category()";
                            return true;
                        }                        
        
    },
    

    
    edit_category:function(itemId)
    {
            
            XTR_main.show_loading();               
            XTR_main.load_module_tpls(this.module_name, new Array('edit_category'));                        
            this.connector.execute({load_category:{id:itemId}});         
            this.current_node = itemId;   
            this.tabs.createTabNode({id: 't_edit',name: _lang_common['editing'],temporal: true}, 'top', true);
            XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'edit_category'));                                                   
            xoad.html.importForm('ecategory',this.connector.result.category_data); 
            this.validation = new Validation('ecategory',{immediate: true});                 
            XTR_main.hide_loading();

    },     
       
                
    save_edited_category:function()
    {  
    if(!this.validation.validate())return void(0);
        XTR_main.show_loading();
        formdata=(xoad.html.exportForm('ecategory'));
        this.connector.execute({save_edited_category:{id:this.current_node,data:formdata}});
        
                        if(this.connector.result.is_saved)
                        {
                            XTR_main.hide_loading();                              
                            this.tree.refreshItem(1);
                            XTR_main.set_result(_lang_common['group_success_saved']);
                            return true;
                        }                        
        
    },
    
 
    
    
    add_banner: function()
        {            
            
            XTR_main.load_module_tpls(this.module_name, new Array('select_banner_type'));              
            XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'select_banner_type'));                                                                            
            this.validation = new Validation('select_banner_type',{immediate: true});            
            
        },
       
        
    
   create_banner: function()
        {            
            if(!this.validation.validate())return void(0);
            
            var banner_type = $('banner_type').value;   
            XTR_main.load_module_tpls(this.module_name, new Array(banner_type));              
            selected=this.tree.getSelectedItemId();
            params=true;
            
            
            
            if(selected)
            {
                if(this.tree.get_objtype(selected)=='_BANNERSGROUP')
                {
                  params={group_id:selected};
                }
            }
            
            this.connector.execute({load_initial_banners_data:params});            
            XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, banner_type));    
            xoad.html.importForm('abanner',this.connector.result.banners_data);                                     
            this.validation = new Validation('abanner',{immediate: true}); 
            //datapicker
                new Control.DatePicker('date1', { locale:'ru',use24hrs: true,timePicker: true, timePickerAdjacent: true});
                new Control.DatePicker('date2', { locale:'ru',use24hrs: true,timePicker: true, timePickerAdjacent: true});        
            
        },
        

   save_banner:function()
         {
   
        if(!this.validation.validate())return void(0);
        
              
        switch ($('banner_type').value){

            case 'gif':
                var gif_types = new Array('gif','jpeg','jpg','png','GIF','JPEG','JPG','PNG');
                var ftype = $('file_name').value.split('.')[1];
                if($('file_name').value=='') {alert(_lang_banners['load_banner_file']);return void(0);}    
                if (!in_array(gif_types,ftype)){alert(_lang_banners['GIF-banners_do_not_support_format']+ftype+'\n'+_lang_banners['load_format_file_gif_jpeg_png']); return void(0);};
                break;
            case 'flash':
                if($('file_name').value=='') {alert(_lang_banners['load_banner_file']);return void(0);}
                var ftype = $('file_name').value.split('.')[1];
                if (ftype!='swf'){alert(_lang_banners['FLASH-banners_do_not_support_format']+ftype+'\n'+_lang_banners['load_format_file_swf']); return void(0);}
                break;
                
            case 'html':
                
                break;
        }




        var add_banner = xoad.html.exportForm('abanner'); 
        this.connector.execute({save_banner:{main:add_banner}});
                        if(this.connector.result.is_saved){
                            this.current_node = this.connector.result.is_saved;
                            $('action').href = "javascript:XTR_banners.save_edited_banner();";
                            $('preview').show();
                            this.tree.refreshItem(add_banner.category);
                            XTR_main.set_result(_lang_banners['banner_success_saved']);                        
                        }
                        
                        
                        
          
       },

       
       
       preview_banner:function(){
        
            var type = $('banner_type').value;
            var cat_id = $('category').value;
            XTR_main.load_module_tpls(this.module_name, new Array('banner_content','flash_container','gif_container'));
            if ($('banner_type').value!='html'){
                var fname = $('file_name').value;
                if (fname.length ==0){alert(_lang_banners['banner_file_is_not_loaded']); return void(0);}
            }            
            this.connector.execute({get_banner_info:{fname:fname,type:type},get_category_info:{id:cat_id}});
            
            switch (type){
                case 'flash':
                    window.open(fname,'','width='+this.connector.result.banner_info.width+',height='+this.connector.result.banner_info.height); 
                    break;
                case 'gif':
                    var syntax = /(^|.|\r|\n)(\{%F:\s*(\w+)\s*%})/;
                    var t = new Template(XTR_main.get_tpl(this.module_name, type+'_container'), syntax);
                    this.connector.result.banner_info.fname = fname;
                    var parsed = t.evaluate(this.connector.result.banner_info);            
                    Control.Modal.load('banners');
                    mwin= new Control.Modal('banners',{
                        opacity: 100,        
                        width:this.connector.result.banner_info.width+20,
                        contents:XTR_main.get_tpl(this.module_name, 'banner_content'),
                        mode:'contents',
                        fade:true,
                        overlayCloseOnClick:true
                    }
                    );
                    mwin.open();
                    $('form').innerHTML = parsed;
                    break;
                case 'html':
                    var text = $('flash_text').value;
                    text = text.replace(/^\s+|\s+$/g,"");
                    if (text.length==0) {
                        alert(_lang_banners['enter_banner_html_code']); return void(0);
                    }
                    Control.Modal.load('banners');
                    mwin= new Control.Modal('banners',{
                        opacity: 100,
                        contents:XTR_main.get_tpl(this.module_name, 'banner_content'),
                        mode:'contents',
                        fade:true,
                        overlayCloseOnClick:true
                    }
                    );
                    mwin.open();
                    $('form').innerHTML = $('flash_text').value;                    
                    break;            
     
                };
            
        },
        
    view_gif: function()
        {            
             
        },
        
        //m                
    show_edit_banner: function(itemId)
        {    
            XTR_main.show_loading();              
            this.tabs.createTabNode({id: 't_edit',name: _lang_common['editing'],temporal: true}, 'top', true);
            this.current_node = itemId;
            this.connector.execute({load_banner:{banner_id:itemId},get_banner_stats:{banner_id:itemId}});            
                this.current_node = itemId;               
                XTR_main.load_module_tpls(this.module_name, new Array(this.connector.result.banner_data.banner_type+'_edit','stats'));                             
                XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, this.connector.result.banner_data.banner_type+'_edit'));                
                
                new Control.DatePicker('date1', { locale:'ru',use24hrs: true,timePicker: true, timePickerAdjacent: true});
                new Control.DatePicker('date2', { locale:'ru',use24hrs: true,timePicker: true, timePickerAdjacent: true});
            
                xoad.html.importForm('abanner',this.connector.result.banner_data);
                xoad.html.importForm('xls_upload',this.connector.result.banner_data);
                $('slotzcontainer').innerHTML = XTR_main.get_tpl(this.module_name,'stats');
                
                
                this.current_edit_id=itemId;           
                //validation part
                this.validation = new Validation('abanner', {immediate : true});
                XTR_main.hide_loading(); 
             
        },

    
    save_edited_banner:function()
    {    
      
        switch ($('banner_type').value){

            case 'gif':
                var gif_types = new Array('gif','jpeg','jpg','png');
                var ftype = $('file_name').value.split('.')[1];
                if($('file_name').value=='') {alert(_lang_banners['load_banner_file']);return void(0);}    
                if (!in_array(gif_types,ftype)){alert(_lang_banners['GIF-banners_do_not_support_format']+ftype+'\n'+_lang_banners['load_format_file_gif_jpeg_png']); return void(0);};break;
                break;
            case 'flash':
                if($('file_name').value=='') {alert(_lang_banners['load_banner_file']);return void(0);}
                var ftype = $('file_name').value.split('.')[1];
                if (ftype!='swf'){alert(_lang_banners['FLASH-banners_do_not_support_format']+ftype+'\n'+_lang_banners['select_format_file_swf']); return void(0);}
                break;
                
            case 'html':
                break;
        }

        if ($('banner_type').value!='html'){
            if($('file_name').value=='') {alert(_lang_banners['load_banner_file']);return void(0);}
        }                        
        var main    =  xoad.html.exportForm('abanner');
  
        
        this.connector.execute({save_edited_banner :{main:main,id:this.current_node}});
        if(this.connector.result.is_saved){
                            XTR_main.set_result(_lang_banners['banner_success_saved']);
                            this.tree.refreshItem(this.tree.getParentId(this.current_node));
                        }                                 
        
    },
    
    clear_statistics: function()
    {
        AL = new Array();        
        this.connector.execute({clear_statistics:{id:this.current_node}});
    
    },

    build_interface: function()
        {

        //parent tree
        
        toggle_main_menu(true);          
        if(!this.tree)
        {

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
            this.tree.setXMLAutoLoading("tree_xml_server.php?tree=banners_container");            
            this.tree.loadJSON("tree_xml_server.php?tree=banners_container&id=0",function(){
                this.tree.refreshItem(1);                  
            }.bind(this));   
            
            
            
            menu = new dhtmlXMenuObject();
            menu.renderAsContextMenu();            
            menu.addNewChild(menu.topId, 0, "refresh", _lang_common['refresh'], false,'','',function(id,zid){this.tree.refreshItem(zid);}.bind(this));
            menu.addNewChild(menu.topId, 0, "delete",_lang_common['delete'], false,'','',this.delete_obj.bind(this));                           
            menu.addNewChild(menu.topId, 0, "add_banner",_lang_banners['add_banner'], false,'','',this.add_banner.bind(this));                           
            menu.addNewChild(menu.topId, 0, "add_group", _lang_common['add_group'], false,'','',this.add_category.bind(this));                                       
            this.tree.enableContextMenu(menu);
            
        
        }
        else{            
            $(this.module_name+"_treebox").show();        
        }
                

                
               
        //recieve tpl from server
         
         var oTabs = [					
                    {id:'t_firstpage',name: _lang_common['info'],temporal:true},
                    {id:'t_addcontent',name: _lang_banners['add_banner'], callback: this.add_banner.bind(this)},
                    {id:'t_addgroup',name: _lang_common['add_group'], callback: this.add_category.bind(this)},                 
					
				]
          
          this.tabs=new XTRFabtabs('bookmarks',oTabs);  
          this.first_start();
             
        },
        
    
        first_start:function()
            {                  
                   XTR_main.load_module_tpls(this.module_name, new Array('banners_first')); 
                    XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name,'banners_first'));        
                    
                    this.tabs.makeActiveById('t_firstpage');             
            
            },
            
    
  
            get_action_properties:function(_action)
            {
                   this.connector.module_name = 'banners';                                                         
                   this.connector.execute({get_action_properties :{Action:_action},load_initial_banners_data :true});                                    
                   if(this.connector.result.action_properties)
                   {
                    $('action_properties').update(this.connector.lct.action_properties);                               
                   }else{
                    $('action_properties').update(_lang_common['properties_are_absent']);
                   }
                   
                    xoad.html.importForm('tune_actions',this.connector.result.action_properties_form);
                    xoad.html.importForm('tune_actions',this.connector.result.banners_data);   
 
                  
                   
                   if(this.connector.result.xlist)
                   {
                     
                        var XpopGallery = new XTRpop('startXXlist', null, { position: 'bottom', delta_x: -203, delta_y: 0, donotdestroy:true });
                        var xlistGallery = this.group_dialog(XpopGallery.tool_tip.id,'load_xlist_data',1, 'showbannersName', 'bannersId','dialogtable');
                        xlistGallery.connectXpop(XpopGallery);                    
                        this.validation=new Validation('tune_actions', {immediate : true}); 
                   }
                   
                                         
            },
            
            
                  
          destructor:function()
             {
            
                $(this.module_name+"_treebox").hide();   
                  XTR_main.set_rightside_eform();
                  
                  this.tabs.destructor();
              
             
             }   

    });
    
