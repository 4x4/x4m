var XTRsubscribe = Class.create();
var XTR_subscribe;

XTRsubscribe.prototype = Object.extend(new _modulePrototype(), {
    initialize: function() {
        this.destructable = true;
        this.module_name='subscribe';
        this.current_edit_id=null;
        this.connector=null;
        this.tree=null;
        this.leaves_obj_type=new Array('_SUBSCRIBEGROUP');  
        this.init();   
    },

    tree_object_clicked: function(itemid) {
        object_type = this.tree.getNodeParam(itemid, 'obj_type');
        switch(object_type) {
            case "_SUBSCRIBE":
                this.show_edit_subscribe(itemid);
                break;

            case "_SUBSCRIBEGROUP":
                this.show_category(itemid);
                break;            

            default: return false;
        }
    },

    group_dialog: function() {
        columns = $H({image: ' ', name: _lang_common['name']});
        _columnsHeadersWidth = new Array('20px', '150px');
        _columnsInterpretAs = $H({image: 'IMAGE'});
        _columnsInterpretAs = $H({image: 'IMAGE'});
        _images = $H({group: 'img/tree/folderClosed.gif'});
            
        if(arguments[0]) {
            xlist_name=arguments[0];
        }else {            
            xlist_name="xlist";
        }
        
        return xlist = new XTRxlist(xlist_name, this.connector,{
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

    add_category:function() {            
        this.connector.execute({load_news_cats:true, load_news_pages:true,  load_templates:true});               
        XTR_main.load_module_tpls(this.module_name, new Array('add_category','news_chbx'));         
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'add_category'));
        xoad.html.importForm('add_category',this.connector.result);
        var cats = this.connector.result.cats;
        this.push_news(cats); 
        this.validation = new Validation('add_category',{immediate: true});            
    },
    
    push_news: function(cats) {
        var chbxs = '';
        var syntax = /(^|.|\r|\n)(\{%F:\s*(\w+)\s*%})/;
        var t = new Template(XTR_main.get_tpl(this.module_name, 'news_chbx'), syntax);
        cats.each(function(c){
            chbxs += t.evaluate(c);
        });
        $('news_cats').innerHTML = chbxs;
    },
    
    save_category:function() {  
        if(!this.validation.validate())return void(0); 
        formdata=(xoad.html.exportForm('add_category'));
        var news = $('news_cats').getElementsByClassName('nnn');
        news = $A(news);
        var ids = new Array();
        news.each(function(n){
            if (n.checked) ids.push( n.name);   
        });
        ids = ids.join(',');
        this.connector.execute({save_category:{data:formdata,news:ids}});                       
        if(this.connector.result.is_saved) {
            this.tree.refreshItem(1);                        
            this.tabs.createTabNode({
                id: 't_firstpage',
                name: _lang_common['info'],
                temporal: true
                }, 'top', true)
                $('pw-edit').update(_lang_common['group_success_saved']);
        }                        
    },

    save_edited_category:function() {  
        if(!this.validation.validate())return void(0);
        formdata=(xoad.html.exportForm('edit_category'));
        var news = $('news_cats').getElementsByClassName('nnn');
        var ids = new Array();
        news = $A(news);
        news.each(function(n){
            if (n.checked) ids.push( n.name);   
        });
        ids = ids.join(',');        
        this.connector.execute({save_edited_category:{id:this.current_node,data:formdata,news:ids}})
        if(this.connector.result.is_saved) {
            XTR_main.hide_loading();                              
            this.tree.refreshItem(1);                        
            this.tabs.createTabNode({
                id: 't_firstpage',
                name: _lang_common['info'],
                temporal: true
                }, 'top', true);
            $('pw-edit').update(_lang_common['group_success_saved']);
        }else {
            $('pw-edit').update(_lang_common['group_is_not_saved']);    
        }                        
    },

    edit_category:function(b,itemId) {                                                                                                                        
        
        XTR_main.load_module_tpls(this.module_name, new Array('edit_category','news_chbx'));                             
        this.connector.execute({load_category:{id:itemId}, load_news_cats:true, load_news_pages:true, load_templates:true});         
        this.current_node = itemId;   
        this.tabs.createTabNode({id: 't_edit',name: _lang_common['editing'],temporal: true}, 'top', true);
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'edit_category'));
        xoad.html.importForm('edit_category',this.connector.result);
        xoad.html.importForm('edit_category',this.connector.result.cat);
        var selected = this.connector.result.cat.news_cats.split(',');
        var cats = this.connector.result.cats;
        this.push_news(cats);
        var elts = $('news_cats').getElementsByClassName('nnn');
        if(elts) {
            elts = $A(elts);
            elts.each(function(elt){
                selected.each(function(s){
                    if (elt.name==s) elt.checked = true;    
                });
            });
        }
        
        this.validation = new Validation('edit_category',{immediate: true});     
    },     
    
    add_subscribe: function() {            
        var sel = this.tree.getSelectedItemId();     
        

        
        
        if(sel.length != 0) {
            if(this.tree.get_objtype(sel)=='_SUBSCRIBEGROUP') {
                this.step2(sel);
            }
        }else {
            this.step1();
        }
    },
        
    step1: function() {
        this.connector.execute({load_categories:true});            
        
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'add_subscribe_step1',true));
        xoad.html.importForm('add_subscribe',this.connector.result.forms_data);
        this.validation = new Validation('add_subscribe',{immediate: true});
    },
        
    step2: function(sel) {
        var selected = this.tree.getSelectedItemId();
        if(selected.length!=0) {
            if(this.tree.get_objtype(selected)=='_SUBSCRIBEGROUP') {
                category = selected;    
            }
        }else {
            category = $('category').value;
        }
        this.category = category;
        this.connector.execute({load_category_data:{category:category}});
        
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'add_subscribe',true));
        var cats = this.connector.result.cats;
        var inf = this.connector.result.params;
        
        if(inf.message) {
            var templ="message";
            if(inf.html){templ+='_html';}
            XTR_main.load_module_tpls(this.module_name, new Array(templ));
            $('msgplace').innerHTML = XTR_main.get_tpl(this.module_name, templ);
        }
        
        if(inf.news_cats.length > 0) {
            $('newsplace').innerHTML = '';
            var cats = this.connector.result.cats;
            XTR_main.load_module_tpls(this.module_name, new Array('newslist_chbx','newslist','download'));
            var syntax = /(^|.|\r|\n)(\{%F:\s*(\w+)\s*%})/;
            var n = new Template(XTR_main.get_tpl(this.module_name, 'newslist_chbx'), syntax);
            var nlist = new Template(XTR_main.get_tpl(this.module_name, 'newslist'), syntax);
            var nc = '';
            var news = this.connector.result.news;
            if(news!=null) {
                cats.each(function(c) {
                    nc+= nlist.evaluate(c);
                    news.each(function(x){
                    x.checked = 'checked';
                    if(x['ctg_id']==c.id) {    
                        nc+= n.evaluate(x);}    
                    });
                });
                $('newslist').innerHTML = nc;
            }
        }
        $('file_loader').innerHTML = XTR_main.get_tpl(this.module_name, 'download',true);
        $('category').value = category;     
        this.validation = new Validation('add_subscribe',{immediate: true});
    },
        
    add_file: function() {
        XTR_main.load_module_tpls(this.module_name, new Array('file'));
        var fname = $('file_name').value;
        if(fname == ''){ alert (_lang_subscribe['at_first_choose_a_file']);}
        else {
            if(!this.in_list()) {
                var syntax = /(^|.|\r|\n)(\{%F:\s*(\w+)\s*%})/;
                var fl = new Template(XTR_main.get_tpl(this.module_name, 'file'), syntax);
                var r = new Array();
                r['fname'] = fname;
                var cont = fl.evaluate(r);
                a = new Insertion.Bottom('filelist', cont);
            }
        }
    },
        
    in_list: function() {
        var flist = $('filelist').getElementsByClassName('files');    
        flist = $A(flist);
        var bool = false;
        flist.each(function(file) {
            if(file.name==$('file_name').value) {bool = true;}
        });
        return bool;
    },
        
    delete_file: function(elt) {
        var el1 = elt.up().up();
            el1.remove();    
    },
    
    save_subscribe:function(e) {
        if(!this.validation.validate()){return void(0)};
        
        var data = xoad.html.exportForm('add_subscribe'); 
        var newslist = $('newslist').getElementsByClassName('news');
        var ids = new Array();
        newslist = $A(newslist);
        if(newslist.length > 0) {
            newslist.each(function(s) {
                if(s.checked) ids.push(s.id);    
            });
            newsids = ids.join(',');
        } else  
            newsids = '';
        
        var files = $('filelist').getElementsByClassName('files');
         
        files = $A(files);
        fff = new Array();
        if(files.length != 0) {
            files.each(function(f) {
                fff.push(f.name);
            });
            files = fff.join(',');
        } else {
            files = '';
        }
        
        this.connector.execute({save_subscribe:{main:data,news:newsids,files:files}});
          
      if(this.connector.result.is_saved) {
         this.show_category(this.category);                                             
         XTR_main.set_result(_lang_subscribe['dispatch_success_saved'], e);
         $('pw-edit').update(_lang_subscribe['dispatch_saved']);
      }
   },

   show_edit_subscribe: function(evt) {
       function in_array(arr, value) {
           for(var i in arr) {
               if(arr[i] == value) return true;
           }
           return false;
       }    

       cat = this.tree.getSelectedItemId();              
       this.tabs.createTabNode({id: 't_edit',name: _lang_common['editing'],temporal: true}, 'top', true);

       XTR_main.load_module_tpls(this.module_name, new Array('edit_subscribe'));
       //elt = Event.element(evt);
       //id = elt.params.get('id');
       id = Number(evt);
            
       this.connector.execute({load_subscribe_data:{id:id}, load_category_data:{category:cat}});

       this.cat_data = this.connector.result.cat_data;
       var syntax = /(^|.|\r|\n)(\{%F:\s*(\w+)\s*%})/;
       var data = this.connector.result.data;
       var cat_data = this.connector.result.cat_data;
       
       XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'edit_subscribe',true));                                               
       var plus = '';
                
       if (cat_data.html) plus = "_html";
       XTR_main.load_module_tpls(this.module_name, new Array('message'+plus));
                
       $('msgplace').innerHTML = XTR_main.get_tpl(this.module_name, 'message'+plus);
       $('message').value = data.msg;
       xoad.html.importForm('edit_subscribe',this.connector.result.data);
       $('category').value =   this.tree.getSelectedItemId();
//       if (cat_data.files) {
           XTR_main.load_module_tpls(this.module_name, new Array('file','download'));
           $('file_loader').innerHTML = XTR_main.get_tpl(this.module_name, 'download');
           if (data.files.length!=0) {
               var flist = '';
               var fl = new Template(XTR_main.get_tpl(this.module_name, 'file'), syntax);
               var files = data.files.split(',');
               var r= new Array();
               files.each(function(f){
                   r['fname'] = f;
                   flist += fl.evaluate(r);
               });
               
               $('filelist').innerHTML = flist;
           }
//       }
       
       if(data.news.length!=0) {
           var nids = data.news.split(',');
           XTR_main.load_module_tpls(this.module_name, new Array('newslist','newslist_chbx'));
           $('newsplace').innerHTML = '';
           var cats = this.connector.result.cats;
           XTR_main.load_module_tpls(this.module_name, new Array('newslist_chbx','newslist','download'));
           var n = new Template(XTR_main.get_tpl(this.module_name, 'newslist_chbx'), syntax);
           var nlist = new Template(XTR_main.get_tpl(this.module_name, 'newslist'), syntax);
           var nc = '';
           news = this.connector.result.news;
           if(news!=null){
           if (news.length!=0) {
               cats.each(function(c){
                   nc+= nlist.evaluate(c);
                   news.each(function(x){
                       if (x.ctg_id == c.id) nc += n.evaluate(x);
                   });
               });
               
               $('newslist').innerHTML = nc;
               var ns = $('newslist').getElementsByClassName('news');
               ns = $A(ns);
               ns.each(function(n){
                   if (in_array(nids,n.id)){
                       n.checked = true;
                   }
               });
           }
           }
       }
       
       $('id').value = id;                    
       this.validation = new Validation('edit_subscribe', {immediate:true});
   },
    
    save_edited_subscribe:function(e) {
        if(!this.validation.validate()){return void(0)};
        var data = xoad.html.exportForm('edit_subscribe'); 
        
        newsids = '';
        if (this.cat_data.news_cats){
            var newslist = $('newslist').getElementsByClassName('news');
            newslist = $A(newslist);
            var ids = new Array();
            if(newslist.length > 0)
            {
            newslist.each(function(s){
                if (s.checked) ids.push(s.id);    
            });
            newsids = ids.join(',');
            }
        }

        files = '';
        
        if (this.cat_data.files){
            var files = $('filelist').getElementsByClassName('files');
            files = $A(files);
            fff = new Array();
            if (files.length!=0){
                files.each(function(f){
                    fff.push(f.name);
                }
                );
                files = fff.join(',');
            }
        }
                                        
        this.connector.execute({update_subscribe:{main:data,news:newsids,files:files}});
            if(this.connector.result.is_saved) {
                XTR_main.set_result(_lang_subscribe['dispatch_success_saved'], e);
            }
            
            else XTR_main.set_result(_lang_common['preservation_error'], e);   
    },
    
    
    delete_subscribe: function(evt){
            if (!confirm(_lang_subscribe['you_are_assured_what_wish_to_remove_the_chosen_dispatch'])){return false;}
        id = this.gridlist.getSelectedRowId(1);            
        this.connector.execute({delete_subscribe:{id:id}});
            if (this.connector.result.deleted)
            {
               this.gridlist.deleteSelectedRows();
               XTR_main.set_result(_lang_subscribe['subscribe_deleted']);
            }
    },
    
    
    paste_obj:function(anc) {
        if(this.cp_buffer == null) return false;
           
        obj_type = this.tree.get_objtype(anc)                
        //this.leaves_obj_type-????? ????????? ??????
        if(this.leaves_obj_type.indexOf(obj_type) == false) {               
            anc = this.tree.getParentId(anc);         
        }              
        
        this.connector.execute({ _copy :{anc: anc,node: this.cp_buffer}});
          
        if(this.connector.result.nodecopy) {
            this.tree.refreshItem(anc);
        }
    },
    
    start_subscribe: function() {
        
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name,'start_subscribe',true));
    },
        
    subscribe: function(step) {
        if(step == 0) {                          
            this.connector.execute({start_subscribe:{first:true}});
            
            if(this.connector.result.error) {
                s = document.createElement('div');
                s.innerHTML = _lang_subscribe['dispatches_expecting_sending_are_not_present'];
                $('status_container').appendChild(s);
            }

            if(this.connector.result.ready) {
                s = document.createElement('div');
                s.innerHTML = _lang_subscribe['it_is_prepared'] + this.connector.result.scount + _lang_subscribe['dispatch(es) for'] + this.connector.result.ucount + _lang_subscribe['user(s)']
                $('ready_container').appendChild(s);
                this.connector.execute({start_subscribe:{first:false}});
                setTimeout('XTR_subscribe.subscribe(' + (++step) + ')', 10)
            } 
        
        }else {
            if(this.connector.result.next) {
                
                if(this.connector.result.sended) {
                    s = document.createElement('div');

                    
                        s.innerHTML = _lang_subscribe['dispatch'] + _lang_common['address'] + this.connector.result.sended.email;                            
                    
                    $('status_container').appendChild(s);    
                } 
            
                this.connector.execute({start_subscribe:{first:false}});
                setTimeout('XTR_subscribe.subscribe(' + (++step) + ')', 10)
            }
            
            if(this.connector.result.complete) {
            
                s = document.createElement('div');
                s.innerHTML = _lang_subscribe['dispatch_is_finished'];
                $('status_container').appendChild(s);                            
            }
        }    
     },
     
    users_list: function() {
        
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name,'first_start',true));       
    },        
    
    build_interface: function() {
            toggle_main_menu(true);     
        if(!this.tree) {        
        

            menu = new dhtmlXMenuObject();
            menu.renderAsContextMenu();
            menu.addNewChild(menu.topId, 0, "delete",_lang_common['delete'], false,'','',this.delete_obj.bind(this));                           
            
            menu.addNewChild(menu.topId, 0, 'add_dispatch',_lang_price['add_price'], false,'','', this.add_subscribe.bind(this));                       
            menu.addNewChild(menu.topId, 0, "add_group",_lang_common['add_group'], false,'','',this.add_category.bind(this));           
            
            menu.addNewChild(menu.topId, 0, "userlist",_lang_subscribe['list_subscribers'], false,'','',this.show_users_list.bind(this));           
            menu.addNewChild(menu.topId, 0, "edit_category",_lang_common['edit'], false,'','',this.edit_category.bind(this));           
            menu.addNewChild(menu.topId, 0, "copy",_lang_common['copy'], false,'','',this.copy_obj.bind(this));           
            menu.addNewChild(menu.topId, 0, "paste",_lang_common['paste'], false,'','',this.paste_obj.bind(this));           
            
            tree_node = new Element('div',{id: this.module_name+"_treebox",className:'treebox'});
            $('tp-tree-window').appendChild(tree_node);
            
            this.tree = new dhtmlXTreeObject(this.module_name+"_treebox", 'auto', 'auto', "0");
            this.tree.enableDragAndDrop(1);                        
            this.tree.setImagePath("/xres/ximg/green/");          
            this.tree.enableContextMenu(menu);       
            this.tree.setOnDblClickHandler(this.tree_object_clicked.bind(this));        
            this.tree.setDragHandler(this.on_tree_drag.bind(this)); 
            this.tree.setDataMode("json");
            
            this.tree.setXMLAutoLoading("tree_xml_server.php?tree=subscribe_container");
            this.tree.loadJSON("tree_xml_server.php?tree=subscribe_container&id=0",function(){
                    this.tree.refreshItem(1);                  
                }.bind(this));        
                        
            //this.tree.setObjImages({_SUBSCRIBEGROUP:'folderClosed.gif',_PAGE:'page.gif'});
            //this.tree.setInheritanceArr(this.connector.result.tree_inheritance);
        }else {
            $(this.module_name+"_treebox").show();
        } 
        

               
        var oTabs = [					
            {id:'t_firstpage',name: _lang_common['info'],callback: this.first_start.bind(this),temporal:true},
            {id:'t_addcontent',name: _lang_subscribe['add_dispatch'], callback: this.add_subscribe.bind(this)},
            {id:'t_addgroup',name: _lang_common['add_group'], callback: this.add_category.bind(this)},
            {id:'t_startsubscr',name: _lang_subscribe['begin_dispatch'], callback: this.start_subscribe.bind(this)},
            {id:'t_adduser',name: _lang_subscribe['operations_over_subscribers'], callback: this.user_operations.bind(this)}
		]

        this.tabs=new XTRFabtabs('bookmarks',oTabs);  
        this.first_start();
        this.connector.onexecuteError = function(error) {
		    alert(_lang_common['error_on_server']+'\n\n' + error.message);
		};
         
        
    },
    
    first_start:function() {                  
        
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name,'subscribe_first',true));        
        this.tabs.makeActiveById('t_firstpage');
    },
            
    user_operations: function() {
        XTR_main.load_module_tpls(this.module_name, new Array('users_operations','subscribe_chbx'));
        XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name,'users_operations'));
        var syntax = /(^|.|\r|\n)(\{%F:\s*(\w+)\s*%})/;
        var t = new Template(XTR_main.get_tpl(this.module_name, 'subscribe_chbx'), syntax);
        var subs = '';
        this.connector.execute({get_subscribeslist:true});
        
        if(cats = this.connector.result.cats) {
            cats.each(function(cat){
                subs += t.evaluate(cat);
            });
            $('slist').innerHTML = subs;    
        }
            
        this.search_users_list();
        this.add_validation = new Validation('add_user', {immediate : true});
        
    },
        
    save_user: function(e) {
        if(!this.add_validation.validate()){return void(0)}; 
        email = $('addemail').value;
        var ctg = new Array();
        
        if(chbxs = $('slist').getElementsByClassName('chbx')) {
            chbxs = $A(chbxs);
            chbxs.each(function(ch) {
                if (ch.checked) ctg.push(ch.value);
            });    
        }
        
        if (ctg.length == 0) {
                alert (_lang_subscribe['select_dispatch']);
        }else {
            
            this.connector.execute({add_user:{email:email,cats:ctg}});
            
            if(this.connector.result.saved) {
                XTR_main.set_result(_lang_common['user'] + email + _lang_common['successfully_added'], e);
                $('addemail').value = '';
                this.search_users_list();
            }else {
                XTR_main.set_result(_lang_subscribe['user_already_exists'], e);        
            }    
        }
    },
    
    

   show_users_list: function() 
   {
       if(!this.jump_from_edit) {               
           this.current_subscribe = this.tree.getSelectedItemId();
       }
       else {
           this.current_subscribe = this.jump_from_edit;
           this.jump_from_edit = null;
       }
       
                    menu = new dhtmlXMenuObject();
                    menu.renderAsContextMenu();
                    menu.addNewChild(menu.topId, 0, "delete", _lang_common['delete'], false, '', '', this.delete_users.bind(this));
                    $('pw-edit').update('');
                    $('pw-edit').className='pw-edit-b';
                    $('pw-edit').insert('<div id="t-container"></div>');
                    this.gridlist = new dhtmlXGridObject('t-container');   
                    this.gridlist.selMultiRows = true;
                    this.gridlist.setImagePath("xres/ximg/grid/imgs/");
                    this.gridlist.setHeader('id,'+_lang_subscribe['email']+','+_lang_subscribe['status']);
                    this.gridlist.setInitWidths("100,*,100");
                    this.gridlist.setColAlign("center,left,center");
                    this.gridlist.attachHeader("#text_filter,#text_filter,#text_filter");
                    this.gridlist.setColTypes("ro,ro,ro");
                    this.gridlist.setColSorting("int,str,str");
                    this.gridlist.enableAutoWidth(true);
                    this.gridlist.attachEvent("onRowDblClicked", this.show_edit_user.bind(this));
                    this.gridlist.enableContextMenu(menu);
                    this.gridlist.init();
                    this.gridlist.setSkin("modern");
                    this.connector.execute({load_subscribers_list:{anc_id:this.current_subscribe}});            
                    
                    if(this.connector.result.data_set)
                    {
                        this.gridlist.parse(this.connector.result.data_set,"xjson")
                    }
   },
   
   
   search_users_list: function() 
   {
       menu = new dhtmlXMenuObject();
       menu.renderAsContextMenu();
       menu.addNewChild(menu.topId, 0, "delete", _lang_common['delete'], false, '', '', this.delete_users.bind(this));
       
       this.gridlist = new dhtmlXGridObject('userslist');   
       this.gridlist.selMultiRows = true;
       this.gridlist.setImagePath("xres/ximg/grid/imgs/");
       this.gridlist.setHeader('id,'+_lang_subscribe['email']+','+_lang_subscribe['status']);
       this.gridlist.setInitWidths("100,*,100");
       this.gridlist.setColAlign("center,left,center");
       this.gridlist.attachHeader("#text_filter,#text_filter,#text_filter");
       this.gridlist.setColTypes("ro,ro,ro");
       this.gridlist.setColSorting("int,str,str");
       this.gridlist.enableAutoWidth(true);
       this.gridlist.attachEvent("onRowDblClicked", this.show_edit_user.bind(this));
       this.gridlist.enableContextMenu(menu);
       this.gridlist.init();
       this.gridlist.setSkin("modern");
       this.connector.execute({load_subscribers_list:{}});
       
        if(this.connector.result.data_set)
        {
            this.gridlist.parse(this.connector.result.data_set,"xjson")
        }
   },
   
   
   show_edit_user: function(evt,id) {
       this.tabs.createTabNode({id: 'tedit_user', name: _lang_common['editing'], temporal: true}, 'top', true);
       XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'edit_user',true));

       if(!id) {
           //elt = Event.element(Number(evt));
           //id = elt.params._object.id;
       } else {
           //$('back').hide();
           id = Number(evt);
       }

       this.connector.execute({get_user_data:{id:id, subscribe_id:this.current_subscribe}, get_subscribeslist:true});       
           $('user_email').innerHTML = this.connector.result.user.email;
           var syntax = /(^|.|\r|\n)(\{%F:\s*(\w+)\s*%})/;
           var t = new Template( XTR_main.get_tpl('subscribe', 'subscribe_chbx',true), syntax);
           
           if(cats = this.connector.result.cats) {
               subs = '';
               cats.each(function(cat) {
                   subs += t.evaluate(cat);
               });
                    
           $('slist').innerHTML =  subs;           
           xoad.html.importForm('cats',this.connector.result.user);
           xoad.html.importForm('edit_user',this.connector.result.user);    
       }           
   },
   
   save_edited_user: function() {
        this.jump_from_edit = this.current_subscribe;
        data = xoad.html.exportForm('edit_user');
        cats = xoad.html.exportForm('cats');
        subscribes = new Array();
        
        for (var key in cats) {
            ind = key.split('_')[1];
            if (cats[key]) subscribes.push(ind);    
        }
            
        this.connector.execute({save_edited_user:{data:data,cats:subscribes}});
        
        if (this.connector.result.saved) {
            XTR_main.set_result(_lang_subscribe['changes_successfully_saved']);
        }
        else {
            XTR_main.set_result(_lang_common['preservation_error']);
        }
   },
   
   

    delete_users: function(evt){
            if (!confirm(_lang_subscribe['you_are_assured_what_wish_to_remove_user'])){return false;}
        id = this.gridlist.getSelectedRowId(1);            
        this.connector.execute({delete_user:{id:id}});
            if (this.connector.result.deleted)
            {
               this.gridlist.deleteSelectedRows();
               XTR_main.set_result(_lang_subscribe['user_deleted']);
            }
    },
   
   
   find_user: function(e) {
       if(!this.validation1.validate()) {return void(0)};
       
       email = $('searchemail').value;
       this.connector.execute({find_user:{email:email}});

       if(this.connector.result.user) {
           XTR_subscribe.show_edit_user(null,this.connector.result.user.id);        
       }else {
           XTR_main.set_result(_lang_subscribe['user_is_not_found'], e);
       }
   },
    
   show_category:function(id) {    
       
       XTR_main.set_rightside_eform(XTR_main.get_tpl(this.module_name, 'subscribe_first'),'b');
       this.tabs.createTabNode({
           id: 'tedit_page',
           name: _lang_subscribe['list_dispatches'],
           temporal: true
       }, 'top', true);
       
       
            menu = new dhtmlXMenuObject();
            menu.renderAsContextMenu();
            menu.addNewChild(menu.topId, 0, "delete", _lang_common['delete'], false, '', '', this.delete_subscribe.bind(this));
            
            $('pw-edit').insert('<div id="t-container"></div>');
            
            this.gridlist = new dhtmlXGridObject('t-container');   
            this.gridlist.selMultiRows = true;
            this.gridlist.setImagePath("xres/ximg/grid/imgs/");
            this.gridlist.setHeader('id,'+_lang_common['date']+','+_lang_subscribe['dispatch']+','+_lang_common['status']);
            this.gridlist.setInitWidths("100,100,*,200");
            this.gridlist.setColAlign("center,left,left,left");
            this.gridlist.attachHeader("#text_filter,#text_filter,#text_filter,#text_filter");
            this.gridlist.setColTypes("ed,ed,ed,ed");
            this.gridlist.setColSorting("int,date_rus,str,str");
            this.gridlist.enableAutoWidth(true);
            this.gridlist.setMultiLine(true);
            this.gridlist.attachEvent("onRowDblClicked", this.show_edit_subscribe.bind(this));    
            this.gridlist.enableContextMenu(menu);  
            this.gridlist.init();
            this.gridlist.setSkin("modern");
               
            this.connector.execute({load_subscribe_list:{id:id}});            
                    if(this.connector.result)
                    {
                        this.gridlist.parse(this.connector.result.data_set,"xjson")
                    }
      
    },
    
    get_action_properties:function(_action,prefix) {
        if(prefix!='action' && prefix!='secondary') {
            destination_prefix = 'action';
            defaultAction = prefix;
        }else if(prefix=='secondary') {
            destination_prefix = prefix;
            defaultAction = null;    
        }
        else {
            destination_prefix = prefix;
            defaultAction = null;
        }             
        
        this.connector.module_name = 'subscribe';                   
        this.connector.execute({get_action_properties:{Action:_action}});
        
        if(this.connector.result.action_properties) {
            $(destination_prefix + '_properties').update(this.connector.lct.action_properties);                               
        }
                   
        if(this.connector.result.action_signals) {
            $('action_signals').update(this.connector.lct.action_signals);                               
        }
        
        xoad.html.importForm('tune_actions',this.connector.result.action_properties_form);

        if(this.connector.result.xlist) {
            columns=$H({image:' ',name:_lang_common['name']});
            _columnsHeadersWidth=new Array('20px','150px');
            _columnsInterpretAs=$H({image:'IMAGE'});
            _images=$H({group:'img/tree/folderClosed.gif', page:'img/tree/page.gif'});           
            var Xpop = new XTRpop('startXXlist',null,{position:'bottom',delta_x:-272,delta_y:-60});                        
            
            xlist = new XTRxlist(Xpop.tool_tip.id,this.connector,{
                resultSource:'showsubscribeName',
                resultIDSource:'subscribeId',
                columnsHeaders:columns,
                tableId:'dialogtable',
                columnsHeadersWidth:_columnsHeadersWidth,
                columnsInterpretAs:_columnsInterpretAs,
                images:_images,
                className:'dialog-table'
            });
            
            
            xlist.connectXpop(Xpop);
        }
        
        if(defaultAction) {
            this.get_action_properties(defaultAction,'secondary');    
        }                    
        
        this.validation=new Validation('tune_actions', {immediate : true}); 
    },

    
                  
    destructor:function() {
        $(this.module_name+"_treebox").hide();
        XTR_main.set_rightside();
        
        this.tabs.destructor();
    }   
});