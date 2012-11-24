/**
 * @author Ryan Johnson <ryan@livepipe.net>
 * @copyright 2007 LivePipe LLC
 * @package Control.Modal
 * @license MIT
 * @url http://livepipe.net/projects/control_modal/
 * @version 2.2.2
 */

if(typeof(Control) == "undefined")
	Control = {};
Control.Modal = Class.create();
Object.extend(Control.Modal,{
	loaded: false,
    opened:new Array(),  
	loading: false,
	loadingTimeout: false,
	overlay: false,
	container: new Hash(),
	current: false,
	ie: false,
	effects: {
		containerFade: false,
		containerAppear: false,
		overlayFade: false,
		overlayAppear: false
	},

	overlayStyles: {
		position: 'fixed',
		top: 0,
		left: 0,
		width: '100%',
		height: '100%',
		zIndex: 10
	},
	overlayIEStyles: {
		position: 'absolute',
		top: 0,
		left: 0,
		zIndex: 10
	},
	disableHoverClose: false,
	load: 
        function(modal_container){
		if(typeof Control.Modal.container.get(modal_container)!='object'){
		
			Control.Modal.ie = !(typeof document.body.style.maxHeight != 'undefined');
			
            Control.Modal.overlay = $(document.createElement('div'));
			Control.Modal.overlay.id = 'modal_overlay';
			Object.extend(Control.Modal.overlay.style,Control.Modal['overlay' + (Control.Modal.ie ? 'IE' : '') + 'Styles']);			
            Control.Modal.overlay.hide();
			
            Control.Modal.container.set(modal_container,new Element('div',{id:modal_container+'_modal_container'}).setStyle({visibility:'hidden',display:'none'}));
            
			
			Control.Modal.opened[modal_container]=false;
            
			var body_tag = document.getElementsByTagName('body')[0];
			body_tag.appendChild(Control.Modal.overlay);
			body_tag.appendChild(Control.Modal.container.get(modal_container));
			
			Control.Modal.container.get(modal_container).observe('mouseout',function(event){
				if(!Control.Modal.disableHoverClose && Control.Modal.current && Control.Modal.current.options.hover && !Position.within(Control.Modal.container.get(modal_container),Event.pointerX(event),Event.pointerY(event)))
					Control.Modal.close();
			}); 
		    
            
        }
	},
	open: function(contents,options)
    {
		
        options = options || {};
		if(!options.contents)
			options.contents = contents;
		var modal_instance = new Control.Modal(false,options);modal_instance.open();
		return modal_instance;
	},
    
	close: function(mcn){
		
		if(Control.Modal.opened[mcn])Control.Modal.opened[mcn].close();
	},	
	center: function(element){
		if(!element._absolutized){
			element.setStyle({
				position: 'absolute'
			}); 
			element._absolutized = true;
		}
		var dimensions = element.getDimensions();
		Position.prepare();
		var offset_left = (Position.deltaX + Math.floor((Control.Modal.getWindowWidth() - dimensions.width) / 2));
		var offset_top = (Position.deltaY + ((Control.Modal.getWindowHeight() > dimensions.height) ? Math.floor((Control.Modal.getWindowHeight() - dimensions.height) / 2) : 0));
		element.setStyle({
			top: ((dimensions.height <= Control.Modal.getDocumentHeight()) ? ((offset_top != null && offset_top > 0) ? offset_top : '0') + 'px' : 0),
			left: ((dimensions.width <= Control.Modal.getDocumentWidth()) ? ((offset_left != null && offset_left > 0) ? offset_left : '0') + 'px' : 0)
		});
	},
	getWindowWidth: function(){
		return (self.innerWidth || document.documentElement.clientWidth || document.body.clientWidth || 0);
	},
	getWindowHeight: function(){
		return (self.innerHeight ||  document.documentElement.clientHeight || document.body.clientHeight || 0);
	},
	getDocumentWidth: function(){
		return Math.min(document.body.scrollWidth,Control.Modal.getWindowWidth());
	},
	getDocumentHeight: function(){
		return Math.max(document.body.scrollHeight,Control.Modal.getWindowHeight());
	},
	onKeyDown: function(event){
		if(event.keyCode == Event.KEY_ESC)
			Control.Modal.close();
	}
});

Object.extend(Control.Modal.prototype,{
	mode: '',
	html: false,
	href: '',
	element: false,
	src: false,
    mcn:'',

    
    
	initialize: function(modal_container_name,options){
		this.mcn=modal_container_name;
        this.container=Control.Modal.container.get(this.mcn);      //!
        this.options = {
			beforeOpen: Prototype.emptyFunction,
			afterOpen: Prototype.emptyFunction,
		
            beforeClose: Prototype.emptyFunction,
			afterClose: Prototype.emptyFunction,
			onSuccess: Prototype.emptyFunction,
			onFailure: Prototype.emptyFunction,
			onException: Prototype.emptyFunction,
			beforeImageLoad: Prototype.emptyFunction,
			afterImageLoad: Prototype.emptyFunction,
			autoOpenIfLinked: false,
			contents: false,
			loading: false, //display loading indicator
			fade: false,
			fadeDuration: 0.1,
			image: false,
			imageCloseOnClick: true,
			hover: false,	
            
			overlayDisplay: true,
			overlayClassName: '',
			overlayCloseOnClick: true,
			containerClassName: '',
			opacity: 0.3,
            statics:false,
			zIndex: 99,
			width: null,
			height: null,
            closeButton:null, 
			offsetLeft: 0, //for use with 'relative'
			offsetTop: 0, //for use with 'relative'
			position: 'absolute', //'absolute' or 'relative'
            semistatics:false
		};
		Object.extend(this.options,options || {});
		var target_match = false;
		var image_match = false;
	
		if(this.options.position == 'mouse')
			this.options.hover = true;
		if(this.options.contents){
			this.mode = 'contents';
		}

        if(this.element){
			if(this.options.hover){
				
               this.element.observe('mouseover',this.open.bind(this));
				this.element.observe('mouseout',function(event){
					if(!Position.within(Control.Modal.container.get(this.mcn),Event.pointerX(event),Event.pointerY(event)))
						this.close();
				}.bindAsEventListener(this));
              
			}else{
				this.element.onclick = function(event){
					this.open();
					Event.stop(event);
					return false;
				}.bindAsEventListener(this);
			}
	
        }    
		
        
		this.position = function(event){
			if(this.options.position == 'absolute')
				Control.Modal.center(Control.Modal.container.get(this.mcn));
			else{
				var xy = (event && this.options.position == 'mouse' ? [Event.pointerX(event),Event.pointerY(event)] : Position.cumulativeOffset(this.element));
				Control.Modal.container.get(this.mcn).setStyle({
					position: 'absolute',
					top: xy[1] + (typeof(this.options.offsetTop) == 'function' ? this.options.offsetTop() : this.options.offsetTop) + 'px',
					left: xy[0] + (typeof(this.options.offsetLeft) == 'function' ? this.options.offsetLeft() : this.options.offsetLeft) + 'px'
				});
			}
			
		}.bind(this);
	    
	},
    makeDraggable:function(dragHandler)
    {
      new Draggable(this.container,{endeffect:false,starteffect:false,scroll:window,handle:dragHandler});

    },
	open: function(force){
		if(!force && this.notify('beforeOpen') === false)
			return;
		
        Control.Modal.load(this.mcn);
        
        
        if(!this.options.hover){
		                    Event.observe($(document.getElementsByTagName('body')[0]),'keydown',Control.Modal.onKeyDown);
        }
		
        Control.Modal.opened[this.mcn] = this;
	
    	if(!this.options.hover)
			Control.Modal.overlay.setStyle({
				zIndex: this.options.zIndex,
				opacity: this.options.opacity
			});
            
       Control.Modal.container.get(this.mcn).setStyle({
			    zIndex: this.options.zIndex + 200,
                display:'block',     //!       
			    width: (this.options.width ? (typeof(this.options.width) == 'function' ? this.options.width() : this.options.width) + 'px' : null),
			    height: (this.options.height ? (typeof(this.options.height) == 'function' ? this.options.height() : this.options.height) + 'px' : null)
		    });
		
		Control.Modal.overlay.addClassName(this.options.overlayClassName);
        Control.Modal.container.get(this.mcn).style.visibility='visible';        
		Control.Modal.container.get(this.mcn).addClassName(this.options.containerClassName);        
        Control.Modal.center(Control.Modal.container.get(this.mcn));
		
        switch(this.mode)
        {
			case 'contents':
				     
                    this.update((typeof(this.options.contents) == 'function' ? this.options.contents() : this.options.contents));
   
				break;
	
		}
		
        if(!this.options.hover)
        {
			if(this.options.overlayCloseOnClick && this.options.overlayDisplay)
			Control.Modal.overlay.observe('click',Control.Modal.close);
			if(this.options.overlayDisplay){
				if(!this.options.fade){
					if(Control.Modal.effects.overlayFade)
						Control.Modal.effects.overlayFade.cancel();
					Control.Modal.effects.overlayAppear = new Effect.Appear(Control.Modal.overlay,{
						queue: {
							position: 'front',
							scope: 'Control.Modal'
						},
						to: this.options.opacity,
						duration: this.options.fadeDuration / 2
					});
				}else
					Control.Modal.overlay.show();
			}
		}
		if(this.options.position == 'mouse'){
			this.mouseHoverListener = this.position.bindAsEventListener(this);
			this.element.observe('mousemove',this.mouseHoverListener);
		}   
		this.notify('afterOpen');
        
	},
    
    
	update: function(html,showwin){
	 if(this.options.statics==false)
    {                    
    	if(typeof(html) == 'string')
			Control.Modal.container.get(this.mcn).update(html);
		else{
			Control.Modal.container.get(this.mcn).update('');
		(html.each) ? html.each(function(node){
				Control.Modal.container.get(this.mcn).appendChild(node);
			}) : Control.Modal.container.get(this.mcn).appendChild(node);
		}
     }
		if(!this.options.fade){
			if(Control.Modal.effects.containerFade)
				Control.Modal.effects.containerFade.cancel();
           
           /* 
			Control.Modal.effects.containerAppear = new Effect.Appear(Control.Modal.container[this.mcn],{
				queue: {
					position: 'end',
					scope: 'Control.Modal'
				},
				from:'0.1',            
                to: 1,
				duration: this.options.fadeDuration / 2
    
			
            });*/
         
		}
			if(showwin==null)
            {
                Control.Modal.container.get(this.mcn).show();
            }
            
	        this.position();
            
		    Event.observe(window,'resize',this.position,false);
		    Event.observe(window,'scroll',this.position,false);
	},
	
    
    close: function(){
		
		
		if(Control.Modal.ie && !this.options.hover){
			$A(document.getElementsByTagName('select')).each(function(select){
				select.style.visibility = 'visible';
			});			
		}
        
        
		if(!this.options.hover)Event.stopObserving(window,'keyup',Control.Modal.onKeyDown);
		
        Control.Modal.opened[this.mcn] = false;
		Event.stopObserving(window,'resize',this.position,false);
		Event.stopObserving(window,'scroll',this.position,false);
		
        if(!this.options.hover){
			if(this.options.overlayCloseOnClick && this.options.overlayDisplay)
				Control.Modal.overlay.stopObserving('click',Control.Modal.close);
			if(this.options.overlayDisplay){
				if(!this.options.fade){
					if(Control.Modal.effects.overlayAppear)
						Control.Modal.effects.overlayAppear.cancel();
					Control.Modal.effects.overlayFade = new Effect.Fade(Control.Modal.overlay,{
						queue: {
							position: 'end',
							scope: 'Control.Modal'
						},
						from: this.options.opacity,
						to: 0,
						duration: this.options.fadeDuration / 2
					});
				}else
					Control.Modal.overlay.hide();
			}
		}
		if(!this.options.fade){
			if(Control.Modal.effects.containerAppear)
				Control.Modal.effects.containerAppear.cancel();
			Control.Modal.effects.containerFade = new Effect.Fade(Control.Modal.container.get(this.mcn),{
				queue: {
					position: 'front',
					scope: 'Control.Modal'
				},
				from: 1,
				to: 0,
				duration: this.options.fadeDuration / 2,
				afterFinish: function(){
                 
                 if((!this.options.statics)&&(!this.options.semistatics)) 
                {
					Control.Modal.container.get(this.mcn).update('');
                    
                }
					
                    if(!this.options.semistatics)
                    {
                        this.resetClassNameAndStyles();    
                    }
                    
                    
				}.bind(this)
			});
		}else{
			Control.Modal.container.get(this.mcn).hide();
			if((!this.options.statics)&&(!this.options.semistatics))
            {
                 
                 Control.Modal.container.get(this.mcn).update('');
                 this.resetClassNameAndStyles();
            }
			
		}
		if(this.options.position == 'mouse')
			this.element.stopObserving('mousemove',this.mouseHoverListener);
		this.notify('afterClose');
	},   
	    resetClassNameAndStyles: function(){
		Control.Modal.overlay.removeClassName(this.options.overlayClassName);
		Control.Modal.container.get(this.mcn).removeClassName(this.options.containerClassName);
		Control.Modal.container.get(this.mcn).setStyle({
			height: null,
			width: null,
			top: null,
			left: null
		});
	},
	notify: function(event_name){
		try{
			if(this.options[event_name])
				return [this.options[event_name].apply(this.options[event_name],$A(arguments).slice(1))];
		}catch(e){
			if(e != $break)
				throw e;
			else
				return false;
		}
	}
});


if(typeof(Object.Event) != 'undefined')
	Object.Event.extend(Control.Modal);

