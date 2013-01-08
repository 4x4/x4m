
var Slotz = new Class({
    
        Implements: Options,
        
        initialize:function(options)
        {
           this.setOptions(options);
           this.initiateSlotz();
            
        },
        
        initiateSlotz:function()
        {
            $('#modulec_footer,#modulec_right_side').sortable({
               placeholder: "placeholder",
                tolerance: "intersect",
             axis: "y",
            forceHelperSize: true,
                cursorAt:{top:1,left:1},
                revert:true,
                 
                       connectWith: ".connectedSortable"
              
            }).disableSelection();
                           
                           
                           
            //$( "#modulec_footer,#modulec_right_side" ).sortable( "option", "connectWith", ".connectedSortable" );
            
                              
            
            
            
        }  
        
    
});