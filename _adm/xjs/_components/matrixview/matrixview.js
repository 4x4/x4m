//
// MatrixView 1.0.3
//
// For more information on this library, please see http://www.matrixview.org/.
//
// Copyright (c) 2007-2008 Justin Mecham <justin@aspect.net>
// 
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
// 
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
// 
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.
//

// modified for X3M.CMS Bobrov Dmitry 2009
var matrixHandy=new Array();
var  XSF=null;
var MatrixView = Class.create()

MatrixView.prototype = {

  // The Attached Element
  element: null,
  // Handlers
  selectHandler: null,
  deselectHandler: null,
  openHandler: null,
  deleteHandler: null,
  // Selected Items
  selectedItems: null,
  keydown:null,
  windowHandle:'default',
  
  
  initialize:function(){
    
  },
  
  
  stopobserveKeydown:function()
  {
    Event.stopObserving(document, 'keydown',this.keydown);  
  },
  
  observeKeydown:function()
  {
      if(!this.keydown){
      this.keydown=function(event)
      {
        // Meta/Control
        if (event.metaKey)
        {
          if (event.keyCode == 65) // Shift-A (Select All)
          {
            matrixHandy[this.windowHandle].selectAll()
            event.stop()
            return false
          }
          return
        }

        // Shift
       else if (event.shiftKey)
        {
          if (event.keyCode == Event.KEY_LEFT || event.keyCode == 63234) // Left Arrow
            matrixHandy[this.windowHandle].expandSelectionLeft(event)
      /*    if (event.keyCode == Event.KEY_UP || event.keyCode == 63232) // Up Arrow
            matrixHandy[this.windowHandle].expandSelectionUp(event)
           */ 
           
           
          if (event.keyCode == Event.KEY_RIGHT || event.keyCode == 63235) // Right Arrow
            matrixHandy[this.windowHandle].expandSelectionRight(event)
         /* if (event.keyCode == Event.KEY_DOWN || event.keyCode == 63233) // Down Arrow
            matrixHandy[this.windowHandle].expandSelectionDown(event)*/
          if (event.keyCode == 32) // Space
            event.stop()
          if (event.keyCode == Event.KEY_TAB) // Tab
          {
            if (matrixHandy[this.windowHandle].selectedItems.size() > 0)
              matrixHandy[this.windowHandle].moveLeft(event)
          }
          return
        }
   
        if (event.keyCode == Event.KEY_RETURN) // Enter (Open Item)
        {
          if (matrixHandy[this.windowHandle].selectedItems.size() == 1)
            matrixHandy[this.windowHandle].open(matrixHandy[this.windowHandle].selectedItems.first())
        }
        /*if (event.keyCode == Event.KEY_DELETE  ) // Delete/Backspace
        {
          
          matrixHandy[this.windowHandle].destroy(matrixHandy[this.windowHandle].selectedItems)
          event.stop()
        } */
        /*
        if (event.keyCode == Event.KEY_LEFT || event.keyCode == 63234) // Left Arrow
          matrixHandy[this.windowHandle].moveLeft(event)
        if (event.keyCode == Event.KEY_UP || event.keyCode == 63232) // Up Arrow
          matrixHandy[this.windowHandle].moveUp(event)
        if (event.keyCode == Event.KEY_RIGHT || event.keyCode == 63235) // Right Arrow
          matrixHandy[this.windowHandle].moveRight(event)
        if (event.keyCode == Event.KEY_DOWN || event.keyCode == 63233) // Down Arrow
          matrixHandy[this.windowHandle].moveDown(event)
          */
      }.bind(this) }

      Event.observe(document, 'keydown',this.keydown);

 
  
  
  },

  initilizeObservers: function()
  {
        
   element=this.element; 
    // Observe keys
      this.observeKeydown();
      

     
    // Double Click
    Event.observe(element, 'dblclick',
      function(event) {
        element = Event.element(event)
        if (element.tagName != 'LI') element = element.up('li')
        if (element)
        {
          matrixHandy[this.windowHandle].deselectAll()
          matrixHandy[this.windowHandle].open(element)
        }
        event.preventDefault()
      }.bind(this)
    )

    // Click / Mouse Down
    Event.observe(element, 'mousedown',
      function(event) {
        f_element = Event.element(event)
        if (f_element.tagName != 'LI') element = f_element.up('li')
        if (element)
          matrixHandy[this.windowHandle].select(element, event)
        else{
            if(f_element.ancestors().indexOf(matrixHandy[this.windowHandle].element)) matrixHandy[this.windowHandle].deselectAll();
        }

        event.preventDefault()
      }.bind(this)
    )

},

  deselectAll: function() {
    this.element.getElementsBySelector('li.selected').invoke('removeClassName', 'selected')
    this.selectedItems.clear()
    // If a custom deselect handler has been defined, call it
    if (this.deselectHandler != null)
      this.deselectHandler()
  },

  select: function(element, event)
  {

    // Multiple Selection (Shift-Select)
    if (event && event.shiftKey)
    {
      // Find first selected item
      firstSelectedElement      = this.element.down('li.selected')
      firstSelectedElementIndex = this.items().indexOf(firstSelectedElement)
      selectedElementIndex      = this.items().indexOf(element)

      // If the first selected element is the element that was clicked on
      // then there's nothing for us to do.
      if (firstSelectedElement == element)
        return

      // If no elements are selected already, just select the element that
      // was clicked on.
      if (firstSelectedElementIndex == -1) {
        matrixHandy[this.windowHandle].select(element)
        return
      }

      siblings = null
      if (firstSelectedElementIndex < selectedElementIndex)
        siblings = firstSelectedElement.nextSiblings()
      else
        siblings = firstSelectedElement.previousSiblings()
      done = false
      siblings.each(
        function(el) {
          if (done == false) {
            el.addClassName('selected')
            matrixHandy[this.windowHandle].selectedItems.push(el)
          }
          if (element == el) done = true
        }.bind(this)
      )
    }

    // Multiple Selection (Meta-Select)
    else if (event && event.metaKey)
    {
      // If the element is already selected, deselect it
      if (element.hasClassName('selected'))
      {
        this.selectedItems[this.selectedItems.indexOf(element)] = null
        element.removeClassName('selected')
      }

      // Otherwise, select it
      else
      {
        this.selectedItems.push(element)
        element.addClassName('selected')
      }
    }

    // Single Selection (Single Click)
    else
    {
      $$('#' + this.element.id + ' li.selected').invoke('removeClassName', 'selected')
      this.selectedItems = new Array(element)
      element.addClassName('selected')
    }

    // If a custom select handler has been defined, call it
    if (this.selectHandler != null)
      this.selectHandler(element)
  },

  open: function(element)
  {
    
    this.deselectAll()
    element.addClassName('selected')
    
    // If a custom open handler has been defined, call it
    if (this.openHandler != null)
      this.openHandler(element)
  },

  destroy: function(elements)
  {
    // If a custom open handler has been defined, call it
    if (this.deleteHandler != null)
      this.deleteHandler(elements)
  },

  selectAll: function()
  {
    this.deselectAll()
    $$('#' + this.element.id + ' li').each(
      function(el) {
        el.addClassName('selected')
        matrixHandy[this.windowHandle].selectedItems.push(el)
      }.bind(this)
    )

    // If a custom select handler has been defined, call it
    if (this.selectHandler != null)
      this.selectHandler(matrixHandy[this.windowHandle].selectedItems)
  },

  selectFirst: function()
  {

    element = $$('#' + this.element.id + ' li').first()

    this.deselectAll()
    this.select(element)

    this.scrollIntoView(element, 'down')

    // If a custom select handler has been defined, call it
    if (this.selectHandler != null)
      this.selectHandler(element)
  },

  selectLast: function()
  {
    element = $$('#' + this.element.id + ' li').last()

    this.deselectAll()
    this.select(element)

    this.scrollIntoView(element, 'down')

    // If a custom select handler has been defined, call it
    if (this.selectHandler != null)
      this.selectHandler(element)
  },

  moveLeft: function(event)
  {
    event.stop()
    element = $$('#' + this.element.id + ' li.selected').first()
    if (!element)
      return this.selectFirst()
    if (previousElement = element.previous())
    {
      this.select(previousElement)
      this.scrollIntoView(previousElement, 'up')
    }
    else
      this.selectFirst()
  },

  moveRight: function(event)
  {
    event.stop()
    element = $$('#' + this.element.id + ' li.selected').last()
    if (!element)
      return this.selectFirst()    
    if (nextElement = element.next())
    {
      this.select(nextElement)
      this.scrollIntoView(nextElement, 'down')
    }
    else
      this.selectLast()
  },

  moveUp: function(event)
  {
    event.stop()

    element = $$('#' + this.element.id + ' li.selected').first()
    if (!element) return this.selectFirst()

    offset = Position.cumulativeOffset(element)
    y = Math.floor(offset[1] - element.getHeight())

    previousSiblings = element.previousSiblings()
    if (previousSiblings.size() == 0) return this.selectFirst()

    previousSiblings.each(
      function(el) {
        if (Position.within(el, offset[0], y))
        {
          matrixHandy[this.windowHandle].select(el)
          matrixHandy[this.windowHandle].scrollIntoView(el, 'up')
        }
      }.bind(this)
    )

  },

  moveDown: function(event)
  {
    event.stop()

    element = this.element.getElementsBySelector('li.selected').last()
    if (!element) return this.selectFirst()

    offset = Position.cumulativeOffset(element)
    y = Math.floor(offset[1] + element.getHeight() + (element.getHeight() / 2)) + parseInt($(element).getStyle('margin-bottom'))

    nextSiblings = element.nextSiblings()
    if (nextSiblings.size() == 0) return this.selectLast()

    selected = false

    nextSiblings.each(
      function(el) {
        if (Position.within(el, offset[0], y))
        {
          matrixHandy[this.windowHandle].select(el)
          matrixHandy[this.windowHandle].scrollIntoView(el, 'down')
          selected = true
        }
      }.bind(this)
    )

    if (!selected) this.selectLast()

  },

  expandSelectionLeft: function(event)
  {
    element = this.element.down('li.selected')
    otherElement = element.previous()
    otherElement.addClassName('selected')
    this.selectedItems.push(otherElement)

    matrixHandy[this.windowHandle].scrollIntoView(element, 'up')

    // If a custom select handler has been defined, call it
    if (this.selectHandler != null)
      this.selectHandler(element)
  },

  expandSelectionRight: function(event)
  {
    element = this.element.getElementsBySelector('li.selected').last()
    otherElement = element.next()
    otherElement.addClassName('selected')
    this.selectedItems.push(otherElement)

    matrixHandy[this.windowHandle].scrollIntoView(element, 'down')

    // If a custom select handler has been defined, call it
    if (this.selectHandler != null)
      this.selectHandler(element)
  },

  expandSelectionUp: function(event)
  {
    event.stop()
    element        = this.element.down('li.selected')
    itemWidth      = element.getWidth()
    itemOffset     = Position.cumulativeOffset(element)
    done = false
    element.previousSiblings().each(
      function(el)
      {
        if (done == false)
        {
          el.addClassName('selected')
          matrixHandy[this.windowHandle].selectedItems.push(el)
        }
        if (Position.within(el, itemOffset[0], itemOffset[1] - element.getHeight()))
        {
          done = true
          matrixHandy[this.windowHandle].scrollIntoView(el, 'up')
        }
      }.bind(this)
    )

    // If a custom select handler has been defined, call it
    if (this.selectHandler != null)
      this.selectHandler(element)
  },

  expandSelectionDown: function(event)
  {
    event.stop()
    element = this.element.getElementsBySelector('li.selected').last()

    offset = Position.cumulativeOffset(element)
    y = Math.floor(offset[1] + element.getHeight() + (element.getHeight() / 2)) + parseInt($(element).getStyle('margin-bottom'))

    done = false
    element.nextSiblings().each(
      function(el)
      {
        if (done == false)
        {
          el.addClassName('selected')
          matrixHandy[this.windowHandle].selectedItems.push(el)
        }
        if (Position.within(el, offset[0], y))
        {
          done = true
          matrixHandy[this.windowHandle].scrollIntoView(el, 'down')
        }
      }.bind(this)
    )
 
    // If a custom select handler has been defined, call it
    if (this.selectHandler != null)
      this.selectHandler(element)
  },

  items: function()
  {
    return this.element.getElementsBySelector('li')
  },

  scrollIntoView: function(element, direction)
  {
    
    if (direction == 'down' || direction == 'right')
    {
      if ((Position.page(element)[1] + element.getHeight()) >= (this.scrollingView.getHeight() + Position.cumulativeOffset(this.scrollingView)[1]))
        this.scrollingView.scrollTop = (Position.cumulativeOffset(element)[1] - this.scrollingView.getHeight() + element.getHeight())
      else if (Position.page(element)[1] <= 0)
        this.scrollingView.scrollTop = (Position.cumulativeOffset(element)[1] - this.scrollingView.getHeight() + element.getHeight())
    }
    else if (direction == 'up' || direction == 'left')
    {
      if ((Position.page(element)[1] + element.getHeight()) >= (this.scrollingView.getHeight() + Position.cumulativeOffset(this.scrollingView)[1]))
        this.scrollingView.scrollTop = (Position.cumulativeOffset(element)[1] - parseInt(element.getStyle('margin-top'))) - 24
      else if (Position.page(element)[1] <= 0)
        this.scrollingView.scrollTop = (Position.cumulativeOffset(element)[1] - parseInt(element.getStyle('margin-top'))) - 24
    }
  }

}




var xMatrixView = Class.create();

    xMatrixView.prototype = Object.extend(new MatrixView(), 
    {
        initialize: function(element,options)
        {
          this.selectedItems   = new Array();
          this.selectMode=1;
          this.windowHandle='MatrixView';
          
          this.xwindow= XTR_main.dhxWins.createWindow("xfilemanager", 20, 10, 1100, 600,1);
          this.xwindow.setModal(false);
          this.xwindow.attachHTMLString(XTR_main.get_tpl('admin', 'matrix',true));
          this.xwindow.hidewin();
          this.xwindow.centerOnScreen();
          this.xwindow.setText(_lang_matrix['file_manager']);
          
          matrixHandy[this.windowHandle]=this;
          /*
            1 - просмотр по клику [default]
            2 - передача  относительного пути файла в контекст input
          */
          
          
       //   this.xwindow.showwin();
          
          
          this.currentView=null;
          
          
       
          
          //this.xwindow.update(XTR_main.get_tpl('admin', 'matrix',true),true);
          this.element         = $(element);
          //matrixHandy[this.windowHandle]    = this;          
          
          this.currentPath=null;
          this.options = Object.extend({
            selectFilePrefix:'/media',
            image_width:100,
            image_height:70,
            connector: null,
            filter:'*',
            mode:'icons',
            callback : null,
            modal : false
            }, options || { });
        
          
          this.initilizeObservers(this.element);
          this.xmview=this.element; 
          this.scrollingView=this.xmview;
          this.pathtofile=$("pathtofile");
     

         
          var settings = {
                post_params: {"PHPSESSID": PHPSESSID},
                flash_url : "/xjs/_components/matrixview/swfupload.swf",
                upload_url: "../../../upload.php",    // Relative to the SWF file
                file_size_limit : "800 MB",
                file_types : "*.*",
                file_types_description : "All Files",
                file_upload_limit : 100,
                file_queue_limit : 100,

                debug:false,

                
                custom_settings : {
                        progressTarget : "fsUploadProgress",
                        cancelButtonId : "btnCancel"
                            },
                // Button settings
                
       

    //    button_image_url : "/xres/ximg/upload.png",
        button_placeholder_id : "spanButtonPlaceholder",
        button_width: 109,
        button_height: 26,
        button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
        button_cursor: SWFUpload.CURSOR.HAND,

        
        swfupload_loaded_handler : swfUploadLoaded,
        file_queued_handler : fileQueued,
        file_queue_error_handler : fileQueueError,
        file_dialog_complete_handler : fileDialogComplete,
        upload_start_handler : uploadStart,
        upload_progress_handler : uploadProgress,
        upload_error_handler : uploadError,
        upload_success_handler : uploadSuccess,
        upload_complete_handler : uploadComplete,
        queue_complete_handler : queueComplete,    // Queue plugin event
        
        // SWFObject settings
        
        swfupload_pre_load_handler : swfUploadPreLoad,
        swfupload_load_failed_handler : swfUploadLoadFailed,
        //debug_handler : debug,

               
               
                // The event handler functions are defined in handlers.js
          /*      file_queued_handler : fileQueued,
                file_dialog_complete_handler: fileDialogComplete,
                upload_start_handler : uploadStart,
                upload_progress_handler : uploadProgress,
                upload_success_handler : uploadSuccess,
                upload_complete_handler : uploadComplete,
                
                custom_settings : {
                    tdFilesQueued : document.getElementById("tdFilesQueued"),
                    tdFilesUploaded : document.getElementById("tdFilesUploaded"),
                    tdErrors : document.getElementById("tdErrors"),
                    tdCurrentSpeed : document.getElementById("tdCurrentSpeed"),
                    tdAverageSpeed : document.getElementById("tdAverageSpeed"),
                    tdMovingAverageSpeed : document.getElementById("tdMovingAverageSpeed"),
                    tdTimeRemaining : document.getElementById("tdTimeRemaining"),
                    tdTimeElapsed : document.getElementById("tdTimeElapsed"),
                    tdPercentUploaded : document.getElementById("tdPercentUploaded"),
                    tdSizeUploaded : document.getElementById("tdSizeUploaded")
                
            };*/
            }
            XSF=this.xSFU = new SWFUpload(settings);
        },
    
    
      buildElementMatrix:function()
      {
      el='<ul class="rcontainer">';            
            if( this.currentView!=null){         
             
                this.currentView.each(function(pair)
                {
                    siz=Object.isUndefined(pair.siz)?'':pair.siz;                    
                    if((this.options.mode=='images')&&(pair.ext!='_dir'))
                    {
                        el+='<li class="fimage" path='+pair.nam+' type='+pair.ext+'><div><img src="/image.php/'+this.full_path(pair.nam)+
                        '?width='+this.options.image_width+'&height='+this.options.image_height+'&image='+this.full_path(pair.nam)+'"></div><div class="name">'+pair.nam+'</div><div class="fm-size">'+siz+'</div><div class="fm-size"><a target="_blank" href="'+this.full_path(pair.nam)+'">'+_lang_common['download_file']+'</a></div></li>';    
                    } else
                    {
                       if(this.options.mode=='images'){_class='class="fimage"';}else{_class="";}
                     el+='<li '+_class+' path='+pair.nam+' type='+pair.ext+'><div class="fm-icon '+pair.ext +'"></div><div class="name">'+pair.nam+'</div><div class="fm-size">'+siz+'</div></li>';    
                    }
                
                }.bind(this));      
 
            }   
                       el+='</ul>';
            this.xmview.update(el);    
            
      },
      
      
      createFolder:function()
      {
      

         var reply = prompt(_lang_matrix['enter_folder_name'], "")
         if(reply.trim().length>0)
         {
             
                this.options.connector.execute({matrixFileManager:{createFolder:{name:translit(reply.trim(),true)}}},false,'incroute'); 
                
                if(this.options.connector.result.folderCreated)
                {
                  this.refresh();
                }else{
                
                  alert(_lang_matrix['cant_create_folder'])
                }
         
         }
         
         
         },

      
       deleteHandler:function()
       {       
         this.unlink();
       },
       
       copy:function()
       {
       
            if((this.selectedItems.length>0))
            {
                a= new Array();
                this.selectedItems.each(function(i)
                {
                      a.push(i.down('div.name').innerHTML);
                });
                this.copyBuffer=a;
                this.copyPath=this.currentPath;
                matrixHandy[this.windowHandle].deselectAll();
            }else{            
                alert(_lang_matrix['select_files_to_copy']);
            }
       },
       
       paste:function()
       {
            if(this.copyBuffer.length>0)
            {
            this.options.connector.execute({matrixFileManager:{copyFiles:{names:this.copyBuffer,path:this.copyPath,currentPath:this.currentPath,prefix:this.options.selectFilePrefix}}},false,'incroute'); 
                
                if(this.options.connector.result.copy)
                {
                    this.refresh();
                }
            
            }
       }
       ,
       
       
       
       unlink:function()
       {
            if((this.selectedItems.length>0)&&(confirm(_lang_common['you_really_wish_to_remove_this_objects'])))
            {
                a= new Array();
                for(i=0;i<this.selectedItems.length;i++)
                {
                      a.push(this.selectedItems[i].down('div.name').innerHTML);
                }
                this.options.connector.execute({matrixFileManager:{unlinkFiles:{names:a}}},false,'incroute'); 
                if(this.options.connector.result.unlink)
                {
                    this.refresh();
                }
            
            }
       },
         
         full_path:function(fname)
         {
            return this.options.selectFilePrefix+this.currentPath+'/'+fname;
         
         }
         ,
    
      openHandler:function(el)
      {
      
      
            
            
            if(el.type=='_dir')
            {
                this.getWalk(this.currentPath+'/'+el.down('div.name').innerHTML);      
            
            }else{
               

               switch (this.selectMode)
                {
                
                case   2:
                    this.inputContext.value=this.full_path(el.down('div.name').innerHTML);
                    this.submit_callback();
                    this.xwindow.hidewin();   
  
                break;                  
                
                case 3:
                    alert(_lang_matrix['this_mode_allows_folders_selected_only']);
                break;
                
                default:
                p='/media/'+this.currentPath+'/'+el.down('div.name').innerHTML;
                if(el.type=='jpg'||el.type=='jpeg'||el.type=='gif'||el.type=='png'){lightbox.start('/image.php/'+p+'?width=800&image='+p,'1');}else{                
                    alert(p);
                }
                }
                
                
            }      
            

      } ,
 
       refresh:function()
       {
           this.getWalk(this.currentPath);
           
       }
       ,
      
     
      selectImage:function(inpContext)
      {
        this.selectMode=2;
        this.inputContext=$(inpContext);         
        this.xwindow.setModal(this.options.modal);
        if(Object.isArray (arguments[1]))
        {        
            this.options.filter=arguments[1];
        }else{
            this.options.filter='*';
            
        }
        
        this.xwindow.showwin();      
        
        
        XTR_main.dhxWins.window("xfilemanager").bringToTop(100);
        
        this.switchmode('images');
        
      
      },
      
      activateButtons:function(but)
      {
          $('bottomArea').childElements().invoke('hide');
          $(but).show();
          $('bottomArea').show();
          
      },
      
      submitMultiply:function()
      {

                             
          $('bottomArea').hide();
          this.xwindow.hidewin();  
          this.submit_callback();
          
          //this.xwindow.setModal(false);          
          
      },
      
      submit_callback:function(){
          if (this.options.callback){
              
              var files=this.selectedItems.map(function(el){return el.readAttribute('path')});
              var xfiles = [];
              var xthis = this;
              files.each(function(file){
                  var xfile = xthis.options.selectFilePrefix + xthis.currentPath + "/"+file;
                  xfiles.push(xfile);
              });
              
              this.options.callback(xfiles);
              this.options.callback=null;
          }
          this.xwindow.setModal(false);
          
          
      },
      
      selectFolder:function(inpContext)
      {
        this.inputContext=$(inpContext);        
        this.selectMode=3;        
        //показывает только папки
        this.xwindow.setModal(this.options.modal);
        this.xwindow.showwin();    
        
        XTR_main.dhxWins.window("xfilemanager").bringToTop();
        if(arguments[1]){
           this.switchmode('folders');
        }
        this.activateButtons('selectFolder');
  
        this.refresh();     
      
      },   
      
      selectMultiplyFile:function(inputContext)
      {
          this.inputContext=inputContext;
          this.selectMode=3;
          this.xwindow.setModal(this.options.modal);
          this.xwindow.showwin();
          XTR_main.dhxWins.window("xfilemanager").bringToTop();
          this.activateButtons('selectMultiply');
          this.refresh();     
          
      },
      
      selectFile:function(inpContext)
      {
        this.inputContext=$(inpContext);
        this.selectMode=2;
        this.xwindow.setModal(this.options.modal);
        if(Object.isArray(arguments[1]))
        {        
            this.options.filter=arguments[1];
        }else{
            this.options.filter='*';
            
        }
        this.xwindow.showwin();  
        XTR_main.dhxWins.window("xfilemanager").bringToTop();    
        this.refresh();     
      
      },
      
      submitPath:function()
      {
            this.inputContext.value=this.full_path('');
            this.xwindow.hidewin(); 
            this.submit_callback();
      
      }
      ,
      start:function()
      {   
            this.selectMode=1;    
            $('bottomArea').hide();  
            this.options.filter='*'; 
            
            this.xwindow.showwin();      
            this.refresh();     

      },
      
      close:function()
      {
           xFileManager.xwindow.hidewin();   
      
      },
      
      getWalk:function(_path)
      {        
          if(_path==null){_path='null';}
          this.options.connector.execute({matrixFileManager:{getWalk:{path:_path,mode:this.options.mode,filter:this.options.filter}}},false,'incroute');
          if(!Object.isUndefined(this.options.connector.result.filesMatrix))this.currentView=this.options.connector.result.filesMatrix;
          this.currentPath=this.options.connector.result.currentPath;          
          this.pathtofile.value=this.currentPath;                    
          this.buildElementMatrix();          
          if(this.currentPath){path=this.currentPath;}else{path='/';}
          setTimeout("XSF.addPostParam('path','"+path+"')",500);
            
          
      
      },
      
      xtractLast:function(data)
      {
           if(!Object.isUndefined(data))
           {
             var m = data.match(/(.*)[\/\\]([^\/\\]+)$/);
             if(m)return {path: m[1], last: m[2]}
           }else{return null;}
      }

      ,                                                   
      Up:function()
      {                                            
           p=this.xtractLast(this.currentPath);      
           if(p!=null)
           {
            this.getWalk(p.path);           
           }                        
      }
      ,
      sleep:function()
      {     //disableSomeEvents            
            $(element).stopObserving(document, 'keydown');
      } ,
      
      
      switchmode:function(v)
      {
         this.options.mode=v;
         selector_select('XMselector',v);         
         this.refresh();
      }
      

    });

