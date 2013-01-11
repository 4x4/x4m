$(document).ready(function() {
    if(Modernizr.csstransforms3d)
        {
    if(!$('div.sph')[0])
    {
        return;
    }
    
    
    var Degree = 0;
    var degree = 0;
    var scale;
    var tiltx;
    var tilty;
    var xp,yp;

    
    
  var _touch= ('ontouchstart' in window);
   
    retfunc=function(event) 
    {
        
        //$('#sphere').removeClass('animatix');
        
        
	if(event.emulate)
        {
            xp=event.pageX;
            yp=event.pageY;            
        }else{  
        
	
			if(_touch)
			{
			    
			    xp=event.originalEvent.touches[event.originalEvent.touches.length-1].pageX;
			    yp=event.originalEvent.touches[event.originalEvent.touches.length-1].pageY;
			    
			}else{
			    xp=event.pageX;
			    yp=event.pageY;
			}
        
	}
        
        
        
             
             
        bx=Math.ceil($('body').width() / 2.0);
        by=Math.ceil($('body').height() / 2.0);
        
        cx = Math.ceil($('div.sph').width() / 2.0);
        cy = Math.ceil($('div.sph').height() / 2.0);    
        
        dx = xp - bx;
        dy = yp - by;

        
        
        tiltx = (dy / by);
        tilty = (dx / bx);

        radius = Math.sqrt(Math.pow(tiltx,2) + Math.pow(tilty,2));
        degree = ((radius*10)*(radius*10));
        
        
          scale=radius;
             scale=1;
            
             /*
          if((1/scale)>1)
          
          if((1/scale)<0.2)scale=0.9;
            scale=1/scale; 
            */
            
            if(tilty<0){
                y=1
            }else{                
                y=-1;                
            }

            degree=degree*y;
            Scale = scale;
            
    //$('#sphere').css('-webkit-transform',' scale('+scale+') rotateY('+degree+'deg)');
    
    };
    

    hig=function()
    {
        var naklon = 0;
             

             
             
             /*console.log('x ' +xp);
             console.log('y ' +yp);*/
             
          
             if((yp<270&&yp>200)&&(tilty<0.15&&tilty>-0.15))
            {
                naklon = -80;                
                
                rg=Degree%360;
                tsix=0;
                if(rg>180){tsix=360;}
                Degree=Degree+tsix-rg;                    

            
    
                
                
            }else if((yp>650&&yp<800)&&(tilty<0.15&&tilty>-0.15))
            {
                
                naklon = 80;   
                rg=Degree%360;
                tsix=0;
                if(rg>180){tsix=360;}
                Degree=Degree+tsix-rg;                    
                             
                
            }else{
              
                naklon =tiltx*15;
            }
        
            
            
    
            if(Math.abs(tilty) > 0.15)
            {
                Degree = degree/7 + Degree;
                naklon=0;
   
            }
         
         //$('#sphere').css('-webkit-transform',' rotate3d(1,0,0,90deg)');      
        $('#sphere').css('-webkit-transform',' scale('+scale+') rotateY('+Degree+'deg)   rotateX('+naklon+'deg)');         
        $('#sphere').css('-moz-transform',' scale('+scale+') rotateY('+Degree+'deg)   rotateX('+naklon+'deg)');      
        $('#sphere').css('-ms-transform',' scale('+scale+') rotateY('+Degree+'deg)   rotateX('+naklon+'deg)');      
    }
    
    $('div.sph').mousemove(retfunc);
                  
    bx=Math.ceil($('body').width() / 2.0)-150;
    by=Math.ceil($('body').height() / 2.0)-60;
    
    
 

         ev={pageX:bx,pageY:by,emulate:true};  

     
     
     
     retfunc(ev);
     a=window.setInterval(hig, 200);

    $('div.sph').hammer({prevent_default:true}).bind("drag", retfunc);
        
        
                //$('#sphere').addClass('animatix');
        
        }    
});