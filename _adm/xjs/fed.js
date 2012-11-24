
var XTRFEDEDWIN = Class.create();
XTRFEDEDWIN.prototype = 
{    
    initialize:function()
    {        
            this.win = new Element('div', { 'id': 'XTRFEDEDWIN',className:'__controlcont' }).setStyle({ border:'1px solid black',zIndex:25, position:'absolute', top:'10px', right :'10px', backgroundColor: '#FFFFFF'});    
            d = new Element('div',{id:'XTRFEDEDWIN_drag',className:'__ct-top'});    
            d.insert('<p class="__add"><span>'+_lang_pages['add_page']+'</span></p><p class="__move"/>');
            this.win.appendChild(d);
            this.ucontext=new Element('ul');            
            this.win.appendChild(this.ucontext);
            document.body.appendChild(this.win);        
            new Draggable('XTRFEDEDWIN',{handle:'XTRFEDEDWIN_drag'});
            Event.observe(window,'scroll',this.position.bind(this));
        
    },
    
    appendModule:function(name,alias)
    {        
        m=new Element('li',{id:'x3f'+name, className:'__x3fmodule'});   
        m.insert('<p>'+alias+'</p>');
        this.ucontext.appendChild(m);
        
        
        if(Object.isFunction(XTR_FED.called_modules[name].click))
        {
            m.observe('click',XTR_FED.called_modules[name].click);   
        }
        
    },
    
    position:function(){
        
        var xy = document.viewport.getScrollOffsets() ;
                this.win.setStyle({                    
                    top: (xy[1]) +   'px',
                });
            }

}
    
    


var XTR_FED;
var XTRFED = Class.create();
XTRFED.prototype = {
   initialize : function() {
       
    //  this.xtredwin=new XTRFEDEDWIN();
      this.connector= new Connector('fed','incroute');
      this.slotz = new Hash();
      this.modules = new Hash();
      this.options = new Hash();
      this.called_modules =new Array();
      this.options.slot_delta = 26;
      this.options.module_delta = 15;
      this.options.slot_min_height = 70;
      this.options.slot_min_width = 130;      
      this.hide = false;
      
      

   },
    
    go:function()
    {
      this.load_modules();     
      this.abs_modules();
      this.abs_slotz();

      
      Event.observe(document, 'click', function(e) {
         if(e.altKey) {
            Event.stop(e); if(this.hide) {
               invoke = 'show'; this.hide = false; }
            else {
               invoke = 'hide'; this.hide = true; }
            $$('.__amodule').invoke(invoke); $$('.__aslot').invoke(invoke); }
         }
      .bind(this));
      
    },            

      
      load_modules:function(moduleslist)
      {
        this.connector.execute({getModuleList:true});        
        //modules=$H(this.connector.result.moduleList);
        modules=new Hash();
        modules.set('w',{name:'pages',alias:'pages'});
        modules.set('wa',{name:'content',alias:'articles'});
        modules.set('waa',{name:'catalog',alias:'catalog'});
        modules.set('dwa1',{name:'news',alias:'news'});  
        modules.set('dwa2',{name:'banners',alias:'banners'});   
        modules.set('dwa3',{name:'fusers',alias:'site users'});   
        modules.set('dwa4',{name:'ishop',alias:'ishop'});     
        modules.set('dwa5',{name:'search',alias:'search'});     
        modules.set('dwa6',{name:'votes',alias:'votes'});   
        modules.set('dwa7',{name:'subscribe',alias:'subscribe'});   
        
        modules.each(function(p)
        {
            xtr_name = 'FXTR_' + p[1].name;   
            xtr_prototype_name='FXTR' + p[1].name;            
                                 
           // if(XTR_main.load_js('/modules/'+p[1].name+'/js/'+p[1].name+'.fed.js'))
            //{
                if (typeof this.called_modules[xtr_name] != 'object') 
                {                                                          
                    run_str = xtr_name + "=new "+xtr_prototype_name + "();"; eval(run_str);             
                    //run_str = xtr_name + ".build_interface();"; eval(run_str);                 
                        this.called_modules[p[1].name] =eval(xtr_name);
                        //this.xtredwin.appendModule(p[1].name,p[1].alias);   
                }    
            
     //       }
            
        }.bind(this));
        }      
      
      
      ,refresh : function() 
      {
           $$('.__amodule').invoke('remove');
           $$('.__aslot').invoke('remove');            
           this.slotz=new Array();
           this.modules=new Array();
           this.abs_modules();   
           this.abs_slotz();

      }
      
   , abs_slotz : function() {
       
      slotz = document.getElementsByClassName('__slot');
      for(i = 0; i < slotz.length; i++) {
         replica = this.absolutize(slotz[i], 24);
         replica.id = '_i' + i;
         replica.setStyle( {
            border : '2px dotted #A1A1A1', opacity:0.5}
         );
         replica.className = '__aslot';
         replica.esource = slotz[i];
         replica.esource.pid = slotz[i].id.substr(2); 
      //   replica.onmouseover = this._slot_over.bindAsEventListener(this);
      //   replica.onmouseout = this._slot_out.bindAsEventListener(this);
         _edit = document.createElement('a');
         _edit.onclick = function() {
            alert('ddd');
            }
         _edit.style.display = 'none';
         _edit.innerHTML = slotz[i].getAttribute('alias');
         Droppables.add(replica.id , {
             accept: ['__amodule'],
             onDrop: function(drag,drop) 
             {   
                 
                 if(drag.esource&&FXTR_pages.change_module_slot(drag.esource.pid,drop.esource.pid))
                 {
                         cl = drag.esource.cloneNode(true);
                         drop.esource.appendChild(cl);
                         cl.show();
                         drag.esource.remove();
                         this.refresh();
                 }
                 
             }.bind(this)
             });
             

         replica.appendChild(_edit);
         this.slotz[slotz[i].id] = replica;
      }
   }
   , element_growth : function(element, delta) {
      Position.prepare();
      var offsets = Position.positionedOffset(element);
      element.style.top = (offsets[1] - Math.round(delta / 2)) + 'px';
      element.style.left = (offsets[0] - Math.round(delta / 2)) + 'px';
      element.style.width = (element.clientWidth + delta) + 'px';
      element.style.height = (element.clientHeight + delta) + 'px';
      }
   , 
    _module_click : function(evt) 
     {
          var element = Event.element(evt);
          element.up().hide();
      }
      ,
      
      module_drag_start:function(o,p)
      {          
          o.current_slot=p.id;
          o.handle.esource.hide();          
      },
      
      module_drag_end:function(o,p)
      {                
          o.handle.esource.show();                  
      },
      
      
     modulePrototypeAction:function(obj)
     
     {
         (obj.observe) ? obj.observe : obj.observe='click';(obj.actType) ? obj.actType : obj.actType='__act1'; (obj.backFunc) ? obj.backFunc : obj.backFunc=function(){};            
         li=new Element('li',{className:obj.actType})
         li.observe(obj.observe, obj.backFunc);
         s=new Element('span'); li.insert(s.insert(obj.name));
         return li;
     },
     
     del_module:function(e)
     {  
        elt=Event.element(e).up('.__amodule');   
         if(confirm(_lang_common['you_really_wish_to_remove_this_object'])){
             if(FXTR_pages.delete_obj(elt.esource.pid))
             {
                     elt.esource.remove();elt.remove();                              
                     this.refresh();    
             }
           }   
     }
     
     ,
     save_module:function()
     {

                 if(!Object.isUndefined(this.current_module.validation))
                 {
                    this.current_module.validation.onSubmit();
                 }
                
                    ta=$('tune_actions').getElements();            
                    combine=combine_to_hash(ta.pluck('id'),ta.pluck('value'));                
                    checkboxes=$('tune_actions').getInputs('checkbox'); 
                    if(!Object.isUndefined(checkboxes))
                    {
                    vh=new Hash;
                    checkboxes.each(function(chbx)
                    {                  
                      vh.set(chbx.id,chbx.checked);
                    
                    });
                    
                        combine.update(vh || {});  
                    }

                if(this.save_mode)
                {                
                    Object.extend(this.current_action_module.params, combine.toObject() || {});                                           
                    htmlResult=FXTR_pages.save_module(this.current_action_module.id,this.current_action_module.params);    
                    this.current_amodule.esource.update(htmlResult);
                    this.refresh();
                    
                }
                else{
                    //combine.update({type:this.last_selected.up().readAttribute('mname'),is_new:true,Active:true,RAction:$('Action').options[$('Action').selectedIndex].text});                            
                }
                
                
        },
     
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
                            this.current_module.validation.reset();   
                        }
                        break;
                }
        
            }
            else{
                this.current_module.validation.reset();
                this.current_module.get_action_properties(action,prefix);
            }
    },
    
     call_action_properties:function(e)
     {
            
         elt=Event.element(e).up('.__amodule');  
            module=FXTR_pages.get_action(elt.esource.pid);        
            if(Object.isFunction(this.called_modules[elt.esource.mtype].get_action_properties))
            {
                this.xAP_win = XTR_main.dhxWins.createWindow("mw", 20, 10, 438, 630); 
                this.xAP_win.centerOnScreen();
                this.xAP_win.setModal(true);
                this.xAP_win.attachHTMLString(XTR_main.get_tpl('admin', 'FED_action_properties',true));
                
                   
                this.current_amodule=elt;
                this.current_module=this.called_modules[elt.esource.mtype];
                
                this.current_module.get_action_properties(module.params.Action,module.params.Default_action);         
                this.current_action_module=module;
                $('Action').update('');
                this.current_module.connector.execute({load_actions:true});       
                xoad.html.importForm('tune_actions',this.current_module.connector.result.tune_actions);                              
                xoad.html.importForm('tune_actions',module.params);   
                this.save_mode=true;                      
        }
     }
     
     
     
     ,visualize_module_prototype:function(mtype,malias,mcontext)
     {
        mcontext.insert('<p></p>');
        mcontext.insert(new Element('p',{className:'__close'}).observe('click',this.del_module.bind(this)))        
        mcontext.insert('<p class="__mod-name '+mtype+'">'+malias+'</p>');
        mcontext.insert('<p style="color:white">'+mcontext.esource._etime+'</p>');
        ul= new Element('ul');
        ul.insert(XTR_FED.modulePrototypeAction({name:_lang_common['options'],backFunc:this.call_action_properties.bind(this)}));
        if(Object.isFunction(this.called_modules[mtype].get_module_prototype_vcontext)){this.called_modules[mtype].get_module_prototype_vcontext(ul);mcontext.insert(ul);}                                                     
     }
      
   , abs_modules : function() {
      oldpid = null;
      modules = document.getElementsByClassName('__module');
      
      for(i = 0; i < modules.length; i++) {
         replica = this.absolutize(modules[i], 25,null,null,true);
         replica.id =   i+'i';
         replica.className = '__amodule';  
         replica.esource = modules[i];        
         replica.esource.mtype=modules[i].getAttribute('mtype');
         replica.esource.alias=modules[i].getAttribute('alias');         
         
         replica.esource._etime=modules[i].getAttribute('etime');         
         
         this.visualize_module_prototype(replica.esource.mtype,replica.esource.alias,replica);
         this.element_growth(replica, - 10);
       //  replica.onmouseover = this._module_over.bindAsEventListener(this);
         //replica.onmouseout = this._module_out.bindAsEventListener(this);
         
         modules[i].setStyle( {
            minHeight : '20px',
            float:'left',            
         }
         );
      
         var mydrag = new Draggable(replica.id, { revert: true,
         onStart :this.module_drag_start.bind(this),
         onEnd: this.module_drag_end.bind(this)
         });
  
         if(oldpid != modules[i].parentNode.id)nh = new Hash();
         
         replica.esource.pid = modules[i].id.substr(2);
         nh[modules[i].id] = replica;
         this.modules[modules[i].parentNode.id] = nh;         
         oldpid = modules[i].parentNode.id }
      }
   
   , absolutize : function (el, zindex,w,h,dchs) {
      element = $(el);      
      if (element.style.position == 'absolute') return;
      Position.prepare();
      //var p = Position.positionedOffset(element);
      _element = document.createElement('div');
      _element.style.position = 'absolute';
      _element.style.zIndex = zindex;
      var p = element.cumulativeOffset();
          
      var delta = [0, 0];
      var parent = null;
      if (Element.getStyle(element, 'position') == 'absolute') {
         parent = element.getOffsetParent();
         delta = parent.viewportOffset();
         }
      _element.style.left =(p[0] - delta[0] ) + 'px';
      _element.style.top = (p[1] - delta[1] ) + 'px';
      
      if(!dchs){
      if(!w){      
      if(element.clientWidth < this.options.slot_min_width) {
         w = this.options.slot_min_width;
         }
      else {
         w = element.clientWidth;
         }
      }
      if(!h){
      if(element.clientHeight < this.options.slot_min_height) {
         h = this.options.slot_min_height;
         }
      else {
         h = element.clientHeight;
         }
      }
      _element.style.width = w + 'px';
      _element.style.height = h + 'px';
      
      }
      //добавим реплику
      document.body.appendChild(_element);
      return _element;
      }
   }
   
   
detectDefaultAction=function(prefix)
{   
                    defaultAction = null;       
                    if (prefix!='action' && prefix!='secondary') {
                        destination_prefix = 'action';
                        defaultAction = prefix;
                    }
                    else if (prefix=='secondary'){
                        destination_prefix = prefix;
                        defaultAction = null;    
                    }
                    else {
                        destination_prefix = 'action';
                        defaultAction = null;     
                    }        
                    return {destination_prefix : destination_prefix,defaultAction : defaultAction}
}




    
    Event.observe(window, 'load', function() {
    $(document.body).scrollTo();
       XTR_FED = new XTRFED();   
       XTR_FED.go();
    
    });
    

