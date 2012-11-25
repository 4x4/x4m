<?php

/**
* !Внимание для корректной работы папка /cache/templates  - должна быть достпна для записи
* Порядок подключения шаблонов главный шаблон->шаблон домена->обычный шаблон либо 
* главный шаблон->шаблон домена для языковой версии -> обычный шаблон
* _index.html->/mydomain.com/_index.html/mycustomtemplate.html
* языковой вариант:
* _index.html->/mydomain.ru/_index@ru.html/mycustomtemplate@ru.html
* 
*/

class templatesCommon extends xModuleCommon implements xCommonInterface
{
    var $registeredFields;
    var $changedMainTpls;
    private $nonChangedTpls;
    private $mainTemlateChanged;
    function templatesCommon()
    {
        parent::__construct(__CLASS__);
        Common::loadDriver('xCache','xCacheFileDriver'); 
    }
    function defineFrontActions()
    {
    }
    /**
     * записать данные о шаблоне
     * 
     * @param mixed $tplPath
     * @param mixed $data array(time=>time,slotz=>('name'=>'alias'))
     */
    function setTplData($tplPath, $data)
    {
        
        xCacheFileDriver::serializedWrite($data, $this->_moduleName, $tplPath,false);
    }
    
    function getTplData($tplPath)
    {
        return xCacheFileDriver::serializedRead($this->_moduleName, $tplPath,false);
    }
    
    function getTpl($tpl,$domain)
    {
        return xCacheFileDriver::serializedRead($this->_moduleName, $domain.'/'.$tpl,false);
    }
    
    function getSlotzForDomain($domain,$lang='')
    {
        if ($allTemplates = XFILES::files_list(xConfig::get('PATH' ,'COMMON_TEMPLATES').$domain, 'files', array('.html'), true))
        {
            
        }
    }
    
    /**
    * Индексация шаблонов у которых прозошли изменения
    * 
    * @param mixed $startdir
    */
    function indexChangedMainTpls($startdir)
    {
        if ($allTemplates = XFILES::files_list($startdir, 'all', array('.html'), true))
        {
            foreach ($allTemplates as $file)
            {
                if (strstr($file, '.html'))
                {
                    $fmtime   = filemtime($file);
                    $template = str_replace($startdir . '/', '', $file);

                    $tplData  = $this->getTplData($template);
                    $tParts   = explode('/', $template);
                    preg_match("/(.+)@(.+)\.html/", $tParts[1], $tplExp);
                    
                    if (strstr($tParts[1], '_index'))
                    {
                        $isMain = true;
                    }
                    else
                    {
                        $isMain = false;
                    }
                    
                    if ($tParts[0] == '_index.html')
                    {
                        $this->mainTemlateChanged = true;
                    }
                    
                    if (($fmtime > $tplData['lastModified']) or !$tplData)
                    {
                        $changedMainTpls[$tParts[0]][] = array(
                            'tpl' => $tParts[1],
                            'main' => $isMain,
                            'lang' => $tplExp[2]
                        );
                    }
                    else
                    {
                        $this->nonChangedTpls[$tParts[0]][] = array(
                            'tpl' => $tParts[1],
                            'main' => $isMain,
                            'lang' => $tplExp[2]
                        );
                    }
                }
            }
            if ($changedMainTpls)
            {
                foreach ($changedMainTpls as $domain => $tpls)
                {
                    foreach ($tpls as $tpl)
                    {
                        if ($tpl['main'])
                        {
                            if ($this->nonChangedTpls[$domain])
                            {
                                foreach ($this->nonChangedTpls[$domain] as $itpl)
                                {
                                    if (!$itpl['main'] && ($itpl['lang'] == $tpl['lang']))
                                    {
                                        $changedMainTpls[$domain][] = $itpl;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            unset($changedMainTpls['_index.html']);            
            return $changedMainTpls;
        }
    }
    
    function refreshMainTpls()
    {
        if ($this->changedMainTpls = $this->indexChangedMainTpls(xConfig::get('PATH' ,'COMMON_TEMPLATES')))
        {
            if ($this->mainTemlateChanged)
            {
                $this->processMainTemplate();
                return;
            }
            
            
            foreach ($this->changedMainTpls as $tplDomain => $tpl)
            {
                $this->processTemplate($tplDomain, $tpl);
            }
        }
    }
    /**
     * переиндексация всех шаблонов в случае изменения глобального
     */
    function processMainTemplate()
    {
        
        
        foreach ($this->changedMainTpls as $tplDomain => $tpl)
        {
            $this->processTemplate($tplDomain, $tpl);
        }
        
        if ($this->nonChangedTpls)
        {
            foreach ($this->nonChangedTpls as $tplDomain => $tpl)
            {
                $this->processTemplate($tplDomain, $tpl);
            }
        }
    }
    
    /**
    *  переиндексация шаблонов согласно домену
    */
    
    function processTemplate($tplDomain, $tpls)
    {
        foreach ($tpls as $tpl)
        {
            $TMS         = new tMultiSection();
            

                $tplFullPath = xConfig::get('PATH' ,'COMMON_TEMPLATES') . $tplDomain . '/' . $tpl['tpl'];
                //слоты шаблона
                $name        = $TMS->AddFileSection($tplFullPath);

            //если не относиться к главным шаблонам                
            if (!$tpl['main'])
            {
                if ($tpl['lang'])
                {
                    $lang = '@' . $tpl['lang'];
                }
                $tplMainForDomain = xConfig::get('PATH' ,'COMMON_TEMPLATES') . $tplDomain . '/_index' . $lang . '.html';
                $TMS->AddFileSection($tplMainForDomain);
            }
            //Глобальный кроссдоменный шаблон
            $TMS->AddFileSection(xConfig::get('PATH' ,'COMMON_TEMPLATES') . '_index.html');            
            $registeredFields = $TMS->MainFields;
            $tplData          = array(
                'lastModified' => time(),
                'slotz' => $TMS->MainFields,
                'name' => $name,
                'lang' => $tpl['lang'],
                'path' => $tplDomain . '/' . $tpl['tpl']
            );
                    
            $this->setTplData($tplDomain . '/' . $tpl['tpl'], $tplData);
        }
    }
}
?>
