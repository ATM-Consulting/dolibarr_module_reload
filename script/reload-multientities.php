<?php 

	require '../config.php';
	
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	dol_include_once("/multicompany/class/actions_multicompany.class.php");
	
	set_time_limit(0);
	
	//unset($conf->file->dol_document_root['main']);
	$modulesdir = dolGetModulesDirs();
	
    $sql = "SELECT rowid, label FROM llx_entity ORDER BY rowid ASC";
    $res = $db->query($sql);
    $TEntities = array();
    if($res){
        while ($obj = $db->fetch_object($res)) {
            $TEntities[$obj->rowid] = $obj->label;
        }
    }
    
    foreach ($TEntities as $e => $label){
        $object = new ActionsMulticompany($db);
        $ret = $object->switchEntity($e);
        echo '<h2>'.$label.'</h2>';
        
        if(!empty($conf->history->enabled)) {
            unset($conf->history->enabled, $conf->global->MAIN_MODULE_HISTORY);
            $resarray = activateModule('modHistory');
        }
        
        foreach ($modulesdir as $dir)
        {
            // Load modules attributes in arrays (name, numero, orders) from dir directory
            //print $dir."\n<br>";
            dol_syslog("Scan directory ".$dir." for module descriptor files (modXXX.class.php)");
            $handle=@opendir($dir);
            if (is_resource($handle))
            {
                while (($file = readdir($handle))!==false)
                {
                    //print "$i ".$file."\n<br>";
                    if (is_readable($dir.$file) && substr($file, 0, 3) == 'mod'  && substr($file, dol_strlen($file) - 10) == '.class.php')
                    {
                        $modName = substr($file, 0, dol_strlen($file) - 10);
                        
                        $name = strtolower(preg_replace('/^mod/i','',$modName));
                        
                        if(!empty($conf->{$name}->enabled)) {
                            
                            echo $name.'<br>';
                            
                            //$res = unActivateModule($modName);
                            unset($conf->{$name}->enabled, $conf->global->{'MAIN_MODULE_'.strtoupper($name)});
                            $resarray = activateModule($modName);
                            //var_dump($res, $resarray);exit;
                        }
                        
                    }
                    
                }
                
            }
            
        }
    }
    
