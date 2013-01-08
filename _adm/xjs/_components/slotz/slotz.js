
var Slotz = new Class({
    
        Implements: Options,
        
        initialize:function(options)
        {
           this.setOptions(options);
           this.initiateSlotz();
            
        },
        
        initiateSlotz:function()
        {
            $('.def-slot ul').sortable({
                connectWith: '.def-slot ul'
            });
            
        }  
        
    
});