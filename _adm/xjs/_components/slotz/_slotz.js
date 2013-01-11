var xtr_slots=null; 

var XTRModule = Class.create();

XTRModule.prototype =
    {
    //m
    initialize: function(source,options)
        {
        //this.options.Active=1; 
        this.source = source;                        
        this.options={};        
            if(options.params.is_new)
            {            
              
                this.id=getContinua();                
                delete options.params.is_new;
                this.source.setAttribute("id",'m_'+this.id);     
            }else
            {
            
               this.source.setAttribute("id", 'm_'+options.id);     
               this.id=options.id;
            }
        
        
        Object.extend(this.options, options || {});

        },
    //m
    set_module_options: function(param, value)
        {
            this.options[param] = value;
        }
    };

var XTRSlot = Class.create();

XTRSlot.prototype= 
{
	//m
  initialize: function(slot_name,slot_id,options,source,back_box) 
  {
  	this.slot_name= slot_name;
	this.slot_id= slot_id;
	this.source=source;
	this.mod_container=source.lastChild;
	this.modules = new Hash();
    this.module_index = new Array();
	this.options=new Array();  
    this.options.visible=true;
	this.set_options(options);
	this.behaviors();
    this.mlist=null;
	this.back_box=back_box;
	},

  behaviors:function()
	{
	  this.source.firstChild.ondblclick     = this._onclick.bindAsEventListener(this);	
      this.source.down('a.add-mod').onclick     = this._onclick.bindAsEventListener(this);	
	},
	
    
    //m
	//m??

    close_smart_window:function(vcontext)
    {
        if(!vcontext){vcontext=this.mlist;}
        vcontext.hide(); 
        vcontext.previous('div').down('a').removeClassName('on');
        vcontext.writeAttribute('opened',0);
    },
    
    close_smart_windows:function()
    {
            d=this.back_box.SB_container.select('div.def-mlist:visible');                    
            cntx=this;
            if(d[0]){d.each(function(v){cntx.close_smart_window(v);})}        
    },
    
    
    create_module_list:function()
    {
                    
                    if(!XTR_main.tpl_exists('pages','mlist'))
                    {
                        tpl_text='';        
                        modules=$H(XTR_main.get_modules(1));
                        tpl_text+='<ul>';
                        modules.each(function(m)
                        {
                            tpl_text+='<li onclick="XTR_pages.xtr_slots.XTR_ModulelistEditor.add_new_module(\''+m[1].name+'\')" class="'+m[1].name+'"><a href="javascript:void(0)"><span></span>'+m[1].alias+'</a></li>';
                        });
                        tpl_text+='</ul>';        
                        XTR_main.cache_tpl('pages','mlist', tpl_text, 0,1); 
        
        }
    },
    
    
    open_smart_module_win:function(element)
    {
        if(!this.mlist)
        {
            this.create_module_list();      
             this.mlist=new Element('div', {className: 'def-mlist'});
             this.mlist.insert(new Element('a', {className: 'close'}));
             this.source.insert({
             bottom: this.mlist
         });
        }
        
                    this.mlist.update(XTR_main.get_tpl('pages','mlist'));
                    this.back_box.activeSlot=this;
                    this.mlist.show();
                    if(element.tagName=='SPAN')
                    {
                          element= element.up();
                        
                    }
                    element.addClassName('on');
                    
                    this.mlist.writeAttribute('opened',1);
          }
        
    ,
    
	_onclick:function(e)
	{
        el=Event.element(e);
        if(el.nodeName=='DIV'){el=el.down('a');  }
        
        if(this.mlist&&this.mlist.readAttribute('opened')=="1")
            {
                this.close_smart_window(this.mlist);
                return;
            }else{
                this.close_smart_windows();   this.open_smart_module_win(el);
            }
        

	},

    /*find by  module id */

    
    //m
	set_options:function(opt_array)
	{   		    
		Object.extend(this.options, opt_array || {});
	},

    
    b_show:function(e)
    {
       element = Event.element(e); 
       id=element.up().id;
       id=id.substr(2);
       if(this.modules.get(id).options.params.Active)
           {this.modules.get(id).options.params.Active=0;element.addClassName('off');
           }else{this.modules.get(id).options.params.Active=1;element.removeClassName('off');}
        
        
    },
    
    b_del:function(e)
    {
    
        element = Event.element(e); 
        id=element.up().id;
        id=id.substr(2); 
        if(confirm(_lang_common['you_really_wish_to_remove_this_object']))
        {
            this.modules.get(id).source.remove(); 
            this.modules.unset(id);
        }
    
         
    } ,
    
    b_extra:function(e)
    {
        element = Event.element(e); 
        id=element.up('li').id;
        id=id.substr(2); 
        
        module_params=this.modules.get(id).options.params;
        
        XTR_pages.destructor();
        XTR_main.load_module(module_params.type,'normal');
        
        module_source=eval('XTR_'+module_params.type);
        f=module_source[module_params.Action+'_extra'];        
        f.call(module_source,module_params);
        
        
    },
    
    edit_module:function(e)
    {
         element = Event.element(e); 
         if(element.nodeName!='LI'){
         id=element.up('li').id;
         }else{
             id=element.id;             
         }
         
         id=id.substr(2);   
         this.back_box.activeSlot=this;
         XTR_ModulelistEditor.call_modulein_slot_instance(id);
         
    },
   //m
   add_module_to_slot:function(modulec_list_link,options)
   {  	
	 	 li_module_source = new Element("li");                
         if(options.params._Extra){extraTxt=options.params._Extra;}else{extraTxt='';}
		 li_module_source.innerHTML='<div class="tp '+options.params.type+'"></div><div class="slot-name">'+
         '<a href="javascript:void(0)">'+options.params.Alias+'</a> <p>'+options.params.RAction+'</p><p><span></span></p></div>';
        
          if(options.params.Active==1){_classname='b-show';}else{_classname='b-show off';}
          
          b_show = new Element('a',{className:_classname,href:'javascript:void(0)'}).observe('click', this.b_show.bind(this));
          b_del = new Element('a',{className:'b-del',href:'javascript:void(0)'}).observe('click', this.b_del.bind(this));
          
          li_module_source.appendChild(b_show);
          li_module_source.appendChild(b_del);
      
		this.mod_container.appendChild(li_module_source);        
		this.last_added=new XTRModule(li_module_source,options);
        sp=$(li_module_source.select('span'));
        sp[0].appendChild(new Element('a',{href:'javascript:void(0)'}).update(extraTxt).observe('click', this.b_extra.bind(this)));
        
        this.modules.set(this.last_added.id,this.last_added);
        
        li_module_source.observe('dblclick', this.edit_module.bind(this));           
		Sortable.destroy(this.mod_container);
		Sortable.create(this.mod_container,{dropOnEmpty:true,
        //scroll:'scroll-container',
        onUpdate:this.back_box.on_drag_update.bind(this.back_box),     
        containment:modulec_list_link});
          

   },

	//m
	   clear_modules:function()
	   {

        if(this.modules.size()>0)
        this.modules.each(
        function(module){
       
       if(typeof module[0] == 'number')
	       {	
            //source del        
            this.modules.get(module_id[0]).source.remove(); 
            //array_del
             this.modules.unset(module_id[0]);
	       }
        
        }.bind(this)
        );
		
		}

}

/*---------------------XTRSlotBox------------------------------------------------------------------------*/

var XTRSlotBox = Class.create();
 

XTRSlotBox.getInstance = function(SB_container,options) 
{
        if (XTRSlotBox.instance == null)
        {

                XTRSlotBox.instance = new XTRSlotBox(SB_container,options);

        }

        return XTRSlotBox.instance;
}


 XTRSlotBox.prototype={
 	//m
  initialize: function(SB_container,options) 
  {
	this.SB_container=$(SB_container);
    this.slots= new Hash();
	this.options= new Array(); 
	this.mod_containers= new Array(); 
	this.set_options(options);	
    this.slotzcache=new Hash();
    XTR_ModulelistEditor=this.XTR_ModulelistEditor=new XTRModulelistEditor(this);   
	},
    
    
    reinit:function(SB_container)
    {        
        
        this.SB_container=$(SB_container);
        this.mod_containers= new Array(); 
        this.clear_slotz();            
    },
  
  	
   clear_slotz:function()
   {
    
        this.slots.each( function(slot)
        {
             if(!Object.isUndefined(slot[1]))
             {
                slot[1].clear_modules();
                slot[1].source.remove();
                this.slots.unset(slot[0]);
             }
                
        }.bind(this));
    
   }, 
    
  export_module_slotz:function(module_id)
  {  
        var export_hash= new Hash();
        
        this.slots.each(function(slot)
        {

           {
                module_pack=[]
                           
                order=Sortable.sequence(slot[1].mod_container);
                
                for(i=0;i<order.length;i++)
                {
                        module_pack.push(slot[1].modules.get(order[i]).options);
                }
                
                export_hash.set(slot[0],{options:slot[0].options,modules:module_pack});
                module_pack=null;            
           
           }
        }
        );
        
        return export_hash.toObject();     
  }, 
  
  
   is_slotz_template_cached:function(template)
   {
             if(!(Object.isUndefined(this.slotzcache))&&(!Object.isUndefined(this.slotzcache.get(template))))
            {
             return true;
            }
   
   },
  

        import_slotz:function(template,slotz)
        {
                
                 if(slotz!=null){this.slotzcache.set(template,$H(slotz));}                
                 this.slotzcache.get(template).each(function(pair)            
                {this.add_slot(pair.value.basic,pair.value.alias);}.bind(this));
        
        },
        
          import_modules:function(modules,client_side) 
          {

                    modules=$H(modules);                         
                    
	                modules.each(function(pair)
                    { 
                        slot_id=pair.key;
                        
                        if(client_side){
                        module=$H(pair.value.modules);
                        }else{
                        module=$H(pair.value);
                        }
                        module.each(function(mpair)
                        {
                            this.add_module_to_slot(slot_id,mpair.value);
                        
                        }.bind(this));
                    
                    }.bind(this));
          }
  
  ,
  
  clear:function()
  { 
	$A(this.SB_container.childNodes).each(function(e){
    e.parentNode.removeChild(e);
	} )	 
  },	

	set_options:function(opt_array)
	{   
		this.options['slot_class']='def-slot',
		this.options['slot_module_class']='def-slot-module'    
		this.options['name_contain_class']='def-name-cont'    
		Object.extend(this.options, opt_array || {});
	},
	

  //
  on_drag_update:function(elt)
  {  

    slot_in=null;
    slot_out=null;
    module_out=null;
      
        this.slot_selected=new Array();
        this.slots.each(function(sp)
        {   
               
            options = Object.extend(Sortable.options(sp.value.mod_container), arguments[1] || {});
            s_visual=$A(Sortable.findElements(sp.value.mod_container, options));
            
            id_array = Sortable.sequence(sp.value.mod_container);
            if ( typeof s_visual!='undefined')
            {
                
                //+module in slot                
               if(sp.value.modules.size()<s_visual.size())
                   {
                    
                         s_visual.each(function(s)
                             {
                               if(typeof sp.value.modules[s.id]=='undefined'){                                                  
                                    slot_in=sp.value;
                               }
                             }.bind(this)
                             );                       
                         
                   }//- module in slot
               else{if(sp.value.modules.size()>s_visual.size())
                   {                                
                        sp.value.modules.each(function(module)
                             {
                                 if(s_visual.indexOf(module.value.source)==-1)
                                 {
                                    module_out=module.value;   
                                    slot_out=sp.value;                                       
                                 }
    
                             }.bind(this)
                             );                                                         
                   }                   
               }
               
            }
            
        }.bind(this)
        );
        
        if(slot_in!=null)
        {
        
           slot_in.modules.set(module_out.id,module_out);
           slot_out.modules.unset(module_out.id);
        }

  },
      b_edit:function()
      {
      
      
      
      }
      ,
	//id ????? ???, ?????, ?????? ???????? ??????????? ????? ??????
  add_slot:function(slot_id,slot_name,options)
  {
   		   	    slot_div = document.createElement("DIV")
				slot_div.className = this.options.slot_class;
								
				modules_ul = document.createElement("ul");	
				modules_ul.id='modulec_'+slot_id;
																								
				name_container = document.createElement("DIV")	
				name_container.className =this.options.name_contain_class;
                    
				name_container.update('<h1>'+slot_name+'</h1><a class="add-mod" href="javascript:void(0)"><span></span>'+_lang_common['add']+'</a>');		                                
				slot_div.appendChild(name_container);
				slot_div.appendChild(modules_ul);				
				
                this.SB_container.appendChild(slot_div);				
				slot =new XTRSlot(slot_name,slot_id,options,slot_div,this);				
                this.mod_containers.push('modulec_'+slot_id);	
                
		        Sortable.create(
                slot_div.lastChild,
                {
                    dropOnEmpty:true,
                    ghosting:true,
                    scroll:'scroll-container',containment:false,                    
                    onUpdate:this.on_drag_update.bind(this)
                    
                    }
                    );		        
				    
                this.slots.set(slot_id,slot);			  			                
				return slot;
  			    
  },
 //m
 add_module_to_slot:function(slot_id,options)
 {		
	
	this.slots.get(slot_id).add_module_to_slot(this.mod_containers,options);
 }
  ,
 delete_module:function(slot_id,module_id)
 {
    this.slots.get(slot_id).del_module(module_id);
 }
  
};




/*---------------------XTRModulelistEditor------------------------------------------------------------------------*/
var XTR_ModulelistEditor=null;
var XTRModulelistEditor = Class.create();

XTRModulelistEditor.prototype =
    {        
    //m
    initialize: function(slot_box,options)
        {
        
            this.slot_box=slot_box;
            Object.extend(this.options, options || {});   
            this.callback=null;
        },
    
    
     add_new_module:function(module_type)
     {     
            this.mw = XTR_main.dhxWins.createWindow("mw", 20, 10, 438, 630);
            this.mw.centerOnScreen();
            this.mw.setText(module_name(module_type));
            this.mw.setModal(true);
            this.mw.attachHTMLString(XTR_main.get_tpl('pages', 'pages_slot_editor',true));
                 
             this.slot_box.activeSlot.close_smart_window();
             this.ai=$('ainterface');
             this.ai.hide(); 
             this.call_module_ainterface(module_type);
             $('Alias').value=module_name(module_type);
             this.current_module_type=module_type;
     },
 
    _module_onclick:function(e)
        {
            var element = Event.element(e);
            if(typeof this.last_selected=='object')
            {
                this.last_selected.className=''            
            }            
            
            this.last_selected=element;
            element.className='selected';

            this.call_module_ainterface(element.up().readAttribute('mname'));
            $('Alias').value=element.text;

            
        },
    
    

    
    call_modulein_slot_instance:function(moduleid)
    {
            this.mw = XTR_main.dhxWins.createWindow("mw", 20, 10, 438, 630);
            this.mw.centerOnScreen();
            this.mw.setModal(true);
            this.mw.attachHTMLString(XTR_main.get_tpl('pages', 'pages_slot_editor',true));                                
            this.ai=$('ainterface');     
            instance=this.slot_box.activeSlot.modules.get(moduleid);
            this.call_module_ainterface(instance.options.params.type);        
            this.mw.setText(instance.options.params.Alias);
            
            $('Alias').value=instance.options.params.Alias;
            dA = (typeof(instance.options.params.Default_action)!='undefined'); 
            defaultAction = dA ? instance.options.params.Default_action : 'action';
            this.callback = this.get_action_properties(instance.options.params.Action, defaultAction);             
            xoad.html.importForm('tune_actions',instance.options.params);
            this.save_mode=instance;
            
            if ( typeof this.callback == 'function') {
                this.callback(instance.options.params);
                this.callback = null;                
            }
                        
    },
    

    create_slot_module:function()
        {

                if(!Object.isUndefined(this.xtr_module_source.avalidation))
                 {
                   this.xtr_module_source.avalidation.onSubmit();
                 }
                
                ta=$('tune_actions').getElements();            
                combine=combine_to_hash(ta.pluck('id'),ta.pluck('value'));                                                
                

                checkboxes=$('tune_actions').getInputs('checkbox'); 
                radiobuttons=$('tune_actions').getInputs('radio'); 

                $$('#tune_actions select[multiple]').each(function(mel)
                {
                    opt=mel.options;
                    selected=new Array();
                    for (var intLoop=0; intLoop < opt.length; intLoop++) 
                    {
                        if (opt[intLoop].selected) 
                         {                        
                             selected.push(opt[intLoop].value);
                         }
                    }

                    combine.set(mel.name,implode(selected));                    
                });
                
                
                if(!Object.isUndefined(checkboxes))
                {
                    vh=new Hash;
                    checkboxes.each(function(chbx)
                    {                  
                      vh.set(chbx.id,chbx.checked);
                    });
                        combine.update(vh || {});  
                }
                
                if(!Object.isUndefined(radiobuttons))
                {
                    vh=new Hash;
                    radiobuttons.each(function(rbx)
                    {                  
                        if (rbx.checked) {
                            
                            vh.set(rbx.readAttribute('Name'),rbx.getValue());
                        }
                      
                    });
                        combine.update(vh || {});  
                }

                
                
                          
                    if(typeof this.xtr_module_source._extra=='object')
                    {                        
                        extraField=this.xtr_module_source._extra[$('Action').options[$('Action').selectedIndex].value];
                        extraTxt=combine.get(extraField);
                    }else{
                        extraTxt='';
                    }
               
                
                if(typeof this.save_mode!='undefined')
                {      
                    Object.extend(this.save_mode.options.params, combine.toObject() || {});                       
                    delete this.save_mode.options.params['_object'];
                    this.save_mode.source.down('div.slot-name').update('<a href="javascript:void(0)">'+this.save_mode.options.params.Alias+'</a> <p> '+$('Action').options[$('Action').selectedIndex].text+'</p><p><span>'+extraTxt+'</span></p>');
                    
                    delete this.save_mode;
                    
                }
                else{
                
                    combine.update({_Extra:extraTxt,type:this.current_module_type,is_new:true,Active:true,RAction:$('Action').options[$('Action').selectedIndex].text});        
                    this.slot_box.activeSlot.add_module_to_slot(null,{params:combine.toObject()});                                                                   
                }
                
               
        }
    ,



    get_action_properties:function(action,prefix)
    {
            prefix = (typeof prefix=='undefined')?'':prefix;
            if (action.trim()==''){
                switch (prefix){
                    case '':
                        $('action_properties').update();
                        break;
                    case 'secondary':
                        if ($('Default_action') != null){
                            $('Default_action').removeClassName('validate-selection');
                            $('secondary_properties').update(); 
                            this.xtr_module_source.avalidation.reset();   
                        }
                        break;
                }
        
            }
            else{
                this.xtr_module_source.avalidation.reset();
                return this.xtr_module_source.get_action_properties(action,prefix);
            }
    },
    
    call_module_ainterface:function(module)
        {   
            XTR_main.load_module(module,'silent');
            this.xtr_module_source=XTR_main.called_modules['XTR_'+module];
            this.xtr_module_source.get_ainterface();            
            Effect.Appear(this.ai,{duration:0.2});
        
        }
    };
    
    
    
          

 
 