<?php

	require __DIR__ . '/../config.php';

	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';


	// Security check
	if (empty($user->admin)){
		die('Administrator only');
	}


	// Add un activate option
	$unActivate = GETPOST('unactivate','int');

	// Manage dependency
	$dependency = 1;
	if(GETPOSTISSET('dependency')){
		$dependency = GETPOST('dependency','int');
	}


	unset($conf->file->dol_document_root['main']);
	$modulesdir = dolGetModulesDirs();

	if(!empty($conf->history->enabled)) {
                unset($conf->history->enabled, $conf->global->MAIN_MODULE_HISTORY);
                $resarray = activateModule('modHistory');
        }


	$TModuleToReload = array();

	foreach ($modulesdir as $dir)
	{
		// Load modules attributes in arrays (name, numero, orders) from dir directory
		dol_syslog("Scan directory ".$dir." for module descriptor files (modXXX.class.php)");
		$handle=@opendir($dir);
		if (is_resource($handle))
		{
			while (($file = readdir($handle))!==false)
			{
				//print "$i ".$file."\n<br>";
				if (is_readable($dir.$file) && substr($file, 0, 3) == 'mod'  && substr($file, dol_strlen($file) - 10) == '.class.php')
				{

					$modToReload = new stdClass();
					$modToReload->modName  = substr($file, 0, dol_strlen($file) - 10);
					$modToReload->name  = strtolower(preg_replace('/^mod/i','',$modToReload->modName));
					$modToReload->enabled  = !empty($conf->{$modToReload->name}->enabled);

					$TModuleToReload[$modToReload->name] = $modToReload->name;
				}
			}
		}
	}



	if(!empty($TModuleToReload)) {

		// premiere étape désactivation des modules
		if(!empty($unActivate)) {
			echo '<h6>Disable : '.'</h6>';
			foreach ($TModuleToReload as $modToReload) {
				if (!empty($modToReload->enabled)) {
					echo $modToReload->name . '<br>';
					$res = unActivateModule($modToReload->modName, $dependency);
					unset($conf->{$modToReload->name}->enabled, $conf->global->{'MAIN_MODULE_' . strtoupper($modToReload->name)});
				}
			}
		}

		echo '<h6>Activate : '.'</h6>';
		// deuxime etape activation de tous les modules necessaire
		foreach ($TModuleToReload as $modToReload)
		{
			if(!empty($modToReload->enabled)) {
				echo $modToReload->name.'<br>';
				$resarray = activateModule($modToReload->modName);
			}
		}
	}

