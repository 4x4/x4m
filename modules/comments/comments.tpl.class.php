<?php
class comments_module_tpl extends x3_module
{
        /*  TMS callable
            param[0] -tread_name
            param[1] -cobject id
            param[2] -marker
            param[3] -do not show add comment
        */
        
        function show_comments($params)
        {
            global $TMS;

            
            $TMS->AddMassReplace('_send_comments_form',array('tread'=>$params[0],'Marker'=>$params[2],'CobjectId'=>$params[1]));
            $TMS->parseSection('_send_comments_form',true);         
            if($c=$this->_get_comments(array('tread'=>$params[0],'CobjectId'=>$params[1])))
            {
                $TMS->AddMassReplace('_comments',array('tread'=>$params[0],'CobjectId'=>$params[1]));
                
                if(is_array($c))
                {
                    foreach ($c as $comment)
                    {
                        $TMS->AddMassReplace('_comment',$comment);
                        $TMS->parseSection('_comment',true);
                    }                    
                    $TMS->parseSection('_comments',true);
                
                }elseif($c==1)
                {
                    $TMS->parseSection('_tread_not_active',true);  
                
                }elseif($c==2)
                {
                    $TMS->parseSection('_object_not_active',true);  
                }                
            }
        }
        
        
        
        function get_comments_object($params)
        {
            if($params[0])
            {
                $node=$this->_tree->getNodeInfo($params[0]);

                return $node['params']; 
            }
        }
            
        
        
        function count_comments($params)
        {
            global $TDB;
            if($tread=$this->_common_obj->get_tread_by_name($params[0]))
               {           
                    if($cobj=$this->get_cobject($tread['id'],$params[1]))
                    {
                        
                        if($r=$TDB->get_results('select count(id) as ccount from comments where Active=1 and cid='.$cobj['id']))
                        {                                                                    
                            return $r[1]['ccount'];

                        }else{
                            
                            return 0;
                        }
                    }
               }
        }
        
        
        
        
        
    


}
?>