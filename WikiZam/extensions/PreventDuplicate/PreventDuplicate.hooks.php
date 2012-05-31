<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PreventDuplicate
 *
 * @author yannouk
 */
class PreventDuplicateHooks {
	//put your code here
	
	/**
	 * @param Title $title the article (Article object) being saved
	 * @param User $user the user (User object) saving the article
	 * @param string $action the action
	 * @param array $result User permissions error to add. If none, return true. $result can be 
	 * returned as a single error message key (string), or an array of error message keys when 
	 * multiple messages are needed (although it seems to take an array as one message key 
	 * with parameters?)
	 */
	public static function blockCreateDuplicate($title, $user, $action, &$result) {
				
		switch ($action) {
			case 'create':
			case 'edit':
			case 'upload':
			case 'createpage':
			case 'move-target':
				if (!$title->isKnown()) {
					break;
				}
			default:
				return true;
		}
				
		wfDebugLog('preventduplicate', wfGetPrettyBacktrace() ); // return true;
				
		if ( $duplicate = TitleKey::exactMatchTitle($title) ) {
			
			wfDebugLog('preventduplicate', "{$user->getName()} wants to create {$title->getPrefixedDBkey()} but duplicate title {$duplicate->getPrefixedDBkey()} already exists, so creation forbidden");
			
			$result = array ('pvdp-duplicate-exists');
			return false;
			
		}
		
		return true;
		
	}
	
}