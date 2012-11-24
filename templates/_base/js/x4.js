fConnector.prototype = {
    execute: function (data, func)
    {
        d = new Array();
        d[this.module+'.xfront'] = data;
        this.result = null;
        xConnector['xroute'](d, func);
        this.result = xConnector.result;
       
        if(xConnector.error)
        {
            this.onerror(xConnector.error);    
        }
        
        if(xConnector.message)
        {
            this.onmessage(xConnector.onmessage);    
        }
        
    },

        onerror:function(err)
        {
            
            if (window.console) 
            {
                console.log(err);
            }

        },
        
        onmessage:function(mes)
        {
            
            if (window.console) 
            {
                console.log(mes);
            }

        }
}

function fConnector(module)
{
    this.module = module;
    this.result = null;
}



/*
    Base.js, version 1.1a
    Copyright 2006-2010, Dean Edwards
    License: http://www.opensource.org/licenses/mit-license.php
*/

var Base = function ()
    {
        // dummy
    };

Base.extend = function (_instance, _static)
{ // subclass
    var extend = Base.prototype.extend;

    // build the prototype
    Base._prototyping = true;
    var proto = new this;
    extend.call(proto, _instance);
    proto.base = function ()
    {
        // call this method from any other method to invoke that method's ancestor
    };
    delete Base._prototyping;

    // create the wrapper for the constructor function
    //var constructor = proto.constructor.valueOf(); //-dean
    var constructor = proto.constructor;
    var klass = proto.constructor = function ()
        {
            if (!Base._prototyping)
            {
                if (this._constructing || this.constructor == klass)
                { // instantiation
                    this._constructing = true;
                    constructor.apply(this, arguments);
                    delete this._constructing;
                }
                else if (arguments[0] != null)
                { // casting
                    return (arguments[0].extend || extend).call(arguments[0], proto);
                }
            }
        };

    // build the class interface
    klass.ancestor = this;
    klass.extend = this.extend;
    klass.forEach = this.forEach;
    klass.implement = this.implement;
    klass.prototype = proto;
    klass.toString = this.toString;
    klass.valueOf = function (type)
    {
        //return (type == "object") ? klass : constructor; //-dean
        return (type == "object") ? klass : constructor.valueOf();
    };
    extend.call(klass, _static);
    // class initialisation
    if (typeof klass.init == "function") klass.init();
    return klass;
};

Base.prototype = {
    extend: function (source, value)
    {
        if (arguments.length > 1)
        { // extending with a name/value pair
            var ancestor = this[source];
            if (ancestor && (typeof value == "function") && // overriding a method?
            // the valueOf() comparison is to avoid circular references
            (!ancestor.valueOf || ancestor.valueOf() != value.valueOf()) && /\bbase\b/.test(value))
            {
                // get the underlying method
                var method = value.valueOf();
                // override
                value = function ()
                {
                    var previous = this.base || Base.prototype.base;
                    this.base = ancestor;
                    var returnValue = method.apply(this, arguments);
                    this.base = previous;
                    return returnValue;
                };
                // point to the underlying method
                value.valueOf = function (type)
                {
                    return (type == "object") ? value : method;
                };
                value.toString = Base.toString;
            }
            this[source] = value;
        }
        else if (source)
        { // extending with an object literal
            var extend = Base.prototype.extend;
            // if this object has a customised extend method then use it
            if (!Base._prototyping && typeof this != "function")
            {
                extend = this.extend || extend;
            }
            var proto = {
                toSource: null
            };
            // do the "toString" and other methods manually
            var hidden = ["constructor", "toString", "valueOf"];
            // if we are prototyping then include the constructor
            var i = Base._prototyping ? 0 : 1;
            while (key = hidden[i++])
            {
                if (source[key] != proto[key])
                {
                    extend.call(this, key, source[key]);

                }
            }
            // copy each of the source object's properties to this object
            for (var key in source)
            {
                if (!proto[key]) extend.call(this, key, source[key]);
            }
        }
        return this;
    }
};

// initialise
Base = Base.extend(
{
    constructor: function ()
    {
        this.extend(arguments[0]);
    }
}, {
    ancestor: Object,
    version: "1.1",

    forEach: function (object, block, context)
    {
        for (var key in object)
        {
            if (this.prototype[key] === undefined)
            {
                block.call(context, object[key], key, object);
            }
        }
    },

    implement: function ()
    {
        for (var i = 0; i < arguments.length; i++)
        {
            if (typeof arguments[i] == "function")
            {
                // if it's a function, call it
                arguments[i](this.prototype);
            }
            else
            {
                // add the interface using the extend method
                this.prototype.extend(arguments[i]);
            }
        }
        return this;
    },

    toString: function ()
    {
        return String(this.valueOf());
    }
});



var x4Module = Base.extend(
{
    jquery: (typeof window.jQuery === 'undefined') ? false : true
});





var _x4 = Base.extend(
{

    calledModules: {},
    addModule: function (module, obj)
    {
        this.calledModules[module] = obj;
    },
    constructor: function ()
    {}

})


x4 = new _x4();

//nano template system
if (typeof window.jQuery != 'undefined')
{
    (function ($)
    {
        $.nano = function (template, data)
        {
            return template.replace(/\{([\w\.]*)\}/g, function (str, key)
            {
                var keys = key.split("."),
                    value = data[keys.shift()];
                $.each(keys, function ()
                {
                    value = value[this];
                });
                return (value === null || value === undefined) ? "" : value;
            });
        };
    })(jQuery);

    $(document).ready(function()
    {
        $.fn.mainFieldWith = function (options)
        {
                
            defaults = {
                initalWord: ''
            }
            
            var options = $.extend(defaults, options);


            $(this).focus(function ()
            {

                if ($(this).val() == options.initalWord)
                {
                    this.value = '';
                }

            }).blur(function ()
            {

                if (!$(this).val())
                {
                    this.value = options.initalWord;
                }

            });
        }
        
        for (i in x4.calledModules) x4.calledModules[i].jqueryRun();
              
    });
    
    
    
    
    
    
}


pages= new fConnector('pages'); 
pages.execute({test:{value:'t'}});
  
/*  


$(function(){
  

  catalog= new fConnector('catalog') 
  
  $('a[rel=tocart]').click(function(e)
  {
      
       e.preventDefault();
       v=$(this).parent().find('input[name=count]').val();
       ishop.execute({add_to_cart:{id:$(this).attr('id'),count:$(this).parent().find('input[name=count]').val()}});
            
            $('#cartcount').html(ishop.result.cart.count);      
                  $('#added').css({top:e.pageY,left:e.pageX}).fadeIn(400).delay(600).fadeOut(400);
  })
  
  
  $('input[name=comparse]').click(function(e)
  {
       if($(this).attr('checked'))
        {
           catalog.execute({add_comparse:{id:$(this).attr('rel')}});    
            $(this).attr('checked',true);
       }else{
           
           catalog.execute({remove_comparse:{id:$(this).attr('rel')}});
           $(this).attr('checked',false);
       }
      
  })

  
  
  
});

*/