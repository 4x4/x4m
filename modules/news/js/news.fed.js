var FXTRnews = Class.create();
var FXTR_news;

    
FXTRnews.prototype = 
    {
    initialize: function()
            {
                    this.module_name = 'news';            
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
             
                this.connector.execute({get_action_properties :{Action:_action}});       
                
                if(this.connector.result.action_properties) {         
                    $(destination_prefix+'_properties').update(this.connector.lct.action_properties);                               
                }else {
                    $(destination_prefix+'_properties').update(_lang_common['properties_are_absent']);
                }
                           
                xoad.html.importForm('tune_actions',this.connector.result.action_properties_form);   
                
                if(this.connector.result.xlist) 
                {
                    gd = this.group_dialog('xxlist');                         
                    var Xpop = new XTRpop('startXXlist','xxlist',{position:'bottom',delta_x:-272,delta_y:-60});
                    gd.connectXpop(Xpop); 
                } 
                
                if(defaultAction){this.get_action_properties(defaultAction,'secondary');}
    
                this.validation=new Validation('tune_actions', {immediate : true});                    
            
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
