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
 * @package		news
 */

class Block_side_news
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['author']='Chris Graham';
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=2;
		$info['locked']=false;
		$info['parameters']=array('param','blogs','historic','zone','filter','filter_and','title','as_guest');
		return $info;
	}

	/**
	 * Standard modular cache function.
	 *
	 * @return ?array	Map of cache details (cache_on and ttl) (NULL: module is disabled).
	 */
	function cacheing_environment()
	{
		$info=array();
		$info['cache_on']='((addon_installed(\'content_privacy\')) && (!(array_key_exists(\'as_guest\',$map)?($map[\'as_guest\']==\'1\'):false)))?NULL:array(array_key_exists(\'title\',$map)?$map[\'title\']:\'\',array_key_exists(\'blogs\',$map)?$map[\'blogs\']:\'-1\',array_key_exists(\'historic\',$map)?$map[\'historic\']:\'\',array_key_exists(\'as_guest\',$map)?($map[\'as_guest\']==\'1\'):false,$GLOBALS[\'FORUM_DRIVER\']->get_members_groups(get_member(),false,true),array_key_exists(\'zone\',$map)?$map[\'zone\']:get_module_zone(\'news\'),array_key_exists(\'filter\',$map)?$map[\'filter\']:get_param(\'news_filter\',\'\'),array_key_exists(\'param\',$map)?intval($map[\'param\']):5,array_key_exists(\'filter_and\',$map)?$map[\'filter_and\']:\'\')';
		$info['ttl']=(get_value('no_block_timeout')==='1')?60*60*24*365*5/*5 year timeout*/:15;
		return $info;
	}

	/**
	 * Standard modular run function.
	 *
	 * @param  array		A map of parameters.
	 * @return tempcode	The result of execution.
	 */
	function run($map)
	{
		require_lang('news');
		require_css('news');

		$max=array_key_exists('param',$map)?intval($map['param']):5;
		$zone=array_key_exists('zone',$map)?$map['zone']:get_module_zone('news');
		require_lang('news');
		$blogs=array_key_exists('blogs',$map)?intval($map['blogs']):-1;
		$historic=array_key_exists('historic',$map)?$map['historic']:'';
		$filter_and=array_key_exists('filter_and',$map)?$map['filter_and']:'';

		global $NEWS_CATS_CACHE;
		if (!isset($NEWS_CATS_CACHE))
		{
			$NEWS_CATS_CACHE=$GLOBALS['SITE_DB']->query_select('news_categories',array('*'),array('nc_owner'=>NULL));
			$NEWS_CATS_CACHE=list_to_map('id',$NEWS_CATS_CACHE);
		}

		$content=new ocp_tempcode();

		// News Query
		require_code('ocfiltering');
		$filter=array_key_exists('filter',$map)?$map['filter']:get_param('news_filter','*');
		$filters_1=ocfilter_to_sqlfragment($filter,'p.news_category','news_categories',NULL,'p.news_category','id'); // Note that the parameters are fiddled here so that category-set and record-set are the same, yet SQL is returned to deal in an entirely different record-set (entries' record-set)
		$filters_2=ocfilter_to_sqlfragment($filter,'d.news_entry_category','news_categories',NULL,'d.news_category','id'); // Note that the parameters are fiddled here so that category-set and record-set are the same, yet SQL is returned to deal in an entirely different record-set (entries' record-set)
		$q_filter='('.$filters_1.' OR '.$filters_2.')';
		if ($blogs===0)
		{
			if ($q_filter!='') $q_filter.=' AND ';
			$q_filter.='nc_owner IS NULL';
		}
		elseif ($blogs===1)
		{
			if ($q_filter!='') $q_filter.=' AND ';
			$q_filter.='(nc_owner IS NOT NULL)';
		}
		if ($blogs!=-1)
		{
			$join=' LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'news_categories c ON c.id=p.news_category';
		} else $join='';

		if ($filter_and!='')
		{
			$filters_and_1=ocfilter_to_sqlfragment($filter_and,'p.news_category','news_categories',NULL,'p.news_category','id'); // Note that the parameters are fiddled here so that category-set and record-set are the same, yet SQL is returned to deal in an entirely different record-set (entries' record-set)
			$filters_and_2=ocfilter_to_sqlfragment($filter_and,'d.news_entry_category','news_categories',NULL,'d.news_category','id'); // Note that the parameters are fiddled here so that category-set and record-set are the same, yet SQL is returned to deal in an entirely different record-set (entries' record-set)
			$q_filter.=' AND ('.$filters_and_1.' OR '.$filters_and_2.')';
		}

		if (addon_installed('content_privacy'))
		{
			require_code('content_privacy');
			$as_guest=array_key_exists('as_guest',$map)?($map['as_guest']=='1'):false;
			$viewing_member_id=$as_guest?$GLOBALS['FORUM_DRIVER']->get_guest_id():mixed();
			list($privacy_join,$privacy_where)=get_privacy_where_clause('news','p',$viewing_member_id);
			$join.=$privacy_join;
			$q_filter.=$privacy_where;
		}

		if ($historic=='')
		{
			$news=$GLOBALS['SITE_DB']->query('SELECT p.* FROM '.get_table_prefix().'news p LEFT JOIN '.get_table_prefix().'news_category_entries d ON d.news_entry=p.id'.$join.' WHERE '.$q_filter.' AND validated=1'.(can_arbitrary_groupby()?' GROUP BY p.id':'').' ORDER BY date_and_time DESC',$max,NULL,false,true);
		} else
		{
			if (function_exists('set_time_limit')) @set_time_limit(0);
			$start=0;
			do
			{
				$_rows=$GLOBALS['SITE_DB']->query('SELECT p.* FROM '.get_table_prefix().'news p LEFT JOIN '.get_table_prefix().'news_category_entries d ON p.id=d.news_entry'.$join.' WHERE '.$q_filter.' AND validated=1'.(can_arbitrary_groupby()?' GROUP BY p.id':'').' ORDER BY p.date_and_time DESC',200,$start,false,true);
				$news=array();
				foreach ($_rows as $row)
				{
					$ok=false;
					switch ($historic)
					{
						case 'month':
							if ((date('m',utctime_to_usertime($row['date_and_time']))==date('m',utctime_to_usertime())) && (date('Y',utctime_to_usertime($row['date_and_time']))!=date('Y',utctime_to_usertime()))) $ok=true;
							break;

						case 'week':
							if ((date('W',utctime_to_usertime($row['date_and_time']))==date('W',utctime_to_usertime())) && (date('Y',utctime_to_usertime($row['date_and_time']))!=date('Y',utctime_to_usertime()))) $ok=true;
							break;

						case 'day':
							if ((date('d',utctime_to_usertime($row['date_and_time']))==date('d',utctime_to_usertime())) && (date('Y',utctime_to_usertime($row['date_and_time']))!=date('Y',utctime_to_usertime()))) $ok=true;
							break;
					}
					if ($ok)
					{
						if (count($news)<$max) $news[]=$row;
						else break;
					}
				}
				$start+=200;
			}
			while ((count($_rows)==200) && (count($news)<$max));
			unset($_rows);
		}
		$news=remove_duplicate_rows($news,'id');

		$_title=do_lang_tempcode(($blogs===1)?'BLOGS_POSTS':'NEWS');
		if ((array_key_exists('title',$map)) && ($map['title']!='')) $_title=protect_from_escaping(escape_html($map['title']));

		foreach ($news as $myrow)
		{
			if (has_category_access(get_member(),'news',strval($myrow['news_category'])))
			{
				$url_map=array('page'=>'news','type'=>'view','id'=>$myrow['id']);
				if ($filter!='*') $url_map['filter']=$filter;
				if (($filter_and!='*') && ($filter_and!='')) $url_map['filter_and']=$filter_and;
				if ($blogs===1) $url_map['blog']=1;
				$full_url=build_url($url_map,$zone);

				$news_title=get_translated_tempcode($myrow['title']);
				$date=locale_filter(date('d M',utctime_to_usertime($myrow['date_and_time'])));

				$summary=get_translated_tempcode($myrow['news']);
				if ($summary->is_empty())
				{
					$summary=get_translated_tempcode($myrow['news_article']);
				}

				if (!array_key_exists($myrow['news_category'],$NEWS_CATS_CACHE))
				{
					$_news_cats=$GLOBALS['SITE_DB']->query_select('news_categories',array('*'),array('id'=>$myrow['news_category']),'',1);
					if (array_key_exists(0,$_news_cats))
						$NEWS_CATS_CACHE[$myrow['news_category']]=$_news_cats[0];
				}
				$category=get_translated_text($NEWS_CATS_CACHE[$myrow['news_category']]['nc_title']);

				$content->attach(do_template('BLOCK_SIDE_NEWS_SUMMARY',array(
					'_GUID'=>'f7bc5288680e68641ca94ca4a3111d4a',
					'IMG_URL'=>($NEWS_CATS_CACHE[$myrow['news_category']]['nc_img']=='')?'':find_theme_image($NEWS_CATS_CACHE[$myrow['news_category']]['nc_img']),
					'AUTHOR'=>$myrow['author'],
					'ID'=>strval($myrow['id']),
					'SUBMITTER'=>strval($myrow['submitter']),
					'CATEGORY'=>$category,
					'BLOG'=>$blogs===1,
					'FULL_URL'=>$full_url,
					'NEWS'=>$summary,
					'NEWS_TITLE'=>$news_title,
					'_DATE'=>strval($myrow['date_and_time']),
					'DATE'=>$date,
				)));
			}
		}

		$tmp=array('page'=>'news','type'=>'misc');
		if ($filter!='*') $tmp[is_numeric($filter)?'id':'filter']=$filter;
		if (($filter_and!='*') && ($filter_and!='')) $tmp['filter_and']=$filter_and;
		if ($blogs!=-1) $tmp['blog']=$blogs;
		$archive_url=build_url($tmp,$zone);
		$_is_on_rss=get_option('is_rss_advertised',true);
		$is_on_rss=is_null($_is_on_rss)?0:intval($_is_on_rss); // Set to zero if we don't want to show RSS links
		$submit_url=new ocp_tempcode();

		if ((($blogs!==1) || (has_privilege(get_member(),'have_personal_category','cms_news'))) && (has_actual_page_access(NULL,($blogs===1)?'cms_blogs':'cms_news',NULL,NULL)) && (has_submit_permission('high',get_member(),get_ip_address(),($blogs===1)?'cms_blogs':'cms_news')))
		{
			$map2=array('page'=>($blogs===1)?'cms_blogs':'cms_news','type'=>'ad','redirect'=>SELF_REDIRECT);
			if (is_numeric($filter))
			{
				$map2['cat']=$filter; // select news cat by default, if we are only showing one news cat in this block
			} elseif ($filter!='*')
			{
				$pos_a=strpos($filter,',');
				$pos_b=strpos($filter,'-');
				if ($pos_a!==false)
				{
					$first_cat=substr($filter,0,$pos_a);
				}
				elseif ($pos_b!==false)
				{
					$first_cat=substr($filter,0,$pos_b);
				} else $first_cat='';
				if (is_numeric($first_cat))
				{
					$map2['cat']=$first_cat;
				}
			}
			$submit_url=build_url($map2,get_module_zone(($blogs===1)?'cms_blogs':'cms_news'));
		}

		return do_template('BLOCK_SIDE_NEWS',array('_GUID'=>'611b83965c4b6e42fb4a709d94c332f7','BLOG'=>$blogs===1,'TITLE'=>$_title,'CONTENT'=>$content,'SUBMIT_URL'=>$submit_url,'ARCHIVE_URL'=>$archive_url));
	}

}


