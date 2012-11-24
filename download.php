<?php
  
  function   download_file_as_content($contentasfile,$fileName,$cnt_type='application/octet-stream')  
  {     
          session_write_close();
          header("Cache-Control: ");// leave blank to avoid IE errors
          header("Pragma: ");// leave blank to avoid IE errors
          header("Content-Type: $cnt_type");
          header("Content-Disposition: attachment; filename=\"".$fileName."\"");          
          header("Content-length:".(string)(strlen($contentasfile)));
          print($contentasfile);
          flush();
          exit; 
          
}
 
 session_start();

 if($_SESSION['sendfile']['content'])
 {
    download_file_as_content($_SESSION['sendfile']['content'],$_SESSION['sendfile']['filename'],'application/vnd.ms-excel');
 }
exit;
 ?>