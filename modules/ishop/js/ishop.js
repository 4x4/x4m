var XTRishop = Class.create();
var XTR_ishop;

XTRishop.prototype=Object.extend(new _modulePrototype(), {
    initialize: function() {
        this.destructable = true;
        this.module_name = 'ishop';
        this.current_edit_id = null;
        this.tree = null;
        this.scheme = new Hash();
        this.connector = null;
        this.init();
    },
   
    build_interface: function() {
        //recieve tpl from server
         var oTabs = [					                    
            {id:'t_orders',name: _lang_ishop['orders'],callback: this.show_orders.bind(this)},                   
            {id:'t_paySystem',name: _lang_ishop['payment_systems'], callback: this.show_pay_systems.bind(this)},
            {id:'t_disount',name: _lang_ishop['schemes_discounts'], callback: this.discounts.bind(this)}, 
            {id:'t_currency',name: _lang_common['currency'], callback: this.currency.bind(this)},
            {id:'t_tunes',name: _lang_common['options'], callback: this.tunes.bind(this)} 
		 ]
         
         this.connector.execute({get_module_options:true});
         this.options = this.connector.result.options;
         XTR_main.switch_main_state('sv_container');                                                  
         this.tabs = new XTRFabtabs('bookmarkssv',oTabs);  
         this.tabs.makeActiveById('t_orders');         
         toggle_main_menu(true);        
    },
     
    currency_window:function(title)
    {
            w=XTR_main.dhxWins.createWindow("currency_edit", 20, 10, 400, 430);
            w.centerOnScreen();
            w.setText(title);
            w.attachHTMLString(XTR_main.get_tpl('ishop', 'add_currency',true));
            this.validation = new Validation('currency_edit',
            {
                immediate: true
            });
        
    }, 
     
    add_currency:function()
    {
            this.currency_window(_lang_ishop['add_currency']);            
    },
    edit_currency:function(id)
    {
            this.currency_window(_lang_ishop['edit_currency']);
            this.connector.execute({load_currency:{id:id}});                                
            this.currentId=id;
            xoad.html.importForm('currency_edit',this.connector.result.currency);  
            
    }
    ,
    save_currency:function()
    {
                if(!this.validation.validate())return void(0);                        
                fdata = xoad.html.exportForm('currency_edit');
                if(this.currentId){                
                    fdata.id=this.currentId;
                    this.currentId=null;
                }  
                this.connector.execute({save_currency:{data:fdata}});                                
                XTR_main.dhxWins.window('currency_edit').close();
                this.reload_currency();
    
    },
    del_currency:function()
    {
        this.delete_obj_grid(this.gridlist,'del_currency') ;
    },
    
    change_main_currency:function(id)
    {
        this.connector.execute({change_main_currency:{id:id}});                                
        this.reload_currency();
    },
    
    reload_currency:function()
    {
        this.connector.execute({load_rates_data:true});                                
                    if(this.connector.result.data_set)
                    {
                        this.gridlist.clearAll();                     
                        this.gridlist.parse(this.connector.result.data_set,"xjson")
                    }
        
    },
    currency:function()
    {
                    XTR_main.set_svside(XTR_main.get_tpl(this.module_name, 'currency', true));
                    menu = new dhtmlXMenuObject();
                    menu.renderAsContextMenu();
                    menu.addNewChild(menu.topId, 0, "delete",_lang_common['delete'], false,'','',this.del_currency.bind(this));
                    this.gridlist = new dhtmlXGridObject('currencylist');   
                    this.gridlist.selMultiRows = true;

                    this.gridlist.setImagePath("xres/ximg/grid/imgs/");
                    this.gridlist.setHeader('id,'+_lang_ishop['currency_name']+','+_lang_ishop['currency_alias']+','+_lang_ishop['rate']+','+_lang_ishop['currency_is_main']);
                    this.gridlist.setInitWidths("100,300,300,150,150");
                    this.gridlist.setColAlign("center,left");
                    this.gridlist.attachHeader("#text_filter,#text_filter,#text_filter,#text_filter,#text_filter");
                    this.gridlist.setColTypes("ro,ro,ro,ro,ra");
                    
                    this.gridlist.setColSorting("int,str,str,str,str");
                    this.gridlist.enableAutoWidth(true);
                    this.gridlist.setMultiLine(true);
                    
                    this.gridlist.attachEvent("onCheckbox", this.change_main_currency.bind(this));
                    this.gridlist.attachEvent("onRowDblClicked", this.edit_currency.bind(this));
                    this.gridlist.enableContextMenu(menu);  
                    this.gridlist.init();
                    this.gridlist.setSkin("modern");
                    
                    this.reload_currency();

        
    }, 
        
    group_dialog: function() {

        columns = $H({image:' ',name:_lang_common['name']});
        _columnsHeadersWidth = new Array('20px', '150px');
        _columnsInterpretAs = $H({image:'IMAGE'});
        _images = $H({group:'xres/ximg/tree/folderClosed.gif',page:'xres/ximg/tree/page.gif'});
        
        xlist_name = (arguments[0]) ? arguments[0] : 'xlist';
        fcall = Object.isUndefined(arguments[1]) ? 'load_xlist_data' : arguments[1];
        startwith = Object.isUndefined(arguments[2]) ? 0 : arguments[2];
        resultFunc = Object.isUndefined(arguments[3]) ? 0 : arguments[3];
            
        return xlist = new XTRxlist(xlist_name, this.connector, {
            permanent:true,
            resultSource:'showGroup',
            serverCallFunc:fcall,
            resultIDSource:'showGroupId',
            columnsHeaders:columns,
            tableId:'dialogtable',
            resultFunc:resultFunc,
            startWithAncestor:startwith,
            columnsHeadersWidth:_columnsHeadersWidth,
            columnsInterpretAs:_columnsInterpretAs,
            images:_images,
            className:'dialog-table'
        });
    },    
        
        

    
    delete_schemeitem:function(id) {
        this.scheme.get(id).source.remove();
        delete this.scheme.unset(id);
    },

    add_dscheme:function() {   
        
        this.scheme = new Hash();
        this.currentId = null;
        
        this.tabs.createTabNode(
            {
            id: 'tedit_page',
            name:_lang_ishop['add_sheme'],
            temporal: true
            }, 'top', true);
            
        XTR_main.set_svside(XTR_main.get_tpl(this.module_name, 'add_dscheme',true));            
        var Xpop = new XTRpop('startXlist', null, {position: 'bottom',delta_x: -350,delta_y: 0});
        xlist = this.group_dialog(Xpop.tool_tip.id);
        xlist.connectXpop(Xpop);
        
    },
                    
    _add_to_scheme:function(id,name,discount) {                        
        if(!Object.isUndefined(this.scheme.get(id))) {
            alert(_lang_ishop['discount_for_the_given_category_is_already_appointed']);
            return;
        }    
                        
        if(name) {
            discount = parseInt(discount);                        
            if(isNaN(discount)) discount = 0;
            li = document.createElement('li');
            li.innerHTML = '<div class="prop">'+name+'</div><div class="prop"><input size="2" value="'+discount+'"></div><a href="javascript:XTR_ishop.delete_schemeitem('+id+')">'+_lang_common['delete']+'</div>';
            this.scheme.set(id,{name:name,discount:discount,source:li});                                                  
            $('schemeitems').appendChild(li);
        }
    },

    add_to_scheme:function() {
        
        fdata = xoad.html.exportForm('add_dscheme'); 
                
            
        if((fdata.showGroupId != '') && (fdata.discount != '')) {
            $('showGroup').value = ''; 
            $('showGroupId').value = '';
            $('discount').value = '';
            this._add_to_scheme(fdata.showGroupId,fdata.showGroup,fdata.discount);    
        }
    },
               
    save_dscheme:function() 
    {
        if(this.scheme.size() > 0) 
            {
                rpr= new Array();    
                this.scheme.each(function(pair) 
                {
                    pair[1].source = '';
                    pair[1].Name = '';
                    pair[1].catid = pair[0];
                    rpr.push(pair[1]);
                });            
                    
               fdata = xoad.html.exportForm('dscheme');             
               this.connector.execute({save_dscheme:{dscheme:fdata,ditems:rpr,id:this.currentId}});
               this.discounts(); 
                
        }else{
            alert('scheme empty!');
        }
        
                    
    },
        
    discounts:function() {
                    XTR_main.set_svside(XTR_main.get_tpl(this.module_name, 'discounts',true));
                    menu = new dhtmlXMenuObject();
                    menu.renderAsContextMenu();
                    menu.addNewChild(menu.topId, 0, "delete",_lang_common['delete'], false,'','',this.del_discount_scheme.bind(this));
                    this.gridlist = new dhtmlXGridObject('xdiscountslist');   
                    this.gridlist.selMultiRows = true;
                    this.gridlist.setImagePath("xres/ximg/grid/imgs/");
                    this.gridlist.setHeader('id,'+_lang_ishop['name_scheme_discounts']);
                    this.gridlist.setInitWidths("100,400");
                    this.gridlist.setColAlign("center,left");
                    this.gridlist.attachHeader("#text_filter,#text_filter");
                    this.gridlist.setColTypes("ro,ro");                    
                    this.gridlist.setColSorting("int,str");
                    this.gridlist.enableAutoWidth(true);
                    this.gridlist.setMultiLine(true);
                    this.gridlist.attachEvent("onRowDblClicked", this.edit_discount_scheme.bind(this));
                    this.gridlist.enableContextMenu(menu);  
                    this.gridlist.init();
                    this.gridlist.setSkin("modern");
                    
                    
                    this.connector.execute({discount_schemes:true});                                
                    if(this.connector.result.data_set)
                    {
                        this.gridlist.parse(this.connector.result.data_set,"xjson")
                    }
                    

    },  
        
    edit_discount_scheme:function(id) {
        this.scheme = new Hash();
        XTR_main.load_module_tpls(this.module_name, new Array('add_dscheme'));     
        XTR_main.set_svside(XTR_main.get_tpl(this.module_name, 'add_dscheme'));            
        var Xpop = new XTRpop('startXlist', null, {position: 'bottom',delta_x: -350,delta_y: 0});
        xlist = this.group_dialog(Xpop.tool_tip.id);
        xlist.connectXpop(Xpop);        
        this.connector.execute({load_scheme:{id : id}});
        this.currentId = id;
        xoad.html.importForm('dscheme', this.connector.result.dscheme);
        items = $H(this.connector.result.schemeitems);
        items.each(function(pair) {                
            this._add_to_scheme(pair[1].catid,pair[1].Name,pair[1].discount);
        }.bind(this));
    },
        
    del_discount_scheme:function(kid,id) {
        if(!confirm(_lang_ishop['you_really_wish_to_remove_this_scheme'])) return false;
        this.connector.execute({del_discount_scheme:{id:id}});
        
        if(this.connector.result.isDel)
        {
            this.gridlist.deleteSelectedRows();
        }
        
        
    },
    
    save_tunes:function() {
        formdata = xoad.html.exportForm('tunes');                
        this.connector.execute({save_tunes:formdata});
        XTR_main.set_result(_lang_ishop['options_success_saved']);          
    },
    
    tunes:function() {
        XTR_main.load_module_tpls(this.module_name, new Array('tunes'));                                   
        XTR_main.set_svside(XTR_main.get_tpl(this.module_name, 'tunes'));                        
        this.connector.execute({load_tunes:true}); 
        xoad.html.importForm('tunes',this.connector.result.tunes); 
   },             
   
   
   show_edit_paysystem:function(kid,id)
   {
       this.connector.execute({show_websystem:{id : kid}}); 
       XTR_main.set_svside(this.connector.result.template);
       if(this.connector.result.data)
       {
            xoad.html.importForm('paysystem',this.connector.result.data);
       }
       this.validation = new Validation('paysystem',
            {
                immediate: true
            });
        
   },
   
   save_paysystem:function()
   {
       if(!this.validation.validate())return void(0);                        
       fdata = xoad.html.exportForm('paysystem');
       this.connector.execute({save_websystem:{data : fdata}}); 
   },   
   
   show_pay_systems:function()
   {
                    XTR_main.set_svside(XTR_main.get_tpl(this.module_name, 'paysystems',true));
                    menu = new dhtmlXMenuObject();
                    menu.renderAsContextMenu();
                    
                    this.gridlist = new dhtmlXGridObject('paysystems');   
                    this.gridlist.selMultiRows = true;
                    this.gridlist.setImagePath("xres/ximg/grid/imgs/");
                    this.gridlist.setHeader('id,'+_lang_common['name']);
                    this.gridlist.setInitWidths("100,*");
                    this.gridlist.setColAlign("center,left");
                    this.gridlist.attachHeader("#text_filter,#text_filter");
                    this.gridlist.setColTypes("ro,ro");
                    this.gridlist.setColSorting("int,str");
                    this.gridlist.enableAutoWidth(true);
                    this.gridlist.setMultiLine(true);
                    
                    this.gridlist.attachEvent("onRowDblClicked", this.show_edit_paysystem.bind(this));
                    this.gridlist.enableContextMenu(menu);  
                    this.gridlist.init();
                    this.gridlist.setSkin("modern");
                    
                    this.connector.execute({load_paysystems:true});       
                                             
                    if(this.connector.result.data_set)
                    {
                        this.gridlist.parse(this.connector.result.data_set,"xjson")
                    }
                       
             

   },
        
             
   save_order:function() {
       this.connector.execute({save_order:{data:{form:xoad.html.exportForm('order'),id : this.current}}}); 
   },        
             
   show_edit_order:function(id) {
    
             this.tabs.createTabNode(
            {
                id: 'tedit_order',
                name:_lang_common['editing'],
                temporal: true
            }, 'top', true);  
       
       
                        this.connector.execute({edit_order:{id : id}}); 
                        this.current = id;
                        XTR_main.set_svside(this.connector.result.order);                        
                        xoad.html.importForm('order',this.connector.result.formdata);
     
                        this.gridlist = new dhtmlXGridObject('xgoodslist');   
                        this.gridlist.selMultiRows = true;
                        this.gridlist.setImagePath("xres/ximg/grid/imgs/");
                        this.gridlist.setHeader(_lang_ishop['number_in_catalog']+','+_lang_ishop['goods_name']+','+_lang_ishop['price']+','+_lang_ishop['quantity']+','+_lang_ishop['sum']+','+_lang_ishop['comments']);
                        this.gridlist.setInitWidths("100,*,100,130,150,300");
                        this.gridlist.setColAlign("center,left,center,center,center,left");
                        this.gridlist.attachHeader("#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter");
                        this.gridlist.setColTypes("ro,ro,ro,ro,ro,ro");
                        this.gridlist.setColSorting("int,str,str,str,str,str");
                        this.gridlist.enableAutoWidth(true);
                        this.gridlist.setMultiLine(true);
                        this.gridlist.init();
                        this.gridlist.setSkin("modern");


                    this.connector.execute({load_goods_data:{id:id}});            
                    
                    if(this.connector.result.data_set)
                    {
                       this.gridlist.parse(this.connector.result.data_set,"xjson")
                    }  
             
    
   },
    
        del_order : function() 
        {
        id=this.gridlist.getSelectedRowId(1);
        if(id.length>0)
        {                
            if (!confirm(_lang_ishop['you_really_wish_to_remove_this_order'])) 
            {
                return false;
            }
            
            this.connector.execute({delete_order:{id :id}});
            if (this.connector.result.isDel)
            {
               this.gridlist.deleteSelectedRows();
            }
        }
    },

   show_orders:function() {
                   XTR_main.set_svside(XTR_main.get_tpl(this.module_name, 'orders',true));
                    menu = new dhtmlXMenuObject();
                    menu.renderAsContextMenu();
                    menu.addNewChild(menu.topId, 0, "delete",_lang_common['delete'], false,'','',this.del_order.bind(this));
                    this.gridlist = new dhtmlXGridObject('orders');   
                    this.gridlist.selMultiRows = true;
                    this.gridlist.setImagePath("xres/ximg/grid/imgs/");
                    this.gridlist.setHeader(_lang_ishop['order_number']+','+_lang_common['date']+','+
                    _lang_ishop['customer']+','+_lang_common['phone']+','+_lang_ishop['order_sum']+','+_lang_ishop['status']);
                    this.gridlist.setInitWidths("100,300,250,200,150,150");
                    this.gridlist.setColAlign("center,center,left,left,center,center");
                    this.gridlist.attachHeader("#text_filter,#text_filter,#select_filter,#text_filter,#text_filter,#select_filter");
                    this.gridlist.setColTypes("ro,ro,ro,ro,ro,ro");
                    this.gridlist.setColSorting("int,date_rus,str,str,str");
                    this.gridlist.enableAutoWidth(true);
                    this.gridlist.setMultiLine(true);
                    this.gridlist.enablePaging(true, 30, 8, "pagingArea", true, "recinfoArea");
                    this.gridlist.attachEvent("onRowDblClicked", this.show_edit_order.bind(this));
                    this.gridlist.enableContextMenu(menu);  
                    this.gridlist.init();
                    this.gridlist.setSkin("modern");
                    
                    
                    this.connector.execute({load_orders_data:true});                                
                    if(this.connector.result.data_set)
                    {
                        this.gridlist.parse(this.connector.result.data_set,"xjson")
                    }

   },
                  
   destructor:function() 
  {          
    XTR_main.set_rightside();
    this.tabs.destructor();
  }   
});