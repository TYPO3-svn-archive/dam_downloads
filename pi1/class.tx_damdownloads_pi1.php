<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2002  (dt@dpool.net)
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
 * Plugin 'DAM Downloads' for the 'dam_downloads' extension.
 *
 * @author	 Daniel Thomas <dt@dpool.net>
 */


require_once(PATH_tslib.'class.tslib_pibase.php');

class tx_damdownloads_pi1 extends tslib_pibase {
	var $prefixId = 'tx_damdownloads_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_damdownloads_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey = 'dam_downloads';				// The extension key.
	var $table = 'tx_dam';
	var $table_cat = 'tx_dam_cat';
	var $table_mm_cat = 'tx_dam_mm_cat';

	function main($content,$conf)	{
		$this->conf = $conf;					// Setting the TypoScript passed to this function in $this->conf
		$this->pi_setPiVarDefaults();			// GP-parameter configuration
		$this->pi_loadLL();						// Loading the LOCAL_LANG values
		$this->pi_initPIflexForm();				// Init FlexForms array

		$this->ff = $this->cObj->data['pi_flexform'];
		
		
		#### find category to display: can either be set in Plugin or by piVars['cat']
		$cat = intval($this->piVars['cat']);
		$this->cat = $cat ? $cat : $this->cObj->data['tx_damdownloads_category'];
		$this->isSearch = $this->pi_getFFvalue($this->ff,'searchform','searchDef') ? true : false;
		$this->noCatSearch =  $this->pi_getFFvalue($this->ff,'nocategory','searchDef') ? true : false;
		#### if there is no category isSearch should be true because nothing is displayed otherwise
		if(!$this->cat) {
			$this->isSearch = true;
		}

		
		#### main template file
		$this->template = $this->cObj->fileResource($this->conf['template']);
		
		
		### if we want to save a uid to downloadlist ###
		$saveUid = intval($this->piVars['save']);
		if($saveUid) {
			$this->saveUid($saveUid);
		}

		
		### if we want to save a uid to downloadlist ###
		$deleteUid = intval($this->piVars['delete']);
		if($deleteUid) {
			$this->deleteUid($deleteUid);
		}

		
		#### downloadlist or standard list
		$type = $this->pi_getFFvalue($this->ff,'type','general');
		if($this->piVars['downloadlist'] || $type == 'COLLECT') {
			$this->downloadlist = true;
			$this->tmp = 'collectView.';
			$this->uidList = $GLOBALS['TSFE']->fe_user->getKey('ses','tx_damdownloads_pi1_collect');
		}
		else {
			$this->downloadlist = false;
			$this->tmp = 'listView.';
		}
		

		#### configuration
		$this->conf['listView.']['lWidth'] = $this->pi_getFFvalue($this->ff,'thumbwidth','listDef')
												? $this->pi_getFFvalue($this->ff,'thumbwidth','listDef')
												: $this->conf['listView.']['image.']['file.']['width'];
												
		$this->conf['collectView.']['lWidth'] = $this->pi_getFFvalue($this->ff,'thumbwidth','collectDef')
												? $this->pi_getFFvalue($this->ff,'thumbwidth','collectDef')
												: $this->conf['collectView.']['image.']['file.']['width'];
		
		$this->conf['listView.']['cNum'] = $this->pi_getFFvalue($this->ff,'columns','listDef')
											? $this->pi_getFFvalue($this->ff,'columns','listDef')
											: $this->conf['listView.']['cols'];
											
		$this->conf['collectView.']['cNum'] = $this->pi_getFFvalue($this->ff,'columns','collectDef')
												? $this->pi_getFFvalue($this->ff,'columns','collectDef')
												: $this->conf['collectView.']['cols'];
		
		$this->conf['listView.']['results_at_a_time'] = $this->pi_getFFvalue($this->ff,'items','listDef')
														? $this->pi_getFFvalue($this->ff,'items','listDef')
														: $this->conf['listView.']['results_at_a_time'];
														
		$this->conf['collectView.']['results_at_a_time'] = $this->pi_getFFvalue($this->ff,'items','collectDef')
															? $this->pi_getFFvalue($this->ff,'items','collectDef')
															: $this->conf['collectView.']['results_at_a_time'];
		
		$this->internal['rewind'] = $this->conf['listView.']['rewind'];
		$this->internal['cue'] = $this->conf['listView.']['cue'];
		$this->internal['currentTable'] = $this->table;
		
		$this->conf['dWidth'] = $this->pi_getFFvalue($this->ff,'detailwidth','detailDef')
								? $this->pi_getFFvalue($this->ff,'detailwidth','detailDef')
								: $this->conf['singleView.']['image.']['file.']['width'];


		#### query parameters
		$this->conf['pidList'] = $this->conf['pid'];	// needed by queries in pibase
		$this->mmcat = array(
			'table' => $this->table_cat,
			'mmtable' => $this->table_mm_cat,
			'catUidList' => $this->cat
		);
		$this->internal['orderByList'] = $this->conf[$this->tmp]['orderBy'] ? $this->conf[$this->tmp]['orderBy'] : 'ORDER BY title';
		$this->internal['results_at_a_time'] = $this->conf[$this->tmp]['results_at_a_time'];
		$this->internal['maxPages'] = $this->conf[$this->tmp]['maxPages'];

		
		$this->where = $this->getWhere();
		
		
		#### display the searchform if no detail and isSearch true
		if($this->isSearch && !$this->piVars['showUid']) {
			$content = $this->displaySearch($this->conf['searchView.']);
			if($this->piVars['swords']) {
				$this->searchWhere();
			}
		}
		
		
		#### if there is a UID to be shown
		if ($this->piVars['showUid'])	{
			$this->internal['currentRow'] = $this->pi_getRecord($this->table,$this->piVars['showUid']);
			$content.= $this->singleView($this->conf['singleView.']);
		}
		elseif($this->downloadlist) { #### elseif we display the downloadlist
			$content.= $this->showDownloadList();
		}
		else { #### else we display the list
			$content.= $this->listView('listView.');
		}

		return $this->pi_wrapInBaseClass($content);
	}
	

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function saveUid($uid) {
		$list = $GLOBALS['TSFE']->fe_user->getKey('ses','tx_damdownloads_pi1_collect');
		if(is_array($list)) {
			$list[] = $uid;
		}
		else {
			$list = array($uid);
		}
		$GLOBALS['TSFE']->fe_user->setKey('ses','tx_damdownloads_pi1_collect',array_unique($list));
	}
	

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function deleteUid($uid) {
		$list = $GLOBALS['TSFE']->fe_user->getKey('ses','tx_damdownloads_pi1_collect');
		if(is_array($list)) {
			$index = array_search($uid,$list);
			unset($list[$index]);
		}
		$GLOBALS['TSFE']->fe_user->setKey('ses','tx_damdownloads_pi1_collect',array_unique($list));
	}
	

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function showDownloadList() {
		$content = $this->listView('collectView.');
		return $content;
	}
	

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getZIPlink() {
		$array = array();
		
		$url = 'typo3conf/ext/dam_downloads/zipit.php?filename='.$this->conf['download.']['filename'];
		$url.= '&directoryName='.$this->conf['download.']['directoryName'];
		
		$url.= '&files='.$this->getFileNames();
		
		$array[] = '<a href="'.$url.'" target="_blank">';
		$array[] = '</a>';
		
		return $array;
	}
	

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getFileNames() {
		$items = array();
		
		if(is_array($this->uidList)) { $where = 'AND uid IN ('.implode(',',$this->uidList).')'; }
		else { $where = 'AND 1=2'; }
		$res = $this->pi_exec_query($this->table,0,$where);
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$items[] = $row['file_path'].$row['file_name'];
		}
		
		return urlencode(implode(',',$items));
	}
	

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function listView($type = 'listView.')	{
		$lConf = $this->conf[$type];
		
		if($this->downloadlist) {
			if(is_array($this->uidList)) {
				$where = 'AND uid IN ('.implode(',',$this->uidList).')';
			}
			else {
				$where = 'AND 1=2';
			}
			### count query
			$count = $this->pi_exec_query($this->table,1,$where);
			$temp = $GLOBALS['TYPO3_DB']->sql_fetch_row($count);
			$this->internal['res_count'] = $temp[0];
			#### display query
			$res = $this->pi_exec_query($this->table,0,$where,'','',$this->internal['orderByList']);
		}
		else {
			### count query
			$count = $this->pi_exec_query($this->table,1,$this->where,$this->mmcat);
			$temp = $GLOBALS['TYPO3_DB']->sql_fetch_row($count);
			$this->internal['res_count'] = $temp[0];
			#### display query
			$res = $this->pi_exec_query($this->table,0,$this->where,$this->mmcat,'',$this->internal['orderByList']);
		}

		#### define content
		$marker['###BROWSELINKS###'] = $this->pi_list_browsebar();
		$marker['###BROWSERESULTS###'] = $this->pi_list_browseresults();
		$marker['###ITEMS###'] = $this->listViewList($res, $type);
		$wrappedSubparts['###DOWNLOAD_ZIP###'] = $this->getZIPlink();
		
		
		#### transfer content
		$template = $this->cObj->getSubpart($this->template,'###'.$lConf['templatePrefix'].'_MAIN###');
		$content = $this->cObj->substituteMarkerArrayCached($template,$marker,array(),$wrappedSubparts);

		return $content;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$res: ...
	 * @return	[type]		...
	 */
	function listViewList($res, $type)	{

		$lConf = $this->conf[$type];
		$items = array();
		$template = $this->cObj->getSubpart($this->template,'###'.$lConf['templatePrefix'].'_ROW###');

		#### Make list table rows
		while($this->internal["currentRow"] = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$items[] = $this->singleView($lConf);
		}


		#### build rows for display
		reset($items);
		for($x = 0; $x < (count($items)/$lConf["cNum"]); $x++) {
			$markerArray['###ITEMS###'] = '';
			for($i = 0; $i < $lConf["cNum"]; $i++) {
				$markerArray['###ITEMS###'].= current($items);
				next($items);
			}
			$rows.= $this->cObj->substituteMarkerArray($template, $markerArray);
		}

		return $rows;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$lConf: ...
	 * @return	[type]		...
	 */
	function singleView($lConf)	{
		$content = '';

		if($this->isImg()) {
			$template = $this->cObj->getSubpart($this->template,'###'.$lConf['templatePrefix'].'_ITEM###');
		}
		else {
			$template = $this->cObj->getSubpart($this->template,'###'.$lConf['templatePrefix'].'_ITEM_NOIMG###');
		}

		$markerArray = $this->getMarkerArray($lConf);
		$wrappedSubparts = $this->getWrappedSubparts($lConf);

		$content = $this->cObj->substituteMarkerArrayCached($template,$markerArray,array(),$wrappedSubparts);

		return $content;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$lConf: ...
	 * @return	[type]		...
	 */
	function displaySearch($lConf)	{
		$content = '';
		
		$template = $this->cObj->getSubpart($this->template,'###SEARCH###');
		$overrule = $this->piVars;
		$overrule['pointer'] = '';	// pointer screws up resulting page
		$markerArray['###FORM_URL###'] = $this->pi_linkTP_keepPIvars_url($overrule);
		$markerArray['###SWORDS###'] = $this->piVars['swords'];
		$markerArray['###SUBMITLABEL###'] = $this->pi_getLL('submit','submit');
		$markerArray['###OPTIONS###'] = $this->getCategoryOptions();
			
		if($this->noCatSearch) {
			$subpart['###CATEGORIES###'] = '';
		}
		
		$content = $this->cObj->substituteMarkerArrayCached($template,$markerArray,$subpart);

		return $content;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getCategoryOptions()	{
		$content = '<option value="">' . $this->pi_getLL('getCategoryOptions_header') . '</option>';

		$where = '1 = 1 ' . $this->cObj->enableFields($this->table_cat);

		if($this->cObj->data['tx_damdownloads_category']){
			$where .= ' AND ' . $this->table_cat . '.parent_id = ' . $this->cObj->data['tx_damdownloads_category'];
		}
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			$this->table_cat . '.title,' . $this->table_cat . '.uid',
			$this->table_cat,
			$where
		);
			
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$content .= '<option value="' . $row['uid'] . '"' . ($this->piVars['cat'] == $row['uid'] ? ' selected="selected"' : '') . '>' . $row['title'] . '</option>';
		}

		return $content;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$lConf: ...
	 * @return	[type]		...
	 */
	function getMarkerArray($lConf)	{
		$row = $this->internal['currentRow'];
		$markerArray = array();

		$markerArray['###DOC###'] = $this->getDOC($lConf);

		#### textinfo ####
		$markerArray['###TITLE###'] = $this->cObj->stdWrap($row['title'],$lConf['std.']['title.']);
		$markerArray['###DESCRIPTION###'] = $this->cObj->stdWrap($row['description'],$lConf['std.']['description.']);
		$markerArray['###ABSTRACT###'] = $this->cObj->stdWrap($row['abstract'],$lConf['std.']['abstract.']);
		$markerArray['###SEARCH_CONTENT###'] = $this->cObj->stdWrap($row['search_content'],$lConf['std.']['search_content.']);
		$markerArray['###LANGUAGE###'] = $this->cObj->stdWrap($row['language'],$lConf['std.']['language.']);
		$markerArray['###COPYRIGHT###'] = $this->cObj->stdWrap($row['copyright'],$lConf['std.']['copyright.']);
		$markerArray['###FILE_NAME###'] = $this->cObj->stdWrap($row['file_name'],$lConf['std.']['file_name.']);
		$markerArray['###FILE_PATH###'] = $this->cObj->stdWrap($row['file_path'],$lConf['std.']['file_path.']);
		$markerArray['###FILE_SIZE###'] = $this->cObj->stdWrap($row['file_size'],$lConf['std.']['file_size.']);
		$markerArray['###FILE_TYPE###'] = $this->cObj->stdWrap($row['file_type'],$lConf['std.']['file_type.']);
		$markerArray['###FILE_DL_NAME###'] = $this->cObj->stdWrap($row['file_dl_name'],$lConf['std.']['file_dl_name.']);
		$markerArray['###CREATOR###'] = $this->cObj->stdWrap($row['creator'],$lConf['std.']['creator.']);
		$markerArray['###HPIXELS###'] = $this->cObj->stdWrap($row['hpixels'],$lConf['std.']['hpixels.']);
		$markerArray['###VPIXELS###'] = $this->cObj->stdWrap($row['vpixels'],$lConf['std.']['vpixels.']);


		#### dateinfo ####
		$markerArray['###CRDATE###'] = $this->cObj->stdWrap($row['crdate'],$lConf['std.']['crdate.']);
		$markerArray['###TSTAMP###'] = $this->cObj->stdWrap($row['tstamp'],$lConf['std.']['tstamp.']);
		$markerArray['###FILE_CTIME###'] = $this->cObj->stdWrap($row['file_ctime'],$lConf['std.']['file_ctime.']);


		#### categories ####
		$markerArray['###CATEGORIES###'] = $this->getCategories($lConf);

		return $markerArray;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$lConf: ...
	 * @return	[type]		...
	 */
	function getCategories($lConf) {
		$uid = $this->internal['currentRow']['uid'];
		$from = $this->table_cat.','.$this->table_mm_cat;
		$where = $this->table_cat.'.uid = '.$this->table_mm_cat.'.uid_foreign AND '.$this->table_mm_cat.'.uid_local = '.$uid.$this->cObj->enableFields($this->table_cat);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($this->table_cat.'.title,'.$this->table_cat.'.uid,'.$this->table_cat.'.parent_id',$from,$where);

		$cat = array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			#### check whether to display the whole path or just the category title ####
			if($lConf['std.']['pathCategory']) {
				$temp = $this->pathCategory($row['uid']);
			}
			else {
				$temp = $row['title'];
			}

			#### check whether to link the category path or title to the display of that category ####
			if($lConf['std.']['linkCategory']) {
				$temp = $this->linkCategory($temp,$row['uid']);
			}
			else {
				$temp = $row['title'];
			}

			#### wrap the whole thing ####
			$categories.= $this->cObj->stdWrap($temp,$lConf['std.']['category.']);
		}

		return $this->cObj->stdWrap($categories,$lConf['std.']['categories.']);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$uid: ...
	 * @return	[type]		...
	 */
	function pathCategory($uid) {
		return '';
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$str: ...
	 * @param	[type]		$uid: ...
	 * @return	[type]		...
	 */
	function linkCategory($str,$uid) {
		$params['cat'] = $uid;
		return $this->pi_linkTP($str,array($this->prefixId=>$params),1);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$str: ...
	 * @param	[type]		$uid: ...
	 * @return	[type]		...
	 */
	function getWhere() {
		$where = array();
		if($this->conf['restrict']) {
			$fileTypes = t3lib_div::trimExplode(',',$this->conf['restrict.']['fileTypes']);
			while(list(,$v) = each($fileTypes)) {
				$where[] = $this->table.".file_type = '".$v."'";
			}
		}
		if(count($where)) {
			$out = 'AND (('.implode(') OR (',$where).'))';
		}
		return $out;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$lConf: ...
	 * @return	[type]		...
	 */
	function getWrappedSubparts($lConf) {
		$wrappedSubparts = array();
		$row = $this->internal['currentRow'];

		unset($this->piVars['save']);
		unset($this->piVars['delete']);
		
		#### provides a link to the original file
		$wrappedSubparts['###LINK_DL_FILE###'] = array(
			'<a href="'.$row['file_path'].urlencode($row['file_name']).'" target="_blank">',
			'</a>'
		);
		
		
		#### provides a link to the detail of the file
		$overrule['showUid'] = $row['uid'];
		$wrappedSubparts['###LINK_DETAIL###'] = array(
			'<a href="'.$this->pi_linkTP_keepPIvars_url($overrule,1).'" target="_self">',
			'</a>'
		);
		unset($overrule['showUid']);
		
		#### provides a link to save this file for download list
		$overrule['save'] = $row['uid'];
		$wrappedSubparts['###LINK_SAVE###'] = array(
			'<a href="'.$this->pi_linkTP_keepPIvars_url($overrule).'" target="_self">',
			'</a>'
		);
		unset($overrule['save']);
		
		
		#### provides a link to delete this file from download list
		$overrule['delete'] = $row['uid'];
		$wrappedSubparts['###LINK_DELETE###'] = array(
			'<a href="'.$this->pi_linkTP_keepPIvars_url($overrule).'" target="_self">',
			'</a>'
		);
		unset($overrule['delete']);
		

		if($lConf['showPrevNext']) {
			$items = array();
			$query = $this->pi_list_query($this->table,0,'',$this->mmcat);
			$res = mysql(TYPO3_db,$query);
			while($temp = mysql_fetch_assoc($res)) {
				$items[] = $temp['uid'];
			}
			$count = count($items);
			$index = array_search($row['uid'],$items);
			if($index == 0) {
				$prev = $items[($count-1)];
				$next = $items[($index+1)];
			}
			elseif($index <= ($count-2)) {
				$prev = $items[($index-1)];
				$next = $items[($index+1)];
			}
			else {
				$prev = $items[($index-1)];
				$next = $items[0];
			}

			#### provides a link to the next image
			if($next) {
				$overrule['showUid'] = $next;
				$wrappedSubparts['###LINK_NEXT###'] = array(
					'<a href="'.$this->pi_linkTP_keepPIvars_url($overrule,1).'" target="_self">',
					'</a>'
				);
			}
			#### provides a link to the previous image
			if($prev) {
				$overrule['showUid'] = $prev;
				$wrappedSubparts['###LINK_PREV###'] = array(
					'<a href="'.$this->pi_linkTP_keepPIvars_url($overrule,1).'" target="_self">',
					'</a>'
			)	;
			}
		}

		if($lConf['showItemNumbers']) {
			$wrappedSubparts['###ITEM_NUMBERS###'] = array(
				($index+1),
				$count
			);
		}

		#### provides a link back to the gallery
		$this->piVars = array();
		$wrappedSubparts['###LINK_GALLERY###'] = array(
			'<a href="'.$this->pi_linkTP_keepPIvars_url().'" target="_self">',
			'</a>'
		);

		return $wrappedSubparts;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$lConf: ...
	 * @return	[type]		...
	 */
	function getDOC($lConf) {
		if($this->isImg()) {
			$img = $this->getImage($lConf);
		}
		if(!$img) {
			$img = $this->conf[$lConf['imageType']]['noImg'];
			if($this->conf[$lConf['imageType']][$this->internal['currentRow']['file_type']]) {
				$img = $this->conf[$lConf['imageType']][$this->internal['currentRow']['file_type']];
			}
		}

		return $img;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$lConf: ...
	 * @return	[type]		...
	 */
	function getImage($lConf) {

		$TSconf = $lConf['image.'];
		$TSconf['file.']['width'] = $this->piVars['showUid'] ? $this->conf['dWidth"'] : $this->conf[$this->tmp]['lWidth'];

		$TSconf['file'] = $this->internal['currentRow']['file_path'].$this->internal['currentRow']['file_name'];

		$img = $this->cObj->IMAGE($TSconf);

		return $img;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function isImg() {
		$imgExt = t3lib_div::trimExplode(',',$this->conf['imgTypes']);
		if(in_array($this->internal['currentRow']['file_type'],$imgExt)) {
			return true;
		}
		return false;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function searchWhere() {
		$this->where = $this->cObj->searchWhere($this->piVars['swords'],$this->conf['searchFieldList'],$this->table);
	}

	/***************************
	 *
	 * Functions for listing, browsing, searching etc.
	 *
	 **************************/

	/**
	 * Returns a results browser. This means a bar of page numbers plus a "previous" and "next" link. For each entry in the bar the piVars "pointer" will be pointing to the "result page" to show.
	 * Using $this->piVars['pointer'] as pointer to the page to display
	 * Using $this->internal['res_count'], $this->internal['results_at_a_time'] and $this->internal['maxPages'] for count number, how many results to show and the max number of pages to include in the browse bar.
	 *
	 * @param	boolean		If set (default) the text "Displaying results..." will be show, otherwise not.
	 * @param	string		Attributes for the table tag which is wrapped around the table cells containing the browse links
	 * @return	string		Output HTML, wrapped in <div>-tags with a class attribute
	 */
	function pi_list_browsebar($showResultCount=1,$tableParams='')	{

			// Initializing variables:
		$pointer = $this->piVars['pointer'];
		$count = $this->internal['res_count'];
		$results_at_a_time = t3lib_div::intInRange($this->internal['results_at_a_time'],1,1000);
		$maxPages = t3lib_div::intInRange($this->internal['maxPages'],1,100);
		$max = t3lib_div::intInRange(ceil($count/$results_at_a_time),1,$maxPages);
		$pointer = intval($pointer);
		$links = array();

		if($this->internal['rewind'] && $pointer > 1) {
				$links[] = $this->cObj->stdWrap($this->pi_linkTP_keepPIvars($this->conf['browsebar.']['start.']['btn'],array('pointer'=>''),0),$this->conf['browsebar.']['start.']['std.']);
		}

			// Make browse-table/links:
		if ($pointer > 0)	{
			$links[] = $this->cObj->stdWrap(
							$this->pi_linkTP_keepPIvars(
									$this->conf['browsebar.']['back.']['btn'],
									array('pointer'=>($pointer-1?$pointer-1:'')),
									0),
							$this->conf['browsebar.']['back.']['std.']
						);
		}
		$first = $this->cObj->stdWrap(implode('',$links),$this->conf['browsebar.']['first.']);

		$max = $pointer+$max;
		$p = $pointer;
		$links = array();
			
			//
		if(($max-1)*$results_at_a_time > $count) {
			$max = intval($count/$results_at_a_time)+1;
			$p = $max - $maxPages;
		}
		
		for($a = $p; $a < $max; $a++)	{
			if(($a < 0) || ($a*$results_at_a_time >= $count)) {
				
			}
			elseif($pointer == $a) {
				$links[] =  $this->cObj->stdWrap(
								$this->conf['browsebar.']['page.']['btn'].($a+1),
								$this->conf['browsebar.']['page.']['stdAct.']
							);
			}
			else {
				$links[] = $this->cObj->stdWrap(
								$this->pi_linkTP_keepPIvars(
										$this->conf['browsebar.']['page.']['btn'].($a+1),
										array('pointer'=>($a ? $a : '')),
										0),
								$this->conf['browsebar.']['page.']['std.']
							);
			}
		}
		$second = $this->cObj->stdWrap(implode('',$links),$this->conf['browsebar.']['second.']);


		$links = array();
		if ($pointer < ceil($count/$results_at_a_time) - 1)	{
			$links[] = $this->cObj->stdWrap(
							$this->pi_linkTP_keepPIvars(
									$this->conf['browsebar.']['next.']['btn'],
									array('pointer'=>$pointer+1),
									0),
							$this->conf['browsebar.']['next.']['std.']
						);
		}

		if($this->internal['cue'] && ($pointer<ceil($count/$results_at_a_time)-2)) {
				$links[] = $this->cObj->stdWrap(
								$this->pi_linkTP_keepPIvars(
										$this->conf['browsebar.']['end.']['btn'],
										array('pointer'=>intval($count/$results_at_a_time)),
										0),
								$this->conf['browsebar.']['end.']['std.']
							);
		}
		$third = $this->cObj->stdWrap(implode('',$links),$this->conf['browsebar.']['third.']);

		$out = $this->cObj->stdWrap($first.$second.$third,$this->conf['browsebar.']['whole.']);

		if(!$this->internal['res_count']) {
			$out = '';
		}

		return $out;
	}

	/***************************
	 *
	 * Functions for listing, browsing, searching etc.
	 *
	 **************************/

	/**
	 * Returns a results browser. This means a bar of page numbers plus a "previous" and "next" link. For each entry in the bar the piVars "pointer" will be pointing to the "result page" to show.
	 * Using $this->piVars['pointer'] as pointer to the page to display
	 * Using $this->internal['res_count'], $this->internal['results_at_a_time'] and $this->internal['maxPages'] for count number, how many results to show and the max number of pages to include in the browse bar.
	 *
	 * @param	boolean		If set (default) the text "Displaying results..." will be show, otherwise not.
	 * @param	string		Attributes for the table tag which is wrapped around the table cells containing the browse links
	 * @return	string		Output HTML, wrapped in <div>-tags with a class attribute
	 */
	function pi_list_browseresults($showResultCount=1,$tableParams='')	{

		$pointer=$this->piVars['pointer'];
		$results_at_a_time = t3lib_div::intInRange($this->internal['results_at_a_time'],1,1000);
		$pointer=intval($pointer);

		$pR1 = $pointer * $results_at_a_time + 1;
		$pR2 = $pointer * $results_at_a_time + $results_at_a_time;

		if($this->internal['res_count']) {
    			$out = sprintf(
					$this->pi_getLL('browseresults'),
					$this->internal['res_count'] > 0 ? $pR1 : 0,
					min(array($this->internal['res_count'],$pR2)),
					$this->internal['res_count']
				);
		}
		else {
			$out = $this->pi_getLL('noEntries');
		}

		return $out;
	}
}


if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/dam_downloads/pi1/class.tx_damdownloads_pi1.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/dam_downloads/pi1/class.tx_damdownloads_pi1.php"]);
}

?>