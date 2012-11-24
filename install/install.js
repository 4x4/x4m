var XTRinstall = Class.create();
var XTR_install;


XTRinstall.prototype =
    {
    
    indicator:function(step)
    {
        $('indicator').down('span',step).addClassName('selected');   
    },
    
    initialize: function(source)
        {
            this.source=$(source);
            
            for(var name in  globalStorage[window.location.hostname]) {
            delete globalStorage[window.location.hostname][name];            
            }
            
            globalStorage[window.location.hostname]=null;
        },
    
      next:function(page)
      {
        install.execute({process:{page:page}});
        this.indicator(1);
        this.source.update(install.result.steppage);
      },
      
      error:function($text)
      {
        if(install.result.error)
        {
            $('error').update(install.result.errortext);
            $('error').style.display='block';
            
        }
      
      }
      ,
      check_admin:function()
      {
        
        install.execute({check_admin:{user:xoad.html.exportForm('admin')}});
        if(install.result.admin)
        {  
            this.indicator(3);
            this.source.update(install.result.steppage);
        }else{
            this.error();
        }
      },
      
        
      database:function()
      {

        install.execute({check_database:{data:xoad.html.exportForm('database')}});
        if(install.result.db)
        {          this.indicator(2);
            this.source.update(install.result.steppage);
        }else{
            this.error();
        }
      }
    
    }
    
Event.observe(window,'load',function()
{
 XTR_install=new XTRinstall('content');
});