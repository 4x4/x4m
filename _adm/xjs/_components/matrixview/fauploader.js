var fauploader = Class.create();
fauploader.prototype =
    {
        percentage:new Array(),
        fcounter:0,
                //m
    initialize: function(source,options)
        {
                  this.options={
                      
                      mode:'input',
                      uploadPath:'upload.php',
                      destination:'',
                      onUploadProgress:this.uploadProgress,
                      onLoadedXHR:this.loadedXHR,
                      onUploadError:this.uploadError,
                      onBoundaryReady:Prototype.emptyFunction

                  }
                  Object.extend(this.options, options || {});                  
                  
                  
                   

   
                  if(this.options.mode!='dropzone')
                  {                     
                      this.input=$(source);                      
                      this.input.observe('change',this.loadFiles.bindAsEventListener(this));
                  }else{                          
                      this.dropzone=$(source);
                  }
                    
        },

        
        uploadProgress:function (event) 
        {
                if (event.lengthComputable) {
                    this.percentage[event.currentTarget.file.FileName] = Math.round((event.loaded * 100) / event.total);
                }
        },
            
            
            loadedXHR:function (event) 
            {
                delete this.percentage[event.currentTarget.file.FileName];
            },
            
            
            
        uploadError:function (error) 
            {
                console.log("error: " + error);
            },
            
            
         loadFiles:function()
         {
              for (var i = 0; i < this.input.files.length; i++) 
              {
                      this.processFile(this.input.files[i]);
              }
         } ,              
            
        
        processFile:function(file) 
            {
                        this.fcounter++;
                        file.id=this.fcounter;
                        boundary='------multipartformboundary' + (new Date).getTime();
                        var dashdash = '--';
                        var crlf     = '\r\n';

                        /* Build RFC2388 string. */
                        var builder = '';
                       
                        builder += dashdash;
                        builder += boundary;
                        builder += crlf;
                        
                        /* Generate inputs headers. */ 
                                    builder += 'Content-Disposition: form-data; name="path"';
                                    builder += crlf;
                                    builder += crlf;
                                    builder += this.options.destination;      
                                                                                            
                                    builder += crlf;
                                    builder += dashdash;
                                    builder += boundary;
                                    builder += crlf;
                            
                            
                            /* Generate file headers. */ 
                            
                            builder += 'Content-Disposition: form-data; name="user_file[]"';
                            if (file.fileName) 
                            {
                                  builder += '; filename="' + file.fileName + '"';
                            }
                            builder += crlf;

                            builder += 'Content-Type: application/octet-stream';
                            builder += crlf;
                            builder += crlf; 
                            builder += file.getAsBinary();
                            builder += crlf;
                            builder += dashdash;
                            builder += boundary;
                            builder += crlf;
                            builder += dashdash;
                            builder += boundary;
                            builder += dashdash;
                            builder += crlf;
                        
                        this.notify('onBoundaryReady',file);
                        
                        
                          var worker = new Worker('/xjs/_components/matrixview/worker.js');                     
                         
                          worker.postMessage({uploadPath:this.options.uploadPath,boundary:boundary,builder:builder,file:file});  
                        
                        /*var xhr = new XMLHttpRequest(),
                        fileUpload = xhr.upload;
                        fileUpload.file=file;
                        fileUpload.addEventListener("progress", this.options.onUploadProgress.bind(this), false);
                        fileUpload.addEventListener("load", this.options.onLoadedXHR.bind(this), false);
                        fileUpload.addEventListener("error", this.options.onUploadError.bind(this), false);
                        xhr.open("POST", this.options.uploadPath, true);
                        xhr.setRequestHeader('content-type', 'multipart/form-data; boundary=' + boundary);
                        xhr.sendAsBinary(builder);  */
        
                        
            },
            
            
            notify: function(event_name)
            {
             if(this.options[event_name])
            return [this.options[event_name].apply(this.options[event_name],$A(arguments).slice(1))];
           }
        
    }