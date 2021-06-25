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

						if(!empty($unActivate)){
							$res = unActivateModule($modName, $dependency);
						}
						unset($conf->{$name}->enabled, $conf->global->{'MAIN_MODULE_'.strtoupper($name)});
						$resarray = activateModule($modName);
						//var_dump($res, $resarray);exit;
					}

				}

			}

		}

	}
