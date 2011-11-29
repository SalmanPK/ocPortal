<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2011

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
 * Render an 'IMAGE_WIDTH'/'IMAGE_HEIGHT' symbol.
 *
 * @param  array			Symbol parameters
 * @return array			A pair: Image dimensions
 */
function _symbol_image_dims($param)
{
	if ((get_option('is_on_gd')=='0') || (!function_exists('imagecreatefromstring'))) return array('','');

	$value=array('','');
	if (isset($param[0]))
	{
		$base_url=get_custom_base_url();
		if ((function_exists('getimagesize')) && (substr($param[0],0,strlen($base_url))==$base_url))
		{
			$details=@getimagesize(get_custom_file_base().'/'.urldecode(substr($param[0],strlen($base_url)+1)));
			if ($details!==false)
			{
				$value=array(strval($details[0]),strval($details[1]));
			}
		} else
		{
			require_code('files');
			$source=@imagecreatefromstring(http_download_file($param[0],1024*1024*20/*reasonable limit*/));
			if ($source!==false)
			{
				$value=array(strval(imagesx($source)),strval(imagesy($source)));
				imagedestroy($source);
			}
		}
	}
	return $value;
}

/**
 * Render a 'THUMBNAIL' symbol.
 *
 * @param  array			Symbol parameters
 * @return string			Rendered symbol
 */
function _symbol_thumbnail($param)
{
	$value='';

	// Usage: {$THUMBNAIL,source,widthxheight,directory,filename,fallback,type,where,option,only_make_smaller}
	// source: URL of image to thumbnail
	// widthxheight: The desired width and height, separated by a lowercase x. Can be -1 to mean "don't care", but better to use type as "width" or "height"
	// directory: Where to save the thumbnail to
	// filename: A suggested filename to use (if the default choice causes problems)
	// fallback: The URL to an image we can use if the thumbnail fails (eg. the original)
	// type: One of "width" (scale until the width matches), "height" (scale until the height matches), "crop" (scale until one dimension's right, cut off the remainder),
	//		"pad" (scale down until the image fits completely inside the dimensions, then optionally fill the gaps), "pad_horiz_crop_horiz" (fit to height, cropping or
	//		padding as needed) or "pad_vert_crop_vert" (fit to width, padding or cropping as needed)
	// where: If padding or cropping, specifies where to crop or pad. One of "start", "end" or "both"
	// option: An extra option if desired. If type is "pad" then this can be a hex colour for the padding
	// only_make_smaller: Whether to avoid growing small images to fit (smaller images are better for the Web). One of 0 (false) or 1 (true)
	if (($param[0]!=''))
	{
		if ((get_option('is_on_gd')=='0') || (!function_exists('imagecreatefromstring'))) return $param[0];

		$only_make_smaller=isset($param[8])?($param[8]=='1'):false;
		$orig_url=$param[0]; // Source for thumbnail generation
		if (url_is_local($orig_url)) $orig_url=get_custom_base_url().'/'.$orig_url;
		if (!array_key_exists(1,$param)) $param[1]=get_option('thumb_width');
		$dimensions=$param[1]; // Generation dimensions.
		$exp_dimensions=explode('x',$dimensions);
		if (count($exp_dimensions)==1) $exp_dimensions[]='-1';
		if (count($exp_dimensions)==2)
		{
			if ($exp_dimensions[0]=='') $exp_dimensions[0]='-1';

			if (isset($param[2]) && $param[2] != '') // Where we are saving to
			{
				$thumb_save_dir=$param[2];
			} else
			{
				$thumb_save_dir=basename(dirname(rawurldecode($orig_url)));
			}
			if (strpos($thumb_save_dir,'/')===false) $thumb_save_dir='uploads/'.$thumb_save_dir;
			if (!file_exists(get_custom_file_base().'/'.$thumb_save_dir)) $thumb_save_dir='uploads/website_specific';
			$filename=rawurldecode(basename((isset($param[3]) && $param[3] != '')?$param[3]:$orig_url)); // We can take a third parameter that hints what filename to save with (useful to avoid filename collisions within the thumbnail filename subspace). Otherwise we based on source's filename
			$save_path=get_custom_file_base().'/'.$thumb_save_dir.'/'.$dimensions.'__'.$filename; // Conclusion... We will save to here

			$value=get_custom_base_url().'/'.$thumb_save_dir.'/'.rawurlencode($dimensions.'__'.$filename);

			// We put a branch in here to preserve the old behaviour when
			// called without the last 2 options
			if (isset($param[5]) && in_array(trim($param[5]),array('width','height','crop','pad','pad_horiz_crop_horiz','pad_vert_crop_vert')))
			{
				// This branch does the new behaviour described above

				$file_prefix = '/'.$thumb_save_dir.'/thumb__'.$dimensions.'__'.trim($param[5]);
				if (isset($param[6])) $file_prefix .= '__'.trim($param[6]);
				if (isset($param[7])) $file_prefix .= '__'.trim(str_replace('#','',$param[7]));
				$save_path = get_custom_file_base().$file_prefix.'__'.$filename;
				$value = get_custom_base_url().$file_prefix.'__'.rawurlencode($filename);

				// Only bother calculating the image if we've not already
				// made one with these options
				if ((!is_file($save_path)) && (!is_file($save_path.'.png')))
				{
					// Branch based on the type of thumbnail we're making
					if (trim($param[5]) == 'width' || trim($param[5]) == 'height')
					{
						// We just need to scale to the given dimension
						$result = convert_image($orig_url,$save_path,(trim($param[5]) == 'width')?$exp_dimensions[0]:-1,(trim($param[5]) == 'height')?$exp_dimensions[1]:-1,-1,false,NULL,false,$only_make_smaller);
					}
					elseif (trim($param[5]) == 'crop' || trim($param[5]) == 'pad' || trim($param[5]) == 'pad_horiz_crop_horiz' || trim($param[5]) == 'pad_vert_crop_vert')
					{
						// We need to shrink a bit and crop/pad
						require_code('files');

						// Find dimensions of the source
						$converted_to_path=convert_url_to_path($orig_url);
						if (!is_null($converted_to_path))
						{
							$sizes=@getimagesize($converted_to_path);
							if ($sizes===false) return '';
							list($source_x,$source_y)=$sizes;
						} else
						{
							$source = @imagecreatefromstring(http_download_file($orig_url,NULL,false));
							if ($source===false) return '';
							$source_x = imagesx($source);
							$source_y = imagesy($source);
							imagedestroy($source);
						}

						// We only need to crop/pad if the aspect ratio
						// differs from what we want
						$source_aspect = floatval($source_x) / floatval($source_y);
						if ($exp_dimensions[1]==0) $exp_dimensions[1]=1;
						$destination_aspect = floatval($exp_dimensions[0]) / floatval($exp_dimensions[1]);

						// We test the scaled sizes, rather than the ratios
						// directly, so that differences too small to affect
						// the integer dimensions will be tolerated.
						if ($source_aspect > $destination_aspect)
						{
							// The image is wider than the output.
							if ((trim($param[5]) == 'crop') || (trim($param[5]) == 'pad_horiz_crop_horiz'))
							{
								// Is it too wide, requiring cropping?
								$scale = floatval($source_y) / floatval($exp_dimensions[1]);
								$modify_image = intval(round(floatval($source_x) / $scale)) != intval($exp_dimensions[0]);
							}
							else
							{
								// Is the image too short, requiring padding?
								$scale = floatval($source_x) / floatval($exp_dimensions[0]);
								$modify_image = intval(round(floatval($source_y) / $scale)) != intval($exp_dimensions[1]);
							}
						}
						elseif ($source_aspect < $destination_aspect)
						{
							// The image is taller than the output
							if ((trim($param[5]) == 'crop') || (trim($param[5]) == 'pad_vert_crop_vert'))
							{
								// Is it too tall, requiring cropping?
								$scale = floatval($source_x) / floatval($exp_dimensions[0]);
								$modify_image = intval(round(floatval($source_y) / $scale)) != intval($exp_dimensions[1]);
							}
							else
							{
								// Is the image too narrow, requiring padding?
								$scale = floatval($source_y) / floatval($exp_dimensions[1]);
								$modify_image = intval(round(floatval($source_x) / $scale)) != intval($exp_dimensions[0]);
							}
						}
						else
						{
							// They're the same, within the tolerances of
							// floating point arithmentic. Just scale it.
							$modify_image = false;
						}

						// We have a special case here, since we can "pad" an
						// image with nothing, ie. shrink it to fit in the
						// output dimensions. This means we don't need to
						// modify the image contents either, just scale it.
						if ((trim($param[5]) == 'pad' || trim($param[5]) == 'pad_horiz_crop_horiz' || trim($param[5]) == 'pad_vert_crop_vert') && isset($param[5]) && (!isset($param[6]) || trim($param[6]) == ''))
						{
							$modify_image = false;
						}

						// Now do the cropping, padding and scaling
						if ($modify_image)
						{
							$result = convert_image($orig_url,$save_path,intval($exp_dimensions[0]),intval($exp_dimensions[1]),-1,false,NULL,false,$only_make_smaller,array('type'=>trim($param[5]),'background'=>(isset($param[7])?trim($param[7]):NULL),'where'=>(isset($param[6])?trim($param[6]):'both'),'scale'=>$scale));
						}
						else
						{
							// Just resize
							$result = convert_image($orig_url,$save_path,intval($exp_dimensions[0]),intval($exp_dimensions[1]),-1,false,NULL,false,$only_make_smaller);
						}
					}

					// If the convertion failed then give back the fallback,
					// or if it's empty then give back the original image
					if (!$result) $value=(trim($param[4])=='')? $orig_url : $param[4];
				}

				if (!is_file($save_path))
					$value.='.png'; // was saved as PNG
			}
			else
			{
				// This branch does the old behaviour of fitting to width, without the "type" or subsequent parameters.
				if (!is_file($save_path))
				{
					if (!convert_image($orig_url,$save_path,intval($exp_dimensions[0]),intval($exp_dimensions[1]),-1,false,NULL,false,$only_make_smaller))
					{ // Ah error, get the closest match we have
						$value=isset($param[4])?$param[4]:$orig_url;
					}
				}
			}
		}
	}
	
	return $value;
}

/**
 * Get the maximum allowed image size, as set in the configuration.
 *
 * @return integer		The maximum image size
 */
function get_max_image_size()
{
	$a=php_return_bytes(ini_get('upload_max_filesize'));
	$b=php_return_bytes(ini_get('post_max_size'));
	$c=intval(get_option('max_download_size'))*1024;
	if (has_specific_permission(get_member(),'exceed_filesize_limit')) $c=0;

	$possibilities=array();
	if ($a!=0) $possibilities[]=$a;
	if ($b!=0) $possibilities[]=$b;
	if ($c!=0) $possibilities[]=$c;

	return min($possibilities);
}

/**
 * Get the tempcode for an image thumbnail.
 *
 * @param  URLPATH		The URL to the image thumbnail
 * @param  mixed			The caption for the thumbnail (string or Tempcode)
 * @param  boolean		Whether to use a JS tooltip. Forcibly set to true if you pass Tempcode
 * @param  boolean		Whether already a thumbnail (if not, function will make one)
 * @param  integer		Thumbnail width to use
 * @param  integer		Thumbnail height to use
 * @return tempcode		The thumbnail
 */
function do_image_thumb($url,$caption,$js_tooltip=false,$is_thumbnail_already=true,$width=90,$height=90)
{
	if (is_object($caption))
	{
		$js_tooltip=true;
	}

	if(!$is_thumbnail_already)
	{	
		$new_name=strval($width).'_'.strval($height).'_'.url_to_filename($url);
		
		if (!is_saveable_image($new_name)) $new_name.='.png';

		$file_thumb=get_custom_file_base().'/uploads/auto_thumbs/'.$new_name;
		
		if (url_is_local($url)) $url=get_custom_base_url().'/'.$url;

		if (!file_exists($file_thumb))
			convert_image($url,$file_thumb,$width,$height,$width,false);

		$url=get_custom_base_url().'/uploads/auto_thumbs/'.rawurlencode($new_name);
	}

	if (url_is_local($url)) $url=get_custom_base_url().'/'.$url;

	if ((!is_object($caption)) && ($caption==''))
	{
		$caption=do_lang('THUMBNAIL');
		$js_tooltip=false;
	}
	return do_template('IMG_THUMB',array('_GUID'=>'f1c130b7c3b2922fe273596563cb377c','JS_TOOLTIP'=>$js_tooltip,'CAPTION'=>$caption,'URL'=>$url));
}

/**
 * Take some image/thumbnail info, and if needed make and caches a thumbnail, and return a thumb url whatever the situation.
 *
 * @param  URLPATH		The full URL to the image which will-be/is thumbnailed
 * @param  URLPATH		The URL to the thumbnail (blank: no thumbnail yet)
 * @param  ID_TEXT		The directory, relative to the ocPortal install, where the thumbnails are stored. MINUS "_thumbs"
 * @param  ID_TEXT		The name of the table that is storing what we are doing the thumbnail for
 * @param  AUTO_LINK		The ID of the table record that is storing what we are doing the thumbnail for
 * @param  ID_TEXT		The name of the table field where thumbnails are saved
 * @param  ?integer		The thumbnail width to use (NULL: default)
 * @return URLPATH		The URL to the thumbnail
 */
function ensure_thumbnail($full_url,$thumb_url,$thumb_dir,$table,$id,$thumb_field_name='thumb_url',$thumb_width=NULL)
{
	if (is_null($thumb_width)) $thumb_width=intval(get_option('thumb_width'));

	if ((get_option('is_on_gd')=='0') || (!function_exists('imagetypes')) || ($full_url==''))
	{
		if ((url_is_local($thumb_url)) && ($thumb_url!='')) return get_custom_base_url().'/'.$thumb_url;
		return $thumb_url;
	}

	if ($thumb_url!='')
	{
		if (url_is_local($thumb_url))
		{
			$thumb_path=get_custom_file_base().'/'.rawurldecode($thumb_url);
			if (!file_exists($thumb_path))
			{
				$from=str_replace(' ','%20',$full_url);
				if (url_is_local($from)) $from=get_custom_base_url().'/'.$from;

				if (is_video($from))
				{
					require_code('galleries2');
					create_video_thumb($full_url,$thumb_path);
				} else
				{
					convert_image($from,$thumb_path,intval($thumb_width),-1,-1,false);
				}
			}
			return get_custom_base_url().'/'.$thumb_url;
		}
		return $thumb_url;
	}

	$url_parts=explode('/',$full_url);
	$i=0;
	$_file=$url_parts[count($url_parts)-1];
	$dot_pos=strrpos($_file,'.');
	$ext=substr($_file,$dot_pos+1);
	if (!is_saveable_image($_file)) $ext='png';
	$_file=substr($_file,0,$dot_pos);
	$thumb_path='';
	do
	{
		$file=rawurldecode($_file).(($i==0)?'':strval($i));
		$thumb_path=get_custom_file_base().'/uploads/'.$thumb_dir.'_thumbs/'.$file.'.'.$ext;
		$i++;
	}
	while (file_exists($thumb_path));
	$thumb_url='uploads/'.$thumb_dir.'_thumbs/'.rawurlencode($file).'.'.$ext;
	if ((substr($table,0,2)=='f_') && (get_forum_type()=='ocf'))
	{
		$GLOBALS['FORUM_DB']->query_update($table,array($thumb_field_name=>$thumb_url),array('id'=>$id),'',1);
	} else
	{
		$GLOBALS['SITE_DB']->query_update($table,array($thumb_field_name=>$thumb_url),array('id'=>$id),'',1);
	}

	$from=str_replace(' ','%20',$full_url);
	if (url_is_local($from)) $from=get_custom_base_url().'/'.$from;
	if (!file_exists($thumb_path))
	{
		if (is_video($from))
		{
			require_code('galleries2');
			create_video_thumb($full_url,$thumb_path);
		} else
		{
			convert_image($from,$thumb_path,intval($thumb_width),-1,-1,false);
		}
	}

	return get_custom_base_url().'/'.$thumb_url;
}

/**
 * Check we can load the given file, given our memory limit.
 *
 * @param  PATH			The file path we are trying to load
 * @param  boolean		Whether to exit ocPortal if an error occurs
 * @return boolean		Success status
 */
function check_memory_limit_for($file_path,$exit_on_error=true)
{
	if (function_exists('memory_get_usage'))
	{
		$ov=ini_get('memory_limit');

		$_what_we_will_allow=get_value('real_memory_available_mb');
		$what_we_will_allow=is_null($_what_we_will_allow)?NULL:(intval($_what_we_will_allow)*1024*1024);

		if ((substr($ov,-1)=='M') || (!is_null($what_we_will_allow)))
		{
			if (is_null($what_we_will_allow))
			{
				$total_memory_limit_in_bytes=intval(substr($ov,0,strlen($ov)-1))*1024*1024;
		
				$what_we_will_allow=$total_memory_limit_in_bytes-memory_get_usage()-1024*1024*3; // 3 is for 3MB extra space needed to finish off
			}

			$details=@getimagesize($file_path);
			if ($details!==false) // Check it is not corrupt. If it is corrupt, we will give an error later
			{
				$magic_factor=3.0; /* factor of inefficency by experimentation */
				
				$channels=4;//array_key_exists('channels',$details)?$details['channels']:3; it will be loaded with 4
				$bits_per_channel=8;//array_key_exists('bits',$details)?$details['bits']:8; it will be loaded with 8
				$bytes=($details[0]*$details[1])*($bits_per_channel/8)*($channels+1)*$magic_factor;

				if ($bytes>floatval($what_we_will_allow))
				{
					$max_dim=intval(sqrt(floatval($what_we_will_allow)/4.0/$magic_factor/*4 1 byte channels*/));

					// Can command line imagemagick save the day?
					$imagemagick='/usr/bin/convert';
					if (!@file_exists($imagemagick)) $imagemagick='/usr/local/bin/convert';
					if (!@file_exists($imagemagick)) $imagemagick='/opt/local/bin/convert';
					if (@file_exists($imagemagick))
					{
						$shrink_command=$imagemagick.' '.@escapeshellarg($file_path);
						$shrink_command.=' -resize '.strval(intval(floatval($max_dim)/1.5)).'x'.strval(intval(floatval($max_dim)/1.5));
						$shrink_command.=' '.@escapeshellarg($file_path);
						$err_cond=-1;
						$output_arr=array();
						@exec($shrink_command,$output_arr,$err_cond);
						if ($err_cond===0) return true;
					}

					$message=do_lang_tempcode('IMAGE_TOO_LARGE_FOR_THUMB',escape_html(integer_format($max_dim)),escape_html(integer_format($max_dim)));
					if (!$exit_on_error)
					{
						attach_message($message,'warn');
					} else
					{
						warn_exit($message);
					}
					
					return false;
				}
			}
		}
	}
	
	return true;
}

/**
 * Resize an image to the specified size, but retain the aspect ratio.
 *
 * @param  URLPATH		The URL to the image to resize
 * @param  PATH			The file path (including filename) to where the resized image will be saved
 * @param  integer		The maximum width we want our new image to be (-1 means "don't factor this in")
 * @param  integer		The maximum height we want our new image to be (-1 means "don't factor this in")
 * @param  integer		This is only considered if both $width and $height are -1. If set, it will fit the image to a box of this dimension (suited for resizing both landscape and portraits fairly)
 * @param  boolean		Whether to exit ocPortal if an error occurs
 * @param  ?string		The file extension to save with (NULL: same as our input file)
 * @param  boolean		Whether $from was in fact a path, not a URL
 * @param  boolean		Whether to apply a 'never make the image bigger' rule for thumbnail creation (would affect very small images)
 * @param  ?array			This optional parameter allows us to specify cropping or padding for the image. See comments in the function. (NULL: no details passed)
 * @return boolean		Success
 */
function convert_image($from,$to,$width,$height,$box_width=-1,$exit_on_error=true,$ext2=NULL,$using_path=false,$only_make_smaller=false,$thumb_options=NULL)
{
	disable_php_memory_limit();

	// Load
	$ext=get_file_extension($from);
	if ($using_path)
	{
		if (!check_memory_limit_for($from,$exit_on_error)) return false;
		$from_file=@file_get_contents($from);
	} else
	{
		$file_path_stub=convert_url_to_path($from);
		if (!is_null($file_path_stub))
		{
			if (!check_memory_limit_for($file_path_stub,$exit_on_error)) return false;
			$from_file=@file_get_contents($file_path_stub);
		} else
		{
			$from_file=http_download_file($from,1024*1024*20/*reasonable limit*/);
		}
	}
	if ($from_file===false)
	{
		if ($exit_on_error) fatal_exit(do_lang_tempcode('UPLOAD_PERMISSION_ERROR',escape_html($from)));
		require_code('site');
		attach_message(do_lang_tempcode('UPLOAD_PERMISSION_ERROR',escape_html($from)),'warn');
		return false;
	}
	$source=@imagecreatefromstring($from_file);
	if ((!is_null($thumb_options)) || (!$only_make_smaller))
		unset($from_file);
	if ($source===false)
	{
		if ($exit_on_error) warn_exit(do_lang_tempcode('CORRUPT_FILE',escape_html($from)));
		require_code('site');
		attach_message(do_lang_tempcode('CORRUPT_FILE',escape_html($from)),'warn');
		return false;
	}

	// Derive actual width x height, for the given maximum box (maintain aspect ratio)
	// ===============================================================================
	$sx=imagesx($source);
	$sy=imagesy($source);
	
	$red=NULL;

	if (is_null($thumb_options))
	{
		if ($width==0) $width=1;
		if ($height==0) $height=1;

		// If we're not sure if this is gonna stretch to fit a width or stretch to fit a height
		if (($width==-1) && ($height==-1))
		{
			if ($sx>$sy)
				$width=$box_width;
			else
				$height=$box_width;
		}

		if (($width!=-1) && ($height!=-1))
		{
			if ((floatval($sx)/floatval($width))>(floatval($sy)/floatval($height)))
			{
				$_width=$width;
				$_height=intval($sy*($width/$sx));
			} else
			{
				$_height=$height;
				$_width=intval($sx*($height/$sy));
			}
		}
		elseif ($height==-1)
		{
			$_width=$width;
			$_height=intval($width/($sx/$sy));
		}
		elseif ($width==-1)
		{
			$_height=$height;
			$_width=intval($height/($sy/$sx));
		}
		if (($_width>$sx) && ($only_make_smaller))
		{
			$_width=$sx;
			$_height=$sy;
			
			// We can just escape, nothing to do
			
			imagedestroy($source);

			if (($using_path) && ($from==$to))
				return true;
			
			if ($using_path)
			{
				copy($from,$to);
			} else
			{
				$_to=@fopen($to,'wb') OR intelligent_write_error($to);
				fwrite($_to,$from_file);
				fclose($_to);
			}
			fix_permissions($to);
			sync_file($to);
			return true;
		}
		if ($_width<1) $_width=1;
		if ($_height<1) $_height=1;

		// Pad out options for imagecopyresized
		// $dst_im,$src_im,$dst_x,$dst_y,$src_x,$src_y,$dst_w,$dst_h,$src_w,$src_h
		$dest_x = 0;
		$dest_y = 0;
		$source_x = 0;
		$source_y = 0;
	}
	else
	{
		// Thumbnail-specific (for the moment) behaviour. We require the ability
		// to crop (ie. window-off a section of the image), and pad (ie. provide a
		// background around the image). We keep this separate to the above code
		// because that already works well across various aspects of the site.
		//
		// Format of the array is 'type'=>'crop' or 'type'=>'pad'; 'where'=>'end',
		// 'where'=>'start' or 'where'=>'both'. For padding, there is an optional
		// 'background'=>'RRGGBBAA' or 'background'=>'RRGGBB' for colored padding
		// with or without transparency.

		// Grab the dimensions we would get if we didn't crop or scale
		$wrong_x = intval(round(floatval($sx) / $thumb_options['scale']));
		$wrong_y = intval(round(floatval($sy) / $thumb_options['scale']));

		// Handle cropping here
		if (($thumb_options['type'] == 'crop') || (($thumb_options['type'] == 'pad_horiz_crop_horiz') && ($wrong_x > $width)) || (($thumb_options['type'] == 'pad_vert_crop_vert') && ($wrong_y > $height)))
		{
			// See which direction we're cropping in
			if (intval(round(floatval($sx) / $thumb_options['scale'])) != $width)
			{
				$crop_direction = 'x';
			}
			else
			{
				$crop_direction = 'y';
			}
			// We definitely have to crop, since symbols.php only tells us to crop
			// if it has to. Thus we know we're going to fill the output image, the
			// only question is with what part of the source image?

			// Get the amount we'll lose from the source
			if ($crop_direction == 'x') $crop_off = intval(($sx - ($width * $thumb_options['scale'])));
			elseif ($crop_direction == 'y') $crop_off = intval(($sy - ($height * $thumb_options['scale'])));

			// Now we see how much to chop off the start (we don't care about the
			// end, as this will be handled by using an appropriate window size)
			$displacement = 0;
			if (($thumb_options['where'] == 'start') || (($thumb_options['where'] == 'start_if_vertical') && ($crop_direction == 'y')) || (($thumb_options['where'] == 'start_if_horizontal') && ($crop_direction == 'x')))
			{
				$displacement = 0;
			}
			elseif (($thumb_options['where'] == 'end') || (($thumb_options['where'] == 'end_if_vertical') && ($crop_direction == 'y')) || (($thumb_options['where'] == 'end_if_horizontal') && ($crop_direction == 'x')))
			{
				$displacement = intval(floatval($crop_off));
			}
			else
			{
				$displacement = intval(floatval($crop_off) / 2.0);
			}

			// Now we convert this to the right x and y start locations for the
			// window
			$source_x = ($crop_direction == 'x')? $displacement : 0;
			$source_y = ($crop_direction == 'y')? $displacement : 0;

			// Now we set the width and height of our window, which will be scaled
			// versions of the width and height of the output
			$sx = intval(($width * $thumb_options['scale']));
			$sy = intval(($height * $thumb_options['scale']));

			// We start at the origin of our output
			$dest_x = 0;
			$dest_y = 0;

			// and it is always the full size it can be (or else we'd be cropping
			// too much)
			$_width = $width;
			$_height = $height;
		}
		elseif ($thumb_options['type'] == 'pad' || (($thumb_options['type'] == 'pad_horiz_crop_horiz') && ($wrong_x < $width)) || (($thumb_options['type'] == 'pad_vert_crop_vert') && ($wrong_y < $height)))
		{
			// Padding code lives here. We definitely need to pad some excess space
			// because otherwise symbols.php would not call us. Thus we need a
			// background (can be transparent). Let's see if we've been given one.
			if (array_key_exists('background',$thumb_options) && !is_null($thumb_options['background']))
			{
				if (substr($thumb_options['background'],0,1)=='#') $thumb_options['background']=substr($thumb_options['background'],1);
				
				// We've been given a background, let's find out what it is
				if (strlen($thumb_options['background']) == 8)
				{
					// We've got an alpha channel
					$using_alpha = true;
					$red_str=substr($thumb_options['background'],0,2);
					$green_str=substr($thumb_options['background'],2,2);
					$blue_str=substr($thumb_options['background'],4,2);
					$alpha_str=substr($thumb_options['background'],6,2);
				}
				else
				{
					// We've not got an alpha channel
					$using_alpha = false;
					$red_str=substr($thumb_options['background'],0,2);
					$green_str=substr($thumb_options['background'],2,2);
					$blue_str=substr($thumb_options['background'],4,2);
				}
				$red = intval($red_str,16);
				$green = intval($green_str,16);
				$blue = intval($blue_str,16);
				if ($using_alpha) $alpha = intval($alpha_str,16);
			}
			else
			{
				// We've not got a background, so let's find a representative color
				// for the image by resampling the whole thing to 1 pixel.
				$temp_img = imagecreatetruecolor(1,1);		// Make an image to map on to
				imagecopyresampled($temp_img,$source,0,0,0,0,1,1,$sx,$sy);		// Map the source image on to the 1x1 image
				$rgb_index = imagecolorat($temp_img, 0, 0);		// Grab the color index of the single pixel
				$rgb_array = imagecolorsforindex($temp_img, $rgb_index);		// Get the channels for it
				$red = $rgb_array['red'];		// Grab the red
				$green = $rgb_array['green'];		// Grab the green
				$blue = $rgb_array['blue'];		// Grab the blue

				// Sort out if we're using alpha
				$using_alpha = false;
				if (array_key_exists('alpha',$rgb_array)) $using_alpha = true;
				if ($using_alpha) $alpha = 255-($rgb_array['alpha']*2+1);

				// Destroy the temporary image
				imagedestroy($temp_img);
			}

			// Now we need to work out how much padding we're giving, and where

			// The axis
			if (intval(round(floatval($sx) / $thumb_options['scale'])) != $width)
			{
				$pad_axis = 'x';
			}
			else
			{
				$pad_axis = 'y';
			}

			// The amount
			if ($pad_axis == 'x') $padding = intval(round(floatval($width) - (floatval($sx) / $thumb_options['scale'])));
			else $padding = intval(round(floatval($height) - (floatval($sy) / $thumb_options['scale'])));

			// The distribution
			if (($thumb_options['where'] == 'start') || (($thumb_options['where'] == 'start_if_vertical') && ($pad_axis == 'y')) || (($thumb_options['where'] == 'start_if_horizontal') && ($pad_axis == 'x')))
			{
				$pad_amount = 0;
			}
			else
			{
				$pad_amount = intval(floatval($padding) / 2.0);
			}

			// Now set all of the parameters needed for blitting our image
			// $sx and $sy are fine, since they cover the whole image
			$source_x = 0;
			$source_y = 0;
			$_width = ($pad_axis == 'x')? intval(round(floatval($sx) / $thumb_options['scale'])) : $width;
			$_height = ($pad_axis == 'y')? intval(round(floatval($sy) / $thumb_options['scale'])) : $height;
			$dest_x = ($pad_axis == 'x')?  $pad_amount : 0;
			$dest_y = ($pad_axis == 'y')?  $pad_amount : 0;
		}
	}
	// Resample/copy
	$gd_version=get_gd_version();
	if ($gd_version>=2.0) // If we have GD2
	{
		// Set the background if we have one
		if (!is_null($thumb_options) && !is_null($red))
		{
			$dest=imagecreatetruecolor($width,$height);
			imagealphablending($dest,false);
			if ((function_exists('imagecolorallocatealpha')) && ($using_alpha)) $back_col = imagecolorallocatealpha($dest, $red, $green, $blue, 127-intval(floatval($alpha)/2.0));
			else $back_col = imagecolorallocate($dest, $red, $green, $blue);
			imagefilledrectangle($dest,0,0,$width,$height,$back_col);
			if (function_exists('imagesavealpha')) imagesavealpha($dest,true);
		} else
		{
			$dest=imagecreatetruecolor($_width,$_height);
			imagealphablending($dest,false);
			if (function_exists('imagesavealpha')) imagesavealpha($dest,true);
		}

		imagecopyresampled($dest,$source,$dest_x,$dest_y,$source_x,$source_y,$_width,$_height,$sx,$sy);
	} else
	{
		// Set the background if we have one
		if (!is_null($thumb_options) && !is_null($red))
		{
			$dest=imagecreate($width,$height);

			$back_col = imagecolorallocate($dest, $red, $green, $blue);
			imagefill($dest,0,0,$back_col);
		} else
		{
			$dest=imagecreate($_width,$_height);
		}

		imagecopyresized($dest,$source,$dest_x,$dest_y,$source_x,$source_y,$_width,$_height,$sx,$sy);
	}

	// Clean up
	imagedestroy($source);

	// Save
	if (is_null($ext2)) $ext2=get_file_extension($to);

	// If we've got transparency then we have to save as PNG
	if (!is_null($thumb_options) && isset($red) && $using_alpha) $ext2='png';
	
	if ($ext2=='png')
	{
		if (strtolower(substr($to,-4)) != '.png') $to = $to . '.png';
		$test=@imagepng($dest,$to);
		if (!$test)
		{
			if ($exit_on_error) warn_exit(do_lang_tempcode('ERROR_IMAGE_SAVE',@strval($php_errormsg)));
			require_code('site');
			attach_message(do_lang_tempcode('ERROR_IMAGE_SAVE',@strval($php_errormsg)),'warn');
			return false;
		}
	}
	elseif (($ext2=='jpg') || ($ext2=='jpeg'))
	{
		$test=@imagejpeg($dest,$to);
		if (!$test)
		{
			if ($exit_on_error) warn_exit(do_lang_tempcode('ERROR_IMAGE_SAVE',@strval($php_errormsg)));
			require_code('site');
			attach_message(do_lang_tempcode('ERROR_IMAGE_SAVE',@strval($php_errormsg)),'warn');
			return false;
		}
	}
	elseif ((function_exists('imagegif')) && ($ext2=='gif'))
	{
		$test=@imagegif($dest,$to);
		if (!$test)
		{
			if ($exit_on_error) warn_exit(do_lang_tempcode('ERROR_IMAGE_SAVE',@strval($php_errormsg)));
			require_code('site');
			attach_message(do_lang_tempcode('ERROR_IMAGE_SAVE',@strval($php_errormsg)),'warn');
			return false;
		}
	} else
	{
		if ($exit_on_error) warn_exit(do_lang_tempcode('UNKNOWN_FORMAT',escape_html($ext2)));
		require_code('site');
		attach_message(do_lang_tempcode('UNKNOWN_FORMAT',escape_html($ext2)),'warn');
		return false;
	}

	// Clean up
	imagedestroy($dest);
	
	fix_permissions($to);
	sync_file($to);
	
	return true;
}

/**
 * Get the version number of GD on the system. It should only be called if GD is known to be on the system, and in use
 *
 * @return float			The version of GD installed
 */
function get_gd_version()
{
	if (function_exists('gd_info'))
	{
		$info=gd_info();
		$matches=array();
		if (preg_match('#(\d(\.|))+#',$info['GD Version'],$matches)!=0)
		{
			$version=$matches[0];
		} else $version=$info['version'];
		return floatval($version);
	}

	ob_start();
	phpinfo();
	$_info=ob_get_contents();
	ob_end_clean();
	$a=explode("\n",$_info);
	foreach ($a as $line)
	{
		if (strpos($line,"GD Version")!==false)
		{
			return floatval(trim(str_replace('GD Version','',strip_tags($line))));
		}
	}
	fatal_exit(do_lang_tempcode('INTERNAL_ERROR'));
	return (-1.0); // trick for Zend
}

/**
 * Find whether the image specified is actually an image, based on file extension
 *
 * @param  string			A URL or file path to the image
 * @return boolean		Whether the string pointed to a file appeared to be an image
 */
function is_image($name)
{
	if (substr(basename($name),0,1)=='.') return false; // Temporary file that some OS's make
	
	$ext=get_file_extension($name);

	$types=explode(',',get_option('valid_images'));
	foreach ($types as $val)
		if (strtolower($val)==$ext) return true;

	return false;
}

/**
 * Find whether the video specified is actually a 'video', based on file extension
 *
 * @param  string			A URL or file path to the video
 * @param  boolean		Whether it really must be an actual video/audio, not some other kind of rich media which we may render in a video spot
 * @return boolean		Whether the string pointed to a file appeared to be a video
 */
function is_video($name,$must_be_true_video=false)
{
	if ((addon_installed('galleries')) && (!$must_be_true_video))
	{
		$ve_hooks=find_all_hooks('systems','video_embed');
		foreach (array_keys($ve_hooks) as $ve_hook)
		{
			require_code('hooks/systems/video_embed/'.$ve_hook);
			$ve_ob=object_factory('Hook_video_embed_'.$ve_hook);
			if (!is_null($ve_ob->get_template_name_and_id($name))) return true;
		}
	}

	$ext=get_file_extension($name);
	if (!$must_be_true_video)
	{
		if ($ext=='swf') return true;
		if ($ext=='pdf') return true; // Galleries can render it as a video
	}
	if (($ext=='rm') || ($ext=='ram')) return true; // These have audio mime types, but may be videos

	require_code('mime_types');
	$mime_type=get_mime_type($ext);
	return ((substr($mime_type,0,6)=='video/') || ((get_option('allow_audio_videos')=='1') && (substr($mime_type,0,6)=='audio/')));
}

/**
 * Use the image extension to determine if the specified image is of a format (extension) saveable by ocPortal or not.
 *
 * @param  string			A URL or file path to the image
 * @return boolean		Whether the string pointed to a file that appeared to be a saveable image
 */
function is_saveable_image($name)
{
	$ext=get_file_extension($name);
	if ((get_option('is_on_gd')=='1') && (function_exists('imagetypes')))
	{
		$gd=imagetypes();
		if (($ext=='gif') && (($gd&IMG_GIF)!=0) && (function_exists('image_gif'))) return true;
		if (($ext=='jpg') && (($gd&IMG_JPEG)!=0)) return true;
		if (($ext=='jpeg') && (($gd&IMG_JPEG)!=0)) return true;
		if (($ext=='png') && (($gd&IMG_PNG)!=0)) return true;
		return false;
	} else return (($ext=='jpg') || ($ext=='jpeg') || ($ext=='png'));
}


