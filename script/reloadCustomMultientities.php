<?php

require __DIR__ . '/../config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$sql = 'SELECT rowid, label FROM llx_entity ORDER BY rowid ASC';
$res = $db->query($sql);
$TEntities = array();
if($res) {
	while($obj = $db->fetch_object($res)) {
		$TEntities[$obj->rowid] = $obj->label;
	}
}

$oldEntity = $conf->entity;

$object = new ActionsMulticompany($db);
$ret = $object->switchEntity(1);
$conf->setValues($db);
$modulesdir = dolGetModulesDirs();
var_dump($modulesdir);exit;
$const_name = 'MAIN_MODULE_'.strtoupper(preg_replace('/^mod/i', '', get_class($objMod)));