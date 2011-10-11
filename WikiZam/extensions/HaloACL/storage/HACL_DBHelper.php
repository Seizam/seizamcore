<?php
/**
 * @file
 * @ingroup HaloACL_Storage
 */

/*  Copyright 2009, ontoprise GmbH
*   This file is part of the HaloACL-Extension.
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
* 
*   Created on 19.10.2007
*
*   Author: kai
*/



class HACLDBHelper {


    /**
     * Make sure the table of the given name has the given fields, provided
     * as an array with entries fieldname => typeparams. typeparams should be
     * in a normalised form and order to match to existing values.
     *
     * The function returns an array that includes all columns that have been
     * changed. For each such column, the array contains an entry
     * columnname => action, where action is one of 'up', 'new', or 'del'
     * If the table was already fine or was created completely anew, an empty
     * array is returned (assuming that both cases require no action).
     *
     * NOTE: the function partly ignores the order in which fields are set up.
     * Only if the type of some field changes will its order be adjusted explicitly.
     *
     * @param string $primaryKeys
     *      This optional string specifies the primary keys if there is more
     *      than one. This is a comma separated list of column names. The primary
     *      keys are not altered, if the table already exists.
     */
    public static function setupTable($table, $fields, $db, $verbose, $primaryKeys = "") {
        global $wgDBname;
        HACLDBHelper::reportProgress("Setting up table $table ...\n",$verbose);
        if ($db->tableExists($table) === false) { // create new table
            $sql = 'CREATE TABLE ' . $wgDBname . '.' . $table . ' (';
            $first = true;
            foreach ($fields as $name => $type) {
                if ($first) {
                    $first = false;
                } else {
                    $sql .= ',';
                }
                $sql .= $name . '  ' . $type;
            }
            if (!empty($primaryKeys)) {
                $sql .= ", PRIMARY KEY(".$primaryKeys.")";
            }
            $sql .= ') ENGINE=MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin';
            $db->query( $sql, 'HACLDBHelper::setupTable' );
            HACLDBHelper::reportProgress("   ... new table created\n",$verbose);
            return array();
        } else { // check table signature
            HACLDBHelper::reportProgress("   ... table exists already, checking structure ...\n",$verbose);
            $res = $db->query( 'DESCRIBE ' . $table, 'HACLDBHelper::setupTable' );
            $curfields = array();
            $result = array();
            while ($row = $db->fetchObject($res)) {
                $type = strtoupper($row->Type);
                if ($row->Null != 'YES') {
                    $type .= ' NOT NULL';
                }
                $curfields[$row->Field] = $type;
            }
            $position = 'FIRST';
            foreach ($fields as $name => $type) {
                if ( !array_key_exists($name,$curfields) ) {
                    HACLDBHelper::reportProgress("   ... creating column $name ... ",$verbose);
                    $db->query("ALTER TABLE $table ADD `$name` $type $position", 'HACLDBHelper::setupTable');
                    $result[$name] = 'new';
                    HACLDBHelper::reportProgress("done \n",$verbose);
                } elseif ($curfields[$name] != $type && stripos($type, "primary key") === false) {
                	// Changing primary keys throws an error
                    HACLDBHelper::reportProgress("   ... changing type of column $name from '$curfields[$name]' to '$type' ... ",$verbose);
                    $db->query("ALTER TABLE $table CHANGE `$name` `$name` $type $position", 'HACLDBHelper::setupTable');
                    $result[$name] = 'up';
                    $curfields[$name] = false;
                    HACLDBHelper::reportProgress("done.\n",$verbose);
                } else {
                    HACLDBHelper::reportProgress("   ... column $name is fine\n",$verbose);
                    $curfields[$name] = false;
                }
                $position = "AFTER $name";
            }
            foreach ($curfields as $name => $value) {
                if ($value !== false) { // not encountered yet --> delete
                    HACLDBHelper::reportProgress("   ... deleting obsolete column $name ... ",$verbose);
                    $db->query("ALTER TABLE $table DROP COLUMN `$name`", 'HACLDBHelper::setupTable');
                    $result[$name] = 'del';
                    HACLDBHelper::reportProgress("done.\n",$verbose);
                }
            }
            HACLDBHelper::reportProgress("   ... table $table set up successfully.\n",$verbose);
            return $result;
        }
    }

    /**
     * Make sure that each of the column descriptions in the given array is indexed by *one* index
     * in the given DB table.
     */
    public static function setupIndex($table, $columns, $db) {
        $table = $db->tableName($table);
        $res = $db->query( 'SHOW INDEX FROM ' . $table , 'SMW::SetupIndex');
        if ( !$res ) {
            return false;
        }
        $indexes = array();
        while ( $row = $db->fetchObject( $res ) ) {
            if (!array_key_exists($row->Key_name, $indexes)) {
                $indexes[$row->Key_name] = array();
            }
            $indexes[$row->Key_name][$row->Seq_in_index] = $row->Column_name;
        }
        foreach ($indexes as $key => $index) { // clean up existing indexes
            $id = array_search(implode(',', $index), $columns );
            if ( $id !== false ) {
                $columns[$id] = false;
            } else { // duplicate or unrequired index
                $db->query( 'DROP INDEX ' . $key . ' ON ' . $table, 'SMW::SetupIndex');
            }
        }

        foreach ($columns as $column) { // add remaining indexes
            if ($column != false) {
                $db->query( "ALTER TABLE $table ADD INDEX ( $column )", 'SMW::SetupIndex');
            }
        }
        return true;
    }

    /**
     * Print some output to indicate progress. The output message is given by
     * $msg, while $verbose indicates whether or not output is desired at all.
     */
    public static function reportProgress($msg, $verbose) {
        if (!$verbose) {
            return;
        }
        if (ob_get_level() == 0) { // be sure to have some buffer, otherwise some PHPs complain
            ob_start();
        }
        print $msg;
        ob_flush();
        flush();
    }

    /**
     * Transform input parameters into a suitable array of SQL options.
     * The parameter $valuecol defines the string name of the column to which
     * sorting requests etc. are to be applied.
     */
    public static function getSQLOptions($requestoptions, $valuecol = NULL) {
        $sql_options = array();
        if ($requestoptions !== NULL) {
            if (is_numeric($requestoptions->limit) && $requestoptions->limit >= 0) {
                $sql_options['LIMIT'] = $requestoptions->limit;
            }
            if (is_numeric($requestoptions->offset) && $requestoptions->offset > 0) {
                $sql_options['OFFSET'] = $requestoptions->offset;
            }
            if ( ($valuecol !== NULL) && ($requestoptions->sort) ) {
                if (is_array($valuecol)) {
                    $sql_options['ORDER BY'] = $requestoptions->ascending ? mysql_real_escape_string(implode(",",$valuecol)) : mysql_real_escape_string(implode(",",$valuecol)) . ' DESC';
                } else {
                    $sql_options['ORDER BY'] = $requestoptions->ascending ? mysql_real_escape_string($valuecol) : mysql_real_escape_string($valuecol) . ' DESC';
                }
            }
        }
        return $sql_options;
    }

    public static function getSQLOptionsAsString($requestoptions, $valuecol = NULL) {
        $options = HACLDBHelper::getSQLOptions($requestoptions,$valuecol);
        $limit = array_key_exists('LIMIT', $options) && is_numeric($options['LIMIT'])? 'LIMIT '.$options['LIMIT'] : '';
        $offset = array_key_exists('OFFSET', $options) && is_numeric($options['OFFSET']) ? 'OFFSET '.$options['OFFSET'] : '';
        $orderby = array_key_exists('ORDER BY', $options) ? 'ORDER BY '.$options['ORDER BY'] : '';
        return $orderby.' '.$limit.' '.$offset;
    }

    /**
     * Transform input parameters into a suitable string of additional SQL conditions.
     * The parameter $valuecol defines the string name of the column to which
     * value restrictions etc. are to be applied.
     * @param $requestoptions object with options
     * @param $valuecol name of SQL column to which conditions apply
     * @param $labelcol name of SQL column to which string conditions apply, if any
     */
    public static function getSQLConditions($requestoptions, $valuecol, $labelcol = NULL) {
        $sql_conds = '';
        if ($requestoptions !== NULL) {
            $db =& wfGetDB( DB_SLAVE );
            if ($requestoptions->boundary !== NULL) { // apply value boundary
                if ($requestoptions->ascending) {
                    if ($requestoptions->include_boundary) {
                        $op = ' >= ';
                    } else {
                        $op = ' > ';
                    }
                } else {
                    if ($requestoptions->include_boundary) {
                        $op = ' <= ';
                    } else {
                        $op = ' < ';
                    }
                }
                $sql_conds .= ' AND ' . mysql_real_escape_string($valuecol) . $op . $db->addQuotes($requestoptions->boundary);
            }
            $operator = isset($requestoptions->disjunctiveStrings) && $requestoptions->disjunctiveStrings === true ? ' OR ' : ' AND ';
            $neutral = isset($requestoptions->disjunctiveStrings) && $requestoptions->disjunctiveStrings === true ? ' FALSE ' : ' TRUE ';
            if ($labelcol !== NULL) { // apply string conditions

                $sql_conds .= ' AND ( ';
                
                foreach ($requestoptions->getStringConditions() as $strcond) {
                    $string = str_replace(array('_', ' '), array('\_', '\_'), $strcond->string);
                    switch ($strcond->condition) {
                        case SMWStringCondition::STRCOND_PRE:
                            $string .= '%';
                            break;
                        case SMWStringCondition::STRCOND_POST:
                            $string = '%' . $string;
                            break;
                        case SMWStringCondition::STRCOND_MID:
                            $string = '%' . $string . '%';
                            break;
                    }
                    if ($requestoptions->isCaseSensitive) {
                        $sql_conds .=  mysql_real_escape_string($labelcol) . ' LIKE ' . $db->addQuotes($string). $operator;
                    } else {
                        $sql_conds .= ' UPPER(' . mysql_real_escape_string($labelcol) . ') LIKE UPPER(' . $db->addQuotes($string).') '.$operator;
                    }
                }
                $sql_conds .= ' '.$neutral.' ) ';
            }
        }
        return $sql_conds;
    }

    /**
     * Returns sql conditions of $requestoptions in an Array.
     * Warning! Does not support SMWAdvRequestOptions
     *
     * @param SMWRequestOptions $requestoptions
     * @param string  $valuecol
     * @param string $labelcol
     * @return array
     */
    public static function getSQLConditionsAsArray($requestoptions, $valuecol, $labelcol = NULL) {
        $sql_conds = array();
        if ($requestoptions !== NULL) {
            $db =& wfGetDB( DB_SLAVE );
            if ($requestoptions->boundary !== NULL) { // apply value boundary
                if ($requestoptions->ascending) {
                    if ($requestoptions->include_boundary) {
                        $op = ' >= ';
                    } else {
                        $op = ' > ';
                    }
                } else {
                    if ($requestoptions->include_boundary) {
                        $op = ' <= ';
                    } else {
                        $op = ' < ';
                    }
                }
                $sql_conds[] =  mysql_real_escape_string($valuecol) . $op . $db->addQuotes($requestoptions->boundary);
            }
            if ($labelcol !== NULL) { // apply string conditions
                foreach ($requestoptions->getStringConditions() as $strcond) {
                    $string = str_replace(array('_', ' '), array('\_', '\_'), $strcond->string);
                    switch ($strcond->condition) {
                        case SMWStringCondition::STRCOND_PRE:
                            $string .= '%';
                            break;
                        case SMWStringCondition::STRCOND_POST:
                            $string = '%' . $string;
                            break;
                        case SMWStringCondition::STRCOND_MID:
                            $string = '%' . $string . '%';
                            break;
                    }
                    $sql_conds[] = 'UPPER('.mysql_real_escape_string($labelcol) . ') LIKE UPPER(' . $db->addQuotes($string).')';
                }
            }
        }
        return $sql_conds;
    }
}
