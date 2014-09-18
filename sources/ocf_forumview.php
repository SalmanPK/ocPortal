<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		ocf_forum
 */

/**
 * Render the OCF forumview.
 *
 * @param  ?integer	Forum ID (NULL: private topics).
 * @param  ?array		The forum row (NULL: private topics).
 * @param  string		The filter category (blank if no filter)
 * @param  integer	Maximum results to show
 * @param  integer	Offset for result showing
 * @param  AUTO_LINK	Virtual root
 * @param  ?MEMBER	The member to show private topics of (NULL: not showing private topics)
 * @param  tempcode	The breadcrumbs
 * @return mixed		Either Tempcode (an interface that must be shown) or a pair: The main Tempcode, the forum name (string). For a PT view, it is always a tuple, never raw Tempcode (as it can go inside a tabset).
 */
function ocf_render_forumview($id,$forum_info,$current_filter_cat,$max,$start,$root,$of_member_id,$breadcrumbs)
{
	require_css('ocf');

	$type=is_null($id)?'pt':'misc';

	if ($type=='pt')
	{
		if (is_guest()) access_denied('NOT_AS_GUEST');

		require_code('ocf_forumview_pt');
		$details=ocf_get_private_topics($start,$max,$of_member_id);
		$root_forum_name=$GLOBALS['FORUM_DB']->query_select_value('f_forums','f_name',array('id'=>$root));
		$pt_username=$GLOBALS['FORUM_DRIVER']->get_username($of_member_id);
		$pt_displayname=$GLOBALS['FORUM_DRIVER']->get_username($of_member_id,true);
		if (is_null($pt_username)) $pt_username=do_lang('UNKNOWN');
		$details['name']=do_lang_tempcode('PRIVATE_TOPICS_OF',escape_html($pt_displayname),escape_html($pt_username));
	} else
	{
		$details=ocf_get_forum_view($id,$forum_info,$start,$max);

		if ((array_key_exists('question',$details)) && (is_null(get_bot_type())))
		{
			// Was there a question answering attempt?
			$answer=post_param('answer','-1#');
			if ($answer!='-1#')
			{
				if (strtolower(trim($answer))==strtolower(trim($details['answer']))) // They got it right
				{
					if (!is_guest())
					{
						$GLOBALS['FORUM_DB']->query_insert('f_forum_intro_member',array('i_forum_id'=>$id,'i_member_id'=>get_member()));
					} else
					{
						$GLOBALS['FORUM_DB']->query_insert('f_forum_intro_ip',array('i_forum_id'=>$id,'i_ip'=>get_ip_address(3)));
					}
				} else // They got it wrong
				{
					$url=get_self_url();
					$title=get_screen_title('INTRO_QUESTION');
					return redirect_screen($title,$url,do_lang_tempcode('INTRO_ANSWER_INCORRECT'),false,'warn');
				}
			} else
			{ // Ask the question
				$title=get_screen_title(($details['answer']=='')?'INTRO_NOTICE':'INTRO_QUESTION');
				$url=get_self_url();
				return do_template('OCF_FORUM_INTRO_QUESTION_SCREEN',array('_GUID'=>'ee9caba0735aea9c39c4194337036e81','ANSWER'=>$details['answer'],'TITLE'=>$title,'URL'=>$url,'QUESTION'=>$details['question']));
			}
		}
	}

	if ($type=='pt') $forum_name=do_lang('PRIVATE_TOPICS');
	else $forum_name=$details['name'];

	$may_mass_moderate=(array_key_exists('may_move_topics',$details)) || (array_key_exists('may_delete_topics',$details));

	// Find forum groupings
	$forum_groupings=new ocp_tempcode();
	if ($type!='pt')
	{
		foreach ($details['forum_groupings'] as $best=>$forum_grouping)
		{
			if (array_key_exists('subforums',$forum_grouping)) // We only show if there is something in it
			{
				// Subforums
				$forums=new ocp_tempcode();
				foreach ($forum_grouping['subforums'] as $subforum)
				{
					if ((array_key_exists('last_topic_id',$subforum)) && (!is_null($subforum['last_topic_id'])))
					{
						if (!is_null($subforum['last_member_id']))
						{
							if (!is_guest($subforum['last_member_id']))
							{
								$poster=do_template('OCF_USER_MEMBER',array(
									'_GUID'=>'39r932rwefldjfldjlf',
									'FIRST'=>true,
									'USERNAME'=>$subforum['last_username'],
									'PROFILE_URL'=>$GLOBALS['FORUM_DRIVER']->member_profile_url($subforum['last_member_id'],false,true),
									'MEMBER_ID'=>strval($subforum['last_member_id'])
								));
							} else $poster=protect_from_escaping(escape_html($subforum['last_username']));
						} else
						{
							$poster=do_lang_tempcode('NA_EM');
						}

						$topic_url=build_url(array('page'=>'topicview','id'=>$subforum['last_topic_id'],'type'=>'first_unread'),get_module_zone('topicview'));
						$topic_url->attach('#first_unread');

						$latest=do_template('OCF_FORUM_LATEST',array(
							'_GUID'=>'dlfsdfkoewfdlfsldfk',
							'DATE'=>is_null($subforum['last_time'])?do_lang_tempcode('NA_EM'):protect_from_escaping(escape_html(get_timezoned_date($subforum['last_time']))),
							'DATE_RAW'=>is_null($subforum['last_time'])?'':strval($subforum['last_time']),
							'TOPIC_URL'=>$topic_url,
							'TOPIC_TITLE'=>($subforum['last_title']=='')?do_lang_tempcode('NA'):$subforum['last_title'],
							'POSTER'=>$poster,
							'MEMBER_ID'=>is_null($subforum['last_member_id'])?'':strval($subforum['last_member_id']),
							'ID'=>strval($subforum['last_topic_id'])
						));
					}
					elseif (array_key_exists('protected_last_post',$subforum))
					{
						$latest=do_lang_tempcode('PROTECTED_LAST_POST');
					}
					else $latest=do_lang_tempcode('NO_POSTS_YET');

					// Work out where the subforum URL is
					if (($subforum['redirection']!='') && (!is_numeric($subforum['redirection'])))
					{
						$subforum_url=$subforum['redirection'];

						$subforum_num_posts=do_lang_tempcode('NA_EM');
						$subforum_num_topics=do_lang_tempcode('NA_EM');
						$latest=do_lang_tempcode('NA_EM');
						$subforum['has_new']=false;
						$subforums=new ocp_tempcode();
						$new_post_or_not='redirect';
					}
					else
					{
						if ($subforum['redirection']!='')
						{
							$subforum_url=build_url(array('page'=>'_SELF','id'=>$subforum['redirection']),'_SELF');
							$new_post_or_not=$subforum['has_new']?'new_posts_redirect':'no_new_posts_redirect';
						} else
						{
							$subforum_url=build_url(array('page'=>'_SELF','id'=>$subforum['id']),'_SELF');
							$new_post_or_not=$subforum['has_new']?'new_posts':'no_new_posts';
						}

						$subforum_num_posts=protect_from_escaping(escape_html(integer_format($subforum['num_posts'])));
						$subforum_num_topics=protect_from_escaping(escape_html(integer_format($subforum['num_topics'])));

						// Subsubforums
						$subforums=new ocp_tempcode();
						ksort($subforum['children']);
						foreach ($subforum['children'] as $child)
						{
							// Work out where the subsubforum url is
							if (is_numeric($child['redirection']))
							{
								$link=hyperlink(build_url(array('page'=>'_SELF','id'=>$child['redirection']),'_SELF'),$child['name'],false,true);
							}
							elseif ($child['redirection']!='')
							{
								$link=hyperlink($child['redirection'],$child['name'],false,true);
							} else
							{
								$link=hyperlink(build_url(array('page'=>'_SELF','id'=>$child['id']),'_SELF'),$child['name'],false,true);
							}
							if (!$subforums->is_empty()) $subforums->attach(do_lang_tempcode('LIST_SEP'));
							$subforums->attach($link);
						}
					}

					$edit_url=has_actual_page_access(get_member(),'admin_ocf_forums')?build_url(array('page'=>'admin_ocf_forums','type'=>'_ed','id'=>$subforum['id']),'adminzone'):new ocp_tempcode();

					$forum_rules_url='';
					$intro_question_url='';
					if (!$subforum['intro_question']->is_empty())
					{
						if ($subforum['intro_answer']=='')
						{
							$keep=keep_symbol(array());
							$intro_rules_url=find_script('rules').'?id='.rawurlencode(strval($subforum['id'])).$keep;
						} else
						{
							$keep=keep_symbol(array());
							$intro_question_url=find_script('rules').'?id='.rawurlencode(strval($subforum['id'])).$keep;
						}
					}

					$forums->attach(do_template('OCF_FORUM_IN_GROUPING',array(
						'_GUID'=>'slkfjof9jlsdjcsd',
						'ID'=>strval($subforum['id']),
						'NEW_POST_OR_NOT'=>$new_post_or_not,
						'LANG_NEW_POST_OR_NOT'=>do_lang('POST_INDICATOR_'.$new_post_or_not),
						'FORUM_NAME'=>$subforum['name'],
						'FORUM_URL'=>$subforum_url,
						'DESCRIPTION'=>$subforum['description'],
						'NUM_POSTS'=>$subforum_num_posts,
						'NUM_TOPICS'=>$subforum_num_topics,
						'LATEST'=>$latest,
						'SUBFORUMS'=>$subforums,
						'EDIT_URL'=>$edit_url,
						'FORUM_RULES_URL'=>$forum_rules_url,
						'INTRO_QUESTION_URL'=>$intro_question_url
					)));
				}

				// Category itself
				if ((!array_key_exists('expanded_by_default',$forum_grouping)) || ($forum_grouping['expanded_by_default']==1))
				{
					$display='block';
					$expand_type='contract';
				} else
				{
					$display='none';
					$expand_type='expand';
				}
				$forum_grouping_description=array_key_exists('description',$forum_grouping)?$forum_grouping['description']:''; // If not set, is missing from DB
				$forum_groupings->attach(do_template('OCF_FORUM_GROUPING',array('_GUID'=>'fc9bae42c680ea0162287e2ed3917bbe','GROUPING_ID'=>strval($best),'EXPAND_TYPE'=>$expand_type,'DISPLAY'=>$display,'GROUPING_TITLE'=>array_key_exists('title',$forum_grouping)?$forum_grouping['title']:'','GROUPING_DESCRIPTION'=>$forum_grouping_description,'FORUMS'=>$forums)));
			}
		}
	}

	// Work out what moderator actions can be performed (also includes marking read/unread)
	$moderator_actions='';
	if (($type=='pt') && ($of_member_id==get_member()) && (get_option('enable_pt_filtering')=='1'))
	{
		$moderator_actions.='<option value="categorise_pts">'.do_lang('_CATEGORISE_PTS').'</option>';
		$filter_cats=ocf_get_filter_cats();
		foreach ($filter_cats as $filter_cat)
		{
			if ($filter_cat!='')
				$moderator_actions.='<option value="categorise_pts__'.escape_html($filter_cat).'">'.do_lang('CATEGORISE_PTS_AS',escape_html($filter_cat)).'</option>';
		}
	}
	if (get_option('enable_mark_forum_read')=='1')
	{
		$moderator_actions.='<option value="mark_topics_read">'.do_lang('MARK_READ').'</option>';
		$moderator_actions.='<option value="mark_topics_unread">'.do_lang('MARK_UNREAD').'</option>';
	}

	// Mass moderation
	if ($may_mass_moderate)
	{
		$moderator_actions.='<option value="move_topics">'.do_lang('MOVE_TOPICS').'</option>';
		if (has_privilege(get_member(),'delete_midrange_content','topics',array('forums',$id)))
		{
			$moderator_actions.='<option value="delete_topics">'.do_lang('DELETE_TOPICS').'</option>';
		}
		$moderator_actions.='<option value="pin_topics">'.do_lang('PIN_TOPIC').'</option>';
		$moderator_actions.='<option value="unpin_topics">'.do_lang('UNPIN_TOPIC').'</option>';
		$moderator_actions.='<option value="sink_topics">'.do_lang('SINK_TOPIC').'</option>';
		$moderator_actions.='<option value="unsink_topics">'.do_lang('UNSINK_TOPIC').'</option>';
		$moderator_actions.='<option value="cascade_topics">'.do_lang('CASCADE_TOPIC').'</option>';
		$moderator_actions.='<option value="uncascade_topics">'.do_lang('UNCASCADE_TOPIC').'</option>';
		$moderator_actions.='<option value="open_topics">'.do_lang('OPEN_TOPIC').'</option>';
		$moderator_actions.='<option value="close_topics">'.do_lang('CLOSE_TOPIC').'</option>';
		if (!is_null($id))
		{
			$multi_moderations=ocf_list_multi_moderations($id);
			if (count($multi_moderations)!=0)
			{
				require_lang('ocf_multi_moderations');
				$moderator_actions.='<optgroup label="'.do_lang('MULTI_MODERATIONS').'">';
				foreach ($multi_moderations as $mm_id=>$mm_name)
					$moderator_actions.='<option value="mmt_'.strval($mm_id).'">'.$mm_name.'</option>';
				$moderator_actions.='</optgroup>';
			}
		}
	}

	// Find topics
	$topics=new ocp_tempcode();
	$pinned=false;
	$num_unread=0;
	foreach ($details['topics'] as $topic)
	{
		if (($pinned) && (!in_array('pinned',$topic['modifiers'])))
		{
			$topics->attach(do_template('OCF_PINNED_DIVIDER'));
		}
		$pinned=in_array('pinned',$topic['modifiers']);
		$topics->attach(ocf_render_topic($topic,$moderator_actions!='',$type=='pt',NULL));
		if (in_array('unread',$topic['modifiers'])) $num_unread++;
	}

	// Buttons
	$button_array=array();
	if ((!is_guest()) && ($type!='pt'))
	{
		if (get_option('enable_mark_forum_read')=='1')
		{
			$read_url=build_url(array('page'=>'topics','type'=>'mark_read','id'=>$id),get_module_zone('topics'));
			$button_array[]=array('immediate'=>true,'title'=>do_lang_tempcode('_MARK_READ'),'url'=>$read_url,'img'=>'buttons__mark_read_forum');
		}
	}
	if ($type!='pt')
	{
		if (addon_installed('search'))
		{
			$search_url=build_url(array('page'=>'search','type'=>'misc','id'=>'ocf_posts','search_under'=>$id),get_module_zone('search'));
			$button_array[]=array('immediate'=>false,'rel'=>'search','title'=>do_lang_tempcode('SEARCH'),'url'=>$search_url,'img'=>'buttons__search');
		}
		$new_topic_url=build_url(array('page'=>'topics','type'=>'new_topic','id'=>$id),get_module_zone('topics'));
	} else
	{
		if (addon_installed('search'))
		{
			$search_url=build_url(array('page'=>'search','type'=>'misc','id'=>'ocf_own_pt'),get_module_zone('search'));
			$button_array[]=array('immediate'=>false,'rel'=>'search','title'=>do_lang_tempcode('SEARCH'),'url'=>$search_url,'img'=>'buttons__search');
		}
		$new_topic_url=build_url(array('page'=>'topics','type'=>'new_pt','id'=>get_member()),get_module_zone('topics'));
	}
	if ($type=='pt')
	{
		//There has been debate in the past whether to have a link from PTs to the forum or not! Currently using the Social menu is considered canon - templating could add a button in though.
		//$archive_url=$GLOBALS['FORUM_DRIVER']->forum_url(db_get_first_id(),true);
		//$button_array[]=array('immediate'=>false,'title'=>do_lang_tempcode('ROOT_FORUM'),'url'=>$archive_url,'img'=>'buttons__forum');
	}
	if (array_key_exists('may_post_topic',$details))
	{
		if ($type=='pt')
		{
			//if ($of_member_id!==get_member())		Actually we'll leave the "send message" button in your inbox, as a way for being able to type in who to send it to
			{
				$button_array[]=array('immediate'=>false,'rel'=>'add','title'=>do_lang_tempcode('ADD_PRIVATE_TOPIC'),'url'=>$new_topic_url,'img'=>'buttons__send');
			}
		} else
		{
			$button_array[]=array('immediate'=>false,'rel'=>'add','title'=>do_lang_tempcode('ADD_TOPIC'),'url'=>$new_topic_url,'img'=>'buttons__new_topic');
		}
	}
	$buttons=ocf_button_screen_wrap($button_array);

	$starter_title=($type=='pt')?do_lang_tempcode('WITH_TITLING'):new ocp_tempcode();

	// Wrap it all up
	$action_url=build_url(array('page'=>'topics'),get_module_zone('topics'),NULL,false,true);
	if (!$topics->is_empty())
	{
		if ($GLOBALS['XSS_DETECT']) ocp_mark_as_escaped($moderator_actions);

		require_code('templates_pagination');
		$pagination=pagination(do_lang_tempcode('FORUM_TOPICS'),$start,'forum_start',$max,'forum_max',$details['max_rows'],false,5,NULL,($type=='pt' && get_page_name()=='members')?'tab__pts':'');

		$sort=array_key_exists('sort',$details)?$details['sort']:'last_post';
		$topic_wrapper=do_template('OCF_FORUM_TOPIC_WRAPPER',array(
			'_GUID'=>'e452b81001e5c6b7adb4d82e627bf983',
			'TYPE'=>$type,
			'ID'=>is_null($id)?NULL:strval($id),
			'MAX'=>strval($max),
			'ORDER'=>$sort,
			'MAY_CHANGE_MAX'=>array_key_exists('may_change_max',$details),
			'ACTION_URL'=>$action_url,
			'BUTTONS'=>$buttons,
			'STARTER_TITLE'=>$starter_title,
			'BREADCRUMBS'=>$breadcrumbs,
			'PAGINATION'=>$pagination,
			'MODERATOR_ACTIONS'=>$moderator_actions,
			'TOPICS'=>$topics,
			'FORUM_NAME'=>$forum_name,
		));
	} else
	{
		$topic_wrapper=new ocp_tempcode();
		$moderator_actions='';
	}

	// Filters
	$filters=new ocp_tempcode();
	if (get_option('enable_pt_filtering')=='1')
	{
		if ($type=='pt')
		{
			$filter_cats=ocf_get_filter_cats(true);

			$filters_arr=array();

			foreach ($filter_cats as $fi=>$filter_cat)
			{
				if ($filter_cat!='')
				{
					$filtered_url=build_url(array('page'=>'_SELF','category'=>$filter_cat),'_SELF',NULL,true,false,false,'tab__pts');
					$filter_active=$filter_cat==$current_filter_cat;
					$filters_arr[]=array(
						'URL'=>$filter_active?new ocp_tempcode():$filtered_url,
						'CAPTION'=>$filter_cat,
						'HAS_NEXT'=>isset($filter_cats[$fi+1]),
					);
				}
			}

			$filters=do_template('OCF_PT_FILTERS',array('_GUID'=>'1ffed81e1cfb82d0741d0669cdc38876','FILTERS'=>$filters_arr,'RESET_URL'=>build_url(array('page'=>'_SELF','category'=>NULL),'_SELF',NULL,true)));
		}
	}

	$map=array(
		'_GUID'=>'1c14afd9265b1bf69375169dd6faf83c',
		'STARTER_TITLE'=>$starter_title,
		'ID'=>is_null($id)?NULL:strval($id),
		'DESCRIPTION'=>array_key_exists('description',
		$details)?$details['description']:'',
		'FILTERS'=>$filters,
		'BUTTONS'=>$buttons,
		'TOPIC_WRAPPER'=>$topic_wrapper,
		'BREADCRUMBS'=>$breadcrumbs,
		'FORUM_GROUPINGS'=>$forum_groupings,
	);
	$content=do_template('OCF_FORUM',$map);

	return array($content,$forum_name);
}

/**
 * Get details of a topic (to show eventually as a row in a forum or results view). This is a helper function, and thus the interface is not very user friendly.
 *
 * @param  array		The DB row of the topic.
 * @param  MEMBER		The member the details are being prepared for.
 * @param  integer	The hot topic definition (taken from the config options).
 * @param  boolean	Whether the viewing member has a post in the topic.
 * @return array		The details.
 */
function ocf_get_topic_array($topic_row,$member_id,$hot_topic_definition,$involved)
{
	$topic=array();

	if (!is_null($topic_row['p_post']))
	{
		$post_row=db_map_restrict($topic_row,array('p_post'))+array('id'=>$topic_row['t_cache_first_post_id']);
		$topic['first_post']=get_translated_tempcode('f_posts',$post_row,'p_post',$GLOBALS['FORUM_DB']);
	} else
	{
		$topic['first_post']=new ocp_tempcode();
	}

	$topic['id']=$topic_row['id'];
	$topic['num_views']=$topic_row['t_num_views'];
	$topic['num_posts']=$topic_row['t_cache_num_posts'];
	$topic['forum_id']=$topic_row['t_forum_id'];
	$topic['description']=$topic_row['t_description'];
	$topic['description_link']=$topic_row['t_description_link'];

	// If it's a spacer post, we need to intercede at this point, and make a better one
	$linked_type='';
	$linked_id='';
	$is_spacer_post=(substr($topic['first_post']->evaluate(),0,strlen(do_lang('SPACER_POST_MATCHER')))==do_lang('SPACER_POST_MATCHER'));
	if ($is_spacer_post)
	{
		$c_prefix=do_lang('COMMENT').': #';
		if ((substr($topic['description'],0,strlen($c_prefix))==$c_prefix) && ($topic['description_link']!=''))
		{
			list($linked_type,$linked_id)=explode('_',substr($topic['description'],strlen($c_prefix)),2);
			$topic['description']='';

			require_code('ocf_posts');
			list(,$new_post)=ocf_display_spacer_post($linked_type,$linked_id);
			if (!is_null($new_post)) $topic['first_post']=$new_post;
		}
	}

	$topic['emoticon']=$topic_row['t_emoticon'];
	$topic['first_time']=$topic_row['t_cache_first_time'];
	$topic['first_title']=$topic_row['t_cache_first_title'];
	if ($topic['first_title']=='') $topic['first_title']=do_lang_tempcode('NA');
	if ($is_spacer_post)
	{
		$topic['first_title']=do_lang('SPACER_TOPIC_TITLE_WRAP',$topic['first_title']);
	}
	$topic['first_username']=$topic_row['t_cache_first_username'];
	$topic['first_member_id']=$topic_row['t_cache_first_member_id'];
	if (is_null($topic['first_member_id']))
	{
		require_code('ocf_posts_action2');
		ocf_force_update_topic_cacheing($topic_row['id'],NULL,true,true);
	}
	if (!is_null($topic_row['t_cache_last_post_id']))
	{
		$topic['last_post_id']=$topic_row['t_cache_last_post_id'];
		$topic['last_time']=$topic_row['t_cache_last_time'];
		$topic['last_time_string']=get_timezoned_date($topic_row['t_cache_last_time']);
		$topic['last_title']=$topic_row['t_cache_last_title'];
		$topic['last_username']=$topic_row['t_cache_last_username'];
		$topic['last_member_id']=$topic_row['t_cache_last_member_id'];
	}

	// Modifiers
	$topic['modifiers']=array();
	$has_read=ocf_has_read_topic($topic['id'],$topic_row['t_cache_last_time'],$member_id,$topic_row['l_time']);
	if (!$has_read) $topic['modifiers'][]='unread';
	if ($involved) $topic['modifiers'][]='involved';
	if ($topic_row['t_cascading']==1) $topic['modifiers'][]='announcement';
	if ($topic_row['t_pinned']==1) $topic['modifiers'][]='pinned';
	if ($topic_row['t_sunk']==1) $topic['modifiers'][]='sunk';
	if ($topic_row['t_is_open']==0) $topic['modifiers'][]='closed';
	if (($topic_row['t_validated']==0) && (addon_installed('unvalidated'))) $topic['modifiers'][]='unvalidated';
	if (!is_null($topic_row['t_poll_id'])) $topic['modifiers'][]='poll';
	$num_posts=$topic_row['t_cache_num_posts'];
	$start_time=$topic_row['t_cache_first_time'];
	$end_time=$topic_row['t_cache_last_time'];
	$days=floatval($end_time-$start_time)/60.0/60.0/24.0;
	if ($days==0.0) $days=1.0;
	if (($num_posts>=8) && (intval(round(floatval($num_posts)/$days))>=$hot_topic_definition)) $topic['modifiers'][]='hot';

	return $topic;
}

/**
 * Render a topic row (i.e. a row in a forum or results view), from given details (from ocf_get_topic_array).
 *
 * @param  array		The details (array containing: last_post_id, id, modifiers, emoticon, first_member_id, first_username, first_post, num_posts, num_views).
 * @param  boolean	Whether the viewing member has the facility to mark off topics (send as false if there are no actions for them to perform).
 * @param  boolean	Whether the topic is a Private Topic.
 * @param  ?string	The forum name (NULL: do not show the forum name).
 * @return tempcode	The topic row.
 */
function ocf_render_topic($topic,$has_topic_marking,$pt=false,$show_forum=NULL)
{
	if ((array_key_exists('last_post_id',$topic)) && (!is_null($topic['last_post_id'])))
	{
		$last_post_url=build_url(array('page'=>'topicview','id'=>$topic['last_post_id'],'type'=>'findpost'),get_module_zone('topicview'));
		$last_post_url->attach('#post_'.strval($topic['last_post_id']));
		if (!is_null($topic['last_member_id']))
		{
			if ($topic['last_member_id']!=$GLOBALS['OCF_DRIVER']->get_guest_id())
			{
				$poster=do_template('OCF_USER_MEMBER',array(
					'_GUID'=>'8cf92d50e26ed25fcb2a551419ce6c82',
					'FIRST'=>true,
					'USERNAME'=>$topic['last_username'],
					'PROFILE_URL'=>$GLOBALS['OCF_DRIVER']->member_profile_url($topic['last_member_id'],false,true),
					'MEMBER_ID'=>strval($topic['last_member_id'])
				));
			} else $poster=protect_from_escaping(escape_html(($topic['last_username']=='')?do_lang('SYSTEM'):$topic['last_username']));
		} else
		{
			$poster=do_lang_tempcode('NA');
		}
		$last_post=do_template('OCF_FORUM_TOPIC_ROW_LAST_POST',array('_GUID'=>'6aa8d0f4024ae12bf94b68b74faae7cf','ID'=>strval($topic['id']),'DATE_RAW'=>strval($topic['last_time']),'DATE'=>$topic['last_time_string'],'POSTER'=>$poster,'LAST_URL'=>$last_post_url));
	} else $last_post=do_lang_tempcode('NA_EM');
	$map=array('page'=>'topicview','id'=>$topic['id']);
	if ((array_key_exists('forum_id',$topic)) && (is_null(get_bot_type())) && (get_param_integer('forum_start',0)!=0)) $map['kfs'.strval($topic['forum_id'])]=get_param_integer('forum_start',0);
	$url=build_url($map,get_module_zone('topicview'));

	// Modifiers
	$topic_row_links=new ocp_tempcode();
	$modifiers=$topic['modifiers'];
	if (in_array('unread',$modifiers))
	{
		$first_unread_url=build_url(array('page'=>'topicview','id'=>$topic['id'],'type'=>'first_unread'),get_module_zone('topicview'));
		$first_unread_url->attach('#first_unread');
		$topic_row_links->attach(do_template('OCF_FORUM_TOPIC_ROW_LINK',array('_GUID'=>'6f52881ed999f4c543c9d8573b37fa48','URL'=>$first_unread_url,'IMG'=>'unread','ALT'=>do_lang_tempcode('JUMP_TO_FIRST_UNREAD'))));
	}
	$topic_row_modifiers=new ocp_tempcode();
	foreach ($modifiers as $modifier)
	{
		if ($modifier!='unread')
		{
			$topic_row_modifiers->attach(do_template('OCF_FORUM_TOPIC_ROW_MODIFIER',array('_GUID'=>'fbcb8791b571187fd699aa6796c3f401','IMG'=>$modifier,'ALT'=>do_lang_tempcode('MODIFIER_'.$modifier))));
		}
	}

	// Emoticon
	if ($topic['emoticon']!='') $emoticon=do_template('OCF_FORUM_TOPIC_EMOTICON',array('_GUID'=>'dfbe0e4a11b3caa4d2da298ff23ca221','EMOTICON'=>$topic['emoticon']));
	else $emoticon=do_template('OCF_FORUM_TOPIC_EMOTICON_NONE');

	if ((!is_null($topic['first_member_id'])) && (!is_guest($topic['first_member_id'])))
	{
		$poster_profile_url=$GLOBALS['OCF_DRIVER']->member_profile_url($topic['first_member_id'],false,true);
		$poster=do_template('OCF_USER_MEMBER',array(
			'_GUID'=>'75e8ae20f2942f898f45df6013678a72',
			'FIRST'=>true,
			'PROFILE_URL'=>$poster_profile_url,
			'USERNAME'=>$topic['first_username'],
			'MEMBER_ID'=>is_null($topic['first_member_id'])?'':strval($topic['first_member_id'])
		));
	} else
	{
		$poster=make_string_tempcode(escape_html(($topic['first_username']=='')?do_lang('SYSTEM'):$topic['first_username']));
	}
	if ($pt)
	{
		$with=($topic['pt_from']==$topic['first_member_id'])?$topic['pt_to']:$topic['pt_from'];
		$with_username=$GLOBALS['OCF_DRIVER']->get_username($with);
		if (is_null($with_username)) $with_username=do_lang('UNKNOWN');
		$colour=get_group_colour(ocf_get_member_primary_group($with));
		$b=do_template('OCF_USER_MEMBER',array(
			'_GUID'=>'e7806e13ba51edd88c8b090ee4b31444',
			'FIRST'=>true,
			'COLOUR'=>$colour,
			'PROFILE_URL'=>$GLOBALS['OCF_DRIVER']->member_profile_url($with,false,true),
			'USERNAME'=>$with_username,
			'MEMBER_ID'=>strval($with)
		));
		$poster=do_template('OCF_PT_BETWEEN',array('_GUID'=>'619cd7076c4baf7b26cb3149694af929','A'=>$poster,'B'=>$b));
	}

	// Marker
	$marker=new ocp_tempcode();
	if ($has_topic_marking)
	{
		$marker=do_template('OCF_TOPIC_MARKER',array('_GUID'=>'62ff977640d3d4270cf333edab42a18f','ID'=>strval($topic['id'])));
	}

	// Title
	$title=$topic['first_title'];

	// Page jump
	$max=intval(get_option('forum_posts_per_page'));
	require_code('templates_result_launcher');
	$pages=results_launcher(do_lang_tempcode('NAMED_TOPIC',escape_html($title)),'topicview',$topic['id'],$max,$topic['num_posts'],'view',5);

	// Tpl
	$post=$topic['first_post'];
	if (!is_null($show_forum))
	{
		$hover=do_lang_tempcode('FORUM_AND_TIME_HOVER',escape_html($show_forum),escape_html(get_timezoned_date($topic['first_time'])));
		$breadcrumbs=ocf_forum_breadcrumbs($topic['forum_id'],NULL,NULL,false);
	} else
	{
		$hover=protect_from_escaping(is_null($topic['first_time'])?'':escape_html(get_timezoned_date($topic['first_time'])));
		$breadcrumbs=new ocp_tempcode();
	}

	return do_template('OCF_FORUM_TOPIC_ROW',array(
		'_GUID'=>'1aca672272132f390c9ec23eebe0d171',
		'BREADCRUMBS'=>$breadcrumbs,
		'RAW_TIME'=>is_null($topic['first_time'])?'':strval($topic['first_time']),
		'UNREAD'=>in_array('unread',$modifiers),
		'ID'=>strval($topic['id']),
		'FORUM_ID'=>isset($topic['forum_id'])?strval($topic['forum_id']):'',
		'HOVER'=>$hover,
		'PAGES'=>$pages,
		'MARKER'=>$marker,
		'TOPIC_ROW_LINKS'=>$topic_row_links,
		'TOPIC_ROW_MODIFIERS'=>$topic_row_modifiers,
		'_TOPIC_ROW_MODIFIERS'=>$modifiers,
		'POST'=>$post,
		'EMOTICON'=>$emoticon,
		'DESCRIPTION'=>$topic['description'],
		'URL'=>$url,
		'TITLE'=>$title,
		'POSTER'=>$poster,
		'NUM_POSTS'=>integer_format($topic['num_posts']),
		'NUM_VIEWS'=>integer_format($topic['num_views']),
		'LAST_POST'=>$last_post,
	));
}

/**
 * Get a map of details relating to the view of a certain forum of a certain member.
 *
 * @param  AUTO_LINK		The forum ID.
 * @param  array			The forum row.
 * @param  integer		The start row for getting details of topics in the forum (i.e. 0 is newest, higher is starting further back in time).
 * @param  ?integer		The maximum number of topics to get detail of (NULL: default).
 * @return array			The details.
 */
function ocf_get_forum_view($forum_id,$forum_info,$start=0,$max=NULL)
{
	if (is_null($max)) $max=intval(get_option('forum_topics_per_page'));

	$member_id=get_member();

	load_up_all_module_category_permissions($member_id,'forums');

	if (!is_null($forum_id)) // Anyone may view the root (and see the topics in the root - but there will hardly be any)
	{
		if (!has_category_access($member_id,'forums',strval($forum_id))) access_denied('CATEGORY_ACCESS_LEVEL'); // We're only allowed to view it existing from a parent forum, or nothing at all -- so access denied brother!
	}

	// Find our subforums first
	$sort=$forum_info['f_order_sub_alpha']?'f_name':'f_position';
	$max_forum_detail=intval(get_option('max_forum_detail'));
	$huge_forums=$GLOBALS['FORUM_DB']->query_select_value('f_forums','COUNT(*)')>$max_forum_detail;
	if ($huge_forums)
	{
		$max_forum_inspect=intval(get_option('max_forum_inspect'));

		$subforum_rows=$GLOBALS['FORUM_DB']->query('SELECT f.* FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_forums f WHERE f.id='.strval($forum_id).' OR f_parent_forum='.strval($forum_id).' ORDER BY f_parent_forum,'.$sort,$max_forum_inspect,NULL,false,false,array('f_description'=>'LONG_TRANS__COMCODE','f_intro_question'=>'LONG_TRANS__COMCODE'));
		if (count($subforum_rows)==$max_forum_inspect) $subforum_rows=array(); // Will cause performance breakage
	} else
	{
		$subforum_rows=$GLOBALS['FORUM_DB']->query_select('f_forums f',array('f.*'),NULL,'ORDER BY f_parent_forum,'.$sort,NULL,NULL,false,array('f_description'=>'LONG_TRANS__COMCODE','f_intro_question'=>'LONG_TRANS__COMCODE'));
	}
	$unread_forums=array();
	if ((!is_null($forum_id)) && (get_member()!=$GLOBALS['OCF_DRIVER']->get_guest_id()))
	{
		// Where are there unread topics in subforums?
		$tree=array();
		$subforum_rows_copy=$subforum_rows;
		$tree=ocf_organise_into_tree($subforum_rows_copy,$forum_id);
		if ($forum_id!=db_get_first_id())
		{
			$child_or_list=ocf_get_all_subordinate_forums($forum_id,'t_forum_id',$tree);
		} else $child_or_list='';
		if ($child_or_list!='') $child_or_list.=' AND ';
		$query='SELECT DISTINCT t_forum_id FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics t LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_read_logs l ON (t.id=l_topic_id AND l_member_id='.strval(get_member()).') WHERE t_forum_id IS NOT NULL AND '.$child_or_list.'t_cache_last_time>'.strval(time()-60*60*24*intval(get_option('post_history_days'))).' AND (l_time<t_cache_last_time OR l_time IS NULL)';
		if ((!has_privilege(get_member(),'see_unvalidated')) && (addon_installed('unvalidated'))) $query.=' AND t_validated=1';
		$unread_forums=array_flip(collapse_1d_complexity('t_forum_id',$GLOBALS['FORUM_DB']->query($query)));
	}

	// Find all the forum groupings that are used
	$forum_groupings=array();
	$or_list='';
	foreach ($subforum_rows as $tmp_key=>$subforum_row)
	{
		if ($subforum_row['f_parent_forum']!=$forum_id) continue;

		if (!has_category_access($member_id,'forums',strval($subforum_row['id'])))
		{
			unset($subforum_rows[$tmp_key]);
			continue;
		}

		$forum_grouping_id=$subforum_row['f_forum_grouping_id'];
		if (!array_key_exists($forum_grouping_id,$forum_groupings))
		{
			$forum_groupings[$forum_grouping_id]=array('subforums'=>array());
			if ($or_list!='') $or_list.=' OR ';
			$or_list.='id='.strval($forum_grouping_id);
		}
	}
	if ($or_list!='')
	{
		$forum_grouping_rows=$GLOBALS['FORUM_DB']->query('SELECT * FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_forum_groupings WHERE '.$or_list,NULL,NULL,false,true);
		foreach ($forum_grouping_rows as $forum_grouping_row)
		{
			$forum_grouping_id=$forum_grouping_row['id'];
			$title=$forum_grouping_row['c_title'];
			$description=$forum_grouping_row['c_description'];
			$expanded_by_default=$forum_grouping_row['c_expanded_by_default'];
			$forum_groupings[$forum_grouping_id]['title']=$title;
			$forum_groupings[$forum_grouping_id]['description']=$description;
			$forum_groupings[$forum_grouping_id]['expanded_by_default']=$expanded_by_default;
		}
		$forum_groupings[NULL]['title']='';
		$forum_groupings[NULL]['description']='';
		$forum_groupings[NULL]['expanded_by_default']=true;
		foreach ($subforum_rows as $subforum_row)
		{
			if ($subforum_row['f_parent_forum']!=$forum_id) continue;

			$forum_grouping_id=$subforum_row['f_forum_grouping_id'];

			$subforum=array();
			$subforum['id']=$subforum_row['id'];
			$subforum['name']=$subforum_row['f_name'];
			$subforum['description']=get_translated_tempcode('f_forums',$subforum_row,'f_description',$GLOBALS['FORUM_DB']);
			$subforum['redirection']=$subforum_row['f_redirection'];
			$subforum['intro_question']=get_translated_tempcode('f_forums',$subforum_row,'f_intro_question',$GLOBALS['FORUM_DB']);
			$subforum['intro_answer']=$subforum_row['f_intro_answer'];

			if (is_numeric($subforum_row['f_redirection']))
			{
				$subforum_row=$GLOBALS['FORUM_DB']->query_select('f_forums',array('*'),array('id'=>intval($subforum_row['f_redirection'])),'',1);
				$subforum_row=$subforum_row[0];
			}

			if (($subforum_row['f_redirection']=='') || (is_numeric($subforum_row['f_redirection'])))
			{
				$subforum['num_topics']=$subforum_row['f_cache_num_topics'];
				$subforum['num_posts']=$subforum_row['f_cache_num_posts'];

				$subforum['has_new']=false;
				if (get_member()!=$GLOBALS['OCF_DRIVER']->get_guest_id())
				{
					$subforums_recurse=ocf_get_all_subordinate_forums($subforum['id'],NULL,$tree[$subforum['id']]['children']);
					foreach ($subforums_recurse as $subforum_potential)
					{
						if (array_key_exists($subforum_potential,$unread_forums)) $subforum['has_new']=true;
					}
				}

				if ((is_null($subforum_row['f_cache_last_forum_id'])) || (has_category_access($member_id,'forums',strval($subforum_row['f_cache_last_forum_id']))))
				{
					$subforum['last_topic_id']=$subforum_row['f_cache_last_topic_id'];
					$subforum['last_title']=$subforum_row['f_cache_last_title'];
					$subforum['last_time']=$subforum_row['f_cache_last_time'];
					$subforum['last_username']=$subforum_row['f_cache_last_username'];
					$subforum['last_member_id']=$subforum_row['f_cache_last_member_id'];
					$subforum['last_forum_id']=$subforum_row['f_cache_last_forum_id'];
				} else $subforum['protected_last_post']=true;

				// Subsubforums
				$subforum['children']=array();
				foreach ($subforum_rows as $tmp_key_2=>$subforum_row2)
				{
					if (($subforum_row2['f_parent_forum']==$subforum_row['id']) && (has_category_access($member_id,'forums',strval($subforum_row2['id']))))
					{
						$subforum['children'][$subforum_row2['f_name'].'__'.strval($subforum_row2['id'])]=array('id'=>$subforum_row2['id'],'name'=>$subforum_row2['f_name'],'redirection'=>$subforum_row2['f_redirection']);
					}
				}
				sort_maps_by($subforum['children'],'name');
			}

			$forum_groupings[$forum_grouping_id]['subforums'][]=$subforum;
		}
	}

	// Find topics
	$extra='';
	if ((!has_privilege(get_member(),'see_unvalidated')) && (addon_installed('unvalidated')) && (!ocf_may_moderate_forum($forum_id,$member_id))) $extra='t_validated=1 AND ';
	if ((is_null($forum_info['f_parent_forum'])) || ($GLOBALS['FORUM_DB']->query_select_value('f_topics','COUNT(*)',array('t_cascading'=>1))==0))
	{
		$where=$extra.' (t_forum_id='.strval($forum_id).')';
	} else
	{
		$extra2='';
		$parent_or_list=ocf_get_forum_parent_or_list($forum_id,$forum_info['f_parent_forum']);
		if ($parent_or_list!='')
		{
			$extra2='AND ('.$parent_or_list.')';
		}
		$where=$extra.' (t_forum_id='.strval($forum_id).' OR (t_cascading=1 '.$extra2.'))';
	}
	$sort=get_param('sort',$forum_info['f_order']);
	$sort2='t_cache_last_time DESC';
	if ($sort=='first_post') $sort2='t_cache_first_time DESC';
	elseif ($sort=='title') $sort2='t_cache_first_title ASC';
	if (get_option('enable_sunk')=='1')
		$sort2='t_sunk ASC,'.$sort2;
	if (is_guest())
	{
		$query='SELECT ttop.*,NULL AS l_time';
		if (multi_lang_content())
		{
			$query.=',t_cache_first_post AS p_post';
		} else
		{
			$query.=',p_post,p_post__text_parsed,p_post__source_user';
		}
		$query.=' FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics ttop';
		if (!multi_lang_content())
		{
			$query.=' LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts p ON p.id=ttop.t_cache_first_post_id';
		}
		$query.=' WHERE '.$where.' ORDER BY t_cascading DESC,t_pinned DESC,'.$sort2;
	} else
	{
		$query='SELECT ttop.*,l_time';
		if (multi_lang_content())
		{
			$query.=',t_cache_first_post AS p_post';
		} else
		{
			$query.=',p_post,p_post__text_parsed,p_post__source_user';
		}
		$query.=' FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics ttop LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_read_logs l ON ttop.id=l.l_topic_id AND l.l_member_id='.strval(get_member());
		if (!multi_lang_content())
		{
			$query.=' LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts p ON p.id=ttop.t_cache_first_post_id';
		}
		$query.=' WHERE '.$where.' ORDER BY t_cascading DESC,t_pinned DESC,'.$sort2;
	}
	if (($start<200) && (multi_lang_content()))
	{
		$topic_rows=$GLOBALS['FORUM_DB']->query($query,$max,$start,false,false,array('t_cache_first_post'=>'LONG_TRANS__COMCODE'));
	} else // deep search, so we need to make offset more efficient, trade-off is more queries
	{
		$topic_rows=$GLOBALS['FORUM_DB']->query($query,$max,$start);
	}
	if (($start==0) && (count($topic_rows)<$max)) $max_rows=$max; // We know that they're all on this screen
	else $max_rows=$GLOBALS['FORUM_DB']->query_value_if_there('SELECT COUNT(*) FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics WHERE '.$where,false,true);
	$topics=array();
	$hot_topic_definition=intval(get_option('hot_topic_definition'));
	$or_list='';
	foreach ($topic_rows as $topic_row)
	{
		if ($or_list!='') $or_list.=' OR ';
		$or_list.='p_topic_id='.strval($topic_row['id']);
	}
	if (($or_list!='') && (!is_guest()))
	{
		$involved=$GLOBALS['FORUM_DB']->query('SELECT DISTINCT p_topic_id FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts WHERE ('.$or_list.') AND p_poster='.strval(get_member()),NULL,NULL,false,true);
		$involved=collapse_1d_complexity('p_topic_id',$involved);
	} else $involved=array();
	foreach ($topic_rows as $topic_row)
	{
		$topics[]=ocf_get_topic_array($topic_row,$member_id,$hot_topic_definition,in_array($topic_row['id'],$involved));
	}

	$description=get_translated_tempcode('f_forums',$forum_info,'f_description',$GLOBALS['FORUM_DB']);
	$out=array(
		'name'=>$forum_info['f_name'],
		'description'=>$description,
		'forum_groupings'=>$forum_groupings,
		'topics'=>$topics,
		'max_rows'=>$max_rows,
		'order'=>$sort,
		'parent_forum'=>$forum_info['f_parent_forum']
	);

	// Is there a question/answer situation?
	$question=get_translated_tempcode('f_forums',$forum_info,'f_intro_question',$GLOBALS['FORUM_DB']);
	if (!$question->is_empty())
	{
		$is_guest=($member_id==$GLOBALS['OCF_DRIVER']->get_guest_id());
		$test=$GLOBALS['FORUM_DB']->query_select_value_if_there('f_forum_intro_ip','i_ip',array('i_forum_id'=>$forum_id,'i_ip'=>get_ip_address(3)));
		if ((is_null($test)) && (!$is_guest))
		{
			$test=$GLOBALS['FORUM_DB']->query_select_value_if_there('f_forum_intro_member','i_member_id',array('i_forum_id'=>$forum_id,'i_member_id'=>$member_id));
		}
		if (is_null($test))
		{
			$out['question']=$question;
			$out['answer']=$forum_info['f_intro_answer'];
		}
	}

	if (ocf_may_post_topic($forum_id,$member_id)) $out['may_post_topic']=1;
	if (ocf_may_moderate_forum($forum_id,$member_id))
	{
		$out['may_change_max']=1;
		$out['may_move_topics']=1;
		if (has_privilege(get_member(),'multi_delete_topics')) $out['may_delete_topics']=1; // Only super admins can casually delete topics - other staff are expected to trash them. At least deleted posts or trashed topics can be restored!
	}
	return $out;
}


