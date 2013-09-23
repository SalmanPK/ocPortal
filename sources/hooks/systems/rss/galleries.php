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
 * @package		galleries
 */

class Hook_rss_galleries
{

	/**
	 * Standard modular run function for RSS hooks.
	 *
	 * @param  string			A list of categories we accept from
	 * @param  TIME			Cutoff time, before which we do not show results from
	 * @param  string			Prefix that represents the template set we use
	 * @set    RSS_ ATOM_
	 * @param  string			The standard format of date to use for the syndication type represented in the prefix
	 * @param  integer		The maximum number of entries to return, ordering by date
	 * @return ?array			A pair: The main syndication section, and a title (NULL: error)
	 */
	function run($_filters,$cutoff,$prefix,$date_string,$max)
	{
		if (!addon_installed('galleries')) return NULL;

		if (!has_actual_page_access(get_member(),'galleries')) return NULL;

		$filters_1=ocfilter_to_sqlfragment($_filters,'name','galleries','parent_id','name','name',false,false); // Note that the parameters are fiddled here so that category-set and record-set are the same, yet SQL is returned to deal in an entirely different record-set (entries' record-set)
		$filters=ocfilter_to_sqlfragment($_filters,'cat','galleries','parent_id','cat','name',false,false); // Note that the parameters are fiddled here so that category-set and record-set are the same, yet SQL is returned to deal in an entirely different record-set (entries' record-set)

		require_lang('galleries');

		$content=new ocp_tempcode();
		$_galleries=array();
		if ($GLOBALS['SITE_DB']->query_value_if_there('SELECT COUNT(*) FROM '.get_table_prefix().'galleries WHERE '.$filters_1,false,true)<3000)
		{
			$_galleries=$GLOBALS['SITE_DB']->query('SELECT fullname,name FROM '.get_table_prefix().'galleries WHERE '.$filters_1,NULL,NULL,false,true);
			foreach ($_galleries as $i=>$_gallery)
			{
				$_galleries[$i]['text_original']=get_translated_text($_gallery['fullname']);
			}
		}
		$galleries=collapse_2d_complexity('name','text_original',$_galleries);

		$privacy_join='';
		$privacy_where='';
		if (addon_installed('content_privacy'))
		{
			require_code('content_privacy');
			list($privacy_join,$privacy_where)=get_privacy_where_clause('video','r');
		}
		$rows1=$GLOBALS['SITE_DB']->query('SELECT r.* FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'videos r'.$privacy_join.' WHERE add_date>'.strval($cutoff).' AND '.$filters.(((!has_privilege(get_member(),'see_unvalidated')) && (addon_installed('unvalidated')))?' AND validated=1 ':'').$privacy_where.' ORDER BY add_date DESC',$max);

		$privacy_join='';
		$privacy_where='';
		if (addon_installed('content_privacy'))
		{
			require_code('content_privacy');
			list($privacy_join,$privacy_where)=get_privacy_where_clause('image','r');
		}
		$rows2=browser_matches('itunes')?array():$GLOBALS['SITE_DB']->query('SELECT r.* FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'images r'.$privacy_join.' WHERE add_date>'.strval($cutoff).' AND '.$filters.(((!has_privilege(get_member(),'see_unvalidated')) && (addon_installed('unvalidated')))?' AND validated=1 ':'').$privacy_where.' ORDER BY add_date DESC',$max);

		$rows=array_merge($rows1,$rows2);
		foreach ($rows as $row)
		{
			$id=strval($row['id']);
			$author=$GLOBALS['FORUM_DRIVER']->get_username($row['submitter']);
			if (is_null($author)) $author='';

			$news_date=date($date_string,$row['add_date']);
			$edit_date=is_null($row['edit_date'])?'':date($date_string,$row['edit_date']);

			if (get_translated_text($row['title'])!='')
			{
				$news_title=xmlentities(get_translated_text($row['title']));
			} else
			{
				$news_title=xmlentities(do_lang('THIS_WITH_SIMPLE',(array_key_exists('video_views',$row)?do_lang('VIDEO'):do_lang('IMAGE')),strval($row['id'])));
			}
			$_summary=get_translated_tempcode($row['description']);
			$summary=xmlentities($_summary->evaluate());
			$news='';

			if (!array_key_exists($row['cat'],$galleries))
			{
				$_fullname=$GLOBALS['SITE_DB']->query_select_value_if_there('galleries','fullname',array('name'=>$row['cat']));
				if (is_null($_fullname)) continue;
				$galleries[$row['cat']]=get_translated_text($_fullname);
			}
			$category=$galleries[$row['cat']];
			$category_raw=$row['cat'];

			$view_url=build_url(array('page'=>'galleries','type'=>array_key_exists('video_views',$row)?'video':'image','id'=>$row['id']),get_module_zone('galleries'),NULL,false,false,true);

			if (($prefix=='RSS_') && (get_option('is_on_comments')=='1') && ($row['allow_comments']>=1))
			{
				$if_comments=do_template('RSS_ENTRY_COMMENTS',array('_GUID'=>'65dc0cec8c75f565c58c95fa1667aa1e','COMMENT_URL'=>$view_url,'ID'=>strval($row['id'])));
			} else $if_comments=new ocp_tempcode();

			require_code('images');
			$thumb_url=ensure_thumbnail($row['url'],$row['thumb_url'],'galleries',array_key_exists('video_views',$row)?'videos':'images',$row['id']);
			$enclosure_url=$row['url'];
			if (url_is_local($enclosure_url)) $enclosure_url=get_custom_base_url().'/'.$enclosure_url;
			list($enclosure_length,$enclosure_type)=get_enclosure_details($row['url'],$enclosure_url);

			$meta=seo_meta_get_for(array_key_exists('video_views',$row)?'video':'image',strval($row['id']));
			$keywords=trim($meta[0],', ');

			$content->attach(do_template($prefix.'ENTRY',array(
				'ENCLOSURE_URL'=>$enclosure_url,
				'ENCLOSURE_LENGTH'=>$enclosure_length,
				'ENCLOSURE_TYPE'=>$enclosure_type,
				'VIEW_URL'=>$view_url,
				'SUMMARY'=>$summary,
				'EDIT_DATE'=>$edit_date,
				'IF_COMMENTS'=>$if_comments,
				'TITLE'=>$news_title,
				'CATEGORY_RAW'=>$category_raw,
				'CATEGORY'=>$category,
				'AUTHOR'=>$author,
				'ID'=>$id,
				'NEWS'=>$news,
				'DATE'=>$news_date,
				'DURATION'=>array_key_exists('video_length',$row)?(strval(intval(floor(floatval($row['video_length']))/60.0)).':'.strval($row['video_length']%60)):NULL,
				'KEYWORDS'=>$keywords,
			)));
		}

		require_lang('galleries');
		return array($content,do_lang('GALLERIES'));
	}

}


