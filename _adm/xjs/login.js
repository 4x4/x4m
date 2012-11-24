
        document.observe('dom:loaded', function()
        {

            
            document.onkeydown = function(event)
               {
                    if (event.keyCode == 13) 
                    {
                        login();
                    }
                }
                
                             
            var login=function()
                    {
                      form=$('login');            
                      if((form.login.value.length>0)&&(form.password.value.length>0))
                      {
                       form.submit();
                      }
                    }
            
    
                $('loginbutton').observe('click',login);
                
                $('langs').observe('click',function()
                {
                      $$('.lang ul li a').invoke('toggle');
                });                

                $$('.lang ul li a').each(function(e){
                    e.observe('click',function(event)
                    {                    
                        el=Event.element(event).up();
                        $('langInput').value=el.readAttribute('lang');
                        $('clearGSTORAGE').value=1;
                        $$('.lang ul li:first')[0].update(el.down().innerHTML);
                        $$('.lang ul li a').invoke('toggle'); 
                    });
                    
                })
                
                
                $('cache').observe('click',function(event)
                {
                    el=Event.element(event).up();
                    if(el.hasClassName('selected'))
                    {
                        $('clearGSTORAGE').value=0;
                        el.removeClassName('selected');
                    }else{                            
                        
                        $('clearGSTORAGE').value=1;    
                        el.addClassName('selected');    
                    }
                });
            

                
        });
        
        
    

