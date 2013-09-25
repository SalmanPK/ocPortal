<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2013

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		content_reviews
 */

/**
 * Show content review details.
 *
 * @param  ID_TEXT		The content type
 * @param  ID_TEXT		The content ID
 * @param  INTEGER		The display mode (0=show full box of review information, 1=show last reviewed time, 2=show next reviewed time)
 * @set 0 1 2
 * @return string			Content review details to show
 */
function show_content_reviews($content_type,$content_id,$display_mode=0)
{
	$content_reviews=$GLOBALS['SITE_DB']->query_select('content_reviews',array('display_review_status','last_reviewed_time','next_review_time'),array(
		'content_type'=>$content_type,
		'content_id'=>$content_id,
	),'',1);
	if (isset($content_reviews[0]))
	{
		if ($display_mode==1)
		{
			$value=strval($content_reviews[0]['last_reviewed_time']);
		}
		elseif ($display_mode==2)
		{
			$value=strval($content_reviews[0]['next_review_time']);
		} else // display mode 0
		{
			require_lang('content_reviews');
			$value=static_evaluate_tempcode(do_template('REVIEW_STATUS',array(
				'LAST_REVIEWED_TIME'=>get_timezoned_date($content_reviews[0]['last_reviewed_time'],false,false,false,true),
				'NEXT_REVIEW_TIME'=>get_timezoned_date($content_reviews[0]['next_review_time'],false,false,false,true),
				'_LAST_REVIEWED_TIME'=>strval($content_reviews[0]['last_reviewed_time']),
				'_NEXT_REVIEW_TIME'=>strval($content_reviews[0]['next_review_time']),
				'CONTENT_TYPE'=>$content_type,
				'CONTENT_ID'=>$content_id,
				'DISPLAY'=>$content_reviews[0]['display_review_status']==1,
			)));
		}
	} else
	{
		$value='';
		if ($GLOBALS['XSS_DETECT']) ocp_mark_as_escaped($value);
	}

	return $value;
}
