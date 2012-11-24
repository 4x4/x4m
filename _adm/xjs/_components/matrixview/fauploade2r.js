var fauploader = Class.create();

                 
                   


fauploader.prototype =
    {
    //m
    initialize: function(source,options)
        {
                  this.options={};        
                  this.options.mode='input';
                  this.options.resultsList='resultsList';
                  Object.extend(this.options, options || {});                  
                  
                  if(this.options.mode!='dropzone')
                  {                     
                      this.input=$(source);                      
                      this.input.observe('change',this.loadFiles.bindAsEventListener(this));
                  }else{                          
                      this.dropzone=$(source);
                  }
                    
                    this.options.resultsList=$(this.options.resultsList);
                    
                    ul = new Element('ul');
                    this.options.resultsList.appendChild(ul);this.options.resultsList=ul;
                    
                    this.binaryDataReader = new FileReader();      
                         
                    //getBinaryDataReader.addEventListener("loadend", function(evt){xhr.sendAsBinary(evt.target.result);}, false);
                    //getBinaryDataReader.readAsBinaryString(file);         

            
        },

        
        uploadProgress:function (event) 
        {
                if (event.lengthComputable) {
                    var percentage = Math.round((event.loaded * 100) / event.total);
                 
                 console.log('perc '+percentage);
                        loaderIndicator = event.target.container.appendChild(new Element('a').update(percentage));
                    //if (percentage < 100) {
                      //  loaderIndicator.style.width = (percentage*2) + "px";
//                        loaderIndicator.textContent = percentage + "%";
  //                  }
                }
            },
            
            
            loadedXHR:function (event) 
            {
                
                console.log("xhr upload of "+event.target.log+" complete");
                 
                 if (this.responseText) {
                                alert(this.responseText);
                                }

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
                        
                        boundary='------multipartformboundary' + (new Date).getTime();
                        var dashdash = '--';
                        var crlf     = '\r\n';

                        /* Build RFC2388 string. */
                        var builder = '';
                       
                        
                       
                        builder += dashdash;
                        builder += boundary;
                        builder += crlf;
                        
                            /* Generate headers. */            
                            builder += 'Content-Disposition: form-data; name="user_file[]"';
                            if (file.fileName) 
                            {
                                  builder += '; filename="' + file.fileName + '"';
                            }
                            builder += crlf;

                            builder += 'Content-Type: application/octet-stream';
                            builder += crlf;
                            builder += crlf; 
                            /* Append binary data. */
                            builder += file.getAsBinary();
                            builder += crlf;
                            /* Write boundary. */
                            builder += dashdash;
                            builder += boundary;
                            builder += crlf;
                        
                        
                        /* Mark end of the request. */
                        builder += dashdash;
                        builder += this.boundary;
                        builder += dashdash;
                        builder += crlf;
                        
                        bli=new Element('li').update(file.fileName)
                        this.options.resultsList.appendChild(bli);
                        
                        var xhr = new XMLHttpRequest(),
                        fileUpload = xhr.upload;
                        fileUpload.log=file.fileName;
                        fileUpload.container=bli;
                        fileUpload.addEventListener("progress", this.uploadProgress, false);
                        fileUpload.addEventListener("load", this.loadedXHR, false);
                        fileUpload.addEventListener("error", this.uploadError, false);

                        xhr.open("POST", "upload2.php", true);
                        
                        xhr.setRequestHeader('content-type', 'multipart/form-data; boundary=' + boundary);
                        xhr.sendAsBinary(builder);  
        
                        
            },
            
            
        
                  
        
        
        
        
        
        
    }