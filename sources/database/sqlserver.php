<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/
/*EXTRA FUNCTIONS: (mssql|sqlsrv)\_.+*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core_database_drivers
 */

/*

Use the Enterprise Manager to get things set up.
You need to go into your server properties and turn the security to "SQL Server and Windows"

*/

/**
 * Standard code module initialisation function.
 */
function init__database__sqlserver()
{
	global $CACHE_DB;
	$CACHE_DB=array();

	@ini_set('mssql.textlimit','300000');
	@ini_set('mssql.textsize','300000');
}

class Database_Static_sqlserver
{

	/**
	 * Get the default user for making db connections (used by the installer as a default).
	 *
	 * @return string			The default user for db connections
	 */
	function db_default_user()
	{
		return 'sa';
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
	 * @param  ID_TEXT		The name of the unique key field for the table
	 */
	function db_create_index($table_name,$index_name,$_fields,$db,$unique_key_field='id')
	{
		if ($index_name[0]=='#')
		{
			if (db_has_full_text($db))
			{
				$index_name=substr($index_name,1);
				$unique_index_name='index'.$index_name.'_'.strval(mt_rand(0,10000));
				$this->db_query('CREATE UNIQUE INDEX '.$unique_index_name.' ON '.$table_name.'('.$unique_key_field.')',$db);
				$this->db_query('CREATE FULLTEXT CATALOG ft AS DEFAULT',$db,NULL,NULL,true); // Might already exist
				$this->db_query('CREATE FULLTEXT INDEX ON '.$table_name.'('.$_fields.') KEY INDEX '.$unique_index_name,$db,NULL,NULL,true);
			}
			return;
		}
		$this->db_query('CREATE INDEX index'.$index_name.'_'.strval(mt_rand(0,10000)).' ON '.$table_name.'('.$_fields.')',$db);
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
	 * @param  string			Our match string
	 * @param  boolean		Whether to do a boolean full text search
	 * @return string			Part of a WHERE clause for doing full-text search
	 */
	function db_full_text_assemble($content,$boolean)
	{
		unset($boolean);

		$content=str_replace('"','',$content);
		return 'CONTAINS ((?),\''.$this->db_escape_string($content).'\')';
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
							'AUTO'=>'integer identity',
							'AUTO_LINK'=>'integer',
							'INTEGER'=>'integer',
							'UINTEGER'=>'bigint',
							'SHORT_INTEGER'=>'smallint',
							'REAL'=>'real',
							'BINARY'=>'smallint',
							'USER'=>'integer',
							'GROUP'=>'integer',
							'TIME'=>'integer',
							'LONG_TRANS'=>'integer',
							'SHORT_TRANS'=>'integer',
							'SHORT_TEXT'=>'varchar(255)',
							'LONG_TEXT'=>'text',
							'ID_TEXT'=>'varchar(80)',
							'MINIID_TEXT'=>'varchar(40)',
							'IP'=>'varchar(40)',
							'LANGUAGE_NAME'=>'varchar(5)',
							'URLPATH'=>'varchar(255)',
							'MD5'=>'varchar(33)'
		);
		return $type_remap;
	}

	/**
	 * Close the database connections. We don't really need to close them (will close at exit), just disassociate so we can refresh them.
	 */
	function db_close_connections()
	{
		global $CACHE_DB;
		$CACHE_DB=array();
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

			$_fields.="	  $name $type $perhaps_null,\n";
		}

		$query='CREATE TABLE '.$table_name.' (
		  '.$_fields.'
		  PRIMARY KEY ('.$keys.')
		)';
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
		return $attribute." LIKE '".$this->db_escape_string($compare)."'";
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
		return $attribute."<>'".$this->db_escape_string($compare)."'";
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
	 * @param  ID_TEXT		The table name
	 * @param  array			The DB connection to delete on
	 */
	function db_drop_if_exists($table,$db)
	{
		$this->db_query('DROP TABLE '.$table,$db,NULL,NULL,true);
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
		return $this->db_escape_string(str_replace('%','*',$pattern));
	}

	/**
	 * Get a database connection. This function shouldn't be used by you, as a connection to the database is established automatically.
	 *
	 * @param  boolean		Whether to create a persistant connection
	 * @param  string			The database name
	 * @param  string			The database host (the server)
	 * @param  string			The database connection username
	 * @param  string			The database connection password
	 * @param  boolean		Whether to on error echo an error and return with a NULL, rather than giving a critical error
	 * @return ?array			A database connection (NULL: failed)
	 */
	function db_get_connection($persistent,$db_name,$db_host,$db_user,$db_password,$fail_ok=false)
	{
		// Potential cacheing
		global $CACHE_DB;
		if (isset($CACHE_DB[$db_name][$db_host]))
		{
			return $CACHE_DB[$db_name][$db_host];
		}

		if ((!function_exists('sqlsrv_connect')) && (!function_exists('mssql_pconnect')))
		{
			$error='The sqlserver PHP extension not installed (anymore?). You need to contact the system administrator of this server.';
			if ($fail_ok)
			{
				echo $error;
				return NULL;
			}
			critical_error('PASSON',$error);
		}

		if (function_exists('sqlsrv_connect'))
		{
			if ($db_host=='127.0.0.1' || $db_host=='localhost') $db_host='(local)';
			$db=@sqlsrv_connect($db_host,($db_user=='')?array('Database'=>$db_name):array('UID'=>$db_user,'PWD'=>$db_password,'Database'=>$db_name));
		} else
		{
			$db=$persistent?@mssql_pconnect($db_host,$db_user,$db_password):@mssql_connect($db_host,$db_user,$db_password);
		}
		if ($db===false)
		{
			$error='Could not connect to database-server ('.@strval($php_errormsg).')';
			if ($fail_ok)
			{
				echo $error;
				return NULL;
			}
			critical_error('PASSON',$error); //warn_exit(do_lang_tempcode('CONNECT_DB_ERROR'));
		}
		if (!function_exists('sqlsrv_connect'))
		{
			if (!mssql_select_db($db_name,$db))
			{
				$error='Could not connect to database ('.mssql_get_last_message().')';
				if ($fail_ok)
				{
					echo $error;
					return NULL;
				}
				critical_error('PASSON',$error); //warn_exit(do_lang_tempcode('CONNECT_ERROR'));
			}
		}

		$CACHE_DB[$db_name][$db_host]=$db;
		return $db;
	}

	/**
	 * Find whether full-text-search is present
	 *
	 * @param  array			A DB connection
	 * @return boolean		Whether it is
	 */
	function db_has_full_text($db)
	{
		global $SITE_INFO;
		if (array_key_exists('skip_fulltext_sqlserver',$SITE_INFO)) return false;
		return true;
	}

	/**
	 * Find whether full-text-boolean-search is present
	 *
	 * @return boolean		Whether it is
	 */
	function db_has_full_text_boolean()
	{
		return false;
	}

	/**
	 * Escape a string so it may be inserted into a query. If SQL statements are being built up and passed using db_query then it is essential that this is used for security reasons. Otherwise, the abstraction layer deals with the situation.
	 *
	 * @param  string			The string
	 * @return string			The escaped string
	 */
	function db_escape_string($string)
	{
		return str_replace("'","''",$string);
	}

	/**
	 * This function is a very basic query executor. It shouldn't usually be used by you, as there are abstracted versions available.
	 *
	 * @param  string			The complete SQL query
	 * @param  array			A DB connection
	 * @param  ?integer		The maximum number of rows to affect (NULL: no limit)
	 * @param  ?integer		The start row to affect (NULL: no specification)
	 * @param  boolean		Whether to output an error on failure
	 * @param  boolean		Whether to get the autoincrement ID created for an insert query
	 * @return ?mixed			The results (NULL: no results), or the insert ID
	 */
	function db_query($query,$db,$max=NULL,$start=NULL,$fail_ok=false,$get_insert_id=false)
	{
		if (!is_null($max))
		{
			if (is_null($start)) $max+=$start;

			if (strtoupper(substr($query,0,7))=='SELECT ') // Unfortunately we can't apply to DELETE FROM and update :(. But its not too important, LIMIT'ing them was unnecessarily anyway
			{
				$query='SELECT TOP '.strval(intval($max)).substr($query,6);
			}
		}

		$GLOBALS['SUPRESS_ERROR_DEATH']=true;
		if (function_exists('sqlsrv_query'))
		{
			$results=sqlsrv_query($db,$query,array(),array('Scrollable'=>'static'));
		} else
		{
			$results=mssql_query($query,$db);
		}
		$GLOBALS['SUPRESS_ERROR_DEATH']=false;
		if (($results===false) && (strtoupper(substr($query,0,12))=='INSERT INTO ') && (strpos($query,'(id, ')!==false))
		{
			$pos=strpos($query,'(');
			$table_name=substr($query,12,$pos-13);
			if (function_exists('sqlsrv_query'))
			{
				@sqlsrv_query($db,'SET IDENTITY_INSERT '.$table_name.' ON');
			} else
			{
				@mssql_query('SET IDENTITY_INSERT '.$table_name.' ON',$db);
			}
		}
		if (!is_null($start))
		{
			if (function_exists('sqlsrv_fetch_array'))
			{
				sqlsrv_fetch($results,SQLSRV_SCROLL_ABSOLUTE,$start-1);
			} else
			{
				@mssql_data_seek($results,$start);
			}
		}
		if ((($results===false) || ((strtoupper(substr($query,0,7))=='SELECT ') && ($results===true))) && (!$fail_ok))
		{
			if (function_exists('sqlsrv_errors'))
			{
				$err=sqlsrv_errors();
			} else
			{
				$_error_msg=array_pop($GLOBALS['ATTACHED_MESSAGES_RAW']);
				if (is_null($_error_msg)) $error_msg=make_string_tempcode('?'); else $error_msg=$_error_msg[0];
				$err=mssql_get_last_message().'/'.$error_msg->evaluate();
				if (function_exists('ocp_mark_as_escaped')) ocp_mark_as_escaped($err);
			}
			if ((!running_script('upgrader')) && (get_page_name()!='admin_import'))
			{
				if (!function_exists('do_lang') || is_null(do_lang('QUERY_FAILED',NULL,NULL,NULL,NULL,false))) fatal_exit(htmlentities('Query failed: '.$query.' : '.$err));

				fatal_exit(do_lang_tempcode('QUERY_FAILED',escape_html($query),($err)));
			} else
			{
				echo htmlentities('Database query failed: '.$query.' [').($err).htmlentities(']'.'<br />'.chr(10));
				return NULL;
			}
		}

		if ((strtoupper(substr($query,0,7))=='SELECT ') && ($results!==false) && ($results!==true))
		{
			return $this->db_get_query_rows($results);
		}

		if ($get_insert_id)
		{
			if (strtoupper(substr($query,0,7))=='UPDATE ') return NULL;

			$pos=strpos($query,'(');
			$table_name=substr($query,12,$pos-13);
			if (function_exists('sqlsrv_query'))
			{
				$res2=sqlsrv_query($db,'SELECT MAX(IDENTITYCOL) AS v FROM '.$table_name);
				$ar2=sqlsrv_fetch_array($res2,SQLSRV_FETCH_ASSOC);
			} else
			{
				$res2=mssql_query('SELECT MAX(IDENTITYCOL) AS v FROM '.$table_name,$db);
				$ar2=mssql_fetch_array($res2);
			}
			return $ar2['v'];
		}

		return NULL;
	}

	/**
	 * Get the rows returned from a SELECT query.
	 *
	 * @param  resource		The query result pointer
	 * @param  ?integer		Whether to start reading from (NULL: irrelevant for this forum driver)
	 * @return array			A list of row maps
	 */
	function db_get_query_rows($results,$start=NULL)
	{
		$out=array();

		if (!function_exists('sqlsrv_num_fields'))
		{
			$num_fields=mssql_num_fields($results);
			$types=array();
			$names=array();
			for ($x=1;$x<=$num_fields;$x++)
			{
				$types[$x-1]=mssql_field_type($results,$x-1);
				$names[$x-1]=strtolower(mssql_field_name($results,$x-1));
			}

			$i=0;
			while (($row=mssql_fetch_row($results))!==false)
			{
				$j=0;
				$newrow=array();
				foreach ($row as $v)
				{
					$type=strtoupper($types[$j]);
					$name=$names[$j];

					if (($type=='SMALLINT') || ($type=='INT') || ($type=='INTEGER') || ($type=='UINTEGER') || ($type=='BYTE') || ($type=='COUNTER'))
					{
						if (!is_null($v)) $newrow[$name]=intval($v); else $newrow[$name]=NULL;
					} else
					{
						if ($v==' ') $v='';
						$newrow[$name]=$v;
					}

					$j++;
				}

				$out[]=$newrow;

				$i++;
			}
		} else
		{
			while (($row=function_exists('sqlsrv_fetch_array')?sqlsrv_fetch_array($results,SQLSRV_FETCH_ASSOC):mssql_fetch_row($results))!==false)
			{
				$out[]=$row;
			}
		}

		if (function_exists('sqlsrv_free_stmt'))
		{
			sqlsrv_free_stmt($results);
		} else
		{
			mssql_free_result($results);
		}
		return $out;
	}

}


