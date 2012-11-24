/*some  modification from FabsTabs*/

var XTRFabtabs = Class.create();

XTRFabtabs.prototype = {
	initialize : function(container,elements) 
    {		        
        this.container = $(container);         
        this.container.update('');        
		this.tabs=new Hash();
        this.current=null;
        var options = Object.extend({}, arguments[1] || {});
		this.setupTab(elements);

                
	},
	
    destructor:function()
    {
    
        
        this.tabs.each(function(tab)
        {
            tab[1].remove();
            this.tabs.unset(tab[0]);
        }.bind(this));
    
    },
    
    addTab:function(tab,position)
    {
         document.all.myDiv.insertBefore(myElement); 
       
    
    
    },
    
    removeTab:function(tab_id)
    {
        
        Event.stopObserving(this.tabs.get(tab_id),'click',this.activate.bindAsEventListener(this));
        Effect.Shrink(this.tabs.get(tab_id),{ duration:0.6,direction:'top-left',
        afterFinish:function()
        {
            if(!Object.isUndefined(this.tabs.get(tab_id)))
            {
                this.tabs.get(tab_id).remove();
                 this.tabs.unset(tab_id);
            }
        }.bind(this)
        });
        
   
    },
    /*
    
           var oLinks = [
					
                    {id:'add_page',name: 'Добавить страницу', callback: this.show_new_page.bind(this)},
                    {id:'add_page',name: 'Добавить папку', callback: this.show_new_page.bind(this)},
                    {separator: true},                    
                    {id:'delete',name: 'Удалить', callback: this.delete_obj.bind(this)}
										
				]
           
    */
    
    createTabNode :function(el,position,activate)
    {

                if(!Object.isUndefined(this.tabs.get(el.id))){return false};
        	     
                 li=new Element('li',{id:el.id}).__extend({_callback:el.callback,temporal:el.temporal});
                 Event.observe(li,'click',this.activate.bindAsEventListener(this),false);
                 
                 if(position=='top'){
                   this.container.insertBefore(li,this.container.firstChild);
                   }else
                    {
                             this.container.appendChild(li);				
                    }
                    li.update('<a href=javascript:void(0)>'+el.name+'</a>');                
                     if(position=='top'){ 
                     li.hide();
                     Effect.Appear(li,{duration:0.3});
                     }
                    this.tabs.set(el.id,li);
                    if(activate)this.makeActive(li);
		    
    },
    
    setupTab : function(elements) 
    {
        elements.each(function(el)
            {							
                this.createTabNode(el,'bottom');                    
			
	        }.bind(this));
	},
    
     makeActiveById:function(id)
     {
        if(this.current!=id){
            this.makeActive(this.tabs.get(id));
        }
     }
     ,
     
     makeActive:function(elm)
     {
       	
         elm.firstChild.addClassName('selected'); 
        
    	this.tabs.each(function(el)
        {
     
            if((el[1].temporal)&&(el[1].id!=elm.id)){this.removeTab(el[1].id);}
            
        }.bind(this)
        );        
        
        if(this.current!=elm.id)
        {
            this.hideEl(this.current);
        }        
        
        this.current=elm.id;
        
        if (elm._callback) 
        {			
			elm._callback();
		}

     }
    ,
    
	activate :  function(ev){
		var elm = Event.element(ev);
		if(elm.tagName!='A')return false;
		elm=elm.up(0);		
        Event.stop(ev);
		
        this.makeActive(elm);
	},
	
    hideEl : function(hide_el)
           {
        	    if(Object.isString(hide_el)){
            
                 this.tabs.get(hide_el).firstChild.removeClassName('selected');   
                }
            
            
            
    //	$(this.tabID(elm)).removeClassName('selected-body');
	},

    
    onClick: function(e) {
		e.stop();
		if (e.target._callback && !e.target.hasClassName('disabled')) {
			this.container.hide();
			e.target._callback();
		}
	}
	
	/*getInitialTab : function() {
		if(document.location.href.match(/#(\w.+)/)) {
			var loc = RegExp.$1;
			var elm = this.menu.find(function(value) { return value.href.match(/#(\w.+)/)[1] == loc; });
			return elm || this.menu.first();
		} else {
			return this.menu.first();
		}
	}  */
}