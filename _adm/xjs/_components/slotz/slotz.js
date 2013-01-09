
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
               placeholder: "placeholder",
               axis: "y",
           //     handle: ".handle" ,
                 revert:100,   
              
                cursorAt:{bottom:1}
            }).disableSelection();
            
            
           
                           
                           
                           
            $( ".def-slot ul" ).sortable( "option", "connectWith", ".connectedSortable" );
            
                              
            
            
            
        }  
        
    
});