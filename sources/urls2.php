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
 * @package		core
 */

/**
 * Standard code module initialisation function.
 */
function init__urls2()
{
	define('MAX_MONIKER_LENGTH',24); // TODO: Make proper option
}

/**
 * Get hidden fields for a form representing 'keep_x'. If we are having a GET form instead of a POST form, we need to do this. This function also encodes the page name, as we'll always want that.
 *
 * @param  ID_TEXT		The page for the form to go to (blank: don't attach)
 * @param  boolean		Whether to keep all elements of the current URL represented in this form (rather than just the keep_ fields, and page)
 * @param  ?array			A list of parameters to exclude (NULL: don't exclude any)
 * @return tempcode		The builtup hidden form fields
 */
function _build_keep_form_fields($page='',$keep_all=false,$exclude=NULL)
{
	if (is_null($exclude)) $exclude=array();

	if ($page=='_SELF') $page=get_page_name();
	$out=new ocp_tempcode();

	if (count($_GET)>0)
	{
		foreach ($_GET as $key=>$val)
		{
			if (!is_string($val)) continue;

			if (get_magic_quotes_gpc()) $val=stripslashes($val);

			if (((substr($key,0,5)=='keep_') || ($keep_all)) && (!in_array($key,$exclude)) && ($key!='page') && (!skippable_keep($key,$val)))
				$out->attach(form_input_hidden($key,$val));
		}
	}
	if ($page!='') $out->attach(form_input_hidden('page',$page));
	return $out;
}

/**
 * Recurser helper function for _build_keep_post_fields.
 *
 * @param  ID_TEXT		Key name to put value under
 * @param  mixed			Value (string or array)
 * @return string			The builtup hidden form fields
 */
function _fixed_post_parser($key,$value)
{
	$out='';

	if (!is_string($key)) $key=strval($key);

	if (is_array($value))
	{
		foreach ($value as $k=>$v)
		{
			if (is_string($k))
			{
				$out.=_fixed_post_parser($key.'['.$k.']',$v);
			}
			else
			{
				$out.=_fixed_post_parser($key.'['.strval($k).']',$v);
			}
		}
	} else
	{
		if (get_magic_quotes_gpc()) $value=stripslashes($value);

		$out.=static_evaluate_tempcode(form_input_hidden($key,is_string($value)?$value:strval($value)));
	}

	return $out;
}

/**
 * Relay all POST variables for this URL, to the URL embedded in the form.
 *
 * @param  ?array			A list of parameters to exclude (NULL: exclude none)
 * @return tempcode		The builtup hidden form fields
 */
function _build_keep_post_fields($exclude=NULL)
{
	$out='';
	foreach ($_POST as $key=>$val)
	{
		if (is_integer($key)) $key=strval($key);

		if ((!is_null($exclude)) && (in_array($key,$exclude))) continue;

		if (count($_POST)>80)
		{
			if (substr($key,0,11)=='label_for__') continue;
			if (substr($key,0,9)=='require__') continue;
		}

      $out.=_fixed_post_parser($key,$val);
	}
	return make_string_tempcode($out);
}

/**
 * Takes a URL, and converts it into a file system storable filename. This is used to cache URL contents to the servers filesystem.
 *
 * @param  URLPATH		The URL to convert to an encoded filename
 * @return string			A usable filename based on the URL
 */
function _url_to_filename($url_full)
{
	$bad_chars=array('!','/','\\','?','*','<','>','|','"',':');
	$new_name=$url_full;
	foreach ($bad_chars as $bad_char)
	{
		$good_char='!'.strval(ord($bad_char));
		if ($bad_char==':') $good_char=';'; // So pagelinks save nice
		$new_name=str_replace($bad_char,$good_char,$new_name);
	}

	if (strlen($new_name)<=200/*technically 256 but something may get put on the start, so be cautious*/)
		return $new_name;

	// Non correspondance, but at least we have something
	if (strpos($new_name,'.')===false) return md5($new_name);
	return md5($new_name).'.'.get_file_extension($new_name);
}

/**
 * Take a URL and base-URL, and fully qualify the URL according to it.
 *
 * @param  URLPATH		The URL to fully qualified
 * @param  URLPATH		The base-URL
 * @return URLPATH		Fully qualified URL
 */
function _qualify_url($url,$url_base)
{
	require_code('obfuscate');
	if (($url!='') && ($url[0]!='#') && (substr($url,0,5)!='data:') && (substr($url,0,7)!='mailto:') && (substr($url,0,strlen(mailto_obfuscated()))!=mailto_obfuscated()))
	{
		if (url_is_local($url))
		{
			if ($url[0]=='/')
			{
				$parsed=@parse_url($url_base);
				if ($parsed===false) return '';
				if (!array_key_exists('scheme',$parsed)) $parsed['scheme']='http';
				if (!array_key_exists('host',$parsed)) $parsed['host']='localhost';
				if (substr($url,0,2)=='//')
				{
					$url=$parsed['scheme'].':'.$url;
				} else
				{
					$url=$parsed['scheme'].'://'.$parsed['host'].(array_key_exists('port',$parsed)?(':'.$parsed['port']):'').$url;
				}
			} else $url=$url_base.'/'.$url;
		}
	}

	$url=str_replace('/./','/',$url);
	$pos=strpos($url,'/../');
	while ($pos!==false)
	{
		$pos_2=strrpos(substr($url,0,$pos-1),'/');
		if ($pos_2===false) break;
		$url=substr($url,0,$pos_2).'/'.substr($url,$pos+4);
		$pos=strpos($url,'/../');
	}

	return $url;
}

/**
 * Convert a URL to a local file path.
 *
 * @param  URLPATH		The value to convert
 * @return ?PATH			File path (NULL: is not local)
 */
function _convert_url_to_path($url)
{
	if (strpos($url,'?')!==false) return NULL;
	if ((strpos($url,'://')===false) || (substr($url,0,strlen(get_base_url())+1)==get_base_url().'/'))
	{
		if (strpos($url,'://')!==false)
			$file_path_stub=urldecode(substr($url,strlen(get_base_url())+1));
		else
			$file_path_stub=urldecode($url);
		if (((substr($file_path_stub,0,7)=='themes/') && (substr($file_path_stub,0,15)!='themes/default/')) || (substr($file_path_stub,0,8)=='uploads/') || (strpos($file_path_stub,'_custom/')!==false))
		{
			$file_path_stub=get_custom_file_base().'/'.$file_path_stub;
		} else
		{
			$file_path_stub=get_file_base().'/'.$file_path_stub;
		}

		if (!is_file($file_path_stub)) return NULL;

		return $file_path_stub;
	}

	return NULL;
}

/**
 * Sometimes users don't enter full URLs but do intend for them to be absolute. This code tries to see what relative URLs are actually absolute ones, via an algorithm. It then fixes the URL.
 *
 * @param  URLPATH		The URL to fix
 * @return URLPATH		The fixed URL (or original one if no fix was needed)
 */
function _fixup_protocolless_urls($in)
{
	if ($in=='') return $in;

	$in=remove_url_mistakes($in); // Chain in some other stuff

	if (strpos($in,':')!==false) return $in; // Absolute (e.g. http:// or mailto:)

	if (substr($in,0,1)=='#') return $in;
	if (substr($in,0,1)=='%') return $in;
	if (substr($in,0,1)=='{') return $in;

	// Rule 1: // If we have a dot before a slash, then this dot is likely part of a domain name (not a file extension)- thus we have an absolute URL.
	if (preg_match('#\..*/#',$in)!=0)
	{
		return 'http://'.$in; // Fix it
	}
	// Rule 2: // If we have no slashes and we don't recognise a file type then they've probably just entered a domain name- thus we have an absolute URL.
	if ((preg_match('#^[^/]+$#',$in)!=0) && (preg_match('#\.(php|htm|asp|jsp|swf|gif|png|jpg|jpe|txt|pdf|odt|ods|odp|doc|mdb|xls|ppt|xml|rss|ppt|svg|wrl|vrml|gif|psd|rtf|bmp|avi|mpg|mpe|webm|mp4|mov|wmv|ram|rm|asf|ra|wma|wav|mp3|ogg|torrent|csv|ttf|tar|gz|rar|bz2)#',$in)==0))
	{
		return 'http://'.$in.'/'; // Fix it
	}

	return $in; // Relative
}

/**
 * Convert a local URL to a page-link.
 *
 * @param  URLPATH		The URL to convert. Note it may not be a short URL, and it must be based on the local base URL (else failure WILL occur).
 * @param  boolean		Whether to only convert absolute URLs. Turn this on if you're not sure what you're passing is a URL not and you want to be extra safe.
 * @param  boolean		Whether to only allow perfect conversions.
 * @return string			The page link (blank: could not convert).
 */
function _url_to_pagelink($url,$abs_only=false,$perfect_only=true)
{
	if (($abs_only) && (substr($url,0,7)!='http://') && (substr($url,0,8)!='https://')) return '';

	// Try and strip any variants of the base URL from our $url variable, to make it relative
	$non_www_base_url=str_replace('http://www.','http://',get_base_url());
	$www_base_url=str_replace('http://','http://www.',get_base_url());
	$url=preg_replace('#^'.str_replace('#','\#',preg_quote(get_base_url().'/')).'#','',$url);
	$url=preg_replace('#^'.str_replace('#','\#',preg_quote($non_www_base_url.'/')).'#','',$url);
	$url=preg_replace('#^'.str_replace('#','\#',preg_quote($www_base_url.'/')).'#','',$url);
	if (substr($url,0,7)=='http://') return '';
	if (substr($url,0,8)=='https://') return '';
	if (substr($url,0,1)!='/') $url='/'.$url;

	// Parse the URL
	if ((strpos($url,'&')!==false) && (strpos($url,'?')===false)) $url=preg_replace('#&#','?',$url,1); // Old-style short URLs workl like this (no first ?, just &)
	$parsed_url=@parse_url($url);
	if ($parsed_url===false)
	{
		require_code('site');
		attach_message(do_lang_tempcode('HTTP_DOWNLOAD_BAD_URL',escape_html($url)),'warn');
		return '';
	}

	// Work out the zone
	$slash_pos=strpos($parsed_url['path'],'/',1);
	$zone=($slash_pos!==false)?substr($parsed_url['path'],1,$slash_pos-1):'';
	if (!in_array($zone,find_all_zones()))
	{
		$zone='';
		$slash_pos=false;
	}
	$parsed_url['path']=($slash_pos===false)?substr($parsed_url['path'],1):substr($parsed_url['path'],$slash_pos+1); // everything AFTER the zone
	$parsed_url['path']=preg_replace('#/index\.php$#','',$parsed_url['path']);
	$attributes=array();
	$attributes['page']=''; // hopefully will get overwritten with a real one

	// Convert short URL path info into extra implied attribute data
	require_code('url_remappings');
	foreach (array(false,true) as $method)
	{
		$mappings=get_remappings($method);
		foreach ($mappings as $mapping) // e.g. array(array('page'=>'cedi','id'=>NULL),'pg/s/ID',true),
		{
			if (is_null($mapping)) continue;

			list($params,$match_string,)=$mapping;
			$match_string_pattern=preg_replace('#[A-Z]+#','[^\&\?]+',preg_quote($match_string));

			$zones=find_all_zones();
			if (preg_match('#^'.$match_string_pattern.'$#',$parsed_url['path'])!=0)
			{
				$attributes=array_merge($attributes,$params);

				$bits_pattern=explode('/',preg_replace('#\.htm$#','',$match_string));
				$bits_real=explode('/',preg_replace('#\.htm$#','',$parsed_url['path']),count($bits_pattern));
				foreach ($bits_pattern as $i=>$bit)
				{
					if ((array_key_exists(strtolower($bit),$params)) && (strtoupper($bit)==$bit) && (is_null($params[strtolower($bit)])))
						$attributes[strtolower($bit)]=$bits_real[$i];
				}

				break;
			}
		}
	}
	if (($attributes==array('page'=>'')) && ($parsed_url['path']!=''))
	{
		if ((strpos($parsed_url['path'],'/pg/')===false) && (strpos($parsed_url['path'],'.htm')===false) && ($parsed_url['path']!='index.php')) return '';
	}

	// Parse query string component into the waiting (and partly-filled-already) attribute data array
	if (array_key_exists('query',$parsed_url))
	{
		$bits=explode('&',$parsed_url['query']);
		foreach ($bits as $bit)
		{
			$_bit=explode('=',$bit,2);

			if ((count($_bit)==2)/* && (substr($_bit[0],0,5)!='keep_')*/)
			{
				$attributes[$_bit[0]]=$_bit[1];
				if (strpos($attributes[$_bit[0]],':')!==false)
				{
					if ($perfect_only) return ''; // Could not convert this URL to a page-link, because it contains a colon
					unset($attributes[$_bit[0]]);
				}
			}
		}
	}

	// Put it together
	$page_link=$zone.':'.$attributes['page'];
	if (array_key_exists('type',$attributes)) $page_link.=':'.$attributes['type']; elseif (array_key_exists('id',$attributes)) $page_link.=':';
	if (array_key_exists('id',$attributes)) $page_link.=':'.urldecode($attributes['id']);
	foreach ($attributes as $key=>$val)
	{
		if (!is_string($val)) $val=strval($val);
		if (($key!='page') && ($key!='type') && ($key!='id'))
			$page_link.=':'.$key.'='.urldecode($val);
	}

	// Hash bit?
	if (array_key_exists('fragment',$parsed_url)) $page_link.='#'.$parsed_url['fragment'];

	return $page_link;
}

/**
 * Convert a local page file path to a written page-link.
 *
 * @param  string			The path.
 * @return string			The page link (blank: could not convert).
 */
function _page_path_to_pagelink($page)
{
	if ((substr($page,0,1)=='/') && (substr($page,0,6)!='/pages')) $page=substr($page,1);
	$matches=array();
	if (preg_match('#^([^/]*)/?pages/([^/]+)/(\w\w/)?([^/\.]+)\.(php|txt|htm)$#',$page,$matches)==1)
	{
		$page2=$matches[1].':'.$matches[4];
		if (($matches[2]=='comcode') || ($matches[2]=='comcode_custom'))
		{
			if (file_exists(get_custom_file_base().'/'.$page))
			{
				$file=file_get_contents(get_custom_file_base().'/'.$page);
				if (preg_match('#\[title\](.*)\[/title\]#U',$file,$matches)!=0)
				{
					$page2.=' ('.$matches[1].')';
				}
				elseif (preg_match('#\[title=[\'"]?1[\'"]?\](.*)\[/title\]#U',$file,$matches)!=0)
				{
					$page2.=' ('.$matches[1].')';
				}
				$page2=preg_replace('#\[[^\[\]]*\]#','',$page2);
			}
		}
	} else $page2='';

	return $page2;
}

/**
 * Called from 'find_id_moniker'. We tried to lookup a moniker, found a hook, but found no stored moniker. So we'll try and autogenerate one.
 *
 * @param  array			The hooks info profile.
 * @param  array			The URL component map (must contain 'page', 'type', and 'id' if this function is to do anything).
 * @return ?string		The moniker ID (NULL: error generating it somehow, can not do it)
 */
function autogenerate_new_url_moniker($ob_info,$url_parts)
{
	$bak=$GLOBALS['NO_DB_SCOPE_CHECK'];
	$GLOBALS['NO_DB_SCOPE_CHECK']=true;
	$select=array($ob_info['id_field']);
	if (substr($ob_info['title_field'],0,5)!='CALL:') $select[]=$ob_info['title_field'];
	if (!is_null($ob_info['parent_category_field'])) $select[]=$ob_info['parent_category_field'];
	$db=((substr($ob_info['table'],0,2)!='f_') || (get_forum_type()=='none'))?$GLOBALS['SITE_DB']:$GLOBALS['FORUM_DB'];
	$_moniker_src=$db->query_select($ob_info['table'],$select,array($ob_info['id_field']=>$ob_info['id_field_numeric']?intval($url_parts['id']):$url_parts['id']));
	$GLOBALS['NO_DB_SCOPE_CHECK']=$bak;
	if (!array_key_exists(0,$_moniker_src)) return NULL; // been deleted?

	if (substr($ob_info['title_field'],0,5)=='CALL:')
	{
		$moniker_src=call_user_func(trim(substr($ob_info['title_field'],5)),$url_parts);
	} else
	{
		if ($ob_info['title_field_dereference'])
		{
			$moniker_src=get_translated_text($_moniker_src[0][$ob_info['title_field']]);
		} else
		{
			$moniker_src=$_moniker_src[0][$ob_info['title_field']];
		}
	}
	if ($moniker_src=='') $moniker_src='untitled';
	return suggest_new_idmoniker_for($url_parts['page'],$url_parts['type'],$url_parts['id'],$moniker_src,true);
}

/**
 * Called when content is added, or edited/moved, based upon a new form field that specifies what moniker to use.
 *
 * @param  ID_TEXT		Page name.
 * @param  ID_TEXT		Screen type code.
 * @param  ID_TEXT		Resource ID.
 * @param  string			String from which a moniker will be chosen (may not be blank).
 * @param  boolean		Whether we are sure this is a new moniker (makes things more efficient, saves a query).
 * @return string			The chosen moniker.
 */
function suggest_new_idmoniker_for($page,$type,$id,$moniker_src,$is_new=false)
{
	if (!$is_new)
	{
		// Deprecate old one if already exists
		$old=$GLOBALS['SITE_DB']->query_value_null_ok('url_id_monikers','m_moniker',array('m_resource_page'=>$page,'m_resource_type'=>$type,'m_resource_id'=>$id,'m_deprecated'=>0),'ORDER BY id DESC');
		if (!is_null($old))
		{
			// See if it is same as current
			$scope=_give_moniker_scope($page,$type,$id,'');
			$moniker=$scope._choose_moniker($page,$type,$id,$moniker_src,$old,$scope);
			if ($moniker==$old)
			{
				return $old; // hmm, ok it can stay actually
			}

			// It's not. Although, the later call to _choose_moniker will allow us to use the same stem as the current active one, or even re-activate an old deprecated one, so long as it is on this same m_resource_page/m_resource_page/m_resource_id.

			// Deprecate
			$GLOBALS['SITE_DB']->query_update('url_id_monikers',array('m_deprecated'=>1),array('m_resource_page'=>$page,'m_resource_type'=>$type,'m_resource_id'=>$id,'m_deprecated'=>0),'',1); // Deprecate

			// Deprecate anything underneath
			global $CONTENT_OBS;
			load_moniker_hooks();
			$looking_for='_SEARCH:'.$page.':'.$type.':_WILD';
			$ob_info=isset($CONTENT_OBS[$looking_for])?$CONTENT_OBS[$looking_for]:NULL;
			if (!is_null($ob_info))
			{
				$parts=explode(':',$ob_info['view_pagelink_pattern']);
				$category_page=$parts[1];
				$GLOBALS['SITE_DB']->query('UPDATE '.get_table_prefix().'url_id_monikers SET m_deprecated=1 WHERE '.db_string_equal_to('m_resource_page',$category_page).' AND m_moniker LIKE \''.db_encode_like($old.'/%').'\''); // Deprecate
			}
		}
	}

	if (is_numeric($moniker_src))
	{
		$moniker=$id;
	} else
	{
		$scope=_give_moniker_scope($page,$type,$id,'');
		$moniker=$scope._choose_moniker($page,$type,$id,$moniker_src,NULL,$scope);

		if (($page=='news') && ($type=='view') && (get_value('google_news_urls')==='1'))
			$moniker.='-'.str_pad($id,3,'0',STR_PAD_LEFT);
	}

	// Insert
	$GLOBALS['SITE_DB']->query_delete('url_id_monikers',array(	// It's possible we're re-activating a deprecated one
		'm_resource_page'=>$page,
		'm_resource_type'=>$type,
		'm_resource_id'=>$id,
		'm_moniker'=>$moniker,
	),'',1);
	$GLOBALS['SITE_DB']->query_insert('url_id_monikers',array(
		'm_resource_page'=>$page,
		'm_resource_type'=>$type,
		'm_resource_id'=>$id,
		'm_moniker'=>$moniker,
		'm_deprecated'=>0
	));

	return $moniker;
}

/**
 * Delete an old moniker, and place a new one.
 *
 * @param  ID_TEXT		Page name.
 * @param  ID_TEXT		Screen type code.
 * @param  ID_TEXT		Resource ID.
 * @param  string			String from which a moniker will be chosen (may not be blank).
 * @param  ?string		Whether to skip the exists check for a certain moniker (will be used to pass "existing self" for edits) (NULL: nothing existing to check against).
 * @param  ?string		Where the moniker will be placed in the moniker URL tree (NULL: unknown, so make so no duplicates anywhere).
 * @return string			Chosen moniker.
 */
function _choose_moniker($page,$type,$id,$moniker_src,$no_exists_check_for=NULL,$scope_context=NULL)
{
	$moniker_src=strip_comcode($moniker_src);

	$moniker=str_replace(array('ä','ö','ü','ß'),array('ae','oe','ue','ss'),$moniker_src);
	$moniker=strtolower(preg_replace('#[^A-Za-z\d\_\-]#','-',$moniker));
	if (strlen($moniker)>MAX_MONIKER_LENGTH)
	{
		$pos=strrpos(substr($moniker,0,MAX_MONIKER_LENGTH),'-');
		if (($pos===false) || ($pos<12)) $pos=MAX_MONIKER_LENGTH;
		$moniker=substr($moniker,0,$pos);
	}
	$moniker=preg_replace('#\-+#','-',$moniker);
	$moniker=rtrim($moniker,'-');
	if ($moniker=='') $moniker='untitled';

	// Check it does not already exist
	$moniker_origin=$moniker;
	$next_num=1;
	if (is_numeric($moniker)) $moniker.='_1';
	$test=mixed();
	do
	{
		if (!is_null($no_exists_check_for))
		{
			if ($moniker==preg_replace('#^.*/#','',$no_exists_check_for)) return $moniker; // This one is okay, we know it is safe
		}

		$dupe_sql='SELECT m_resource_id FROM '.get_table_prefix().'url_id_monikers WHERE ';
		$dupe_sql.=db_string_equal_to('m_resource_page',$page).' AND '.db_string_equal_to('m_resource_type',$type).' AND '.db_string_not_equal_to('m_resource_id',$id);
		$dupe_sql.=' AND (';
		if (!is_null($scope_context))
		{
			$dupe_sql.=db_string_equal_to('m_moniker',$scope_context.$moniker);
		} else
		{
			$dupe_sql.=db_string_equal_to('m_moniker',$moniker);
			$dupe_sql.=' OR m_moniker LIKE \''.db_encode_like('%/'.$moniker).'\'';
		}
		$dupe_sql.=')';
		$test=$GLOBALS['SITE_DB']->query_value_null_ok_full($dupe_sql);
		if (!is_null($test)) // Oh dear, will pass to next iteration, but trying a new moniker
		{
			$next_num++;
			$moniker=$moniker_origin.'_'.strval($next_num);
		}
	}
	while (!is_null($test));

	return $moniker;
}

/**
 * Take a moniker and it's page link details, and make a full path from it.
 *
 * @param  ID_TEXT		Page name.
 * @param  ID_TEXT		Screen type code.
 * @param  ID_TEXT		Resource ID.
 * @param  string			Pathless moniker.
 * @return string			The fully qualified moniker.
 */
function _give_moniker_scope($page,$type,$id,$main)
{
	// Does this URL arrangement support monikers?
	global $CONTENT_OBS;
	load_moniker_hooks();
	$found=false;
	$looking_for='_SEARCH:'.$page.':'.$type.':_WILD';

	$ob_info=isset($CONTENT_OBS[$looking_for])?$CONTENT_OBS[$looking_for]:NULL;

	$moniker=$main;

	if (is_null($ob_info)) return $moniker;

	if (!is_null($ob_info['parent_category_field']))
	{
		// Lookup DB record so we can discern the category
		$bak=$GLOBALS['NO_DB_SCOPE_CHECK'];
		$GLOBALS['NO_DB_SCOPE_CHECK']=true;
		$select=array($ob_info['id_field']);
		if (substr($ob_info['title_field'],0,5)!='CALL:') $select[]=$ob_info['title_field'];
		if (!is_null($ob_info['parent_category_field'])) $select[]=$ob_info['parent_category_field'];
		$id_flat=mixed();
		if ($ob_info['id_field_numeric'])
		{
			$id_flat=intval($id);
		} else
		{
			$id_flat=$id;
		}
		$_moniker_src=$GLOBALS['SITE_DB']->query_select($ob_info['table'],$select,array($ob_info['id_field']=>$id_flat));
		$GLOBALS['NO_DB_SCOPE_CHECK']=$bak;
		if (!array_key_exists(0,$_moniker_src)) return $moniker; // been deleted?

		// Discern the path (will effectively recurse, due to find_id_moniker call)
		$view_category_pagelink_pattern=explode(':',$ob_info['view_category_pagelink_pattern']);
		$parent=$_moniker_src[0][$ob_info['parent_category_field']];
		if (is_integer($parent)) $parent=strval($parent);
		if ((is_null($parent)) || ($parent==='root') || ($parent==='') || ($parent==strval(db_get_first_id())))
		{
			$tree=NULL;
		} else
		{
			$tree=find_id_moniker(array('page'=>$view_category_pagelink_pattern[1],'type'=>$view_category_pagelink_pattern[2],'id'=>$parent));
		}

		// Okay, so our full tree path is as follows
		if (!is_null($tree))
		{
			$moniker=$tree.'/'.$main;
		}
	}

	return $moniker;
}
