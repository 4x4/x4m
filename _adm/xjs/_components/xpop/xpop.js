var XTRpopCURRENT=null;
var XTRpop = Class.create();
XTRpop.prototype = {
  initialize: function(element, tool_tip,options) {
    var options = Object.extend({
      default_css: false,
      margin: "0px",
	  padding: "5px",
	  backgroundColor: "#d6d6fc",
	  delta_x: 5,
	  delta_y: 5,  
      position:'top',
      donotdestroy:false,
      preventevents:false,
      zindex: 1000
    }, arguments[2] || {});

    if(!tool_tip)
    {
        if(!options.donotdestroy)
        {
            if(XTRpopCURRENT)$(XTRpopCURRENT).remove();
        }
            tool_tip    =document.createElement('div'); 
            id=new Date().valueOf();
            tool_tip.id='o_'+id;      
            document.body.appendChild(tool_tip);  
            XTRpopCURRENT=tool_tip.id;    
            
    }
    
    this.element      = $(element);    
    this.tool_tip     = $(tool_tip);
    this.tool_tip.className='xlist';    
    this.state=false;
    this.options      = options;

    // hide the tool-tip by default
    this.tool_tip.hide();         

    if(!    this.options.preventevents)
    {
        this.eventMouseClick = this.showTooltip.bindAsEventListener(this);
        this.eventMouseOut   = this.hideXpop.bindAsEventListener(this);
    }

    this.registerEvents();
  },
  
  

  destroy: function() {
    Event.stopObserving(this.element, "click", this.eventMouseOver);
    //Event.stopObserving(this.element, "mouseout", this.eventMouseOut);
  },

  registerEvents: function() {
    Event.observe(this.element, "click", this.eventMouseClick);
    //Event.observe(this.element, "mouseout", this.eventMouseOut);
  },

  showTooltip: function(event){
  
   if(this.state)return false;
	if(event)
    {
        Event.stop(event);
	    // get Mouse position
        var mouse_x = Event.pointerX(event);
	    var mouse_y = Event.pointerY(event);	
	    ce=Event.element(event); 
    } else{
        
        ce=this.element;
        var ce_dimensions= Element.getDimensions(ce);
        ce_offset=Position.cumulativeOffset(ce); 
        var dimensions = Element.getDimensions( this.tool_tip );
        var element_width = dimensions.width;
        var element_height = dimensions.height;
    
        mouse_x=ce_offset[0]+ce_dimensions.width+this.options.delta_x;
        if(this.options.position=='top'){
            mouse_y=ce_offset[1];
            }else{
            mouse_y=ce_offset[1]+this.options.delta_y;
        
            }}
	// decide if wee need to switch sides for the tooltip
/*	
    
    }*/
	
	// now set the right styles
	this.setStyles(mouse_x, mouse_y);
	// finally show the 
	
	new Effect.Grow(this.tool_tip,{duration:0.2,direction:'top-left'});   
    this.state=true;
  },
  
     setStyles: function(x, y){
    // set the right styles to position the tool tip
	Element.setStyle(this.tool_tip, { position:'absolute',
	 								  top:y + "px",
	 								  left:x + "px",
									  zindex:this.options.zindex
	 								});
	
	// apply default theme if wanted
	if (this.options.default_css){
	  	Element.setStyle(this.tool_tip, { //margin:this.options.margin,
		 								 // padding:this.options.padding,
		                                  backgroundColor:this.options.backgroundColor,
										  zindex:this.options.zindex
		 								});	
	}	
  },

  hideXpop: function(event){
	new Effect.Shrink(this.tool_tip,{duration:0.3,direction:'top-left'});
    this.state=false;  
  },

  getWindowHeight: function(){
    var innerHeight;
	if (navigator.appVersion.indexOf('MSIE')>0) {
		innerHeight = document.body.clientHeight;
    } else {
		innerHeight = window.innerHeight;
    }
    return innerHeight;	
  },
 
  getWindowWidth: function(){
    var innerWidth;
	if (navigator.appVersion.indexOf('MSIE')>0) {
		innerWidth = document.body.clientWidth;
    } else {
		innerWidth = window.innerWidth;
    }
    return innerWidth;	
  }

}