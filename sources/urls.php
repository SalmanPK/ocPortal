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
function init__urls()
{
	global $HTTPS_PAGES;
	$HTTPS_PAGES=NULL;

	global $CHR_0;
	$CHR_0=chr(10);

	global $USE_REWRITE_PARAMS;
	$USE_REWRITE_PARAMS=NULL;

	global $HAS_KEEP_IN_URL;
	$HAS_KEEP_IN_URL=NULL;

	global $URL_REMAPPINGS;
	$URL_REMAPPINGS=NULL;

	global $CONTENT_OBS,$LOADED_MONIKERS;
	$CONTENT_OBS=NULL;
	$LOADED_MONIKERS=array();

	global $SELF_URL_CACHED;
	$SELF_URL_CACHED=NULL;

	define('SELF_REDIRECT','!--:)defUNLIKELY');
}

/**
 * Get a well formed URL equivalent to the current URL. Reads direct from the environment and does no clever mapping at all. This function should rarely be used.
 *
 * @return URLPATH		The URL
 */
function get_self_url_easy()
{
	$protocol=tacit_https()?'https':'http';
	if (!isset($_SERVER['HTTP_HOST']))
	{
		$domain=get_domain();
	} else
	{
		$domain=$_SERVER['HTTP_HOST'];
	}
	$colon_pos=strpos($domain,':');
	if ($colon_pos!==false) $domain=substr($domain,0,$colon_pos);
	$self_url=$protocol.'://'.$domain;
	$port=ocp_srv('SERVER_PORT');
	if (($port!='') && ($port!='80')) $self_url.=':'.$port;
	$s=ocp_srv('PHP_SELF');
	if (substr($s,0,1)!='/') $self_url.='/';
	$self_url.=$s;
	if ((array_key_exists('QUERY_STRING',$_SERVER)) && ($_SERVER['QUERY_STRING']!='')) $self_url.='?'.$_SERVER['QUERY_STRING'];
	return $self_url;
}

/**
 * Get a well formed URL equivalent to the current URL.
 *
 * @param  boolean		Whether to evaluate the URL (so as we don't return tempcode)
 * @param  boolean		Whether to direct to the default page if there was a POST request leading to where we are now (i.e. to avoid missing post fields when we go to this URL)
 * @param  ?array			A map of extra parameters for the URL (NULL: none)
 * @param  boolean		Whether to also keep POSTed data, in the GET request (useful if either_param is used to get the data instead of post_param - of course the POST data must be of the not--persistant-state-changing variety)
 * @param  boolean		Whether to avoid mod_rewrite (sometimes essential so we can assume the standard URL parameter addition scheme in templates)
 * @return mixed			The URL (tempcode or string)
 */
function get_self_url($evaluate=false,$root_if_posted=false,$extra_params=NULL,$posted_too=false,$avoid_remap=false)
{
	global $SELF_URL_CACHED;
	$cacheable=($evaluate) && (!$root_if_posted) && ($extra_params===NULL) && (!$posted_too) && (!$avoid_remap);
	if (($cacheable) && ($SELF_URL_CACHED!==NULL))
	{
		return $SELF_URL_CACHED;
	}

	if ((isset($_SERVER['PHP_SELF'])) || (isset($_ENV['PHP_SELF'])))
	{
		if (running_script('execute_temp'))
		{
			return get_self_url_easy();
		}
	}

	if ($extra_params===NULL) $extra_params=array();
	if ($posted_too)
	{
		$post_array=array();
		foreach ($_POST as $key=>$val)
		{
			if (is_array($val)) continue;
			if (get_magic_quotes_gpc()) $val=stripslashes($val);
			$post_array[$key]=$val;
		}
		$extra_params=array_merge($post_array,$extra_params);
	}
	$page='_SELF';
	if (($root_if_posted) && (count($_POST)!=0)) $page='';
	$params=array('page'=>$page);
	foreach ($extra_params as $key=>$val)
	{
		if ($val===NULL)
		{
			unset($params[$key]);
		}
		$params[$key]=$val;
	}

	$url=build_url($params,'_SELF',NULL,true,$avoid_remap);
	if ($evaluate)
	{
		$ret=$url->evaluate();
		if ($cacheable)
		{
			$SELF_URL_CACHED=$ret;
		}
		return $ret;
	}

	return $url;
}

/**
 * Encode a URL component in such a way that it won't get nuked by Apache %2F blocking security and url encoded '&' screwing. The get_param function will map it back. Hackerish but necessary.
 *
 * @param  URLPATH		The URL to encode
 * @param  ?boolean		Whether we have to consider mod_rewrite (NULL: don't know, look up)
 * @return URLPATH		The encoded result
 */
function ocp_url_encode($url_part,$consider_rewrite=NULL)
{
	if ($consider_rewrite===NULL) $consider_rewrite=can_try_mod_rewrite();
	if ($consider_rewrite) // These interfere with mod_rewrite processing because they get pre-decoded and make things ambiguous
	{
//		$url_part=str_replace(':','(colon)',$url_part); We'll ignore theoretical problem here- we won't expect there to be a need for encodings within redirect URL paths (params is fine, handles naturally)
//		$url_part=str_replace(urlencode(':'),urlencode('(colon)'),$url_part); // horrible but mod_rewrite does it so we need to
//		$url_part=str_replace(urlencode('/'),urlencode(':slash:'),$url_part); // horrible but mod_rewrite does it so we need to
//		$url_part=str_replace(urlencode('&'),urlencode(':amp:'),$url_part); // horrible but mod_rewrite does it so we need to
//		$url_part=str_replace(urlencode('#'),urlencode(':uhash:'),$url_part); // horrible but mod_rewrite does it so we need to
		$url_part=str_replace(array('/','&','#'),array(':slash:',':amp:',':uhash:'),$url_part);
	}
	$url_part=urlencode($url_part);
	return $url_part;
}

/**
 * Encode a URL component, as per ocp_url_encode but without slashes being encoded.
 *
 * @param  URLPATH		The URL to encode
 * @param  ?boolean		Whether we have to consider mod_rewrite (NULL: don't know, look up)
 * @return URLPATH		The encoded result
 */
function ocp_url_encode_mini($url_part,$consider_rewrite=NULL)
{
	return str_replace('%3Aslash%3A','/',ocp_url_encode($url_part,$consider_rewrite));
}

/**
 * Decode a URL component that was encoded with hackerish_url_encode
 *
 * @param  URLPATH		The URL to encode
 * @return URLPATH		The encoded result
 */
function ocp_url_decode_post_process($url_part)
{
	if ((strpos($url_part,':')!==false) && (can_try_mod_rewrite()))
	{
		$url_part=str_replace(':uhash:','#',$url_part);
		$url_part=str_replace(':amp:','&',$url_part);
		$url_part=str_replace(':slash:','/',$url_part);
//		$url_part=str_replace('(colon)',':',$url_part);
	}
	return $url_part;
}

/**
 * Map spaces to %20 and put http:// in front of URLs starting www.
 *
 * @param  URLPATH		The URL to fix
 * @return URLPATH		The fixed result
 */
function remove_url_mistakes($url)
{
	if (substr($url,0,4)=='www.') $url='http://'.$url;
	$url=@html_entity_decode($url,ENT_NOQUOTES);
	$url=str_replace(' ','%20',$url);
	$url=preg_replace('#keep_session=\d*#','filtered=1',$url);
	return $url;
}

/**
 * Find whether we can skip the normal preservation of a keep value, for whatever reason.
 *
 * @param  string			Parameter name
 * @param  string			Parameter value
 * @return boolean		Whether we can skip it
 */
function skippable_keep($key,$val)
{
	global $CACHE_BOT_TYPE;
	if ($CACHE_BOT_TYPE===false) get_bot_type();
	if ($CACHE_BOT_TYPE!==NULL)
	{
		return true;
	}

	return ((($key=='keep_session') && (isset($_COOKIE['has_cookies']))) || (($key=='keep_has_js') && ($val=='1'))) && ((isset($_COOKIE['js_on'])) || (get_option('detect_javascript')=='0'));
}

/**
 * Are we currently running HTTPS.
 *
 * @return boolean		If we are
 */
function tacit_https()
{
	static $tacit_https=NULL;
	if ($tacit_https===NULL)
	{
		$tacit_https=((ocp_srv('HTTPS')!='') && (ocp_srv('HTTPS')!='off')) || (ocp_srv('HTTP_X_FORWARDED_PROTO')=='https');
	}
	return $tacit_https;
}

/**
 * Find whether the specified page is to use HTTPS (if not -- it will use HTTP).
 * All images (etc) on a HTTPS page should use HTTPS to avoid mixed-content browser notices.
 *
 * @param  ID_TEXT		The zone the page is in
 * @param  ID_TEXT		The page codename
 * @return boolean		Whether the page is to run across an HTTPS connection
 */
function is_page_https($zone,$page)
{
	if (get_option('enable_https',true)=='0') return false;
	if (in_safe_mode()) return false;

	if (($page=='login') && (get_page_name()=='login')) // Because how login can be called from any arbitrary page, which may or may not be on HTTPS. We want to maintain HTTPS if it is there to avoid warning on form submission
	{
		if (tacit_https()) return true;
	}

	global $HTTPS_PAGES;
	if (($HTTPS_PAGES===NULL) && (function_exists('persistant_cache_get')))
		$HTTPS_PAGES=persistant_cache_get('HTTPS_PAGES');
	if ($HTTPS_PAGES===NULL)
	{
		$results=$GLOBALS['SITE_DB']->query('SELECT * FROM '.get_table_prefix().'https_pages',NULL,NULL,true);
		if (($results===false) || ($results===NULL)) // No HTTPS support (probably not upgraded yet)
		{
			$HTTPS_PAGES=array();
			return false;
		}
		$HTTPS_PAGES=collapse_1d_complexity('https_page_name',$results);
		if (function_exists('persistant_cache_set'))
			persistant_cache_set('HTTPS_PAGES',$HTTPS_PAGES);
	}
	return in_array($zone.':'.$page,$HTTPS_PAGES);
}

/**
 * Find if mod_rewrite is in use
 *
 * @param  boolean		Whether to explicitly avoid using mod_rewrite. Whilst it might seem weird to put this in as a function parameter, it removes duplicated logic checks in the code.
 * @return boolean		Whether mod_rewrite is in use
 */
function can_try_mod_rewrite($avoid_remap=false)
{
	return ((get_option('mod_rewrite',false)==='1') && ((!array_key_exists('block_mod_rewrite',$GLOBALS['SITE_INFO'])) || ($GLOBALS['SITE_INFO']['block_mod_rewrite']=='0')) && (!$avoid_remap) && ((get_value('ionic_on','0')=='1') || (ocp_srv('SERVER_SOFTWARE')=='') || (substr(ocp_srv('SERVER_SOFTWARE'),0,6)=='Apache') || (substr(ocp_srv('SERVER_SOFTWARE'),0,5)=='IIS7/') || (substr(ocp_srv('SERVER_SOFTWARE'),0,5)=='IIS8/') || (substr(ocp_srv('SERVER_SOFTWARE'),0,10)=='LiteSpeed'))); // If we don't have the option on or are not using apache, return
}

/**
 * Build and return a proper URL, from the $vars array.
 * Note: URL parameters should always be in lower case (one of the coding standards)
 *
 * @param  array			A map of parameter names to parameter values. E.g. array('page'=>'example','type'=>'foo','id'=>2). Values may be strings or integers, or Tempcode, or NULL. NULL indicates "skip this". 'page' cannot be NULL.
 * @param  ID_TEXT		The zone the URL is pointing to. YOU SHOULD NEVER HARD CODE THIS- USE '_SEARCH', '_SELF' (if you're self-referencing your own page) or the output of get_module_zone.
 * @param  ?array			Variables to explicitly not put in the URL (perhaps because we have $keep_all set, or we are blocking certain keep_ values). The format is of a map where the keys are the names, and the values are 1. (NULL: don't skip any)
 * @param  boolean		Whether to keep all non-skipped parameters that were in the current URL, in this URL
 * @param  boolean		Whether to avoid mod_rewrite (sometimes essential so we can assume the standard URL parameter addition scheme in templates)
 * @param  boolean		Whether to skip actually putting on keep_ parameters (rarely will this skipping be desirable)
 * @param  string			Hash portion of the URL (blank: none). May or may not start '#' - code will put it on if needed
 * @return tempcode		The URL in tempcode format.
 */
function build_url($vars,$zone_name='',$skip=NULL,$keep_all=false,$avoid_remap=false,$skip_keep=false,$hash='')
{
	if (empty($vars['page'])) // For SEO purposes we need to make sure we get the right URL
	{
		$vars['page']=get_zone_default_page($zone_name);
		if ($vars['page']===NULL) $vars['page']='start';
	}

	$id=isset($vars['id'])?$vars['id']:NULL;

	$page_link=make_string_tempcode($zone_name.':'./*urlencode not needed in reality, performance*/($vars['page']));
	if ((isset($vars['type'])) || (array_key_exists('type',$vars)))
	{
		if (is_object($vars['type']))
		{
			$page_link->attach(':');
			$page_link->attach($vars['type']);
		} else
		{
			$page_link->attach(':'.(($vars['type']===NULL)?'<null>':urlencode($vars['type'])));
		}
		unset($vars['type']);
		if ((isset($id)) || (array_key_exists('id',$vars)))
		{
			if (is_integer($id))
			{
				$page_link->attach(':'.strval($id));
			} elseif (is_object($id))
			{
				$page_link->attach(':');
				$page_link->attach($id);
			} else
			{
				$page_link->attach(':'.(($id===NULL)?'<null>':urlencode($id)));
			}
			unset($vars['id']);
		}
	}

	foreach ($vars as $key=>$val)
	{
		if (!is_string($key)) $key=strval($key);
		if (is_integer($val)) $val=strval($val);
		if ($val===NULL) $val='<null>';

		if ($key!='page')
		{
			if (is_object($val))
			{
				$page_link->attach(':'.$key.'=');
				$page_link->attach($val);
			} else
			{
				$page_link->attach(':'.$key.'='.(($val===NULL)?'<null>':urlencode($val)));
			}
		}
	}

	if (($hash!='') && (substr($hash,0,1)!='#')) $hash='#'.$hash;

	$page_link->attach($hash);

	$arr=array(
		$page_link,
		$avoid_remap?'1':'0',
		$skip_keep?'1':'0',
		$keep_all?'1':'0'
	);
	if ($skip!==NULL) $arr[]=implode('|',array_keys($skip));

	$ret=symbol_tempcode('PAGE_LINK',$arr);
	global $SITE_INFO;
	if ((isset($SITE_INFO['no_keep_params'])) && ($SITE_INFO['no_keep_params']=='1') && (!is_numeric($id)/*i.e. not going to trigger a URL moniker query*/))
	{
		$ret=make_string_tempcode($ret->evaluate());
	}
	return $ret;
}

/**
 * Find whether URL monikers are enabled.
 *
 * @return boolean		Whether URL monikers are enabled.
 */
function url_monikers_enabled()
{
	if (get_param_integer('keep_simpleurls',0)==1) return false;
	if (get_option('url_monikers_enabled',true)!=='1') return false;
	return true;
}

/**
 * Build and return a proper URL, from the $vars array.
 * Note: URL parameters should always be in lower case (one of the coding standards)
 *
 * @param  array			A map of parameter names to parameter values. Values may be strings or integers, or NULL. NULL indicates "skip this". 'page' cannot be NULL.
 * @param  ID_TEXT		The zone the URL is pointing to. YOU SHOULD NEVER HARD CODE THIS- USE '_SEARCH', '_SELF' (if you're self-referencing your own page) or the output of get_module_zone.
 * @param  ?array			Variables to explicitly not put in the URL (perhaps because we have $keep_all set, or we are blocking certain keep_ values). The format is of a map where the keys are the names, and the values are 1. (NULL: don't skip any)
 * @param  boolean		Whether to keep all non-skipped parameters that were in the current URL, in this URL
 * @param  boolean		Whether to avoid mod_rewrite (sometimes essential so we can assume the standard URL parameter addition scheme in templates)
 * @param  boolean		Whether to skip actually putting on keep_ parameters (rarely will this skipping be desirable)
 * @param  string			Hash portion of the URL (blank: none).
 * @return string			The URL in string format.
 */
function _build_url($vars,$zone_name='',$skip=NULL,$keep_all=false,$avoid_remap=false,$skip_keep=false,$hash='')
{
	global $HAS_KEEP_IN_URL,$USE_REWRITE_PARAMS,$CACHE_BOT_TYPE;

	// Build up our URL base
	$url=get_base_url(is_page_https($zone_name,isset($vars['page'])?$vars['page']:''),$zone_name);
	$url.='/';

	// For bots we explicitly unset skippable injected 'keep_' params because it bloats the crawl-space
	if (($CACHE_BOT_TYPE!==NULL) && (get_bot_type()!==NULL))
	{
		foreach ($vars as $key=>$val)
		{
			if (($key=='redirect') || ($key=='root')) unset($vars[$key]);
			if ((substr($key,0,5)=='keep_') && (skippable_keep($key,$val))) unset($vars[$key]);
		}
	}

	// Things we need to keep in the url
	$keep_actual=array();
	if (($HAS_KEEP_IN_URL===NULL) || ($HAS_KEEP_IN_URL) || ($keep_all))
	{
		$mc=get_magic_quotes_gpc();

		$keep_cant_use=array();
		$HAS_KEEP_IN_URL=false;
		foreach ($_GET as $key=>$val)
		{
			if (!is_string($val)) continue;

			$is_keep=false;
			$appears_keep=(($key[0]=='k') && (substr($key,0,5)=='keep_'));
			if ($appears_keep)
			{
				if ((!$skip_keep) && (!skippable_keep($key,$val)))
					$is_keep=true;
				$HAS_KEEP_IN_URL=true;
			}
			if (((($keep_all) && (!$appears_keep)) || ($is_keep)) && (!array_key_exists($key,$vars)) && (!isset($skip[$key])))
			{
				if ($mc) $val=stripslashes($val);
				if ($is_keep) $keep_actual[$key]=$val;
				else $vars[$key]=$val;
			} elseif ($is_keep)
			{
				if ($mc) $val=stripslashes($val);
				$keep_cant_use[$key]=$val;
			}
		}

		$vars+=$keep_actual;
	}

	global $URL_MONIKERS_ENABLED;
	if ($URL_MONIKERS_ENABLED===NULL) $URL_MONIKERS_ENABLED=url_monikers_enabled();
	if ($URL_MONIKERS_ENABLED)
	{
		$test=find_id_moniker($vars);
		if ($test!==NULL) $vars['id']=$test;
	}

	// We either use mod_rewrite, or return a standard parameterisation
	if (($USE_REWRITE_PARAMS===NULL) || ($avoid_remap))
	{
		$use_rewrite_params=can_try_mod_rewrite($avoid_remap);
		if (!$avoid_remap) $USE_REWRITE_PARAMS=$use_rewrite_params;
	} else $use_rewrite_params=$USE_REWRITE_PARAMS;
	$test_rewrite=NULL;
	if ($use_rewrite_params) $test_rewrite=_url_rewrite_params($zone_name,$vars,count($keep_actual)>0); else $test_rewrite=NULL;
	if ($test_rewrite===NULL)
	{
		$url.='index.php';

		// Fix sort order
		if (isset($vars['id']))
		{
			$_vars=$vars;
			unset($_vars['id']);
			$vars=array('id'=>$vars['id'])+$_vars;
		}
		if (isset($vars['type']))
		{
			$_vars=$vars;
			unset($_vars['type']);
			$vars=array('type'=>$vars['type'])+$_vars;
		}
		if (isset($vars['page']))
		{
			$_vars=$vars;
			unset($_vars['page']);
			$vars=array('page'=>$vars['page'])+$_vars;
		}

		// Build up the URL string
		$symbol='?';
		foreach ($vars as $key=>$val)
		{
			if ($val===NULL) continue; // NULL means skip

			if ($val===SELF_REDIRECT) $val=get_self_url(true,true);

			// Add in
			$url.=$symbol.$key.'='.(is_integer($val)?strval($val):/*ocp_*/urlencode($val/*,false*/));
			$symbol='&';
		}
	} else
	{
		$url.=$test_rewrite;
	}

	// Done
	return $url.$hash;
}

/**
 * Attempt to use mod_rewrite to improve this URL.
 *
 * @param  ID_TEXT		The name of the zone for this
 * @param  array			A map of variables to include in our URL
 * @param  boolean		Force inclusion of the index.php name into a short URL, so something may tack on extra parameters to the result here
 * @return ?URLPATH		The improved URL (NULL: couldn't do anything)
 */
function _url_rewrite_params($zone_name,$vars,$force_index_php=false)
{
	global $URL_REMAPPINGS;
	if ($URL_REMAPPINGS===NULL)
	{
		$old_style=get_option('htm_short_urls')!='1';
		require_code('url_remappings');
		$URL_REMAPPINGS=get_remappings($old_style);
	}

	// Find mapping
	foreach ($URL_REMAPPINGS as $_remapping)
	{
		list($remapping,$target,$require_full_coverage)=$_remapping;
//		if (count($remapping)!=$num_vars) continue;
		$good=true;

		$last_key_num=count($remapping);
		$loop_cnt=0;
		foreach ($remapping as $key=>$val)
		{
			$loop_cnt++;
			$last=($loop_cnt==$last_key_num);

			if ((!is_string($val)) && ($val!==NULL)) $val=strval($val);
			if ((array_key_exists($key,$vars)) && (is_integer($vars[$key]))) $vars[$key]=strval($vars[$key]);

			if (!(((isset($vars[$key])) || (($val===NULL) && ($key=='type') && (array_key_exists('id',$vars)))) && (($key!='page') || ($vars[$key]!='') || ($val==='')) && ((!array_key_exists($key,$vars)/*NB this is just so the next clause does not error, we have other checks for non-existence*/) || ($vars[$key]!='') || (!$last)) && (($val===NULL) || ($vars[$key]==$val))))
			{
				$good=false;
				break;
			}
		}

		if ($require_full_coverage)
		{
			foreach ($_GET as $key=>$val)
			{
				if (!is_string($val)) continue;

				if ((substr($key,0,5)=='keep_')  && (!skippable_keep($key,$val))) $good=false;
			}
			foreach ($vars as $key=>$val)
			{
				if ((!array_key_exists($key,$remapping)) && ($val!==NULL) && (($key!='page') || ($vars[$key]!='')))
					$good=false;
			}
		}
		if ($good)
		{
			// We've found one, now let's sort out the target
			$makeup=$target;
			if ($GLOBALS['DEBUG_MODE'])
			{
				foreach ($vars as $key=>$val)
				{
					if (is_integer($val)) $vars[$key]=strval($val);
				}
			}

			$extra_vars=array();
			foreach (array_keys($remapping) as $key)
			{
				if (!isset($vars[$key])) continue;

				$val=$vars[$key];
				unset($vars[$key]);

				$makeup=str_replace(strtoupper($key),ocp_url_encode_mini($val,true),$makeup);
			}
			if (!$require_full_coverage)
			{
				$extra_vars+=$vars;
			}
			$makeup=str_replace('TYPE','misc',$makeup);
			if ($makeup=='')
			{
				$makeup.=get_zone_default_page($zone_name).'.htm';
			}
			if (($extra_vars!=array()) || ($force_index_php))
			{
				if (get_option('htm_short_urls')!='1') $makeup.='/index.php';

				$first=true;
				foreach ($extra_vars as $key=>$val) // Add these in explicitly
				{
					if ($val===NULL) continue;
					if (is_integer($key)) $key=strval($key);
					if ($val===SELF_REDIRECT) $val=get_self_url(true,true);
					$makeup.=($first?'?':'&').$key.'='.ocp_url_encode($val,true);
					$first=false;
				}
			}

			return $makeup;
		}
	}

	return NULL;
}

/**
 * Find if the specified URL is local or not (actually, if it is relative). This is often used by code that wishes to use file system functions on URLs (ocPortal will store such relative local URLs for uploads, etc)
 *
 * @param  URLPATH		The URL to check
 * @return boolean		Whether the URL is local
 */
function url_is_local($url)
{
	if (preg_match('#^[^:\{%]*$#',$url)!=0) return true;
	return (strpos($url,'://')===false) && (substr($url,0,1)!='{') && (substr($url,0,7)!='mailto:') && (substr($url,0,5)!='data:') && (substr($url,0,1)!='%') && (strpos($url,'{$BASE_URL')===false) && (strpos($url,'{$FIND_SCRIPT')===false);
}

/**
 * Find if a value appears to be some kind of URL (possibly an ocPortalised Comcode one).
 *
 * @param  string			The value to check
 * @param  boolean		Whether to be a bit lax in the check
 * @return boolean		Whether the value appears to be a URL
 */
function looks_like_url($value,$lax=false)
{
	if (($lax) && (strpos($value,'/')!==false)) return true;
	if (($lax) && (substr($value,0,1)=='%')) return true;
	if (($lax) && (substr($value,0,1)=='{')) return true;
	return (((strpos($value,'.php')!==false) || (strpos($value,'.htm')!==false) || (substr($value,0,1)=='#') || (substr($value,0,13)=='{$FIND_SCRIPT') || (substr($value,0,10)=='{$BASE_URL') || (substr(strtolower($value),0,11)=='javascript:') || (substr($value,0,4)=='tel:') || (substr($value,0,7)=='mailto:') || (substr($value,0,7)=='http://') || (substr($value,0,8)=='https://') || (substr($value,0,3)=='../') || (substr($value,0,7)=='sftp://') || (substr($value,0,6)=='ftp://'))) && (strpos($value,'<')===false);
}

/**
 * Get hidden fields for a form representing 'keep_x'. If we are having a GET form instead of a POST form, we need to do this. This function also encodes the page name, as we'll always want that.
 *
 * @param  ID_TEXT		The page for the form to go to (blank: don't attach)
 * @param  boolean		Whether to keep all elements of the current URL represented in this form (rather than just the keep_ fields, and page)
 * @param  ?array			A list of parameters to exclude (NULL: don't exclude any)
 * @return tempcode		The builtup hidden form fields
 */
function build_keep_form_fields($page='',$keep_all=false,$exclude=NULL)
{
	require_code('urls2');
	return _build_keep_form_fields($page,$keep_all,$exclude);
}

/**
 * Relay all POST variables for this URL, to the URL embedded in the form.
 *
 * @param  ?array			A list of parameters to exclude (NULL: exclude none)
 * @return tempcode		The builtup hidden form fields
 */
function build_keep_post_fields($exclude=NULL)
{
	require_code('urls2');
	return _build_keep_post_fields($exclude);
}

/**
 * Takes a URL, and converts it into a file system storable filename. This is used to cache URL contents to the servers filesystem.
 *
 * @param  URLPATH		The URL to convert to an encoded filename
 * @return string			A usable filename based on the URL
 */
function url_to_filename($url_full)
{
	require_code('urls2');
	return _url_to_filename($url_full);
}

/**
 * Take a URL and base-URL, and fully qualify the URL according to it.
 *
 * @param  URLPATH		The URL to fully qualified
 * @param  URLPATH		The base-URL
 * @return URLPATH		Fully qualified URL
 */
function qualify_url($url,$url_base)
{
	require_code('urls2');
	return _qualify_url($url,$url_base);
}

/**
 * Take a page link and convert to attributes and zone.
 *
 * @param  SHORT_TEXT	The page link
 * @return array			Triple: zone, attribute-array, hash part of a URL including the hash (or blank)
 */
function page_link_decode($param)
{
	global $CHR_0;

	if (strpos($param,'#')===false)
	{
		$hash='';
	} else
	{
		$hash_pos=strpos($param,'#');
		$hash=substr($param,$hash_pos);
		$param=substr($param,0,$hash_pos);
	}
	if (strpos($param,$CHR_0)===false)
	{
		$bits=explode(':',$param);
	} else // If there's a line break then we ignore any colons after that line-break. It's to allow complex stuff to be put on the end of the page-link
	{
		$term_pos=strpos($param,$CHR_0);
		$bits=explode(':',substr($param,0,$term_pos));
		$bits[count($bits)-1].=substr($param,$term_pos);
	}
	$zone=$bits[0];
	if ($zone=='_SEARCH')
	{
		if (isset($bits[1]))
		{
			$zone=get_page_zone($bits[1],false);
			if ($zone===NULL) $zone='';
		} else $zone='';
	} elseif (($zone=='site') && (get_option('collapse_user_zones')=='1')) $zone='';
	elseif ($zone=='_SELF') $zone=get_zone_name();
	if (isset($bits[1]))
	{
		if ($bits[1]!='')
		{
			if ($bits[1]=='_SELF')
			{
				$attributes=array('page'=>get_page_name());
			} else
			{
				$attributes=array('page'=>$bits[1]);
			}
		} else
		{
			$attributes=array();
		}
		unset($bits[1]);
	} else
	{
		$attributes=array('page'=>get_zone_default_page($zone));
	}
	unset($bits[0]);
	$i=0;
	foreach ($bits as $bit)
	{
		if (($bit!='') || ($i==1))
		{
			if (($i==0) && (strpos($bit,'=')===false))
			{
				$_bit=array('type',$bit);
			}
			elseif (($i==1) && (strpos($bit,'=')===false))
			{
				$_bit=array('id',$bit);
			} else
			{
				$_bit=explode('=',$bit,2);
			}
		} else
		{
			$_bit=array($bit,'');
		}
		if (isset($_bit[1]))
		{
			$decoded=urldecode($_bit[1]);
			if (($decoded!='') && ($decoded[0]=='{') && (strlen($decoded)>2) && (intval($decoded[1])>51)) // If it is in template format (symbols)
			{
				require_code('tempcode_compiler');
				$decoded=template_to_tempcode($decoded);
				$decoded=$decoded->evaluate();
			}
			if ($decoded=='<null>')
			{
				$attributes[$_bit[0]]=NULL;
			} else
			{
				$attributes[$_bit[0]]=$decoded;
			}
		}

		$i++;
	}

	return array($zone,$attributes,$hash);
}

/**
 * Convert a URL to a local file path.
 *
 * @param  URLPATH		The value to convert
 * @return ?PATH			File path (NULL: is not local)
 */
function convert_url_to_path($url)
{
	require_code('urls2');
	return _convert_url_to_path($url);
}

/**
 * Sometimes users don't enter full URLs but do intend for them to be absolute. This code tries to see what relative URLs are actually absolute ones, via an algorithm. It then fixes the URL.
 *
 * @param  URLPATH		The URL to fix
 * @return URLPATH		The fixed URL (or original one if no fix was needed)
 */
function fixup_protocolless_urls($in)
{
	require_code('urls2');
	return _fixup_protocolless_urls($in);
}

/**
 * Convert a local URL to a page-link.
 *
 * @param  URLPATH		The URL to convert. Note it may not be a short URL, and it must be based on the local base URL (else failure WILL occur).
 * @param  boolean		Whether to only convert absolute URLs. Turn this on if you're not sure what you're passing is a URL not and you want to be extra safe.
 * @param  boolean		Whether to only allow perfect conversions.
 * @return string			The page link (blank: could not convert).
 */
function url_to_pagelink($url,$abs_only=false,$perfect_only=true)
{
	require_code('urls2');
	return _url_to_pagelink($url,$abs_only,$perfect_only);
}

/**
 * Convert a local page file path to a written page-link.
 *
 * @param  string			The path.
 * @return string			The page link (blank: could not convert).
 */
function page_path_to_pagelink($page)
{
	require_code('urls2');
	return _page_path_to_pagelink($page);
}

/**
 * Load up hooks needed to detect how to use monikers.
 */
function load_moniker_hooks()
{
	global $CONTENT_OBS;
	if ($CONTENT_OBS===NULL)
	{
		$CONTENT_OBS=function_exists('persistant_cache_get')?persistant_cache_get('CONTENT_OBS'):NULL;
		if ($CONTENT_OBS!==NULL)
		{
			foreach ($CONTENT_OBS as $ob_info)
			{
				if (($ob_info['title_field']!==NULL) && (strpos($ob_info['title_field'],'CALL:')!==false))
					require_code('hooks/systems/content_meta_aware/'.$ob_info['_hook']);
			}

			return;
		}

		$CONTENT_OBS=array();
		$hooks=find_all_hooks('systems','content_meta_aware');
		foreach ($hooks as $hook=>$sources_dir)
		{
			$info_function=extract_module_functions(get_file_base().'/'.$sources_dir.'/hooks/systems/content_meta_aware/'.$hook.'.php',array('info'));
			if ($info_function[0]!==NULL)
			{
				$ob_info=is_array($info_function[0])?call_user_func_array($info_function[0][0],$info_function[0][1]):eval($info_function[0]);
				if ($ob_info===NULL) continue;
				$ob_info['_hook']=$hook;
				$CONTENT_OBS[$ob_info['view_pagelink_pattern']]=$ob_info;

				if (($ob_info['title_field']!==NULL) && (strpos($ob_info['title_field'],'CALL:')!==false))
					require_code('hooks/systems/content_meta_aware/'.$hook);
			}
		}

		if (function_exists('persistant_cache_set')) persistant_cache_set('CONTENT_OBS',$CONTENT_OBS);
	}
}

/**
 * Find the textual moniker for a typical ocPortal URL path. This will be called from inside build_url, based on details learned from a moniker hook (only if a hook exists to hint how to make the requested link SEO friendly).
 *
 * @param  array			The URL component map (must contain 'page', 'type', and 'id' if this function is to do anything).
 * @return ?string		The moniker ID (NULL: could not find)
 */
function find_id_moniker($url_parts)
{
	if (!isset($url_parts['id'])) return NULL;
	if (!isset($url_parts['type'])) $url_parts['type']='misc';
	if ($url_parts['type']===NULL) $url_parts['type']='misc'; // NULL means "do not take from environment"; so we default it to 'misc' (even though it might actually be left out when SEO URLs are off, we know it cannot be for SEO URLs)
	if (!isset($url_parts['page'])) return NULL;
	if ($url_parts['id']===NULL) $url_parts['id']=/*get_param('id',*/strval(db_get_first_id())/*)*/;
	if (!is_numeric($url_parts['id'])) return NULL;

	// Does this URL arrangement support monikers?
	global $CONTENT_OBS;
	load_moniker_hooks();
	$looking_for='_SEARCH:'.$url_parts['page'].':'.$url_parts['type'].':_WILD';
	$ob_info=isset($CONTENT_OBS[$looking_for])?$CONTENT_OBS[$looking_for]:NULL;

	if ($ob_info===NULL) return NULL;

	if ($ob_info['support_url_monikers'])
	{
	   // Has to find existing if already there
		global $LOADED_MONIKERS;
		if (isset($LOADED_MONIKERS[$url_parts['page']][$url_parts['type']][$url_parts['id']]))
		{
			if (is_bool($LOADED_MONIKERS[$url_parts['page']][$url_parts['type']][$url_parts['id']])) // Ok, none pre-loaded yet, so we preload all and replace the boolean values with actual results
			{
				$or_list='';
				foreach ($LOADED_MONIKERS as $page=>$types)
				{
					foreach ($types as $type=>$ids)
					{
						foreach ($ids as $id=>$status)
						{
							if (!is_bool($status)) continue;

							if (is_integer($id)) $id=strval($id);

							if ($or_list!='') $or_list.=' OR ';
							$or_list.='('.db_string_equal_to('m_resource_page',$page).' AND '.db_string_equal_to('m_resource_type',$type).' AND '.db_string_equal_to('m_resource_id',$id).')';

							$LOADED_MONIKERS[$page][$type][$id]=$id; // Will be replaced with correct value if it is looked up
						}
					}
				}
				$bak=$GLOBALS['NO_DB_SCOPE_CHECK'];
				$GLOBALS['NO_DB_SCOPE_CHECK']=true;
				$query='SELECT m_moniker,m_resource_page,m_resource_type,m_resource_id FROM '.get_table_prefix().'url_id_monikers WHERE m_deprecated=0 AND ('.$or_list.')';
				$results=$GLOBALS['SITE_DB']->query($query);
				$GLOBALS['NO_DB_SCOPE_CHECK']=$bak;
				foreach ($results as $result)
				{
					$LOADED_MONIKERS[$result['m_resource_page']][$result['m_resource_type']][$result['m_resource_id']]=$result['m_moniker'];
				}
			}
			$test=$LOADED_MONIKERS[$url_parts['page']][$url_parts['type']][$url_parts['id']];
		} else
		{
			$bak=$GLOBALS['NO_DB_SCOPE_CHECK'];
			$GLOBALS['NO_DB_SCOPE_CHECK']=true;
			$test=$GLOBALS['SITE_DB']->query_value_null_ok('url_id_monikers','m_moniker',array('m_deprecated'=>0,'m_resource_page'=>$url_parts['page'],'m_resource_type'=>$url_parts['type'],'m_resource_id'=>is_integer($url_parts['id'])?strval($url_parts['id']):$url_parts['id']));
			$GLOBALS['NO_DB_SCOPE_CHECK']=$bak;
			$LOADED_MONIKERS[$url_parts['page']][$url_parts['type']][$url_parts['id']]=$test;
		}
		if (is_string($test)) return $test;

		// Otherwise try to generate a new one
		require_code('urls2');
		return autogenerate_new_url_moniker($ob_info,$url_parts);
	}

	return NULL;
}

