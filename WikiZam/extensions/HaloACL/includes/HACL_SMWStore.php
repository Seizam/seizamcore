<?php
/**
 * @file
 * @ingroup HaloACL
 */

/*  Copyright 2010, ontoprise GmbH
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
 * This file contains the class HACLSMWStore.
 * 
 * @author Thomas Schweitzer
 * Date: 06.07.2010
 * 
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This file is part of the HaloACL extension. It is not a valid entry point.\n" );
}

/**
 * This class is a wrapper for all classes that implement access to some
 * semantic store. The actual store is plugged into this wrapper. This class 
 * checks if certain properties and pages can be accessed before they are 
 * passed to the wrapped semantic store or after they are returned from there. 
 */
class HACLSMWStore extends SMWStore {
	
	//--- Private fields ---
	
	// SMWStore: This store is wrapped by an instance of this class
	private $mWrappedStore = null;
	
	// bool: If <true> the protection of semantic properties is activated for 
	//       all functions. Otherwise it is switched off for some functions.
	private $mProtectionActive = true;
	

	//--- Constructor ---
	/**
	 * Constructor of the HaloACL SMW store.
	 * 
	 * @param SMWStore $wrappedStore
	 * 		The store that will be wrapped by this class.
	 */
	public function __construct($wrappedStore) {
		$this->mWrappedStore = $wrappedStore;
	}
	
	//--- Public methods ---
	
	// Setter for member $mProtectionActive. Sets the new value $protectionActive
	// and returns the current value;
	public function setProtectionActive($protectionActive) {
		$pa = $this->mProtectionActive;
		$this->mProtectionActive = $protectionActive;
		return $pa;
	}
	
///// Reading methods /////

	/**
	 * Retrieve all data stored about the given subject and return it as a
	 * SMWSemanticData container. There are no options: it just returns all
	 * available data as shown in the page's Factbox.
	 * $filter is an array of strings that are datatype IDs. If given, the
	 * function will only retreive values for these properties/properties of
	 * this type.
	 *
	 * @note There is no guarantee that the store does not retrieve more data
	 * than requested when a filter is used. Filtering just ensures that
	 * only necessary requests are made, i.e. it improves performance.
	 */
	public function getSemanticData( $subject, $filter = false ) {
		if (!$this->mProtectionActive) {
			return $this->mWrappedStore->getSemanticData($subject, $filter);
		}
		
		$result = new SMWSemanticData(SMWWikiPageValue::makePageFromTitle($subject), false);
		if (!$this->isSubjectAccessible($subject)) {
			// The subject can not be accessed 
			// => return an empty semantic data object
			return $result;
		}
		$semData = $this->mWrappedStore->getSemanticData($subject, $filter);
		
		// Filter the result and remove protected properties or property values
		// that are protected.
		global $wgUser;
		$properties = $semData->getProperties();
		foreach ($properties as $prop) {
			// check if this property is protected
			if (!$this->isPropertyAccessible($prop)) {
				// The property is protected
				continue;
			}
			// Check if the property's value is protected
			$values = $semData->getPropertyValues($prop);
			foreach ($values as $v) {
				$allowed = true;
				if ($v instanceof SMWWikiPageValue) {
					if (is_string($v->getDBKey())) {
						$allowed = $this->userCanAccessTitle($v->getTitle(), 'read');
					}
				}
				if ($allowed) {
					// Property and its value are not protected 
					// => copy to the new result
					$propKey = $prop->getDBkeys();
					if ($v instanceof SMWRecordValue) {
						$result->addPropertyValue($propKey[0], $v);
					} else {
						$valKey = $v->getDBkeys();
						$result->addPropertyStubValue($propKey[0], $valKey);
					}
				}
			}
					
		}
		
		// Result contains no protected data => return the original result
		return $result;
	}

	/**
	 * Get an array of all property values stored for the given subject and property. The result
	 * is an array of SMWDataValue objects. The provided outputformat is a string identifier that
	 * may be used by the datavalues to modify their output behaviour, e.g. when interpreted as a
	 * desired unit to convert the output to.
	 *
	 * If called with $subject == NULL, all values for the given property are returned.
	 */
	public function getPropertyValues( $subject, SMWPropertyValue $property, $requestoptions = null, $outputformat = '' ) {
		if (!$this->mProtectionActive) {
			return $this->mWrappedStore->getPropertyValues($subject, $property, $requestoptions, $outputformat);
		}
		
		if (!$this->isSubjectAccessible($subject) || !$this->isPropertyAccessible($property)) {
			return array();
		}
		
		$values = $this->mWrappedStore->getPropertyValues($subject, $property, $requestoptions, $outputformat);
		foreach ($values as $k => $v) {
			if ($v instanceof SMWWikiPageValue) {
				$allowed = $this->userCanAccessTitle($v->getTitle(), 'read');
				if (!$allowed) {
					// The property's value is protected
					// => remove this result
					unset($values[$k]);
				}
			}
		}
		
		$values = array_merge($values);
		return $values;
	}

	/**
	 * Get an array of all subjects that have the given value for the given property. The
	 * result is an array of SMWWikiPageValue objects. If NULL is given as a value, all subjects having
	 * that property are returned.
	 */
	public function getPropertySubjects( SMWPropertyValue $property, $value, $requestoptions = null ) {
		if (!$this->isPropertyAccessible($property)) {
			return array();
		}
		
		$subjects = $this->mWrappedStore->getPropertySubjects($property, $value, $requestoptions);
		
		// Filter the subjects that are protected
		foreach ($subjects as $k => $s) {
			$t = $s->getTitle();
			if (!$this->userCanAccessTitle($t, 'read')) {
				unset($subjects[$k]);
			}
		}
		$subjects = array_merge($subjects);
		return $subjects;
	}

	/**
	 * Get an array of all subjects that have some value for the given property. The
	 * result is an array of SMWWikiPageValue objects.
	 */
	public function getAllPropertySubjects( SMWPropertyValue $property, $requestoptions = null ) {
		return $this->getPropertySubjects($property, null, $requestoptions);
	}

	/**
	 * Get an array of all properties for which the given subject has some value. The result is an
	 * array of SMWPropertyValue objects.
	 * @param $subject Title or SMWWikiPageValue denoting the subject
	 * @param $requestoptions SMWRequestOptions optionally defining further options
	 */
	public function getProperties( $subject, $requestoptions = null ) {
		if (!$this->isSubjectAccessible($subject)) {
			return array();
		}
		$properties = $this->mWrappedStore->getProperties($subject, $requestoptions);
		
		return $this->filterProtectedProperties($properties);
		
	}

	/**
	 * Get an array of all properties for which there is some subject that relates to the given value.
	 * The result is an array of SMWWikiPageValue objects.
	 * @note In some stores, this function might be implemented partially so that only values of type Page
	 * (_wpg) are supported.
	 */
	public function getInProperties( SMWDataValue $object, $requestoptions = null ) {
		if ($object instanceof SMWWikiPageValue && !$this->isSubjectAccessible($object)) {
			return array();
		}
		$properties = $this->mWrappedStore->getInProperties($object, $requestoptions);
		
		return $this->filterProtectedProperties($properties);
	}

///// Writing methods /////

	/**
	 * Delete all semantic properties that the given subject has. This
	 * includes relations, attributes, and special properties. This does not
	 * delete the respective text from the wiki, but only clears the stored
	 * data.
	 */
	public function deleteSubject( Title $subject ) {
		return $this->mWrappedStore->deleteSubject($subject);
	}

	/**
	 * Update the semantic data stored for some individual. The data is given
	 * as a SMWSemanticData object, which contains all semantic data for one particular
	 * subject.
	 */
	public function updateData( SMWSemanticData $data ) {
		return $this->mWrappedStore->updateData($data);
	}

	/**
	 * Clear all semantic data specified for some page.
	 */
	function clearData( Title $subject ) {
		return $this->mWrappedStore->clearData($subject);
	}

	/**
	 * Update the store to reflect a renaming of some article. Normally this happens when moving
	 * pages in the wiki, and in this case there is also a new redirect page generated at the
	 * old position. The title objects given are only used to specify the name of the title before
	 * and after the move -- do not use their IDs for anything! The ID of the moved page is given in
	 * $pageid, and the ID of the newly created redirect, if any, is given by $redirid. If no new
	 * page was created, $redirid will be 0.
	 */
	public function changeTitle( Title $oldtitle, Title $newtitle, $pageid, $redirid = 0 ) {
		return $this->mWrappedStore->changeTitle($oldtitle, $newtitle, $pageid, $redirid);
	}

///// Query answering /////

	/**
	 * Execute the provided query and return the result as an SMWQueryResult if the query
	 * was a usual instance retrieval query. In the case that the query asked for a plain
	 * string (querymode MODE_COUNT or MODE_DEBUG) a plain wiki and HTML-compatible string
	 * is returned.
	 */
	public function getQueryResult( SMWQuery $query ) {
		
		wfRunHooks('RewriteQuery', array(&$query, &$queryEmpty) );
		if (!$queryEmpty) {
			$result = $this->mWrappedStore->getQueryResult($query);
			if ($result instanceof SMWQueryResult) {
				wfRunHooks('FilterQueryResults', array(&$result) );
			}
	
			if ($query->querymode == SMWQuery::MODE_COUNT) {
				if ($result instanceof SMWQueryResult) {
					$result = $result->getCount();
				}
			}
		} else {
			$result = new SMWQueryResult( array(), $query, array(), $this);
		}
		
		return $result;
		
	}

///// Special page functions /////

	/**
	 * Return all properties that have been used on pages in the wiki. The result is an array
	 * of arrays, each containing a property title and a count. The expected order is
	 * alphabetical w.r.t. to property title texts.
	 */
	public function getPropertiesSpecial( $requestoptions = null ) {
		$propUsage = $this->mWrappedStore->getPropertiesSpecial($requestoptions);
    	foreach ($propUsage as $k => $propAndCount) {
    		$prop = $propAndCount[0];
			if (!$this->isPropertyAccessible($prop)) {
				// The property is protected
				unset($propUsage[$k]);
			}
    	}
		
		$propUsage = array_merge($propUsage);
    	return $propUsage;
	}

	/**
	 * Return all properties that have been declared in the wiki but that
	 * are not used on any page. Stores might restrict here to those properties
	 * that have been given a type if they have no efficient means of accessing
	 * the set of all pages in the property namespace.
	 */
	public function getUnusedPropertiesSpecial( $requestoptions = null ) {
		return $this->mWrappedStore->getUnusedPropertiesSpecial($requestoptions);
	}

	/**
	 * Return all properties that are used on some page but that do not have any
	 * page describing them. Stores that have no efficient way of accessing the
	 * set of all existing pages can extend this list to all properties that are
	 * used but do not have a type assigned to them.
	 */
	public function getWantedPropertiesSpecial( $requestoptions = null ) {
		return $this->mWrappedStore->getWantedPropertiesSpecial($requestoptions);
	}

	/**
	 * Return statistical information as an associative array with the following
	 * keys:
	 * - 'PROPUSES': Number of property instances (value assignments) in the datatbase
	 * - 'USEDPROPS': Number of properties that are used with at least one value
	 * - 'DECLPROPS': Number of properties that have been declared (i.e. assigned a type)
	 */
	public function getStatistics() {
		return $this->mWrappedStore->getStatistics();
	}

///// Setup store /////

	/**
	 * Setup all storage structures properly for using the store. This function performs tasks like
	 * creation of database tables. It is called upon installation as well as on upgrade: hence it
	 * must be able to upgrade existing storage structures if needed. It should return "true" if
	 * successful and return a meaningful string error message otherwise.
	 *
	 * The parameter $verbose determines whether the procedure is allowed to report on its progress.
	 * This is doen by just using print and possibly ob_flush/flush. This is also relevant for preventing
	 * timeouts during long operations. All output must be valid XHTML, but should preferrably be plain
	 * text, possibly with some linebreaks and weak markup.
	 */
	public function setup( $verbose = true ) {
		return $this->mWrappedStore->setup($verbose);
	}

	/**
	 * Drop (delete) all storage structures created by setup(). This will delete all semantic data and
	 * possibly leave the wiki uninitialised.
	 */
	public function drop( $verbose = true ) {
		return $this->mWrappedStore->drop($verbose);
	}

	/**
	 * Refresh some objects in the store, addressed by numerical ids. The meaning of the ids is
	 * private to the store, and does not need to reflect the use of IDs elsewhere (e.g. page ids).
	 * The store is to refresh $count objects starting from the given $index. Typically, updates
	 * are achieved by generating update jobs. After the operation, $index is set to the next
	 * index that should be used for continuing refreshing, or to -1 for signaling that no objects
	 * of higher index require refresh. The method returns a decimal number between 0 and 1 to
	 * indicate the overall progress of the refreshing (e.g. 0.7 if 70% of all objects were refreshed).
	 *
	 * The optional parameter $namespaces may contain an array of namespace constants. If given,
	 * only objects from those namespaces will be refreshed. The default value FALSE disables this feature.
	 *
	 * The optional parameter $usejobs indicates whether updates should be processed later using
	 * MediaWiki jobs, instead of doing all updates immediately. The default is TRUE.
	 */
	public function refreshData( &$index, $count, $namespaces = false, $usejobs = true ) {
		return $this->mWrappedStore->refreshData($index, $count, $namespaces, $usejobs);
	}

	/**
	 * This function does the same as getSMWPageID() but takes into account
	 * that properties might be predefined.
	 * 
	 * WARNING: This is not an official method of the interface SMWStore but
	 * is is called as public method from other classes. This should be fixed 
	 * where only this interface is expected.
	 */
	public function getSMWPropertyID( SMWPropertyValue $property ) {
		return $this->mWrappedStore->getSMWPropertyID($property);
	}
	
	/**
	 * Initializes the store.
	 * @param bool $verbose
	 * 
	 * WARNING: This is not an official method of the interface SMWStore but
	 * is is called as public method from other classes. This should be fixed 
	 * where only this interface is expected.
	 */
	public function initialize($verbose = true) {
		return $this->mWrappedStore->initialize($verbose);
	}
	
    public function getSMWPageID($title, $namespace, $iw, $canonical=true) {
        return $this->mWrappedStore->getSMWPageID($title, $namespace, $iw, $canonical);
    }

    public function cacheSMWPageID($id, $title, $namespace, $iw) {
        return $this->mWrappedStore->cacheSMWPageID($id, $title, $namespace, $iw);
    }


    public function getSMWPageIDandSort( $title, $namespace, $iw, &$sort, $canonical ) {
        return $this->mWrappedStore->getSMWPageIDandSort($title, $namespace, $iw, $sort, $canonical);
    }
	
	/**
	 * Checks if a subject for properties is accessible.
	 * @param Title/SMWWikiPageValue $subject
	 * 		The subject to check
	 * @return bool
	 * 		<true> if the subject is accessible
	 * 		<false> if not.
	 */	
	private function isSubjectAccessible($subject) {
		if ($subject == null) {
			return true;
		}
		if ($subject instanceof SMWWikiPageValue) {
			$subject = $subject->getTitle();
		} else if (!($subject instanceof Title)) {
			// wrong parameter
			return false;
		}
		return $this->userCanAccessTitle($subject, 'read');
		
	}
	
	/**
	 * Checks if a property is accessible.
	 * @param Title/SMWPropertyValue $subject
	 * 		The subject to check
	 * @return bool
	 * 		<true> if the subject is accessible
	 * 		<false> if not.
	 */	
	private function isPropertyAccessible($property) {
		if ($property == null) {
			return true;
		}
		if ($property instanceof SMWPropertyValue) {
			$property = $property->getWikiPageValue();
			if (!$property) {
				return true;
			}
			$property = $property->getTitle();
			
		} else if (!($property instanceof Title)) {
			// wrong parameter
			return false;
		}
		$id = $property->getArticleID();
		global $wgUser;
		return HACLEvaluator::hasPropertyRight($id,	$wgUser->getId(), HACLRight::READ);
		
	}
	
	
	/**
	 * Checks if the current user can access the given title.
	 * 
	 * @param Title $title
	 * 		Title object for an article
	 * @param string $action
	 * 		The kind of access to the article
	 * @return bool
	 * 		<true> if the article can be accessed and 
	 * 		<false> otherwise
	 */
	private function userCanAccessTitle($title, $action) {
		global $wgUser;
		$result = true;
		wfRunHooks('userCan', array($title, $wgUser, $action, &$result));
		if (isset($result) && $result == false) {
			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * Filters all protected properties from the given array of properties.
	 * @param array<SMWPropertyValue> $properties
	 * 		This array may contain protected properties. Those are removed.
	 * @return array<SMWPropertyValue>
	 * 		An array without protected properties
	 */
	private function filterProtectedProperties(array $properties) { 
		// Filter all protected properties
		foreach ($properties as $k => $prop) {
			// check if this property is protected
			if (!$this->isPropertyAccessible($prop)) {
				// The property is protected
				unset($properties[$k]);
			}
		}
		$properties = array_merge($properties);
		return $properties;		
	}

///// Abstact methods of SMWStore /////

   	/**
	 * @see SMWStore::doDataUpdate
	 *
	 * @param SMWSemanticData $data
	 */
    public function doDataUpdate(SMWSemanticData $data) {
        parent::doDataUpdate($data);
    }
}
