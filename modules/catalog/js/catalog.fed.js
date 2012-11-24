var FXTRcatalog = Class.create();
var FXTR_catalog;

    
FXTRcatalog.prototype = 
    {
    initialize: function()
            {
                    this.module_name = 'catalog';            
                    this.connector=new Connector(this.module_name);                                           
            } , 
             
         
         get_module_prototype_vcontext:function(ocontext)
         {
           //ocontext.insert(XTR_FED.modulePrototypeAction({name:_lang_common['edit'],observe:this.onEdit.bind(this)}));
         }
         ,         
         
         on_edit:function()
         {
          alert('edit');   
         }
         ,
         
         
          get_action_properties:function(_action,prefix)
            {
                   da=detectDefaultAction(prefix);                                       
                   this.connector.execute({get_action_properties:{Action : _action}});

                                             if (this.connector.result.action_properties) {
                                             $(destination_prefix + '_properties')
                                             .update(this.connector.lct.action_properties);
                                             } else {
                                             $(destination_prefix + '_properties')
                                             .update('Свойства отсутствуют');
                                             }
   
                                        xoad.html.importForm('tune_actions',this.connector.result.action_properties_form);
                                        xoad.html.importForm('tune_actions',this.connector.result.catalog_data);   
         
         if (this.connector.result.xlist)
                        {
                            Xpop2 = new XTRpop('startXXlist',null,{position:'bottom',delta_x:-272,delta_y:-120,donotdestroy:true});
                            gd=this.group_dialog(Xpop2.tool_tip.id);                                                    
                            gd.connectXpop(Xpop2); 
                        
                            this.validation=new Validation('tune_actions', {immediate : true}); 
                        }
                        
                        if (this.connector.result.url_point_xlist)
                        {
                                   
                            Xpop = new XTRpop('startXXXlist',null,{position:'bottom',delta_x:-272,delta_y:-120,donotdestroy:true});
                            hd=this.group_dialog(Xpop.tool_tip.id,null,null,null,'showBasicPoint');                                                    
                            hd.connectXpop(Xpop); 
                        }
                        

             if (defaultAction){
                this.get_action_properties(defaultAction,'secondary');    
            }               
    
                    this.validation=new Validation('tune_actions', {immediate : true}); 
                   
  
                                                          
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
        
         
         
         get_action:function(pid)
         {               
                 this.connector.execute({get_module:{id:pid}});
                 return this.connector.result.module;
         },
         
         change_module_slot:function(module,new_slot)
         {
             
             this.connector.execute({ change_module_slot :{id:module,anc:new_slot}});           
             return this.connector.result.isChanged;
         }
         ,
         delete_obj: function(item_id) 
         {
                        this.connector.execute({delete_obj:{id: item_id}});
                        if(Object.isArray(this.connector.result.deleted)) 
                        {
                          return true;  
                            
                        }
             
         }
         
         
    }
