var n = 1;




function start(file,boundary,builder)
{

}

onmessage=function(event)
{
                        
                        
                    var xhr = new XMLHttpRequest();
                        
                        //xhr.upload.file=event.data.file;
                        //xhr.upload.addEventListener("progress", this.options.onUploadProgress.bind(this), false);
                        //xhr.upload.addEventListener("load", this.options.onLoadedXHR.bind(this), false);
                        //xhr.upload.addEventListener("error", this.options.onUploadError.bind(this), false);
                        
                        xhr.open("POST", event.data.uploadPath, true);
                        xhr.setRequestHeader('content-type', 'multipart/form-data; boundary=' + event.data.boundary);
                        xhr.sendAsBinary(event.data.builder);     

                        
    
}