
debug.setLevel(9);

Class.Mutators.jQuery = function(name){
var self = this;
jQuery.fn[name] = function (arg){
var instance = this.data(name);
if (typeOf(arg) == 'string'){
var prop = instance[arg];
if (typeOf(prop) == 'function'){
var returns = prop.apply(instance, Array.slice(arguments, 1));
return (returns == instance) ? this : returns;
} else if (arguments.length == 1){
return prop;
}
instance[arg] = arguments[1];
} else {
if (instance) return instance;
this.data(name, new self(this.selector, arg));
}
return this;
};
};




 var xConnector = {
    "lct": null,
    "result": null,
    "__meta": {
        "lct": "null",
        "result": "null"
    },
    "__size": 2,
    "__class": "connector",
    "__url": "\/admin.php",
    "__uid": "d20208ceea86f16f321d89ef3756d627",
    "__output": null,
    "__timeout": null,
    "xroute": function() {
        return xoad.call(this, "xroute", arguments)
        }
};


xConnector.clearModuleVars = function ()
{
    this.result = null;
}

xConnector.onexecuteError = function (error)
{
    alert(_lang_common['error_on_server'] + '\n\n' + error.message);
    return true;
}

//extending native prototype
String.prototype.toHashCode = function(){
        var hash = 0;
        if (this.length == 0) return hash;
        for (i = 0; i < this.length; i++) {
            char = this.charCodeAt(i);
            hash = ((hash<<5)-hash)+char;
            hash = hash & hash; // Convert to 32bit integer
        }
        return 'h'+hash;
    }

var Connector = new Class({
    module: null,
    result: null,
    initialize: function (module)
    {
        this.module = module;
    },
    //function(data,(arg)) 
    //если  arg - то выполнение откладывается до первого вызова
    //arg (2) - модель роутинга
    onerror: function (error)
    {;
        for (i = 0; i < error.length; i++)

        growlerr.error(error[i].message, {
            sticky: true
        });
    },

    onmessage: function (message)
    {

        for (i = 0; i < message.length; i++)
        growlerr.info(message[i].message, {
            life: 2
        });

    },

    execute: function (data,func)
    {
        d = new Array();
        d[this.module+'.back'] = data;
        this.result = null;
        xConnector['xroute'](d, func);

            if (xConnector.error != null)
            {
                this.onerror(xConnector.error);

            }

            if (xConnector.message != null)
            {
                this.onmessage(xConnector.message);

            }
        
            this.lct = xConnector.lct;            
            this.result = xConnector.result;
            
            if(!func)return this.result;
        }
  
});

/*
c=new Connector('');

c.execute({dd:'dd'});
 */   
 
var _storage= new Class(
{

    detectLocalStorage : function ()
    {
        try {
        return !!localStorage.getItem;
      } catch(e) {
        return false;
      }

    },
    
    initialize:function()
    {
        this.storageDetected=false;

        if(this.detectLocalStorage())
        {
            this.localStorage = window.localStorage;
            debug.log(window.localStorage);
            this.storageDetected=true;                    
        }
    },

    
    set:function(key,value)
    { 

        if (typeof value == "object") 
                {
                    value = JSON.stringify(value);
                }
                this.localStorage.setItem(key, value);

        
    },
    
    hasItem: function( key )
    {
        
        return(
         this.localStorage.getItem( key ) != null
        );
    },

    remove:function(key)
    {
        
        var i = -1,
            key, len = this.localStorage.length;
            
            while (++i < len)
            {
                lkey = this.localStorage.key(i); // retrieve the value of each key at each index

                if(lkey.indexOf(key)==0)
                {
                    this.localStorage.removeItem(lkey);
                }
            }
             

        
    },

    clear :function()
    {
        this.localStorage.clear();   
    },
     

    get:function(key)
    {
        
                if((value = this.localStorage.getItem(key))!=null)
                {

                    return this.jsonCheck(value);                       
                     
                }else{
                    
                    if(value = this.getPartialKey(key+'>'))
                    {
                        return  value;    
                    }
                }
      
        return null;
                
    },
    
     each : function (key,callback) 
     {
         
            if(val=this.get(key))
            {
                Object.each(val,function(key,val)
                {
                    callback(key,val);   
                })
            }
             
     }
     ,
     

 
    setData:function(key,val,obj) 
    {
        
        if (!obj) obj = data; 
        var ka = key.split(/\>/); 
        if (ka.length < 2) { obj[ka[0]] = val; } 
        
        else {
            if (!obj[ka[0]]) obj[ka[0]] = {};
            obj = obj[ka.shift()]; 
            this.setData(ka.join(">"),val,obj); 
        }
     
     },

    
    getPartialKey:function(key)
    {
         var i = -1,
            key, len = this.localStorage.length,
            // the length property tells us 
            // how many items are in the storage
            res = {};

            keySplitted=key.split('>');
            
            while (++i < len)
            {
                lkey = this.localStorage.key(i); // retrieve the value of each key at each index

                if(lkey.indexOf(key)==0)
                {
                    item=this.localStorage.getItem(lkey); 
                  
                    this.setData(lkey,this.jsonCheck(item),res);
                }
            }

             if(res)
             {                                                            
                for(k=0;k < keySplitted.length-1;k++)
                {
                    res=res[keySplitted[k]];
                }
                return res;
             }

    },    
    
    jsonCheck:function(value)
    {
              if (value[0] == "{") {
                    value = JSON.parse(value);
                }
                return value;
    }
    
}); 


var _storageProxy = new Class(
{
    initialize:function(initialBranch)
    {
        lcSt=new _storage();
        this.initialBranch=initialBranch;
        this.initialBranchTimer=initialBranch+'Timer';
        this.storage ={};
        this.syncroTimer={};
        this.localStorageDetected=lcSt.storageDetected
                        
        if(this.localStorageDetected)
        {
            this.localStorage=lcSt;
            
            if(initialBranch)
            {
                
                if(this.localStorage.get(initialBranch))
                {
                    
                    this.localStorage.each(initialBranch,function(k,v)
                    {
                        this.storage[k]=v;        
                    
                    }.bind(this));
                    
                    
                    this.localStorage.each(this.initialBranchTimer,function(k,v)
                    {
                        this.syncroTimer[k]=v;        
                    
                    }.bind(this));
                    
                }
            }
        
        }

    },
    
    clear:function()
    {
            this.storage = {};    
            if(this.localStorageDetected)
                {
                    this.localStorage.remove(this.initialBranch);
                }
    },
    
    set:function(key,val,timer)
    {
        
        this.storage[key]=val;    
        
        if(timer)this.syncroTimer[key]=timer;    
        
        if(this.localStorageDetected)
        {
            this.localStorage.set(this.initialBranch+'>'+key,val);                
            if(timer)this.localStorage.set(this.initialBranchTimer+'>'+key,timer);                
        }
        
    },
    
    getTimer:function(key)
    {
        return this.syncroTimer[key];    
    },
    
    get:function(key)
    {
        return this.storage[key];    
    }
   
}) 


var _templateHolder=new Class(
{
    Implements: Options,
    initialize: function (options)
    {
        this.setOptions(options);
        this.tplStorage= new _storageProxy('tplHolder')
        this.thisSessionLoaded={}
        this.connector= new Connector('adminPanel');
    },
    
    
    setTpl:function(module,tplName,tplText,tplTime,isNew)
    {
        marker=module+'_'+tplName;
        
        if (isNew)        
        {
            this.thisSessionLoaded[marker]=true;
        }
        
        this.tplStorage.set(module+'_'+tplName.toHashCode(),tplText,tplTime);
    },
    
    
    getTpl:function(module,tplName)
    {
        if(typeOf(tplName)!='array')tpl=[tplName];
        this.loadModuleTpls(module,tpl);
        
         /*
        if(this.thisSessionLoaded[marker])
        {  
            tpl=this.tplStorage.get(module+'_'+tplName.toHashCode)
        }  */
        
    },
    
    tplExists: function (module, tplName)
    {
        return (this.cachedTpl) && (this.cachedTpl[module]) && (this.cachedTpl[module].get(tplName));
    },
    
    
    loadModuleTpls: function (module, tplArr)
    {
        
        var tpls = new Array();
        
        if (tplArr)
        {
            Array.each(tplArr,function (tplName,index)
            {
                
                if (this.tplStorage.get(module, tplName) == false)
                {
                    tpls.push(
                    {
                        tplName: tplName
                    });
                }
                else
                {
                    marker=module+'_'+tplName;  
                    
                    if (!this.thisSessionLoaded[marker])
                        {
                            tpls.push(
                            {
                                tplName: tplName,
                                time: this.tplStorage.getTimer(marker)
                            });
                        }
                    }
                }.bind(this));
            
            
            if (tpls.length > 0)
            {
                this.connector.execute(
                {
                    loadModuleTplsBack: 
                    {
                        module: module,
                        tpls: tpls
                    }
                });
                
                if(this.connector.lct)
                {
                    Object.each(this.connector.lct.templates,function (tplText,index)
                    {
                        
                        this.setTpl(module,index,tplText,this.connector.lct.timers[index],true);
                    
                    }.bind(this));
        
        
            }
        }
    }
    }
    
    
    
    
});


var _adminInterface = new Class(
{
    Implements: Options,
    Extends: Router,
    
    routes: 
     {
        '#e/:module/:action/*'    : 'moduleActionDispatch'
     },
    
    initialize: function (options)
    {        
        this.setOptions(options);
        this.currentModule=null;
        this.storedJs=new _storageProxy('js');
        this.calledModules={};
        this.loadedJs=[];
        this.parent();
    },    
    
    
    moduleActionDispatch:function()
    {        
            debug.info('trying to dispatch module action - params:',this.param,this.query);        
     
            if(this.currentModule)
            {
                if(this.currentModule.name!=this.param.module)
                {
                    this.currentModule.sleep('hashDispatch');
                }
                            
            }
        
            if(module=this.loadModule(this.param.module,'normal',true))
            {
                module[this.param.action](this.query);                     
                debug.info('action dispatched success:',this.param,this.query);
            }
            
        
    },
    
    navHashCreate:function(module,action,params)
    {
        return '#e/'+module+'/'+action+'/?'+jQuery.param(params);
    },
    
    loadMultiJs:function(arrJs)
    {
         Array.each(arrJs,function (path,index)
            {
                this.loadJs(path);
            });
        
    },
    
    loadJs: function (path,store)
    {

        path=path.replace('*','/_adm/xjs/');
        hashPath=path.toHashCode()
        
        if (this.loadedJs.indexOf(hashPath)!=-1){return false;}
        
        if(!(code=this.storedJs.get(hashPath)))
        {
            var code;
            
            $.ajax({
              url: path,
              async:false,
              complete:function(data)
              {
                    code =data.responseText;
                    if(store)this.storedJs.set(hashPath,code);
            
              }.bind(this)
              });
                
        }
            
            if(code.clean()=='')
            {
                debug.warn(path+' trying to load but javascript file is empty.');
                return false;
                               
            }else{
                code+=' //@ sourceURL='+path;         //debug issues
                this.loadedJs.push(path);
                eval(code);
                debug.info(path+' load success');                
                return true;        
                
            }
            
            
            
    },
    
    loadModule: function (module, calltype,loadJs)
    {
        x_name = "x_" + module;
            
        if (typeof this.calledModules[x_name] != 'object')
        {
            
            if(loadJs)
            {
                if(!this.loadJs('/modules/'+module+'/js/'+module+'Back.js',true))return;
            }
            
            run_str= x_name + "=new " + module + "Back('"+module+"');";                        
            eval(run_str);                      

            if (calltype == 'normal')
            {
                this.currentModule =window[x_name];
                
                if(typeof this.currentModule.buildInterface == 'function')this.currentModule.buildInterface();                
                this.calledModules[x_name] = this.currentModule;
            }
            else
            {
                //кешируем silent модуль
                this.calledModules[x_name] = window[x_name]
            }
            
        }
        else
        {
            if (calltype == 'normal')
            {
                this.currentModule = this.calledModules[x_name];
                this.currentModule.buildInterface();  
                
            }
        }
        
        return   this.calledModules[x_name];
    }
    
});

var _xModuleBack= new Class(
{
    
    Implements: Options,
    initialize:function()
    {
        this.connector= new Connector(this.name);    
    },
      
    setName:function(name)
    {
          this.name=name;
    },
       
    
    //усыпление модуля при переключении : скрыть активные окна и т.д
    sleep:function(status)
    {
        
    },
    
    CRUN: function (objType,action)
    {        
        this.connector.execute();
    }    
    
});





var cardeonMonitor = new Class({
    
    Implements: [Options, Events], 

        options: {
            container:'.c-blok', 
            header:'.c-title'            
        },

    jQuery: 'cardeonMonitor',

    initialize: function(options){
        this.setOptions(options); // inherited from Options like jQuery.extend();
        
        this.options.clickTarget=this.options.container+' '+this.options.header;
        this.proxied = 
        {
          click: jQuery.proxy(this.clickHandler, this)
          
        };
        
             
        jQuery(this.options.clickTarget).live('click', this.proxied.click);
        
        $(window).on('mutate',function(){
           
           this.autoShow();

        }.bind(this));

    },
    

    clickHandler: function(event)
    {
        event.preventDefault();
        element=event.target;
        if(jQuery(element).next().is(':visible'))
        {
            jQuery(element).removeClass('up');
            jQuery(element).next().slideUp();
            
        }else{
            jQuery(element).addClass('up');
            jQuery(element).next().slideDown();
        }
        
    },

    autoShow: function()
    {
        jQuery(this.options.container+' input,'+this.options.container+' textarea').each(function(n,e)
        {
            if($(e).val())
            {
                $(e).parents("div").show();
            }
        }); 
    }

});




$(document).ready(function(){


var e = jQuery.Event("mutate");

MutationObserver = window.MutationObserver || window.WebKitMutationObserver;
// define a new observer
var obs = new MutationObserver(function(mutations, observer) {
    for(var i=0; i<mutations.length; ++i) {
        // look through all added nodes of this mutation
        for(var j=0; j<mutations[i].addedNodes.length; ++j) {
                                         
            // was a child added with ID of 'bar'?
            if(!$(mutations[i].addedNodes[j]).hasClass('firebugResetStyles'))
            {
                $(window).trigger('mutate');
            }
        }
    } 

    
});
   




// have the observer observe foo for changes in children
obs.observe($("body").get(0), {childList: true,subtree: true});





   
st=new _storage()
st.clear();


var cr= new cardeonMonitor();


var slotz = new Slotz({id:'slotz'}); 
          
AI= new _adminInterface();
//hc=AI.navHashCreate('pages','sleep',{a:'b',c:102});
//AI.navigate(hc);

/*
TH =new _templateHolder();
TH.getTpl('adminPanel','ainterface');


AI= new _adminInterface();
//AI.loadJs('*_components/mt.router/router.js');


//AI.loadModule('content','normal',true);

 */


 
/*
sp = new _storageProxy('sp');
sp.set('spicy',1,150);
sp.set('spicy2',2,180);

s=sp.get('spicy');
alert(s);
*/




   
});


    