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
 * Convert text to an entity format via unicode, compatible with the GD TTF functions. Originally taken from php manual but heavily modified. Passed text is assumed to be in the get_charset() character set.
 *
 * @param  string		Input text.
 * @return string		Output 7-bit unicode-entity-encoded ASCII text.
 */
function foxy_utf8_to_nce($data='')
{
	if ($data=='') return $data;

	$input_charset=get_charset();
	if ($input_charset!='utf-8')
	{
		if (function_exists('unicode_decode'))
		{
			$test=@unicode_decode($data,$input_charset);
			if ($test!==false) $data=$test; else return $data;
		}
		elseif ((function_exists('iconv')) && (get_value('disable_iconv')!=='1'))
		{
			$test=@iconv($input_charset,'utf-8',$data);
			if ($test!==false) $data=$test; else return $data;
		}
		elseif ((function_exists('mb_convert_encoding')) && (get_value('disable_mbstring')!=='1'))
		{
			if (function_exists('mb_list_encodings'))
			{
				$valid_encoding=(in_array(strtolower($input_charset),array_map('strtolower',mb_list_encodings())));
			} else $valid_encoding=true;
			if ($valid_encoding)
			{
				$test=@mb_convert_encoding($data,'utf-8',$input_charset);
				if ($test!==false) $data=$test; else return $data;
			}
		}
		elseif ((strtolower($input_charset)=='utf-8') && (strtolower(substr('utf-8',0,3))!='utf'))
		{
			$test=utf8_decode($data); // Imperfect as it assumes ISO-8859-1, but it's our last resort.
			if ($test!==false) $data=$test; else return $data;
		}
		else return $data;
	}

	$max_count=5; // flag-bits in $max_mark ( 1111 1000==5 times 1)
	$max_mark=248; // marker for a (theoretical ;-)) 5-byte-char and mask for a 4-byte-char;

	$html='';
	for($str_pos=0;$str_pos<strlen($data);$str_pos++)
	{
		$old_chr=$data[$str_pos];
		$old_val=ord($data[$str_pos]);
		$new_val=0;

		$utf8_marker=0;

		// skip non-utf-8-chars
		if ($old_val>127)
		{
			$mark=$max_mark;
			for($byte_ctr=$max_count;$byte_ctr>2;$byte_ctr--)
			{
				// actual byte is utf-8-marker?
				if (($old_val&$mark)==(($mark<<1) & 255 ))
				{
					$utf8_marker=$byte_ctr-1;
					break;
				}
				$mark=($mark<<1)&255;
			}
		}

		// marker found: collect following bytes
		if (($utf8_marker>1) && (isset($data[$str_pos+1])))
		{
			$str_off=0;
			$new_val=$old_val & (127>>$utf8_marker);
			for ($byte_ctr=$utf8_marker;$byte_ctr>1;$byte_ctr--)
			{
				// check if following chars are UTF8 additional data blocks
				// UTF8 and ord() > 127
				if ((ord($data[$str_pos+1])&192)==128)
				{
					$new_val=$new_val<<6;
					$str_off++;
					// no need for Addition, bitwise OR is sufficient
					// 63: more UTF8-bytes; 0011 1111
					$new_val=$new_val | (ord($data[$str_pos+$str_off])&63);
				}
				// no UTF8, but ord() > 127
				// nevertheless convert first char to NCE
				else
				{
					$new_val=$old_val;
				}
			}
			// build NCE-Code
			$html.='&#'.strval($new_val).';';
			// Skip additional UTF-8-Bytes
			$str_pos=$str_pos+$str_off;
		} else
		{
			$html.=chr($old_val);
			$new_val=$old_val;
		}
	}
	return $html;
}

/**
 * Turn utf-8 characters into unicode HTML entities. Useful as GD truetype functions need this. Based on function in PHP code comments.
 *
 * @param  string		Input.
 * @return string		Output.
 */
function utf8tohtml($utf8)
{
	$result='';
	for ($i=0; $i < strlen($utf8); $i++)
	{
		$char=$utf8[$i];
		$ascii=ord($char);
		if ($ascii < 128)
		{
			// one-byte character
			$result.=$char;
		} elseif ($ascii < 192)
		{
			// non-utf8 character or not a start byte
		} elseif ($ascii < 224)
		{
			// two-byte character
			$ascii1=ord($utf8[$i+1]);
			$unicode=(63 & $ascii) * 64 +
					   (63 & $ascii1);
			$result.='&#'.strval($unicode).';';
			$i+=1;
		} elseif ($ascii < 240)
		{
			// three-byte character
			$ascii1=ord($utf8[$i+1]);
			$ascii2=ord($utf8[$i+2]);
			$unicode=(15 & $ascii) * 4096 +
					   (63 & $ascii1) * 64 +
					   (63 & $ascii2);
			$result.='&#'.strval($unicode).';';
			$i+=2;
		} elseif ($ascii < 248)
		{
			// four-byte character
			$ascii1=ord($utf8[$i+1]);
			$ascii2=ord($utf8[$i+2]);
			$ascii3=ord($utf8[$i+3]);
			$unicode=(15 & $ascii) * 262144 +
					   (63 & $ascii1) * 4096 +
					   (63 & $ascii2) * 64 +
					   (63 & $ascii3);
			$result.='&#'.strval($unicode).';';
			$i+=3;
		}
	}
	return $result;
}

/**
 * Do a UTF8 conversion on the environmental GET/POST parameters (ISO-8859-1 charset, which PHP supports internally).
 */
function do_simple_environment_utf8_conversion()
{
	foreach ($_GET as $key=>$val)
	{
		if (is_string($val))
		{
			$_GET[$key]=utf8_decode($val);
		} elseif (is_array($val))
		{
			foreach ($val as $i=>$v)
			{
				$_GET[$key][$i]=utf8_decode($v);
			}
		}
	}
	foreach ($_POST as $key=>$val)
	{
		if (is_string($val))
		{
			$_POST[$key]=utf8_decode($val);
		} elseif (is_array($val))
		{
			foreach ($val as $i=>$v)
			{
				$_POST[$key][$i]=utf8_decode($v);
			}
		}
	}
}

/**
 * Do a UTF8 conversion on the environmental GET/POST parameters.
 *
 * @param  string					Charset that was used to encode the environmental data.
 */
function do_environment_utf8_conversion($from_charset)
{
	foreach ($_GET as $key=>$val)
	{
		if (is_string($val))
		{
			$test=entity_utf8_decode($val,$from_charset);
			if ($test!==false) $_GET[$key]=$test;
		} elseif (is_array($val))
		{
			foreach ($val as $i=>$v)
			{
				$test=entity_utf8_decode($v,$from_charset);
				if ($test!==false) $_GET[$key][$i]=$test;
			}
		}
	}
	foreach ($_POST as $key=>$val)
	{
		if (is_string($val))
		{
			$test=entity_utf8_decode($val,$from_charset);
			if ($test!==false) $_POST[$key]=$test;
		} elseif (is_array($val))
		{
			foreach ($val as $i=>$v)
			{
				$test=entity_utf8_decode($v,$from_charset);
				if ($test!==false) $_POST[$key][$i]=$test;
			}
		}
	}
}

/**
 * Convert some data from one encoding to the internal encoding.
 *
 * @param  string					Data to convert.
 * @param  ?string				Charset to convert from (NULL: that read by the last http_download_file call).
 * @param  ?string				Charset to convert to (NULL: current encoding).
 * @return string					Converted data.
 */
function convert_to_internal_encoding($data,$input_charset=NULL,$internal_charset=NULL)
{
	global $VALID_ENCODING;

	convert_data_encodings(); // In case it hasn't run yet. We need $VALID_ENCODING to be set.

	if (is_null($input_charset)) $input_charset=$GLOBALS['HTTP_CHARSET'];
	if (($input_charset==='') || (is_null($input_charset))) return $data;

	if (is_null($internal_charset)) $internal_charset=get_charset();

	if (((version_compare(phpversion(),'4.3.0')>=0) || (strtolower($internal_charset)=='iso-8859-1')) && (strtolower($input_charset)=='utf-8') && /*test method works...*/(will_be_unicode_neutered($data)) && (in_array(strtolower($internal_charset),array('iso-8859-1','iso-8859-15','koi8-r','big5','gb2312','big5-hkscs','shift_jis','euc-jp')))) // Preferred as it will sub entities where there's no equivalent character
	{
		$test=entity_utf8_decode($data,$internal_charset);
		if ($test!==false) return $test;
	}
	if ((function_exists('unicode_decode')) && ($internal_charset!='utf-8') && ($input_charset=='utf-8') && ($VALID_ENCODING))
	{
		$test=@unicode_decode($data,$input_charset);
		if ($test!==false) return $test;
	}
	if ((function_exists('unicode_encode')) && ($internal_charset=='utf-8') && ($input_charset!='utf-8') && ($VALID_ENCODING))
	{
		$test=@unicode_encode($data,$input_charset);
		if ($test!==false) return $test;
	}
	if ((function_exists('iconv')) && ($VALID_ENCODING) && (get_value('disable_iconv')!=='1'))
	{
		$test=@iconv($input_charset,$internal_charset.'//TRANSLIT',$data);
		if ($test!==false) return $test;
	}
	if ((function_exists('mb_convert_encoding')) && ($VALID_ENCODING) && (get_value('disable_mbstring')!=='1'))
	{
		if (function_exists('mb_list_encodings'))
		{
			$good_encoding=(in_array(strtolower($input_charset),array_map('strtolower',mb_list_encodings())));
		} else $good_encoding=true;

		if ($good_encoding)
		{
			$test=@mb_convert_encoding($data,$internal_charset,$input_charset);
			if ($test!==false) return $test;
		}
	}
	if ((strtolower($input_charset)=='utf-8') && (strtolower(substr($internal_charset,0,3))!='utf'))
	{
		$test=utf8_decode($data); // Imperfect as it assumes ISO-8859-1, but it's our last resort.
		if ($test!==false) return $test;
	}
	if ((strtolower($internal_charset)=='utf-8') && (strtolower(substr($input_charset,0,3))!='utf'))
	{
		$test=utf8_encode($data); // Imperfect as it assumes ISO-8859-1, but it's our last resort.
		if ($test!==false) return $test;
	}

	return $data;
}

/**
 * Convert some data from UTF to a character set PHP supports, using HTML entities where there's no direct match.
 *
 * @param  string					Data to convert.
 * @param  string					Charset to convert to.
 * @return ~string				Converted data (false: could not convert).
 */
function entity_utf8_decode($data,$internal_charset)
{
	$encoded=htmlentities($data,ENT_COMPAT,'UTF-8'); // Only works on some servers, which is why we test the utility of it before running this function. NB: It is fine that this will double encode any pre-existing entities- as the double encoding will trivially be undone again later (amp can always decode to a lower ascii character)
	if ((strlen($encoded)==0) && ($data!='')) $encoded=htmlentities($data,ENT_COMPAT);

	$test=mixed();
	$test=false;
	if (version_compare(phpversion(),'4.3.0')>=0)
	{
		$test=@html_entity_decode($encoded,ENT_COMPAT,$internal_charset); // this is nice because it will leave equivalent entities where it can't get a character match; Comcode supports those entities
		if ((strlen($test)==0) && ($data!='')) $test=false;
	}
	if ($test===false)
	{
		$test=preg_replace_callback('/&#x([0-9a-f]+);/i','unichrm_hex',$encoded); // imperfect as it can only translate lower ascii back, but better than nothing. htmlentities would have encoded key other ones as named entities though which get_html_translation_table can handle
		$test=preg_replace_callback('/&#([0-9]+);/','unichrm',$test); // imperfect as it can only translate lower ascii back, but better than nothing. htmlentities would have encoded key other ones as named entities though which get_html_translation_table can handle
		if (strtolower($internal_charset)=='iso-8859-1') // trans table only valid for this charset. Else we just need to live with things getting turned into named entities. However we don't allow this function to be called if this code branch would be skipped here.
		{
			$test2=convert_bad_entities($test,$internal_charset);
			if ((strlen($test2)!=0) || ($data=='')) $test=$test2;
		}
	}
	if ($test!==false)
	{
		$data=$test;
		$shortcuts=array('(EUR-)'=>'&euro;','{f.}'=>'&fnof;','-|-'=>'&dagger;','=|='=>'&Dagger;','{%o}'=>'&permil;','{~S}'=>'&Scaron;','{~Z}'=>'&#x17D;','(TM)'=>'&trade;','{~s}'=>'&scaron;','{~z}'=>'&#x17E;','{.Y.}'=>'&Yuml;','(c)'=>'&copy;','(r)'=>'&reg;','---'=>'&mdash;','--'=>'&ndash;','...'=>'&hellip;','==>'=>'&rarr;','<=='=>'&larr;');
		foreach ($shortcuts as $to=>$from)
			$data=str_replace($from,$to,$data);
	} else $data=false;
	return $data;
}
