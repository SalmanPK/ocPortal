<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2013

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

class Hook_Preview_block_comcode
{

	/**
	 * Find whether this preview hook applies.
	 *
	 * @return array			Triplet: Whether it applies, the attachment ID type, whether the forum DB is used [optional]
	 */
	function applies()
	{
		if (!has_privilege(get_member(),'comcode_dangerous')) return array(false,NULL,false);

		$applies=!is_null(post_param('block',NULL));
		return array($applies,NULL,false);
	}

	/**
	 * Standard modular run function for preview hooks.
	 *
	 * @return array			A pair: The preview, the updated post Comcode
	 */
	function run()
	{
		if (!has_privilege(get_member(),'comcode_dangerous')) access_denied('I_ERROR');

		require_code('zones2');
		require_code('zones3');

		$bparameters='';
		$bparameters_xml='';
		$block=post_param('block');
		$parameters=get_block_parameters($block);
		foreach ($parameters as $parameter)
		{
			if (($parameter=='filter') && (in_array($block,array('bottom_news','main_news','side_news','side_news_archive'))))
			{
				$value=post_param($parameter,'');
			} else
			{
				$value=post_param($parameter,'0');
			}
			if ($value!='')
			{
				$bparameters.=' '.$parameter.'="'.str_replace('"','\"',$value).'"';
				$bparameters_xml='<blockParam key="'.escape_html($parameter).'" val="'.escape_html($value).'" />';
			}
		}

		$comcode='[block'.$bparameters.']'.$block.'[/block]';

		$preview=comcode_to_tempcode($comcode);

		return array($preview,NULL);
	}

}
