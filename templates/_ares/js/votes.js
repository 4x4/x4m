$(function(){
  
    var navRoots = $('.oprelements'),
    multiple = Number($('#multiple').val());
      //form = $('#voteForm');

  start_opros=function(){
    
      if(navRoots)
      {
          if(multiple)
          {
              var val = 0;
              
              navRoot.find('li').each(function(i) {
                
                  $(this).click(function() {
                      var a = $(this).find('a');
                      
                      if(a.hasClass('selected'))
                      {
                          a.removeClass('selected');
                          val = 0;
                      }
                      else
                      {
                          a.addClass('selected');
                          val = 1;
                      }
                          
                      $(this).find('input[name=variant]').val(val);
                      
                  });
              });
          }
          else
          
          {   
              
              navRoots.each(function()
              {
                    navRoot=$(this);
                    form=navRoot.parent();
                                            
                    form.find('a[rel=vote]').click(function()
                    {
                        $(this).parent().submit();
                        
                    });
                    
                        navRoot.find('li').each(
                        function(i) 
                        {
                          $(this).click(function() 
                          {
                              $(this).parents('form').find('a.selected').removeClass('selected');
                              $(this).parents('form').find("input[name='variant']").val($(this).find('a').attr('rel'));
                              $(this).find('a').addClass('selected');
                              
                          });
                      
                      });
                    });
      
  
      }
  }
  }
  
  
  start_opros();
});