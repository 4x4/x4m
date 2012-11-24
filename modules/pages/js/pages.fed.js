var FXTRpages = Class.create();
var FXTR_pages;



function pages_drag_control() 
{
    this._drag = function(sourceHtmlObject, dhtmlObject, targetHtmlObject) 
    {
                     
        targetHtmlObject.style.border = "2px solid";    
        id=sourceHtmlObject.parentObject.parentNode._attrs.id;
        slot_id = targetHtmlObject.esource.id.substr(2);         
        html=FXTR_pages.create_module(slot_id,{Levels:0,Alias:'pages',Template:'menu_left.html',showGroupId:id,Action:'show_level_menu',Active:1,type:'pages'});
        targetHtmlObject.esource.insert(html);
        XTR_FED.refresh(1);     
   
    }

    this._dragOut=function(targetHtmlObject, dhtmlObject){
           
        
        targetHtmlObject.style.border = "1px";
         return  targetHtmlObject;  
    }
    
    this._dragIn=function(targetHtmlObject, dhtmlObject){
    
         targetHtmlObject.style.border = "3px solid";
         return  targetHtmlObject;
         
         
    }
}
    
FXTRpages.prototype = 
    {
    initialize: function()
            {
                    this.module_name = 'pages';            
                    this.connector=new Connector(this.module_name);                                           
            } , 
             
             
             tree_object_clicked: function(itemid)
        {
            this.current_object_type = this.tree.getRowAttribute(itemid,"obj_type");                     
            this.action_by_obj_type(itemid,this.current_object_type);
        
        },
              
              
        action_by_obj_type:function(id,object_type)
        {
            switch (object_type)
                {
                    case "_PAGE":
                    case "_GROUP":
                    case "_ROOT":
                    
                    this.connector.execute({get_page_url:{id:id}});
                    window.location.href=this.connector.result.link;
                    break;
                    default: return false;
                }
        },             
            
             
             dynXLS:function(id)
             {

                    this.connector.execute({pages_table:{id:id}});            
                    if(this.connector.result)
                        {
                            this.tree.json_dataset=this.connector.result.data_set;
                        }
                 return true;
             },

             
             click:function()
             {
            
                 $(this.module_name+"_treebox").setStyle({height:'300px'})     
                 this.tree = new dhtmlXGridObject(this.module_name+"_treebox");
                 this.tree.selMultiRows = true;
                 this.tree.imgURL = "/xres/ximg/green/";
                 this.tree.setHeader(_lang_pages['page_name']+','+_lang_pages['link']+','+_lang_pages['no_display']);
                 this.tree.setInitWidths("150,*,*");
                 this.tree.setColAlign("left,left,center");
                 this.tree.setColTypes("tree,ed,ch");
                 this.tree.enableDragAndDrop(true);
                 this.tree.enableEditEvents(false,false,true);
                 //this.tree.attachEvent("onDrag",this.on_treegrid_drag.bind(this));
                 //this.tree.setDragBehavior('complex');
            


                this.tree.enableMultiselect(true);
                
                this.tree.attachHeader("#text_search,#text_search,#select_filter");
                this.tree.init();
                this.tree.kidsXmlFile=1;
                
                
                this.tree.attachEvent("onDynXLS",this.dynXLS.bind(this));
                this.tree.setSkin("dhx_skyblue");
                this.tree.attachEvent("onRowDblClicked", this.tree_object_clicked.bind(this));
                this.connector.execute({pages_table:{id:0}});            
                if(this.connector.result)
                        {
                            this.tree.parse(this.connector.result.data_set,"xjson")
                        }
                this.tree.openItem(1);
                this.create_landing_zones(this);  
         },
         
         
         create_landing_zones:function(_this)
         {                  
                XTR_FED.create_landing_zones(_this.tree,new pages_drag_control);             
         },
         
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
         
         create_module:function(id,params)
         {             
                   this.connector.execute({create_module:{slot_id:id,params:params}});                   
                   return this.connector.result.moduleHtml;
                   
         },
         save_module:function(id,params)
         {             
                   this.connector.execute({save_module:{id:id,params:params}});                   
                   return this.connector.result.moduleHtml;
                   
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
