
var Slotz = new Class({
    
        Implements: Options,
        
        initialize:function(options)
        {
           this.setOptions(options);
           this.initiateSelectors();
            
        },
        
        initiateSelectors:function()
        {
            $('.def-slot ul').sortable({
                
               placeholder: 'placeholder',
               axis: 'y',
               revert:100,   
               cursorAt:{bottom:1}
               
               }).disableSelection();
                           
            $('.def-slot ul' ).sortable( 'option', 'connectWith', '.connectedSortable' );
            $('.def-slot ul li').on('dblclick tap',this.slotClick.bind(this));
                        
        },  
        
        slotClick:function(e)
        {
          alert('hello');
        }
        
        
        
        
    
});