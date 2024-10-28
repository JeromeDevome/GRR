<?php
/**
 * mysql.inc.php
 * Bibliothèque de fonctions pour le support mysql
 * Dernière modification : $Date: 2024-10-28 19:50$
 * @author    JeromeB & Laurent Delineau & Yan Naessens
 * @copyright Copyright 2003-2024 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
// connexion à la base
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    if (empty($db_nopersist))
        $GLOBALS['db_c'] = new mysqli('p:'.$dbHost, $dbUser, $dbPass, $dbDb, $dbPort);
    else
        $GLOBALS['db_c'] = new mysqli($dbHost, $dbUser, $dbPass, $dbDb, $dbPort);
    $GLOBALS['db_c']->set_charset("utf8mb4");
} catch(Exception $e) {
  error_log($e->getMessage());
  exit('Error connecting to database');
}
/*
if (empty($db_nopersist))
	$GLOBALS['db_c'] = @mysqli_connect('p:'.$dbHost, $dbUser, $dbPass, $dbDb, $dbPort);
else
	$GLOBALS['db_c'] = mysqli_connect($dbHost, $dbUser, $dbPass, $dbDb, $dbPort);
if (!$GLOBALS['db_c'] || !mysqli_select_db ($GLOBALS['db_c'], $dbDb))
{
	echo "\n<p>Database connection failure</p>\n";
	exit;
}
@mysqli_query($GLOBALS['db_c'], "SET NAMES UTF8");*/
/**
 * @param integer $row
 */
function mysqli_result($res, $row, $field = 0)
{
	$res->data_seek($row);
	$datarow = $res->fetch_array();
	return $datarow[$field];
}
// Free a results handle. You need not call this if you call grr_sql_row or
// grr_sql_row_keyed until the row returns 0, since grr_sql_row frees the results
// handle when you finish reading the rows.
function grr_sql_free($r)
{
	if (is_object($r) && $r instanceof mysqli_result && is_resource($r))
		mysqli_free_result($r);
}
/* Execute a non-SELECT SQL command (insert/update/delete).
 * Returns the number of tuples affected if OK (a number >= 0).
 * Returns -1 on error; use grr_sql_error to get the error message.
 * Parameters : 
 * $sql : a prepared SQL command with $types as types and $params as parameters OR a non-prepared SQL command if $types == $params == NULL
*/
function grr_sql_command($sql, $types=NULL, $params=NULL)
{
    try{
        if(($types == NULL)&&($params == NULL)){
            $res = $GLOBALS['db_c']->query($sql);
            $nb = $GLOBALS['db_c']->affected_rows;
        }
        elseif(($types != NULL)&&($params != NULL)){
            $stmt = $GLOBALS['db_c']->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $nb = $stmt->affected_rows;
            $stmt->close();
        }
        else 
            return -1;  // pourra être amélioré avec php8.2+ où $types peut être omis
    } catch(Exception $e) {
        error_log($e -> getMessage());
        return -1;
    }
    return $nb;
}

/* Execute an SQL query which should return a single non-negative number value.
 * This is a lightweight alternative to grr_sql_query, good for use with count(*)
 * and similar queries. It returns -1 on error or if the query did not return
 * exactly one value, so error checking is somewhat limited.
 * It also returns -1 if the query returns a single NULL value, such as from
 * a MIN or MAX aggregate function applied over no rows.
 * Parameters : 
 * $sql : a prepared SQL command with $types as types and $params as parameters OR a non-prepared SQL command if $types == $params == NULL
 */
function grr_sql_query1($sql, $types = NULL, $params = NULL)
{
    try{
        if(($types == NULL)&&($params == NULL)){
            $res = mysqli_query($GLOBALS['db_c'], $sql);
            if(($res->num_rows != 1)||($res->field_count != 1)||(($r = $res->fetch_row()[0]) == ""))
              $r = -1;
            mysqli_free_result($res);
            return($r);
        }
        elseif(($types != NULL)&&($params != NULL)){
            $stmt = $GLOBALS['db_c']->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_all(MYSQLI_NUM);
            if((!$res)||(count($res) != 1))
              $r = -1;
            else 
              $r = $res[0][0];
            $stmt->close();
            return($r);
        }
    } catch(Exception $e) {
        error_log($e -> getMessage());
        return -1;
    }
}
/*function grr_sql_query1($sql, $types = NULL, $params = NULL)
{
    try{
        if(($types == NULL)&&($params == NULL)){
            $res = mysqli_query($GLOBALS['db_c'], $sql);
        }
        elseif(($types != NULL)&&($params != NULL)){
            $stmt = $GLOBALS['db_c']->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            $stmt->close();
        }
    } catch(Exception $e) {
        error_log($e -> getMessage());
        return -1;
    }
    if(($res->num_rows != 1)||($res->field_count != 1)||(($r = $res->fetch_row()[0]) == ""))
        $r = -1;
    mysqli_free_result($res);
    return($r);
}*/
/* Execute an SQL query. Returns a database-dependent result handle,
 * which should be passed back to grr_sql_row or grr_sql_row_keyed to get the results.
 * Returns 0 on error; use grr_sql_error to get the error message.
 * Parameters : 
 * $sql : a prepared SQL command with $types as types and $params as parameters OR a non-prepared SQL command if $types == $params == NULL
*/
function grr_sql_query($sql, $types = NULL, $params = NULL)
{
    try{
        if(($types == NULL)&&($params == NULL)){
            $res = mysqli_query($GLOBALS['db_c'], $sql);
        }
        elseif(($types != NULL)&&($params != NULL)){
            $stmt = $GLOBALS['db_c']->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            $stmt->close();
        }
    } catch(Exception $e) {
        error_log($e -> getMessage());
        return 0;
    }
    return($res);
}
//retourne la version de mysql
function grr_sql_version()
{
	$r = mysqli_get_server_info($GLOBALS['db_c']);
	return $r;
}

// Return a row from a result. The first row is 0.
// The row is returned as an array with index 0=first column, etc.
// When called with i >= number of rows in the result, cleans up from
// the query and returns 0.
// Typical usage: $i = 0; while ((a = grr_sql_row($r, $i++))) { ... }
/**
 * @param integer $i
 */
function grr_sql_row ($r, $i)
{
	if ($i >= mysqli_num_rows($r))
	{
		mysqli_free_result($r);
		return 0;
	}
	mysqli_data_seek($r, $i);
	return mysqli_fetch_row($r);
}
// Return a row from a result as an associative array keyed by field name.
// The first row is 0.
// This is actually upward compatible with grr_sql_row since the underlying
// routing also stores the data under number indexes.
// When called with i >= number of rows in the result, cleans up from
// the query and returns 0.
/**
 * @param integer $i
 */
function grr_sql_row_keyed ($r, $i)
{
	if ($i >= mysqli_num_rows($r))
	{
		mysqli_free_result($r);
		return 0;
	}
	mysqli_data_seek($r, $i);
	return mysqli_fetch_array($r);
}
// Return the number of rows returned by a result handle from grr_sql_query.
function grr_sql_count ($r)
{
	return mysqli_num_rows($r);
}
// Return the value of an autoincrement field from the last insert.
// Must be called right after an insert on that table!
function grr_sql_insert_id()
{
	return mysqli_insert_id($GLOBALS['db_c']);
}
// Return the text of the last error message.
function grr_sql_error()
{
	return mysqli_error($GLOBALS['db_c']);
}
// Acquire a mutual-exclusion lock on the named table. For portability:
// This will not lock out SELECTs.
// It may lock out DELETE/UPDATE/INSERT or not, depending on the implementation.
// It will lock out other callers of this routine with the same name argument.
// It may timeout in 20 seconds and return 0, or may wait forever.
// It returns 1 when the lock has been acquired.
// Caller must release the lock with grr_sql_mutex_unlock().
// Caller must not have more than one mutex at any time.
// Do not mix this with grr_sql_begin()/sql_end() calls.
//
// In MySQL, we avoid table locks, and use low-level locks instead.
function grr_sql_mutex_lock($name)
{
	global $sql_mutex_shutdown_registered, $grr_sql_mutex_unlock_name;
	if (!grr_sql_query1("SELECT GET_LOCK('$name', 20)"))
		return 0;
	$grr_sql_mutex_unlock_name = $name;
	if (empty($sql_mutex_shutdown_registered))
	{
		register_shutdown_function("grr_sql_mutex_cleanup");
		$sql_mutex_shutdown_registered = 1;
	}
	return 1;
}
// Release a mutual-exclusion lock on the named table. See grr_sql_mutex_unlock.
function grr_sql_mutex_unlock($name)
{
	global $grr_sql_mutex_unlock_name;
	grr_sql_query1("SELECT RELEASE_LOCK('$name')");
	$grr_sql_mutex_unlock_name = "";
}
// Shutdown function to clean up a forgotten lock. For internal use only.
function grr_sql_mutex_cleanup()
{
	global $sql_mutex_shutdown_registered, $grr_sql_mutex_unlock_name;
	if (!empty($grr_sql_mutex_unlock_name))
	{
		grr_sql_mutex_unlock($grr_sql_mutex_unlock_name);
		$grr_sql_mutex_unlock_name = "";
	}
}
// Generate non-standard SQL for LIMIT clauses:
function grr_sql_syntax_limit($count, $offset)
{
	return " LIMIT $offset,$count ";
}
// Generate non-standard SQL to output a TIMESTAMP as a Unix-time:
/**
 * @param string $fieldname
 */
function grr_sql_syntax_timestamp_to_unix($fieldname)
{
	return " UNIX_TIMESTAMP($fieldname) ";
}
// Generate non-standard SQL to match a string anywhere in a field's value
// in a case insensitive manner. $s is the un-escaped/un-slashed string.
// In MySQL, REGEXP seems to be case sensitive, so use LIKE instead. But this
// requires quoting of % and _ in addition to the usual.
function grr_sql_syntax_caseless_contains($fieldname, $s, $type_recherche = 1)
{
	$s = protect_data_sql($s);
//    $s = str_replace("'", "''", $s);
//    $s = str_replace("\\", "\\\\", $s);
	$s = str_replace("%", "\\%", $s);
	$s = str_replace("_", "\\_", $s);
	if ($type_recherche == 1)
		return " $fieldname LIKE '%$s%' ";
	else
		return " $fieldname NOT LIKE '%$s%' ";
}
function grr_sql_syntax_caseless_contains_overload($fieldname, $s, $id_overload, $type_recherche = 1)
{
	$s = urlencode($s);
	$s = str_replace("%", "\\%", $s);
	$s = str_replace("_", "\\_", $s);
	if ($type_recherche == 1)
		return " $fieldname LIKE '%@".$id_overload."@%$s%@\/".$id_overload."@%' ";
	else
		return " $fieldname NOT LIKE '%@".$id_overload."@%$s%@\/".$id_overload."@%' ";
}
// Utilisé dans language.inc.php
$fichier_mysql_inc_est_present = 1;
?>
