var FXTRcontent= Class.create();
var FXTR_content;

function content_drag_control() 
{
    this._drag = function(sourceHtmlObject, dhtmlObject, targetHtmlObject) 
    {
        
        targetHtmlObject.style.border = "2px solid";    
        id=sourceHtmlObject.parentObject.parentNode._attrs.id;
        slot_id = targetHtmlObject.esource.id.substr(2);         
        html=FXTR_pages.create_module(slot_id,{Alias:'content',contentId:id,Action:'show_content',Active:1,type:'content'});
        targetHtmlObject.esource.insert(html);      
        document.fire('FED:refresh'); 
   
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

var sinput = document.getElementById('sInput');

function falseDrag(){return false;}

    
FXTRcontent.prototype = 
    {
    initialize: function()
        {
                this.module_name = 'content';            
                this.connector=new Connector(this.module_name);                                           
        },  
         
         
         click:function(){
             
                $(this.module_name+"_treebox").setStyle({height:'300px'})             
                this.tree = new dhtmlXGridObject(this.module_name+"_treebox");
                this.tree.selMultiRows = true;
                this.tree.imgURL = "/xres/ximg/green/";
                this.tree.setHeader(_lang_content['content_groups']+','+_lang_content['templates']);
                this.tree.setInitWidths("150,*");
                this.tree.setColAlign("left,left");
                this.tree.setColTypes("tree,ed");
                this.tree.enableDragAndDrop(true);
                this.tree.enableEditEvents(false,false,true);
         //       this.tree.attachEvent("onDrag",this.on_treegrid_drag.bind(this));
                this.tree.setDragBehavior('complex');
                this.tree.enableMultiselect(true);
             //   this.tree.enableContextMenu(menu);
                this.tree.attachHeader("#text_search,#text_search");
                
                this.tree.init();
                this.tree.enableMercyDrag(true);
                this.tree.kidsXmlFile=1;
                this.tree.attachEvent("onDynXLS",this.dynXLS.bind(this));
                this.tree.setSkin("dhx_skyblue");
                //this.tree.attachEvent("onRowDblClicked", this.tree_object_clicked.bind(this));
                this.connector.execute({content_table:{id:0}});            
                if(this.connector.result)
                        {                                       
                            this.tree.parse(this.connector.result.data_set,"xjson")
                        }


                   this.create_landing_zones(this);
                        
                //this.tree.setDragHandler(falseDrag);

                 this.connector.execute({get_tree_inheritance:true})
                 //this.tree.setInheritanceArr(this.connector.result.tree_inheritance); 
                 this.tree.refreshItem(1);           
             
         },
         
         create_landing_zones:function(_this)
         {                  
                XTR_FED.create_landing_zones(_this.tree,new content_drag_control);             
         },
         
         get_module_prototype_vcontext:function(ocontext)
         {
            ocontext.insert(XTR_FED.modulePrototypeAction({actType:'act2',name:_lang_common['change'],backFunc:this.onEdit.bind(this)}));
         }
         ,         
         get_action_properties:function(_action,prefix)
            {
                   da=detectDefaultAction(prefix);                                       
             
                   this.connector.execute({get_action_properties :{Action:_action}});                                     
                   
                   if(this.connector.result.action_properties)
                   {
                       $(destination_prefix+'_properties').update(this.connector.lct.action_properties);}else{$(destination_prefix+'_properties').update(_lang_common['options_not_found']);
                   }
                   xoad.html.importForm('tune_actions',this.connector.result.action_properties_form);
                   
                   if(this.connector.result.xlist)
                   {
                     
                      columns=$H({image:' ',name:'Имя'});
                     _columnsHeadersWidth=new Array('20px','150px');
                     _columnsInterpretAs=$H({image:'IMAGE'});
                     _images=$H({
                        group:'xres/ximg/tree/folderClosed.gif',
                        page:'xres/ximg/tree/page.gif'
                        });
                        
                        var Xpop = new XTRpop('startXXlist', null,
                        {
                            position: 'bottom',
                            delta_x: -203,
                            delta_y: 0
                        }); 
                        
                        
                         xlist = new XTRxlist(Xpop.tool_tip.id,this.connector,
                       {
                        resultSource:'showContentName',
                        resultIDSource:'contentId',
                        columnsHeaders:columns,
                        tableId:'dialogtable',
                        columnsHeadersWidth:_columnsHeadersWidth,
                        columnsInterpretAs:_columnsInterpretAs,
                        images:_images,
                        className:'dialog-table'
                       }
                       );
                        
                       xlist.connectXpop(Xpop);                   

                    
             
                        //var Xpop = new XTRpop('startXXlist','xxlist',{position:'bottom',delta_x:-272,delta_y:-60});
                       }
                       if (da.defaultAction){this.get_action_properties(da.defaultAction,'secondary');    }

                       this.validation=new Validation('tune_actions', {immediate : true}); 
            },
            
            get_ainterface:function(alias)
            {
               XTR_main.load_module_tpls('admin', new Array('ainterface'));                
               this.connector.execute({load_actions:true});                                                                                     
               $('ainterface').update(XTR_main.get_tpl('admin', 'ainterface'));                               
               this.connector.result.tune_actions.module_alias=alias;
               xoad.html.importForm('tune_actions',this.connector.result.tune_actions);   
               this.validation=new Validation('tune_actions', {immediate : true}); 

            },
            
            
    dynXLS:function(id)
     {
                                            
            this.connector.execute({content_table:{id:id}});            
     
            if(this.connector.result)
                {
                    this.tree.json_dataset=this.connector.result.data_set;
                }
         return true;
     },
            
         onEdit:function()
         {
          alert('edit');   
         }
         
    }
