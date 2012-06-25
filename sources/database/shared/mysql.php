<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core_database_drivers
 */

class Database_super_mysql
{

	/**
	 * Find whether the database may run GROUP BY unfettered with restrictions on the SELECT'd fields having to be represented in it or aggregate functions
	 *
	 * @return boolean		Whether it can
	 */
	function can_arbitrary_groupby()
	{
		return true;
	}

	/**
	 * Get the default user for making db connections (used by the installer as a default).
	 *
	 * @return string			The default user for db connections
	 */
	function db_default_user()
	{
		return 'root';
	}

	/**
	 * Get the default password for making db connections (used by the installer as a default).
	 *
	 * @return string			The default password for db connections
	 */
	function db_default_password()
	{
		return '';
	}

	/**
	 * Create a table index.
	 *
	 * @param  ID_TEXT		The name of the table to create the index on
	 * @param  ID_TEXT		The index name (not really important at all)
	 * @param  string			Part of the SQL query: a comma-separated list of fields to use on the index
	 * @param  array			The DB connection to make on
	 */
	function db_create_index($table_name,$index_name,$_fields,$db)
	{
		if ($index_name[0]=='#')
		{
			if ($this->using_innodb()) return;
			$index_name=substr($index_name,1);
			$type='FULLTEXT';
		} else $type='INDEX';
		$this->db_query('ALTER TABLE '.$table_name.' ADD '.$type.' '.$index_name.' ('.$_fields.')',$db);
	}

	/**
	 * Change the primary key of a table.
	 *
	 * @param  ID_TEXT		The name of the table to create the index on
	 * @param  array			A list of fields to put in the new key
	 * @param  array			The DB connection to make on
	 */
	function db_change_primary_key($table_name,$new_key,$db)
	{
		$this->db_query('ALTER TABLE '.$table_name.' DROP PRIMARY KEY',$db);
		$this->db_query('ALTER TABLE '.$table_name.' ADD PRIMARY KEY ('.implode(',',$new_key).')',$db);
	}

	/**
	 * Assemble part of a WHERE clause for doing full-text search
	 *
	 * @param  string			Our match string (assumes "?" has been stripped already)
	 * @param  boolean		Whether to do a boolean full text search
	 * @return string			Part of a WHERE clause for doing full-text search
	 */
	function db_full_text_assemble($content,$boolean)
	{
		if (!$boolean)
		{
			$content=str_replace('"','',$content);
			if ((strtoupper($content)==$content) && (!is_numeric($content)))
			{
				return 'MATCH (?) AGAINST (_latin1\''.$this->db_escape_string($content).'\' COLLATE latin1_general_cs) AND (? IS NOT NULL)';
			}
			return 'MATCH (?) AGAINST (\''.$this->db_escape_string($content).'\') AND (? IS NOT NULL)';
		}
		
		return 'MATCH (?) AGAINST (\''.$this->db_escape_string($content).'\' IN BOOLEAN MODE) AND (? IS NOT NULL)';
	}

	/**
	 * Get the ID of the first row in an auto-increment table (used whenever we need to reference the first).
	 *
	 * @return integer			First ID used
	 */
	function db_get_first_id()
	{
		return 1;
	}

	/**
	 * Get a map of ocPortal field types, to actual mySQL types.
	 *
	 * @return array			The map
	 */
	function db_get_type_remap()
	{
		$type_remap=array(
							'AUTO'=>'integer unsigned auto_increment',
							'AUTO_LINK'=>'integer', // not unsigned because it's useful to have -ve for temporary usage whilst importing
							'INTEGER'=>'integer',
							'UINTEGER'=>'integer unsigned',
							'SHORT_INTEGER'=>'tinyint',
							'REAL'=>'real',
							'BINARY'=>'tinyint(1)',
							'USER'=>'integer', // not unsigned because it's useful to have -ve for temporary usage whilst importing
							'GROUP'=>'integer', // not unsigned because it's useful to have -ve for temporary usage whilst importing
							'TIME'=>'integer unsigned',
							'LONG_TRANS'=>'integer unsigned',
							'SHORT_TRANS'=>'integer unsigned',
							'SHORT_TEXT'=>'varchar(255)',
							'LONG_TEXT'=>'longtext',
							'ID_TEXT'=>'varchar(80)',
							'MINIID_TEXT'=>'varchar(40)',
							'IP'=>'varchar(40)', // 15 for ip4, but we now support ip6
							'LANGUAGE_NAME'=>'varchar(5)',
							'URLPATH'=>'varchar(255)',
							'MD5'=>'varchar(33)'
		);
		return $type_remap;
	}

	/**
	 * Whether to use InnoDB for mySQL. Change this function by hand - official only MyISAM supported
	 *
	 * @return boolean			Answer
	 */
	function using_innodb()
	{
		return false;
	}

	/**
	 * Create a new table.
	 *
	 * @param  ID_TEXT		The table name
	 * @param  array			A map of field names to ocPortal field types (with *#? encodings)
	 * @param  array			The DB connection to make on
	 */
	function db_create_table($table_name,$fields,$db)
	{
		$type_remap=$this->db_get_type_remap();

		/*if (multi_lang()==0)
		{
			$type_remap['LONG_TRANS']=$type_remap['LONG_TEXT'];
			$type_remap['SHORT_TRANS']=$type_remap['SHORT_TEXT'];
		}*/

		$_fields='';
		$keys='';
		foreach ($fields as $name=>$type)
		{
			if ($type[0]=='*') // Is a key
			{
				$type=substr($type,1);
				if ($keys!='') $keys.=', ';
				$keys.=$name;
			}

			if ($type[0]=='?') // Is perhaps null
			{
				$type=substr($type,1);
				$perhaps_null='NULL';
			} else $perhaps_null='NOT NULL';

			$type=$type_remap[$type];

			$_fields.='	  '.$name.' '.$type.' '.$perhaps_null.','.chr(10);
		}

		$innodb=$this->using_innodb();
		$table_type=($innodb?'INNODB':'MyISAM');
		$type_key='engine';
		global $SITE_INFO;
		if (((isset($SITE_INFO['mysql_old'])) && ($SITE_INFO['mysql_old']=='1')) || ((!isset($SITE_INFO['mysql_old'])) && (is_file(get_file_base().'/mysql_old'))))
			$type_key='type';
		if (substr($table_name,-8)=='sessions') $table_type='HEAP';

		$query='CREATE TABLE '.$table_name.' (
'.$_fields.'
			PRIMARY KEY ('.$keys.')
		)';
		if (!(((isset($SITE_INFO['mysql_old'])) && ($SITE_INFO['mysql_old']=='1')) || ((!isset($SITE_INFO['mysql_old'])) && (is_file(get_file_base().'/mysql_old')))))
		{
			global $SITE_INFO;
			if (!array_key_exists('database_charset',$SITE_INFO)) $SITE_INFO['database_charset']=(strtolower(get_charset())=='utf-8')?'utf8':'latin1';

			$query.=' CHARACTER SET='.preg_replace('#\_.*$#','',$SITE_INFO['database_charset']);
		}
		$query.=' '.$type_key.'='.$table_type.';';
		$this->db_query($query,$db,NULL,NULL);
	}

	/**
	 * Encode an SQL statement fragment for a conditional to see if two strings are equal.
	 *
	 * @param  ID_TEXT		The attribute
	 * @param  string			The comparison
	 * @return string			The SQL
	 */
	function db_string_equal_to($attribute,$compare)
	{
		return $attribute."='".db_escape_string($compare)."'";
	}

	/**
	 * Encode an SQL statement fragment for a conditional to see if two strings are not equal.
	 *
	 * @param  ID_TEXT		The attribute
	 * @param  string			The comparison
	 * @return string			The SQL
	 */
	function db_string_not_equal_to($attribute,$compare)
	{
		return $attribute."<>'".db_escape_string($compare)."'";
	}

	/**
	 * Find whether expression ordering support is present
	 *
	 * @param  array			A DB connection
	 * @return boolean		Whether it is
	 */
	function db_has_expression_ordering($db)
	{
		return true;
	}

	/**
	 * This function is internal to the database system, allowing SQL statements to be build up appropriately. Some databases require IS NULL to be used to check for blank strings.
	 *
	 * @return boolean			Whether a blank string IS NULL
	 */
	function db_empty_is_null()
	{
		return false;
	}

	/**
	 * Delete a table.
	 *
	 * @param  ID_TEXT			The table name
	 * @param  array				The DB connection to delete on
	 */
	function db_drop_if_exists($table,$db)
	{
		$this->db_query('DROP TABLE IF EXISTS '.$table,$db);
	}

	/**
	 * Determine whether the database is a flat file database, and thus not have a meaningful connect username and password.
	 *
	 * @return boolean			Whether the database is a flat file database
	 */
	function db_is_flat_file_simple()
	{
		return false;
	}

	/**
	 * Encode a LIKE string comparision fragement for the database system. The pattern is a mixture of characters and ? and % wilcard symbols.
	 *
	 * @param  string			The pattern
	 * @return string			The encoded pattern
	 */
	function db_encode_like($pattern)
	{
		$ret=preg_replace('#([^\\\\])\\\\\\\\_#','${1}\_',$this->db_escape_string($pattern));
		return $ret;
	}

	/**
	 * Close the database connections. We don't really need to close them (will close at exit), just disassociate so we can refresh them.
	 */
	function db_close_connections()
	{
		global $CACHE_DB,$LAST_SELECT_DB;
		$CACHE_DB=array();
		$LAST_SELECT_DB=NULL;
	}

}


