<?php
require __DIR__ . '/../config.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';

function displayError($message) {
    print '<div style="color: red; font-weight: bold;">ERROR: ' . $message . '</div><br>';
}

function displaySuccess($message) {
    print '<div style="color: green;">SUCCESS: ' . $message . '</div><br>';
}

function displayInfo($message) {
    print '<div style="color: blue;">INFO: ' . $message . '</div><br>';
}

// Récupérer les entités
$entities = [];
$sql = 'SELECT rowid, label FROM '.MAIN_DB_PREFIX.'entity WHERE active = 1 AND visible = 1 ORDER BY rowid ASC';
$res = $db->query($sql);
if ($res) {
    while ($obj = $db->fetch_object($res)) {
        $entities[$obj->rowid] = $obj->label;
    }
}

if (!empty($entities)) {
    $oldEntity = $conf->entity;
    $actionsMulticompany = new ActionsMulticompany($db);
    $modulesDirs = dolGetModulesDirs();

    // Filtrer les répertoires des modules pour ne garder que ceux dans '/custom/'
    $customModulesDirs = array_filter($modulesDirs, function ($value) {
        return strpos($value, '/custom/') !== false;
    });

    if (!empty($customModulesDirs)) {
        $db->begin();
        $error = 0;

        foreach ($entities as $fkEntity => $entityLabel) {
            print '<div style="margin-bottom: 20px; border: 1px solid #ccc; padding: 10px;">';
            print '--- START ENTITY ' . $fkEntity . ' :' . $entityLabel . '<br>';

            // Changer l'entité
            $ret = $actionsMulticompany->switchEntity($fkEntity);
            if ($ret > 0) {
                $enabledModules = $actionsMulticompany->dao->getEntityConfig($fkEntity);

                foreach ($customModulesDirs as $dir) {

                    //url param "modules" to specify list of modules to reload. Each module must be separate by "|"
                    $modulestoreload = GETPOST('modules', 'alphanohtml');
                    if (!empty($modulestoreload)) {
                        if(!preg_match('#/custom/('.$modulestoreload.')#', $dir)) continue;
                    }

                    print '<div style="margin-left: 20px; border: 1px solid #eee; padding: 5px;">';
                    print '--- START MODULES ' . $dir . '<br>';

                    if ($handle = opendir($dir)) {
                        while (($file = readdir($handle)) !== false) {
                            if (is_readable($dir . $file) && substr($file, 0, 3) == 'mod' && substr($file, -10) == '.class.php') {
                                $modName = substr($file, 0, -10);

                                if ($modName) {
                                    try {
                                        $classPath = $dir . $file;
                                        require_once $classPath;

                                        if (class_exists($modName)) {
                                            $objMod = new $modName($db);
                                            $constName = 'MAIN_MODULE_' . strtoupper(preg_replace('/^mod/i', '', get_class($objMod)));

                                            if (!isset($enabledModules[$constName])) {
                                                displayInfo('---- DISABLED MODULE  '.$constName.'--- GO TO NEXT');
                                                continue;
                                            }

                                            // Désactivation
                                            $ret = $objMod->remove();
                                            if ($ret <= 0) {
                                                $error++;
                                                displayError('REMOVE FAIL ' . $constName . ' : ' . $objMod->errorsToString());
                                            } else {
                                                displaySuccess('REMOVE SUCCESS ' . $constName);
                                            }

                                            // Réactivation
                                            $ret = $objMod->init();
                                            if ($ret <= 0) {
                                                $error++;
                                                displayError('INIT FAIL ' . $constName . ' : ' . $objMod->errorsToString());
                                            } else {
                                                displaySuccess('INIT SUCCESS ' . $constName);
                                            }
                                        } else {
                                            $error++;
                                            displayError('Warning bad descriptor file : ' . $classPath . ' (Class ' . $modName . ' not found into file)');
                                        }
                                    } catch (Exception $e) {
                                        $error++;
                                        displayError('Failed to load ' . $classPath . ' ' . $e->getMessage());
                                    }
                                }
                            }
                        }
                        closedir($handle);
                    } else {
                        $error++;
                        displayError('Failed to open directory ' . $dir . '. See permission and open_basedir option.');
                    }
                    print '--- END MODULES ' . $dir . '<br>';
                    print '</div>'; // module
                }
            } else {
                $error++;
                displayError('Error during switching entity');
            }
            print '--- END ENTITY ' . $fkEntity . ' :' . $entityLabel . '<br>';
            print '</div>'; // entity
        }

        // Revenir à l'entité d'origine
        $actionsMulticompany->switchEntity($oldEntity);

        // Gestion des erreurs
        if ($error > 0) {
            displayError('Errors found, rollback everything');
            $db->rollback();
        } else {
            displaySuccess('Reload success');
            $db->commit();
        }
    }
} else {
    displayError('No entities found');
}
?>
