var XTRrecycle = Class.create();

XTRrecycle.prototype=Object.extend(new _modulePrototype(), 
     {
    initialize: function()
        {
        this.destructable = true;
        this.module_name='recycle';
        this.connector=null;
        this.init();
    
        },
    //m
    
    
   start:function()
     {
        
            this.mw = XTR_main.dhxWins.createWindow("mw", 20, 10, 800, 700);
            this.mw.centerOnScreen();
            this.mw.setText(_lang_recycle['recycle']);
            this.mw.setModal(true);
            this.mw.attachHTMLString(XTR_main.get_tpl(this.module_name, 'recycle_list',true));
        /*
         this.xwindow.update(XTR_main.get_tpl(this.module_name, 'recycle_list',true));
         this.xwindow.makeDraggable(this.xwindow.container.firstChild);
         this.xwindow.open();
          */
         this.load_recycle_list();    
         
     } ,
                 
     load_recycle_list:function()
     {       
            
         
            menu = new dhtmlXMenuObject();
                    menu.renderAsContextMenu();
                    menu.addNewChild(menu.topId, 0, "delete",_lang_common['delete'], false,'','',this.del_recycle.bind(this));
                    menu.addNewChild(menu.topId, 0, "restore",_lang_recycle['restore'], false,'','',this.restore_recycle.bind(this));
                    
                    this.gridlist = new dhtmlXGridObject('recycle_list');   
                    this.gridlist.selMultiRows = true;
                    this.gridlist.setImagePath("xres/ximg/grid/imgs/");
                    this.gridlist.setHeader('id,'+_lang_recycle['object_name']+','+_lang_recycle['removal_time']+','+
                    _lang_common['modul']);
                    this.gridlist.setInitWidths("70,*,150,150");
                    this.gridlist.setColAlign("center,left,center,center");
                    this.gridlist.attachHeader("#text_filter,#text_filter,#text_filter,#text_filter");
                    this.gridlist.setColTypes("ro,ro,ro,ro,ro");
                    
                    this.gridlist.setColSorting("int,str,date_rus,str");
                    this.gridlist.enableAutoWidth(true);
                    this.gridlist.setMultiLine(true);
                    
                    //gridlist.attachEvent("onRowDblClicked", this.show_edit_order.bind(this));
                    this.gridlist.enableContextMenu(menu);  
                    this.gridlist.init();
                    this.gridlist.setSkin("modern");
                    this.connector.execute({list_recycled:true});                                
                    if(this.connector.result.data_set)
                    {
                        this.gridlist.parse(this.connector.result.data_set,"xjson")
                    }

     },
            
          
          clear:function()
          {
                      if(confirm(_lang_common['you_are_assured_what_wish_to_remove_all']))
                      {
                        this.connector.execute({clear:true});
                        this.load_recycle_list();   
                      }
          },
          
          
          
            restore_recycle:function()
            {
                        id=this.gridlist.getSelectedRowId(1);            
                        this.connector.execute({restore_it:{id : id}});            
                        if (this.connector.result.isRestor)
                        {
                           this.gridlist.deleteSelectedRows();
                        }                
            },
            
            del_recycle:function()
            {

                    if (!confirm(_lang_recycle['remove_from_basket'])) {
                        return false;
                    }
                    
                        id=this.gridlist.getSelectedRowId(1);            
                        this.connector.execute({del_recycle:{id :id}});
                        if (this.connector.result.isDel)
                        {
                           this.gridlist.deleteSelectedRows();                         
                        }
            },
            
                
  destructor:function()
     {          
          XTR_main.set_rightside();
          this.tabs.destructor();
     
     }   
     }
    );