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
 * @package		catalogues
 */

class Hook_cron_catalogue_view_reports
{

	/**
	 * Standard modular run function for CRON hooks. Searches for tasks to perform.
	 */
	function run()
	{
		if (!addon_installed('catalogues')) return;
		
		$time=time();

		$done_reports=array('daily'=>false,'weekly'=>false,'monthly'=>false,'quarterly'=>false);

		$catalogues=$GLOBALS['SITE_DB']->query('SELECT c_title,c_name,c_send_view_reports FROM '.get_table_prefix().'catalogues WHERE '.db_string_not_equal_to('c_send_view_reports',''));
		$doing=array();
		foreach ($catalogues as $catalogue)
		{
			switch($catalogue['c_send_view_reports'])
			{
				case 'daily':
					$amount=60*60*24;
					break;
				case 'weekly':
					$amount=60*60*24*7;
					break;
				case 'monthly':
					$amount=60*60*24*31;
					break;
				case 'quarterly':
					$amount=60*60*24*93;
					break;
				default:
					$amount=NULL;
			}

			if (!is_null($amount))
			{
				$last_time=intval(get_value('last_catalogue_reports_'.$catalogue['c_send_view_reports']));
				if ($last_time<=($time-$amount))
				{
					// Mark done
					if (!$done_reports[$catalogue['c_send_view_reports']])
					{
						set_value('last_catalogue_reports_'.$catalogue['c_send_view_reports'],strval($time));
						$done_reports[$catalogue['c_send_view_reports']]=true;
					}

					$doing[]=$catalogue; // Mark as doing, rather than do immediately - so to avoid race conditions
				}
			}
		}

		if (count($doing)!=0)
		{
			require_code('notifications');
			require_code('catalogues');
			require_lang('catalogues');
		}

		if (function_exists('set_time_limit')) @set_time_limit(0);

		// Now for the intensive part
		foreach ($doing as $catalogue)
		{
			$start=0;
			do
			{
				// So, we find all the entries in their catalogue, and group them by submitters
				$entries=$GLOBALS['SITE_DB']->query_select('catalogue_entries',array('id','ce_submitter','ce_views','ce_views_prior'),array('c_name'=>$catalogue['c_name']),'ORDER BY ce_submitter',2000,$start);
				$members=array();
				foreach ($entries as $entry)
				{
					if (!array_key_exists($entry['ce_submitter'],$members))
						$members[$entry['ce_submitter']]=array();

					$members[$entry['ce_submitter']][]=$entry;
				}
				$catalogue_title=get_translated_text($catalogue['c_title']);
				$regularity=do_lang('VR_'.strtoupper($catalogue['c_send_view_reports']));
				$fields=$GLOBALS['SITE_DB']->query_select('catalogue_fields',array('*'),array('c_name'=>$catalogue['c_name']),'ORDER BY id',1);

				// And now we send out mails, and get ready for the next report
				foreach ($members as $member_id=>$member)
				{
					// Work out the contents of the mail
					$buildup='';
					foreach ($member as $entry)
					{
						$field_values=get_catalogue_entry_field_values($catalogue['c_name'],$entry,array(0),$fields);
						$entry_title=$field_values[0]['effective_value'];
						$views=$entry['ce_views']-$entry['ce_views_prior'];
						$GLOBALS['SITE_DB']->query_update('catalogue_entries',array('ce_views_prior'=>$entry['ce_views']),array('id'=>$entry['id']),'',1);
						$temp=do_lang($catalogue['c_name'].'__CATALOGUE_VIEW_REPORT_LINE',comcode_escape(is_object($entry_title)?$entry_title->evaluate():$entry_title),integer_format($views),NULL,NULL,false);
						if (is_null($temp)) $temp=do_lang('DEFAULT__CATALOGUE_VIEW_REPORT_LINE',comcode_escape(is_object($entry_title)?$entry_title->evaluate():$entry_title),integer_format($views));
						$buildup.=$temp;
					}
					$mail=do_lang($catalogue['c_name'].'__CATALOGUE_VIEW_REPORT',$buildup,comcode_escape($catalogue_title),$regularity,get_lang($member_id),false);
					if (is_null($mail)) $mail=do_lang('DEFAULT__CATALOGUE_VIEW_REPORT',$buildup,comcode_escape($catalogue_title),array($regularity,get_site_name()),get_lang($member_id));
					$subject_tag=do_lang($catalogue['c_name'].'__CATALOGUE_VIEW_REPORT_SUBJECT',$catalogue_title,get_site_name(),NULL,get_lang($member_id),false);
					if (is_null($subject_tag)) $subject_tag=do_lang('DEFAULT__CATALOGUE_VIEW_REPORT_SUBJECT',comcode_escape($catalogue_title),comcode_escape(get_site_name()),NULL,get_lang($member_id));

					// Send actual notification
					dispatch_notification('catalogue_view_reports__'.$catalogue['c_name'],NULL,$subject_tag,$mail,array($member_id),A_FROM_SYSTEM_PRIVILEGED);
				}
				
				$start+=2000;
			}
			while (count($entries)==2000);
		}
	}

}


