var XTRsearch = Class.create();

XTRsearch.prototype = Object.extend(new _modulePrototype(), 
{
    initialize: function() {
        this.destructable = true;
        this.module_name='search';
        this.current_edit_id = null;
        this.tree = null;
        this.connector=null;        
        this.leaves_obj_type=new Array('_GALLERY');  
        this.init();
        this.flag = false; 
    },
   
    build_interface: function() {
       var oTabs = [					                    
            {id:'t_index',name: _lang_search['indexation'],callback: this.show_indexing.bind(this)},
            {id:'t_current_index',name: _lang_search['current_indexes'], callback: this.current_indexing_results.bind(this)},
//            {id:'t_tunes',name: 'РќР°СЃС‚СЂРѕР№РєРё', callback: this.tunes.bind(this)} 
       ]

       this.tabs = new XTRFabtabs('bookmarkssv', oTabs);  
       this.tabs.makeActiveById('t_index');
       XTR_main.switch_main_state('sv_container');
       this.show_indexing();        
       toggle_main_menu(true);        
    },
    
    tunes:function(){},
               
    show_indexing:function() {             
        XTR_main.load_module_tpls(this.module_name, new Array('indexing'));                                          
        XTR_main.set_svside(XTR_main.get_tpl(this.module_name, 'indexing'));

                        this.gridlist = new dhtmlXGridObject('indexing_results');   
                        this.gridlist.selMultiRows = true;
                        this.gridlist.setImagePath("xres/ximg/grid/imgs/");
                        this.gridlist.setHeader('id,'+_lang_search['link']+','+_lang_search['size']+','+_lang_search['title']+','+_lang_search['status']);
                        this.gridlist.setInitWidths("70,350,80,550,80");
                        this.gridlist.setColAlign("center,left,center,left,center");
                    //    this.gridlist.attachHeader("#text_filter,#text_filter,#text_filter,#text_filter,#select_filter");
                        this.gridlist.setColTypes("ro,ro,ro,ro,ro");
                        this.gridlist.enableAutoWidth(true);
                        this.gridlist.setMultiLine(true);
 //                       this.gridlist.enablePaging(true, 30, 8, "pagingArea", true, "recinfoArea");
                        this.gridlist.init();
                        this.gridlist.setSkin("modern");
        
    },
         
    start_indexing:function(flag) {
       this.gridlist.clearAll();
        this.flag = flag;
        setTimeout("XTR_search.indexing()",500);
    },

   current_indexing_results:function() {
       XTR_main.load_module_tpls(this.module_name, new Array('current_indexes'));                                          
       XTR_main.set_svside(XTR_main.get_tpl(this.module_name, 'current_indexes'));       
       this.gridlist = new dhtmlXGridObject('indexing_results');   

                        this.gridlist.selMultiRows = true;
                        this.gridlist.setImagePath("xres/ximg/grid/imgs/");
                        this.gridlist.setHeader('id,'+_lang_search['link']+','+_lang_search['title']+','+_lang_search['body']+','+_lang_search['status']);
                        this.gridlist.setInitWidths("70,350,200,400,80");
                        this.gridlist.setColAlign("center,left,left,left,center");
                        this.gridlist.attachHeader("#text_filter,#text_filter,#text_filter,#text_filter,#select_filter");
                        this.gridlist.setColTypes("ro,ro,ro,ro,ro");
                        this.gridlist.enableAutoWidth(true);
                        this.gridlist.setMultiLine(true);
 //                       this.gridlist.enablePaging(true, 30, 8, "pagingArea", true, "recinfoArea");
                        this.gridlist.init();
                        this.gridlist.setSkin("modern");

                        this.connector.execute({indexes_table:true});            
                        
                        if(this.connector.result.data_set)
                        {
                           this.gridlist.parse(this.connector.result.data_set,"xjson")
                           this.color_rows();
                        }
                    
            
       
   },
         
    show_indexing_results:function(indexed_pages, k)     
    {
        if(indexed_pages)
                    {
                       this.gridlist.parse(indexed_pages,"xjson")
                    }        
                    
    },
               
     color_rows:function()
     {
         this.gridlist.forEachRow(function(id)
                    {
                        row=this.gridlist.getRowById(id);
                        
                        if(row._attrs.data[4]=='404')
                        {
                            row.className='red_modern';
                                                        
                        }
                        if(row._attrs.data[4]=='301')
                        {
                            row.className='yellow_modern';
                        }
                        
                            
                    }.bind(this));
         
     },               
               
    indexing:function(iterating) {
        iterating = Object.isUndefined(iterating) ? 0 : 1;
        this.connector.execute({indexing:{iterating:iterating}}); 
        if(!this.connector.result.finished) 
        {                        
            if(indexed_pages = this.connector.result.search.pages) 
            { 
                this.show_indexing_results(indexed_pages,parseInt(this.connector.result.search.indexed_pages_count)); 
            }
             this.color_rows();   
            setTimeout("XTR_search.indexing(2)", 1000); 
        }else {
        
            if(indexed_pages = this.connector.result.search.pages) 
            { 
                this.show_indexing_results(indexed_pages,parseInt(this.connector.result.search.indexed_pages_count)); 
                if(this.flag)this.connector.execute({generateSitemap:true}); 
                
                
            }
        }
    },
         
    get_action_properties:function(_action) {
        this.connector.execute({get_action_properties:{Action:_action}});
        if(this.connector.result.action_properties) {
            $('action_properties').update(this.connector.lct.action_properties);                               
        }else {
            $('action_properties').update(_lang_common['properties_are_absent']);
        }
        
        xoad.html.importForm('tune_actions',this.connector.result.action_properties_form);
        this.validation=new Validation('tune_actions', {immediate : true}); 
    },
            
    destructor:function() {          
        XTR_main.set_rightside();
        this.tabs.destructor();
    }   
});