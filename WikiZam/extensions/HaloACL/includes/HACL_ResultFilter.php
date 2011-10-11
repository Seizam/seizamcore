<?php
/**
 * @file
 * @ingroup HaloACL
 */

/*  Copyright 2009, ontoprise GmbH
*  This file is part of the HaloACL-Extension.
*
*   The HaloACL-Extension is free software; you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation; either version 3 of the License, or
*   (at your option) any later version.
*
*   The HaloACL-Extension is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * This file contains a filter for query results. Informations about protected pages
 * that would appear as result of a query are filtered.
 * 
 * @author Thomas Schweitzer
 * Date: 16.06.2009
 * 
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This file is part of the HaloACL extension. It is not a valid entry point.\n" );
}

 //--- Includes ---
 global $haclgIP;
//require_once("$haclgIP/...");

/**
 * This class filters protected pages from a query result.
 * 
 * @author Thomas Schweitzer
 * 
 */
class  HACLResultFilter  {
	
	//--- Constants ---
		
	//--- Private fields ---
	// The result filter can be temporarily disabled to avoid infinite recursions
	private static $mDisabled = false;
	
	/**
	 * Constructor for  HACLResultFilter
	 *
	 * @param type $param
	 * 		Name of the notification
	 */		
	function __construct() {
	}
	

	//--- getter/setter ---
	
	public static function setDisabled($disabled) { 
		$d = self::$mDisabled;
		self::$mDisabled = $disabled; 
		return $d;
	}
	
	//--- Public methods ---
	
	
	/**
	 * This callback function for the parser hook "FilterQueryResults" removes
	 * all protected pages from a query result.
	 *
	 * @param SMWQueryResult $qr
	 * 		The query result that is modified
	 * @param array<string> $properties
	 * 		A query result may contain values for properties. The names of the
	 * 		variables that contain property names are given in this array.
	 * 		If a row of the result contains a protected property then the complete
	 * 		row is removed. This applies only to SPARQL queries.
	 * 
	 */
	public static function filterResult(SMWQueryResult &$qr, array $properties = null) {
		if (self::$mDisabled) {
			return true;
		}
		
		if ($qr instanceof SMWHaloQueryResult) {
			self::filterSPARQLQueryResult($qr, $properties);
			return true;
		}
		// Retrieve all subjects of a query result
		$results = $qr->getResults();
		$valuesRemoved = false;
		
		global $wgUser;
		
		// Filter all subjects that are protected
		foreach ($results as $k => $r) {
			$t = $r->getTitle();
			unset($allowed);
			wfRunHooks('userCan', array(&$t, &$wgUser, "read", &$allowed));
			if (isset($allowed) && $allowed === false) {
				unset($results[$k]);
				$valuesRemoved = true;
			}
		}
		if ($valuesRemoved) {
			// Some subject were removed => create a new query result.
			$qr = $qr->newFromQueryResult($results);
			$qr->addErrors(array(wfMsgForContent('hacl_sp_results_removed')));
		}

		return true;
	}
	
	//--- Private methods ---
	
	/**
	 * This function removes all protected pages from a SPARQL query result. 
	 * These results don't have a subject. They are just two dimensional tables.
	 * 
	 * In normal query results (for ASK), a subject for each row is given. If
	 * this subject is protected, the complete row can be removed as it reveals
	 * some content of the subject. However, this is not the case for SPARQL
	 * query results. No subject is available, only variable bindings with no
	 * further meaning. Consequently, rows can only be removed if they are completely
	 * empty i.e. contain only protected values or if they contain a protected
	 * property. 
	 *
	 * @param SMWHaloQueryResult $qr
	 * 		The query result that is modified
	 * @param array<string> $properties
	 * 		A query result may contain values for properties. The names of the
	 * 		variables that contain property names are given in this array.
	 * 		If a row of the result contains a protected property then the complete
	 * 		row is removed. This applies only to SPARQL queries.
	 */
	public static function filterSPARQLQueryResult(SMWHaloQueryResult &$qr, array $properties = null) {
		global $wgUser;
		$results = $qr->getFullResults();
		$valuesRemoved = false;
		
		foreach ($results as $kr => $row) {
			$allCellsRemoved = true;
			$deleteRow = false;
			foreach ($row as $cell) {
				// Iterate over all results in a cell
				$pr = $cell->getPrintRequest();
				$isProperty = false;
				if (is_array($properties) 
				    && in_array(strtolower($pr->getLabel()), $properties)) {
					// The cell contains a property name. 
					// => Check if the property is accessible.
					$isProperty = true;
				}
				$items = $cell->getContent();
				$cellModified = false;
				foreach ($items as $k => $item) {
					if ($item instanceof SMWWikiPageValue) {
						$t = $item->getTitle();
						if ($isProperty) {
							$allowed = true;
							wfRunHooks('userCan', array(&$t, &$wgUser, "propertyread", &$allowed));
							if (!$allowed) {
								// The property is protected
								// => remove all cells
								$deleteRow = true;
								break;
							}
						}
						unset($allowed);
						wfRunHooks('userCan', array(&$t, &$wgUser, "read", &$allowed));
						
						if (isset($allowed) && $allowed === false) {
							unset($items[$k]);
							$valuesRemoved = true;
							$cellModified = true;
						} else {
							$allCellsRemoved = false;
						}
					} else {
						$allCellsRemoved = false;
					}
				}
				if ($cellModified) {
					$cell->setContent($items);
				}
				if ($deleteRow) {
					// A row is deleted because it contains protected properties.
					break;
				}
			}
			if ($allCellsRemoved || $deleteRow) {
				// All cells in a row were removed
				// => Remove the complete row from the result.
				unset($results[$kr]);	
			} else {
				reset($row);
			}
		}
		reset($results);
		
		$qr->setResults($results);
		
		if ($valuesRemoved) {
			// Some subject were removed => create a new query result.
			$qr->addErrors(array(wfMsgForContent('hacl_sp_results_removed')));
		}

		return true;
		
	}
}
