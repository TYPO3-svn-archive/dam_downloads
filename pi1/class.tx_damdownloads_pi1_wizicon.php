<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2002-2004 Kasper Skårhøj (kasper@typo3.com)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is 
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
* 
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
* 
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/





/**
 * Class that adds the wizard icon.
 * 
 * @author
 */
class tx_damdownloads_pi1_wizicon {

	/**
	 * Adds the projectmanager wizard icon
	 * 
	 * @param	array		Input array with wizard items for plugins
	 * @return	array		Modified input array, having the item for dam_downloads added.
	 */
	function proc($wizardItems)	{
		global $LANG;

		$LL = $this->includeLocalLang();

		$wizardItems['plugins_tx_damdownloads_pi1'] = array(
			'icon'=>t3lib_extMgm::extRelPath('dam_downloads').'pi1/ce_wiz.gif',
			'title'=>$LANG->getLLL('pi_title',$LL),
			'description'=>$LANG->getLLL('pi_plus_wiz_description',$LL),
			'params'=>'&defVals[tt_content][CType]=list&defVals[tt_content][list_type]=dam_downloads_pi1'
		);

		return $wizardItems;
	}

	/**
	 * Includes the locallang file for the 'dam_downloads' extension
	 * 
	 * @return	array		The LOCAL_LANG array
	 */
	function includeLocalLang()	{
		include(t3lib_extMgm::extPath('dam_downloads').'locallang_db.php');
		return $LOCAL_LANG;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dam_downloads/pi1/class.tx_damdownloads_pi1_wizicon.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dam_downloads/pi1/class.tx_damdownloads_pi1_wizicon.php']);
}

?>