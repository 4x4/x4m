/** 
 * @description		prototype.js based context menu
 * @author			Juriy Zaytsev; kangax@gmail.com; http://thinkweb2.com/projects/prototype
 * @version			0.5
 * @date			8/22/07
 * @requires		prototype.js 1.6.0_rc0
*/

// temporary unobtrusive workaround for 'contextmenu' event missing from DOMEvents in 1.6.0_RC0
/*if (!Event.DOMEvents.include('contextmenu')) {
	Event.DOMEvents.push('contextmenu')
}   */

// nifty helper for setting element's property in a chain-friendly manner



Element.addMethods({
	__extend: function(element, hash) {
		return Object.extend($(element), hash);
	}
})

if (Object.isUndefined(Proto)) { var Proto = { } }

/*модифицировано с привязкой к дереву*/
Proto.Menu = Class.create();
Proto.Menu.prototype = {
	initialize: function (options) {
		
        this.options = Object.extend({
			selector: '.contextmenu',
			className: '.protoMenu',
			pageOffset: 25,
			fade: false
		}, options || { });
		// Setting fade to true only if Effect is defined
		
        this.options.fade = false;
        //this.options.fade && !Object.isUndefined(Effect);
		 
        this.container =new Element('div',{className: this.options.className, style: 'display: none'});              
		
        this.options.menuItems.each(function(item){
			this.container.appendChild(item.separator ? 
				new Element('div', {className: 'separator'}) :
				new Element('a', {
						href: '#',
						title: item.name,
                        id:'pcm_'+item.id,
						className: item.disabled ? 'disabled' : ''
					})
					.observe('click', this.onClick.bind(this))
					.update(item.name)
					.__extend({_callback: item.callback})
				)
		}.bind(this));
        
      
		$(document.body).appendChild(this.container);
		
		Event.observe(document, 'click', function(e){
			this.container.hide();
		}.bind(this));
		 //opera
		$$(this.options.selector).invoke('observe', Prototype.Browser.Opera ? 'click' : 'contextmenu', function(e){
			if (Prototype.Browser.Opera && !e.ctrlKey) {
				return;
			}
			this.show(e);
		}.bind(this));
		
		this.containerWidth = this.container.getWidth();
		this.containerHeight = this.container.getHeight();
	},

    stopIt:function()
    {    
     
		$$(this.options.selector).invoke('stopObserving','contextmenu', function(e){this.show(e);}.bind(this));
        this.container.remove();
    }
    ,
    findById:function(id)
    {   
        _item=null;
    	this.options.menuItems.each(function(item)
        {
           if(item.id==id)
           {
            _item =item;             
           }
        });                
        return _item;
    },
    
    //toogleto=   false=disabled, true=enabled;
    toggleAll:function(toogleto)
    {
        toogleto ? this.container.descendants().invoke('removeClassName','disabled'):        
          this.container.descendants().invoke('addClassName','disabled');
        
     
    },
    
    enableItem:function (itemId)
    {
        $('pcm_'+itemId).removeClassName('disabled');
    },
    
    disableItem:function (itemId)
    {
        $('pcm_'+itemId).addClassName('disabled');
    },
    
	show: function(e) {
		Event.stop(e);
          
          //отключение итемов
           if(this.options.tree.block_mode)
           {
              this.options.tree.block_mode=false;
              return false;
           }
            
            if(!Object.isUndefined(this.options.tree))
            {                
                if(this.options.tree._selected!=null)
                if (this.options.tree._selected.length<1&&tree.getAllChecked().length<1)
                {
                    this.toggleAll(false); 
                }else{
                    this.toggleAll(true); 
                }
            }
        
		var viewport = document.viewport.getDimensions(),
			offset = document.viewport.getScrollOffsets(),
			containerWidth = this.container.getWidth(),
			containerHeight = this.container.getHeight();
		
    
        
        this.container.setStyle({
			left: ((e.pageX + containerWidth + this.options.pageOffset) > viewport.width ? (viewport.width - containerWidth - this.options.pageOffset) : e.pageX) + 'px',
			top: ((e.pageY - offset.top + containerHeight) > viewport.height && (e.pageY - offset.top) > containerHeight ? (e.pageY - containerHeight) : e.pageY) + 'px'
		}).hide();
		this.options.fade ? Effect.BlindDown(this.container, {duration: 0.15}) : this.container.show();
	},
	onClick: function(e) {
				Event.stop(e);  
		if (e.target._callback && !e.target.hasClassName('disabled')) {
			this.container.hide();
                  /*передаем ид выделенного элемента*/
                if(typeof this.options.tree!='undefined'){  
			        e.target._callback(this.options.tree.getSelectedItemId());
                }else{
                    e.target._callback();
                }
            
		}
	}
}