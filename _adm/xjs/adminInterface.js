
debug.setLevel(9);

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
        d[this.module] = data;
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
        
        
            this.result = xConnector.result;
            if(!func)return this.result;
        }
  
});


c=new Connector('');

c.execute({dd:'dd'});
    
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
                $H(val).each(function(pkey)
                {
                    callback(pkey[0],pkey[1]);   
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


var _storageProxy = Class.create(
{
    initialize:function(initialBranch)
    {
        lcSt=new _storage();
        this.initialBranch=initialBranch;
        this.initialBranchTimer=initialBranch+'Timer';
        this.storage = new Hash();
        this.syncroTimer=new Hash();
        this.localStorageDetected=lcSt.storageDetected
                        
        if(this.localStorageDetected)
        {
            this.localStorage=lcSt;
            
            if(initialBranch)
            {
                if(this.localStorage.hasItem(initialBranch))
                {
                    
                    this.localStorage.each(initialBranch,function(k,v)
                    {
                        this.storage.set(k,v);        
                    
                    }.bind(this));
                    
                    
                    this.localStorage.each(this.initialBranchTimer,function(k,v)
                    {
                        this.syncroTimer.set(k,v);        
                    
                    }.bind(this));
                    
                }
            }
        
        }

    },
    
    clear:function()
    {
            this.storage = new Hash();    
            if(this.localStorageDetected)
                {
                    this.localStorage.remove(this.initialBranch);
                }
    },
    
    set:function(key,val,timer)
    {
        this.storage.set(key,val);    
        
        if(timer)this.syncroTimer.set(key,timer);    
        
        if(this.localStorageDetected)
        {
            this.localStorage.set(this.initialBranch+'>'+key,val);                
            if(timer)this.localStorage.set(this.initialBranchTimer+'>'+key,timer);                
        }
        
    },
    
    getTimer:function(key)
    {
        return this.syncroTimer.get(key);    
    },
    
    get:function(key)
    {
        return this.storage.get(key);    
    }
   
}) 


var _templateHolder=Class.create(
{
    
    initialize: function (options)
    {
        this.options = {}        
        this.tplStorage= new _storageProxy('tplHolder')
        this.thisSessionLoaded={}
        Object.extend(this.options,options || {});
    },
    
    
    setTpl:function(module,tplName,tplText,tplTime,isNew)
    {
        marker=module+'_'+tplName;
        
        if (Object.isUndefined(isNew))        
        {
            this.thisSessionLoaded[marker]=true;
        }
        
        this.tplStorage.set(module+'_'+tplName.toHashCode,tplText,tplTime);
    },
    
    
    getTpl:function(module,tplName)
    {
        
        
        if(!Object.isArray(tplName))tpl=[tplName];
        
        this.loadModuleTpls(module,tpl);
        
         /*
        if(this.thisSessionLoaded[marker])
        {  
            tpl=this.tplStorage.get(module+'_'+tplName.toHashCode)
        }  */
        
    },
    
    
    
     cacheTpl: function (module, tpl_name, tpl_text, tpl_time, anew_loaded)
    {
        tpl = new Hash();
        if (Object.isUndefined(anew_loaded))
        {
            anew_loaded = false;
        }
        
        tpl.set(tpl_name, {
            tpl: tpl_text,
            time: tpl_time,
            loaded: anew_loaded
        });
        
        if (useGSTORAGE)
        {
            storageTplTime = String(GSTORAGE[implode(new Array('tpl', module, tpl_name, 'time'), '@')]);
            if (storageTplTime != tpl_time)
            {
                GSTORAGE[implode(new Array('tpl', module, tpl_name, 'time'), '@')] = tpl_time;
                GSTORAGE[implode(new Array('tpl', module, tpl_name, 'tpl'), '@')] = tpl_text;
            }
        }
        if (!this.cached_tpl[module])
        {
            this.cached_tpl[module] = tpl;
        }
        else
        {
            this.cached_tpl[module].update(tpl);
        }
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
            tplArr.each(function (tplName)
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
                                time: this.getTimer(marker)
                            });
                        }
                    }
                }.bind(this));
            
            
            if (tpls.size() > 0)
            {
                this.connector.execute(
                {
                    loadModuleTplsXFront: 
                    {
                        module: module,
                        tpls: tpls
                    }
                }, false, 'route');
                
                this.cache_mass_tpl(module, $H(this.connector.lct), $H(this.connector.result.lm_time));
            }
        }
    }
    
    
    
    
});

*/

var _adminInterface = Class.create(
{
initialize: function (options)
    {
        this.options = {}
        this.currentModule=null;
        this.storedJs=new _storageProxy('js');
        this.calledModules={};
        Object.extend(this.options,options || {});
    },    
    
    
    loadJs: function (path,store)
    {
        
        hashPath=path.toHashCode()
        
        if ($(hashPath))
        {
              return false;
        }
        
        if(!(code=this.storedJs.get(hashPath)))
        {
            var transport = Ajax.getTransport();
            transport.open('POST', path, false);
            transport.send(null);
            code = transport.responseText;    
            this.storedJs.set(hashPath,code);
        }
        
        
        var script = document.createElement("script");
            script.setAttribute('id',hashPath);
            
            if(code.blank())
            {
                debug.warn(path+'  trying to load but javascript file is empty.');
                return false;
                               
            }else{
                script.text = code;
                document.getElementsByTagName('head')[0].appendChild(script);
                this.options.loadedJs.push(path)
                debug.info(path+' load success');                
                return true;        
                
            }
            
        
    },
    
    loadModule: function (module, calltype,loadJs)
    {
        xtr_name = "XTR_" + module;
        
        if (typeof this.calledModules[xtr_name] != 'object')
        {

            
            if(loadJs)
            {
                if(!this.loadJs('/modules/'+module+'/js/'+module+'Back.js'))return;
            }
            
            run_str = xtr_name + "=new XTR" + module + "();";            
            eval(run_str);            
            if (calltype == 'normal')
            {
                this.currentModule =window[xtr_name];
                if(typeof this.currentModule.buildInterface == 'function')this.currentModule.buildInterface();                
                this.calledModules[xtr_name] = this.currentModule;
            }
            else
            {
                //кешируем silent модуль
                this.calledModules[xtr_name] = window[xtr_name]
            }
        }
        else
        {
            if (calltype == 'normal')
            {
                this.currentModule = this.calledModules[xtr_name];
                this.currentModule.build_interface();                   
            }
        }
    }
    
});


document.observe("dom:loaded", function() 
{

/*st=new _storage()
st.clear();
*/ 


/*TH =new _templateHolder();

 
AI= new _adminInterface();
AI.loadModule('backup','normal',true);
*/

 

 

sp = new _storageProxy('sp');
sp.set('spicy',1,150);
sp.set('spicy2',2,180);




   
});


    