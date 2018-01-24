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

    $object = new ActionsMulticompany($db);
    $ret = $object->switchEntity(1);
    $conf->setValues($db);
//    foreach ($TEntities as $e => $label){
//        $object = new ActionsMulticompany($db);
//        $ret = $object->switchEntity($e);

//	$conf->entity = $e;

//        echo '<h2>'.$label.'</h2>';
        
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

			   foreach ($TEntities as $e => $label){
			    $conf->entity = $e;
                            unset($conf->{$name}->enabled, $conf->global->{'MAIN_MODULE_'.strtoupper($name)});
                            $resarray = activateModule($modName);
                            //var_dump($res, $resarray);exit;

		           }
                        }
                        
                    }
                    
                }
                
            }
            
        }
  //  }
    
  
    $object = new ActionsMulticompany($db);
    $ret = $object->switchEntity(1);
    $conf->setValues($db);

// Recopie des conf de l'entité 1 sur les autres entités
$sql = 'INSERT IGNORE INTO llx_const (name, entity, value, type, visible, note) ';
$sql.= 'SELECT c.name, e.rowid, c.value, c.type, c.visible, c.note ';
$sql.= 'FROM llx_const c, llx_entity e ';
$sql.= 'WHERE c.entity = 1 ';
$sql.= 'AND e.rowid != 1 ';
$db->query($sql);

// Recopie des droits groupes de l'entité 1 sur les autres
$sql = 'INSERT INTO llx_usergroup_rights (entity, fk_usergroup, fk_id) ';
$sql.= 'SELECT e.rowid, ur.fk_usergroup, ur.fk_id ';
$sql.= 'FROM llx_usergroup_rights ur, llx_entity e ';
$sql.= 'WHERE ur.entity = 1 ';
$sql.= 'AND e.rowid != 1 ';
$db->query($sql);

// Recopie des droits user de l'entité 1 sur les autres
$sql = 'INSERT INTO llx_user_rights (entity, fk_user, fk_id) ';
$sql.= 'SELECT e.rowid, ur.fk_user, ur.fk_id ';
$sql.= 'FROM llx_user_rights ur, llx_entity e ';
$sql.= 'WHERE ur.entity = 1 ';
$sql.= 'AND e.rowid != 1 ';
$db->query($sql);
