
var Slotz = new Class({
    
        Implements: Options,
        
        initialize:function(options)
        {
           this.setOptions(options);
           this.initiateSlotz();
            
        },
        
        initiateSlotz:function()
        {
            $('#modulec_top,#modulec_footer,#modulec_right_side').sortable({
               placeholder: "placeholder",
               axis: "y",
           //     handle: ".handle" ,
                 revert:200,   
              
                cursorAt:{bottom:1},
       
                connectWith: ".connectedSortable"
            }).disableSelection();
                           
                           
                           
            //$( "#modulec_footer,#modulec_right_side" ).sortable( "option", "connectWith", ".connectedSortable" );
            
                              
            
            
            
        }  
        
    
});