var XTRgallery = Class.create();
var XTR_gallery;

XTRgallery.prototype = Object.extend(new _modulePrototype(), {
    initialize: function() {
        this.destructable = true;
        this.module_name = 'gallery';
        this.current_node = 1;
        this.current_edit_id = null;
        this.current_gallery = null;
        this.current_gallery_name = null;
        this.current_album = null;
        this.current_album_name = null;
        this.tree = null;
        this.connector = null;        
        this.leaves_obj_type = new Array('_PHOTO');  
        this.init();   
    },
    
        
    action_by_obj_type:function(itemid,object_type)
    {
          switch(object_type) {
            case "_PHOTO":
                //this.show_edit_photo('direct', itemid);
                //this.current_node = itemid;

                break;

               
            case "_GALLERY":

                this.current_node = itemid; // для совместимости
                this.current_gallery = itemid;
                this.current_gallery_name = this.tree.getItemText(itemid);
                this.show_edit_gallery('direct', itemid);
                

                break;

                
            case "_ALBUM":
            
               this.current_node = itemid; // для совместимости
               this.current_album = itemid;
               this.current_album_name = this.tree.getItemText(itemid);
               XTR_gallery.album_view(itemid);    

            break;                                
            
            case "_ROOT":
                this.show_edit_root();
                break;

            default: return false;
            }
    },
    
    tree_object_clicked: function(itemid)
        {
            this.current_object_type = this.tree.getRowAttribute(itemid,"obj_type");                     
            this.action_by_obj_type(itemid,this.current_object_type);
        
        },        
                  
    group_dialog: function()
        {
        columns = $H( { image: ' ', name: _lang_common['name'] });

        _columnsHeadersWidth = new Array('20px', '150px');
        _columnsInterpretAs = $H( { image: 'IMAGE' });
        _images=$H({ group:'xres/ximg/tree/folderClosed.gif', page:'xres/ximg/tree/page.gif' });
            
        if(arguments[0]) { xlist_name=arguments[0];} else{ xlist_name="xlist"; }
            
        if(Object.isUndefined(arguments[1])) { fcall='load_xlist_data_galleries';} else { fcall=arguments[1]; }
               
        if(Object.isUndefined(arguments[2])) { startwith=1 } else { startwith=arguments[2]; }
            
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
            className: 'dialog-table',
            include_root_in_selection:true,
            usebackoff:1
            });
        },  

        
    non_uniq_entry: function(v) {
        if (this.current_album > 0) {
            this.connector.execute({check_uniq :{album: this.current_album,basic: v}});
            b = this.connector.result.uniq;
            return b;
        }
        else {
            return true;
        }
    },        
        
        
/*____________________________________________ ROOT ___________*/        
    show_edit_root: function() {
        this.connector.execute({load_root_data:true});
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'gallery_root_edit',true));        
        this.current_node = 1;
        xoad.html.importForm('root_edit', this.connector.result.root_data);
    },
        
    
    save_edit_root:function(el)
    {
        
           Validation.remove('is-none-uniq');    
           
           this.validation = new Validation('root_edit', {immediate: true});
            
                
        if(!this.validation.validate())return void(0);
        
        XTR_main.show_loading();
        formdata = xoad.html.exportForm('root_edit');
        $('root_edit').disable();
        this.connector.execute({save_edited_root :{id:this.current_node,root_data: formdata}});        
        $('root_edit').enable();           
        XTR_main.set_result(_lang_pages['site_properties_success_saved'],el);                  
        //this.tree.refreshItem(1);
        this.reload();
        XTR_main.hide_loading();
        
      
      },  
        

/*___________________________________________ GALLERY ------------ */


    add_gallery:function()
    {
            
            actionList = new Array();     
            XTR_main.show_loading();
            XTR_main.load_module_tpls(this.module_name, new Array('add_gallery','outer_link'));            
                                                            
            this.connector.execute({add_gallery: true});         
            XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'add_gallery'));                
            //$('slotzcontainer').update((XTR_main.get_tpl(this.module_name, 'outer_link')));
            xoad.html.importForm('add_gallery',this.connector.result.category_data); 
            this.validation = new Validation('add_gallery',{immediate: true});
            //$('savecat').href="javascript:XTR_gallery.add_new_gallery()";            
            XTR_main.hide_loading();            

    },    
    
    
    add_new_gallery:function()
    {  
        if(!this.validation.validate())return void(0); 
        XTR_main.show_loading();
        //bugger;
        formdata=(xoad.html.exportForm('add_gallery'));
        
        parent = formdata.ParentCategoryId;
        
        this.connector.execute({save_new_gallery :{data:formdata}});
                
                   if(this.connector.result.is_saved)
                        {
                        XTR_main.hide_loading();                              
                        this.tree.refreshItem(1);                        
                        this.tabs.createTabNode(
                        {
                            id: 't_firstpage',
                            name:_lang_common['info'],
                            temporal: true
                        }, 'top', true);
                        
                        XTR_main.set_result(_lang_common['group_success_saved']);
                        id = this.connector.result.id;
                        this.tree.refreshItem(parent); 
                        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'gallery_first'));  
                        

                        //$('savecat').href="javascript:XTR_gallery.save_edited_gallery()";
                        }                        
                
                
    },    
    
    show_edit_gallery:function(zid, itemId)
    {
            
            XTR_main.load_module_tpls(this.module_name, new Array('edit_gallery'));                        
            this.connector.execute({load_gallery :{id:itemId},get_gallerys :{id:itemId}});         
            this.current_node = itemId;   
            this.tabs.createTabNode({id:'t_edit',name:_lang_common['editing'],temporal: true},'top',true);
            XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'edit_gallery'));  
            //XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'edit_gallery'));  
            xoad.html.importForm('edit_gallery',this.connector.result.category_data); 
            this.validation = new Validation('edit_gallery',{immediate: true});
    },     
    
    
    
    save_edited_gallery:function()
    {  
    if(!this.validation.validate())return void(0);
        XTR_main.show_loading();
        actionList = new Array(); 
        formdata=xoad.html.exportForm('edit_gallery');
        this.connector.execute({save_edited_gallery:{id:this.current_node,data:formdata}});
                
                        if(this.connector.result.is_saved)
                        {                                                
                            parent = this.getSelectedParent();
                            this.tree.refreshItem(parent.id);
                            XTR_main.set_result(_lang_gallery['photogallery_success_saved']);
                        }else{
                            XTR_main.set_result(_lang_common['preservation_error']);
                        }   

                        XTR_main.hide_loading();                              
    },       

    
    
/*___________________________________________ ALBUM __________*/
    
    
    add_album:function()
    {
            actionList = new Array();     
            XTR_main.show_loading();
            XTR_main.load_module_tpls(this.module_name, new Array('add_album','outer_link'));            
                                                            
            this.connector.execute({add_album: true});         
            XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'add_album'));                
            $('slotzcontainer').update((XTR_main.get_tpl(this.module_name, 'outer_link')));
            xoad.html.importForm('add_album',this.connector.result.category_data); 
            xoad.html.importForm('OuterLink',this.connector.result.outerLink); 
            
            if (this.current_gallery) {
                xoad.html.importForm('add_album',{ParentCategoryId:this.current_gallery,ParentCategory:this.current_gallery_name});
            }
            
            $('savecat').href="javascript:XTR_gallery.add_new_album()";            
            var Xpop = new XTRpop('startXlist', null, { position: 'bottom', delta_x: -203, delta_y: 0 });
            xlist = this.group_dialog(Xpop.tool_tip.id,'load_xlist_data_galleries');
            
            Validation.add('is-none-uniq', _lang_pages['value_of_this_field_is_not_unique_enter_other_value'], this.non_uniq_entry.bind(this));
            this.validation = new Validation('add_album',{immediate: true});
            xlist.connectXpop(Xpop);
            XTR_main.hide_loading();            

    },    
    
    
    add_new_album:function()
    {  
        if(!this.validation.validate())return void(0); 
        XTR_main.show_loading();
        
        formdata=(xoad.html.exportForm('add_album'));
        
        parent = formdata.ParentCategoryId;
        
        this.connector.execute({save_album :{data:formdata}});
                
                   if(this.connector.result.is_saved)
                        {
                        XTR_main.hide_loading();                              
                        this.tree.refreshItem(1);                        
                        this.tabs.createTabNode(
                        {
                            id: 't_firstpage',
                            name:_lang_common['info'],
                            temporal: true
                        }, 'top', true);
                        
                        XTR_main.set_result(_lang_common['group_success_saved']);
                        id = this.connector.result.id;
                        this.tree.refreshItem(parent); 
                        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'gallery_first'));  
                        

                        //$('savecat').href="javascript:XTR_gallery.save_edited_album()";
                        }                        
                
                
    },    
    
    edit_album:function(zid, itemId,asd)
    {
        this.connector.execute({load_album :{id:itemId},get_folders :{id:itemId}});         
        if (this.connector.result.not_album) return false;
        XTR_main.load_module_tpls(this.module_name, new Array('edit_album','outer_link'));                        
            
        this.current_node = itemId;   
        this.current_album = itemId;
        this.tabs.createTabNode({id:'t_edit',name:_lang_common['editing'],temporal: true},'top',true);
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'edit_album'));  
        $('slotzcontainer').update((XTR_main.get_tpl(this.module_name, 'outer_link')));
        xoad.html.importForm('edit_album',this.connector.result.category_data); 
        xoad.html.importForm('OuterLink',this.connector.result.outerLink); 
        
        Validation.add('is-none-uniq', _lang_pages['value_of_this_field_is_not_unique_enter_other_value'], this.non_uniq_entry.bind(this));
        this.validation = new Validation('edit_album',{immediate: true});
        
    },     
    
    
    
    save_edited_album:function()
    {  
    if(!this.validation.validate())return void(0);
        XTR_main.show_loading();
        actionList = new Array(); 
        formdata=xoad.html.exportForm('edit_album');
        outerLink=xoad.html.exportForm('OuterLink');
        this.connector.execute({save_edited_album:{id:this.current_node,data:formdata,outerLink:outerLink}});
                         
                        if(this.connector.result.is_saved)
                        {    
                            if(outerLink.isOuterLink&&!Object.isUndefined(outerLink.Destination_page))
                            {
                                if(Object.isUndefined(XTR_pages))
                                { XTR_pages.tree.refreshItem(XTR_pages.tree.getParentId(outerLink.Destination_page)); }
                            }   
                                            
                        XTR_main.set_result(_lang_gallery['photogallery_success_saved']);
                        parent = this.getSelectedParent();
                        this.tree.refreshItem(parent.id);
                            
                        }else{
                            XTR_main.set_result(_lang_common['preservation_error']);
                        }   

                        XTR_main.hide_loading();                              
    },    
    
    
    
/*______________________________________________________ PHOTO ____________*/
    
    add_photo: function()
    {       
            XTR_main.show_loading();
            XTR_main.load_module_tpls(this.module_name, new Array('add_photo'));              
            selected=this.current_node;
            params={group_id:selected};
                                                               
            this.connector.execute({load_initial_gallery_data:params,check_gdlib : true});            
            XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'add_photo'));                                                    
            //$('savephoto').href="javascript:XTR_gallery.save_photo();"
            xoad.html.importForm('add_photo',this.connector.result.gallery_data);                                     
            
            if (this.current_album) {
                xoad.html.importForm('add_photo',{ParentCategoryId:this.current_album,ParentCategory:this.current_album_name});
            }

            this.validation = new Validation('add_photo',{immediate: true}); 
            var Xpop = new XTRpop('startXlist', null, { position: 'bottom', delta_x: -203, delta_y: 0 });
            xlist = this.group_dialog(Xpop.tool_tip.id,'load_xlist_data_albums');
            xlist.connectXpop(Xpop);
            this.tree.refreshItem(this.current_node); 
            XTR_main.hide_loading();
            
        },
        
    show_edit_photo: function(zid, itemId)
    {    
            if(!itemId)
            {
                this.tabs.createTabNode( { id: 'tedit_group', name: _lang_common['editing'], temporal: true }, 'top', true);
                XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'edit_photo'));
            
            }else{
                XTR_main.set_window_eform(XTR_main.get_tpl(this.module_name, 'edit_photo',true));            
            }
            this.current_page = null;
            this.connector.execute({load_photo:{gallery_id:zid}});            
            XTR_main.load_module_tpls(this.module_name, new Array('edit_photo'));                              
            xoad.html.importForm('edit_photo',this.connector.result.gallery_data);          
            image = this.connector.result.gallery_data.image;            
            a = "<img src='image.php/thumb.jpg?width=200&amp;height=200&amp;image="+image+">";
            $('imageview').innerHTML = a;              
            this.current_edit_id=zid;
            this.validation = new Validation('edit_photo', {immediate : true}); 

             
        },
        
    save_edited_photo:function()
    {   
        main=  xoad.html.exportForm('edit_photo');        
        this.connector.execute({save_edited_photo :{main:main,id:this.current_edit_id}});
        if(this.connector.result.is_saved){
                            XTR_main.set_result(_lang_gallery['photo_success_saved']);            
                            XTR_gallery.set_photo();
                            parent = this.getSelectedParent();
                            this.tree.refreshItem(parent.id);
                            this.photo_editor_win.close();
                            this.album_view(this.current_node);
                        }  
    },

    save_new_photo:function()
    {    
        main=  xoad.html.exportForm('add_photo');        
        xoad.html.importForm('add_photo',{image:""});                                     
        parent = main.ParentCategoryId;
        this.connector.execute({save_photo :{main:main}});            
        
        if(this.connector.result.is_saved){
            XTR_main.set_result(_lang_gallery['photo_success_saved']);            
            //XTR_gallery.set_photo();
            XTR_gallery.album_view(main.ParentCategoryId);
            this.tree.refreshItem(parent);
        }  
    },        
        

    save_photo:function() {
        if ($('image').value == ''){alert(_lang_gallery['image_is_not_added']); return void(0);}
        
        else{
            
            var add_photo = xoad.html.exportForm('ephoto'); 
            this.connector.execute({save_photo :{main:add_photo}});             
                            if(this.connector.result.is_saved){
                                 this.tabs.createTabNode(
                                 {
                                    id: 't_firstpage',
                                    name: _lang_gallery['editing_photo'],
                                    temporal: true
                                    }, 'top', true)
                                      
                                this.current_edit_id = this.connector.result.is_saved;
                                XTR_main.set_result(_lang_gallery['photo_success_saved']);
                                $('savephoto').href="javascript:XTR_gallery.save_edited_photo();";
                                this.tree.refreshItem(this.current_node); 
                                XTR_gallery.set_photo();
                            }                              
        }
    },
    
    set_photo: function(){
        image =  $('image').value;
        a = "<img src='image.php/thumb.jpg?width=100&amp;height=100&amp;image="+image+">";
        $('imageview').innerHTML = a;
    },    
       
    delete_photo: function(itemid,object_type)
    {
        var selected = [];
        parent = this.getSelectedParent();
        $$('#galleryList li').each(function(elt){if (elt.hasClassName('selected')) selected.push(elt.id); });
        if (selected.length==0) return void(0);
        
        this.connector.execute({delete_obj :{id: selected}});
            
            if (Object.isArray( this.connector.result.deleted))
            {
                $$('#galleryList li').each(function(elt){
                    if (elt.hasClassName('selected')) elt.remove();            
                });                
                XTR_gallery.album_view();    
                this.tree.refreshItem(parent.id); 
            }

                                
    },
    

    
    
/* ______________________________________________________________ ENGINE ______________________ */    
    
    
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
            
     },
    
    delete_obj: function(action_name, itemid) {
          parent = this.getSelectedParent();
          if (itemid > 1) {

                XTR_main.show_loading();
                this.connector.execute({delete_obj :{id: itemid}});
                if (Object.isArray( this.connector.result.deleted)) {
                    XTR_main.set_result(_lang_common['deleted']);
                    this.tree.refreshItem(parent.id); 
                }                
                
                XTR_main.hide_loading();
          }
                
          
    },

    

    
    album_view: function(id)
    {   
        
        groupId = id;
        this.current_node = id;
        menu = new dhtmlXMenuObject();
        menu.renderAsContextMenu();
        menu.addNewChild(menu.topId, 0, "delete",_lang_common['delete'], false,'','',this.delete_photo_manager.bind(this));                                      
        
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'gallery_list',true),'b');
        $('pw-edit').insert('<div id="t-container"></div>');
        this.gallery_manager = new dhtmlXGridObject('t-container');   
        this.gallery_manager.selMultiRows = true;
        this.gallery_manager.setImagePath("xres/ximg/grid/imgs/");
        this.gallery_manager.setHeader('id,'+_lang_common['name']+','+_lang_common['comment']+',' +_lang_common['image']);
        this.gallery_manager.setInitWidths("50,200,*,200,30");
        this.gallery_manager.enableContextMenu(menu);
        
        
        
        this.gallery_manager.enableDragAndDrop(true);
        this.gallery_manager.setColAlign("center,left,center,center");
        this.gallery_manager.attachHeader("#text_filter,#text_filter,#text_filter,#text_filter");
        this.gallery_manager.setColTypes("ro,ro,ro,img");
        this.gallery_manager.setColSorting("int,str,str,str");
        this.gallery_manager.enableAutoWidth(true);
        this.gallery_manager.enableContextMenu(menu);  
        this.gallery_manager.rowToDragElement = function(id) 
                        {
                                if (this.cells(id, 2).getValue() != ""){return this.cells(id, 2).getValue() + "/" + this.cells(id, 1).getValue();}else
                                {
                                    return this.cells(id, 1).getValue();
                                }
                        }
                        
                        
    
        this.gallery_manager.gridToTreeElement = function(tree,fakeID,gridID,treeID)
                        {
                            
                                this.connector.execute({changeAncestor:{ancestor:treeID,id:gridID}});            
                                if(this.connector.result.dragOK)
                                {
                                    XTR_main.set_result(_lang_fusers['user_moved']);
                                    return true;
                                }else{
                                    return -1;
                                }
                            
                        }.bind(this);
                        
                                                
        this.gallery_manager.init();
        this.gallery_manager.attachEvent("onRowDblClicked", this.manager_click.bind(this));
        this.gallery_manager.setSkin("modern");
        this.gallery_manager.attachEvent("onDrop", this.drop_photo.bind(this));
        this.refresh_album_view(id);        
        
        
    },
    
    drop_photo:function(idNode, idTo)
    {
                                    
       this.connector.execute({changeAncestorGrid : {id: idNode, ancestor: idTo, relative:'sibling'}});
       
       if(this.connector.result['dragOK']) {
           return true;
       }
       
       return false;
   
   
      
    },
    
    
    delete_photo_manager: function(action,id)
    {

        var selected=this.gallery_manager.selectedRows.map(function(el){return el._attrs.data[0]});
        if (selected) {
            this.connector.execute({delete_obj :{id: selected}});
        } else {
            this.connector.execute({delete_obj :{id: id}});
        }
        
        if (Object.isArray( this.connector.result.deleted)) {
            this.tree.refreshItem(this.current_node); 
            this.album_view(this.current_node);
        }
        
    },        
    
        manager_click:function(id)
        {
            var cellObj = this.gallery_manager.cellById(id, 1);  
            var cid = cellObj.getValue();
            this.photo_editor_win= XTR_main.dhxWins.createWindow("edit_photo", 20, 10, 400, 530);
            this.photo_editor_win.centerOnScreen();
            this.photo_editor_win.setModal(true);
            this.photo_editor_win.setText(_lang_gallery['editing_photo']);
            this.photo_editor_win.attachHTMLString('<div id="wineform" style="width:400px;"></div>');
            this.show_edit_photo(id,true);
        },    
    
    
    call_filemanager:function(){
        var fm = xFileManager;
        fm.options.callback=this.callback_filemanager;
        fm.options.modal=true;
        fm.switchmode('images');
        fm.selectMultiplyFile('selected_images_x');
        
    },
    
    callback_filemanager:function(files){
    
        XTR_main.show_loading();
        var fm = xFileManager;
        fm.options.callback=null;
        fm.options.modal=false;
        XTR_gallery.connector.execute({add_photos :{files: files, album : XTR_gallery.current_album}});     
        if (XTR_gallery.connector.result.saved) {
            XTR_gallery.album_view(XTR_gallery.current_album);            
            XTR_main.set_result(_lang_gallery['photo_success_saved']);
        }
        XTR_main.hide_loading();
        
        
    },
    
    refresh_album_view:function(id)
    {
        this.connector.execute({album_table:{id:id}});                                
                                        
        this.gallery_manager.clearAll();
        if(this.connector.result){
            if(this.connector.result.data_set) {
                this.gallery_manager.parse(this.connector.result.data_set,"xjson")
            }       
        }
    },    
    
    
    album_switch : function(elt, page)
    {
        elt.addClassName('selected');
        last_id = this.current_page?this.current_page:1;       
        $('p_'+last_id).removeClassName('selected');
        XTR_gallery.current_page = page;
        var syntax = /(^|.|\r|\n)(\{%F:\s*(\w+)\s*%})/;
        var t = new Template(XTR_main.get_tpl('gallery','photo_item'), syntax);
        start = (page-1) * XTR_gallery.per_page; 
        finish = start + XTR_gallery.per_page;
        i = 0;
        $('galleryList').innerHTML = '';
        for (var key in this.photos){
            if (i>=start&&i<finish){
                var parsed = t.evaluate(array_merge(this.photos[key],{id:key}));
                new Insertion.Bottom('galleryList',parsed);
            }
            i++;    
        }
    },
    
    photoOnClick: function(elt, evt)
    {
        if (!(evt.ctrlKey)){
            selected = $('galleryList').getElementsByClassName('selected')
            if (selected.length>0){
                selected = $A(selected);
                selected.each(function(el){
                    el.removeClassName('selected');    
                });
                
            }
        }
        elt.addClassName('selected');
        },
        
    dynXLS:function(id) {
         this.connector.execute({gallery_table:{id:id}});            
         if(this.connector.result) { this.tree.json_dataset=this.connector.result.data_set; }
         return true;
     },        
                        
   on_treegrid_drag: function(idNode, idTo, drop) 
   {

       if(drop.dragContext)
       {
           this.connector.execute({changeAncestorGrid : {id: idNode, ancestor: idTo, relative:drop.dragContext.dropmode}});
       }
       
       if(this.connector.result['dragOK']) {
           return true;
       }
       
       return false;
   },
                   
    reload:function()
    {
            this.tree = new dhtmlXGridObject(this.module_name+"_treebox");                              
            this.tree.selMultiRows = true;
            this.tree.imgURL = "/xres/ximg/green/";
            this.tree.setHeader(_lang_pages['page_name']);
            this.tree.setInitWidths("*");
            this.tree.setColAlign("left");
            this.tree.setColTypes("tree");
            this.tree.enableDragAndDrop(true);
            this.tree.enableEditEvents(false);
            this.tree.attachEvent("onDrag",this.on_treegrid_drag.bind(this));
            this.tree.setDragBehavior('complex');
            this.tree.setSelectFilterLabel(2,_select_filter_yes_no);
            this.tree.enableMultiselect(true);
            this.tree.enableContextMenu(menu);
            this.tree.attachHeader("#text_search");
            this.tree.init();
            this.tree.kidsXmlFile=1;
            this.tree.attachEvent("onDynXLS",this.dynXLS.bind(this));
            this.tree.setSkin("dhx_skyblue");
            this.tree.attachEvent("onRowDblClicked", this.tree_object_clicked.bind(this));
            this.connector.execute({gallery_table:{id:0}});            
            if(this.connector.result)
            {
                this.tree.parse(this.connector.result.data_set,"xjson")
            }

                this.tree.openItem(1);
       
                this.tree.refreshItem(1); 
      
            this.connector.execute({get_tree_inheritance:true});
            XTR_main.cache_mass_tpl('gallery', $H(this.connector.lct));        
    },

    build_interface: function()
    {            
        toggle_main_menu(true);                 
        if(!this.tree)
        {
            $('tp-tree-window').appendChild(new Element('div',{id: this.module_name+"_treebox",className:'gridbox'}));
            
            menu = new dhtmlXMenuObject();
            menu.renderAsContextMenu();

            menu.addNewChild(menu.topId, 0, "delete",_lang_common['delete'], false,'','',this.delete_obj.bind(this));                           
            menu.addNewChild(menu.topId, 0, "browse",_lang_gallery['album_parameters'], false,'','',this.edit_album.bind(this));           
            menu.addNewChild(menu.topId, 0, "add_photo",_lang_gallery['add_photo'], false,'','', this.add_photo.bind(this));                       
            menu.addNewChild(menu.topId, 0, "add_photoalbum",_lang_gallery['add_photoalbum'], false,'','',this.add_album.bind(this));           
            
        this.reload();

  
        }else{
            
            $(this.module_name+"_treebox").show();
        
        }       
              
                
/*        this.menuObj = new Proto.Menu(
            {
            selector: '#'+this.module_name+"_treebox",
            className: 'menu firefox',
            fade: true,
            tree: this.tree,
            menuItems: oLinks
            });
 */
        
         var oTabs = [                    
                    {id:'t_firstpage',name: _lang_common['info'],temporal:true},
                    {id:'t_addcontent',name: _lang_gallery['add_photo'], callback: this.add_photo.bind(this)},
                    {id:'t_addalbum',name: _lang_gallery['add_photoalbum'], callback: this.add_album.bind(this)},                 
                    {id:'t_addgallery',name: _lang_gallery['add_gallery'], callback: this.add_gallery.bind(this)},
                    
                ]
         
         this.tabs=new XTRFabtabs('bookmarks',oTabs);  
         this.first_start();
        
        },
        
    first_start:function() {                  
        XTR_main.load_module_tpls(this.module_name, new Array('gallery_first'));
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name,'gallery_first'));        
        this.tabs.makeActiveById('t_firstpage');             
        //this.show_edit_root();
    },
            
    check_gdlib: function()
    {
                this.connector.execute({check_gdlib : true});
                return this.connector.result.gdlib;
            },  
            
    get_action_properties:function(_action,prefix) {
        
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
                                             
                this.connector.execute({get_action_properties :{Action : _action}});
                if (this.connector.result.action_properties) {
                    $(destination_prefix + '_properties').update(this.connector.lct.action_properties);
                } 
                else { 
                    $(destination_prefix + '_properties').update('Свойства отсутствуют'); 
                }
                                            
                xoad.html.importForm('tune_actions',this.connector.result.action_properties_form);
                xoad.html.importForm('tune_actions',this.connector.result.gallery_data);   

                  if (this.connector.result.xlist) {
                    var startgalleryxlist = $('startXlistAlbum');
                    if (startgalleryxlist) {
                            
                        var XpopAlbum = new XTRpop('startXlistAlbum', null, { position: 'bottom', delta_x: -203, delta_y: 0 });
                                
                        columns = $H( { image: ' ', name: _lang_common['name'] });
                        _columnsHeadersWidth = new Array('20px', '150px');
                        _columnsInterpretAs = $H( { image: 'IMAGE' });
                        _images=$H({ group:'xres/ximg/tree/folderClosed.gif', page:'xres/ximg/tree/page.gif' });
                                    
                        xlistAlbum = new XTRxlist(XpopAlbum.tool_tip.id, this.connector,  {
                            permanent: true,
                            resultSource: 'GalleryStart',
                            serverCallFunc:'load_xlist_data_albums',
                            resultIDSource: 'GalleryStartId',
                            columnsHeaders: columns,
                            tableId: 'dialogtable',
                            startWithAncestor: 0,
                            columnsHeadersWidth: _columnsHeadersWidth,
                            columnsInterpretAs: _columnsInterpretAs,
                            images: _images,
                            className: 'dialog-table',
                            include_root_in_selection:true,
                            usebackoff:1
                        });                    
                                            
                        xlistAlbum.connectXpop(XpopAlbum);                    
                        this.validation=new Validation('tune_actions', {immediate : true});
                    }
                }
                 
                  

             if (defaultAction){
                this.get_action_properties(defaultAction,'secondary');    
            }  
                    
                    //this.validation=new Validation('tune_actions', {immediate : true}); 
    },
            

                  
  destructor:function()
     {
      $(this.module_name+"_treebox").hide();
      XTR_main.set_rightside();
      
      this.tabs.destructor();
     }   

});