var FXTRsubscribe = Class.create();
var FXTR_subscribe;

    
FXTRsubscribe.prototype = 
    {
    initialize: function()
            {
                    this.module_name = 'votes';            
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
