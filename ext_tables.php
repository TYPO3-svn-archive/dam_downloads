<?php
if (!defined ("TYPO3_MODE")) 	die ("Access denied.");

$tempColumns = array(
		'tx_damdownloads_category' => Array (
			'label' => 'LLL:EXT:dam/locallang_db.php:tx_dam_item.category',
			'exclude' => '0',
			'config' => $GLOBALS['T3_VAR']['ext']['dam']['TCA']['category_config']
		)
);


t3lib_div::loadTCA('tt_content');
t3lib_extMgm::addTCAcolumns("tt_content",$tempColumns,1);
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key,pages,recursive';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='tx_damdownloads_category;;;;1-1-1, pi_flexform';
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:dam_downloads/flexform_ds.xml');
t3lib_extMgm::addPlugin(Array('LLL:EXT:dam_downloads/locallang_db.php:tt_content.list_type_pi1', $_EXTKEY.'_pi1'));


t3lib_extMgm::addPlugin(Array("LLL:EXT:dam_downloads/locallang_db.php:tt_content.list_type_pi1", $_EXTKEY."_pi1"),"list_type");

if (TYPO3_MODE=='BE')	{
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_damdownloads_pi1_wizicon'] = t3lib_extMgm::extPath($_EXTKEY).'pi1/class.tx_damdownloads_pi1_wizicon.php';
}

?>