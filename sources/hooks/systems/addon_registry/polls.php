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
 * @package		polls
 */

class Hook_addon_registry_polls
{

	/**
	 * Get a list of file permissions to set
	 *
	 * @return array			File permissions to set
	 */
	function get_chmod_array()
	{
		return array();
	}

	/**
	 * Get the version of ocPortal this addon is for
	 *
	 * @return float			Version number
	 */
	function get_version()
	{
		return ocp_version_number();
	}

	/**
	 * Get the description of the addon
	 *
	 * @return string			Description of the addon
	 */
	function get_description()
	{
		return 'A poll (voting) system.';
	}

	/**
	 * Get a mapping of dependency types
	 *
	 * @return array			File permissions to set
	 */
	function get_dependencies()
	{
		return array(
			'requires'=>array(),
			'recommends'=>array(),
			'conflicts_with'=>array(),
		);
	}

	/**
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(

			'sources/hooks/systems/notifications/poll_chosen.php',
			'sources/hooks/systems/config_default/points_ADD_POLL.php',
			'sources/hooks/systems/config_default/points_CHOOSE_POLL.php',
			'sources/hooks/systems/config_default/poll_update_time.php',
			'sources/hooks/systems/realtime_rain/polls.php',
			'sources/hooks/systems/awards/poll.php',
			'BLOCK_MAIN_POLL_IFRAME.tpl',
			'poll.php',
			'sources/hooks/systems/content_meta_aware/poll.php',
			'sources/hooks/systems/addon_registry/polls.php',
			'sources/hooks/systems/preview/poll.php',
			'sources/hooks/modules/admin_setupwizard/polls.php',
			'sources/hooks/modules/admin_import_types/polls.php',
			'POLL.tpl',
			'POLL_ANSWER.tpl',
			'POLL_ANSWER_RESULT.tpl',
			'POLL_SCREEN.tpl',
			'POLL_LIST_ENTRY.tpl',
			'POLL_RSS_SUMMARY.tpl',
			'polls.css',
			'themes/default/images/bigicons/polls.png',
			'themes/default/images/pagepics/polls.png',
			'cms/pages/modules/cms_polls.php',
			'lang/EN/polls.ini',
			'site/pages/modules/polls.php',
			'sources/hooks/blocks/main_staff_checklist/polls.php',
			'sources/hooks/modules/search/polls.php',
			'sources/hooks/systems/do_next_menus/polls.php',
			'sources/hooks/systems/rss/polls.php',
			'sources/hooks/systems/trackback/polls.php',
			'sources/polls.php',
			'sources/blocks/main_poll.php',
		);
	}


	/**
	* Get mapping between template names and the method of this class that can render a preview of them
	*
	* @return array			The mapping
	*/
	function tpl_previews()
	{
		return array(
				'BLOCK_MAIN_POLL_IFRAME.tpl'=>'block_main_poll_iframe',
				'POLL_RSS_SUMMARY.tpl'=>'poll_rss_summary',
				'POLL_ANSWER.tpl'=>'poll_answer',
				'POLL_ANSWER_RESULT.tpl'=>'poll_answer_result',
				'POLL.tpl'=>'poll_answer',
				'POLL_LIST_ENTRY.tpl'=>'poll_list_entry',
				'POLL_SCREEN.tpl'=>'poll_screen',
				);
	}

	/**
	* Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	* Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	* Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	*
	* @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	*/
	function tpl_preview__block_main_poll_iframe()
	{
		return array(
			lorem_globalise(
				do_lorem_template('BLOCK_MAIN_POLL_IFRAME',array(
					'RAND'=>placeholder_random(),
					'PARAM'=>"-1",
					'ZONE'=>lorem_word(),
						)
			),NULL,'',true),
		);
	}
	/**
	* Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	* Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	* Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	*
	* @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	*/
	function tpl_preview__poll_rss_summary()
	{
		$_summary = do_lorem_template('POLL_RSS_SUMMARY',array('ANSWERS'=>placeholder_array()));
		$summary = xmlentities($_summary->evaluate());

		$if_comments = do_lorem_template('RSS_ENTRY_COMMENTS',array('COMMENT_URL'=>placeholder_url(),'ID'=>placeholder_id()));

		return array(
			lorem_globalise(
				do_lorem_template('RSS_ENTRY',array(
					'VIEW_URL'=>placeholder_url(),
					'SUMMARY'=>$summary,
					'EDIT_DATE'=>placeholder_date(),
					'IF_COMMENTS'=>$if_comments,
					'TITLE'=>lorem_phrase(),
					'CATEGORY_RAW'=>NULL,
					'CATEGORY'=>'',
					'AUTHOR'=>lorem_word(),
					'ID'=>placeholder_id(),
					'NEWS'=>lorem_paragraph(),
					'DATE'=>placeholder_time()
				)
			),NULL,'',true),
		);
	}
	/**
	* Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	* Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	* Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	*
	* @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	*/
	function tpl_preview__poll_answer()
	{
		return $this->poll('poll');
	}
	/**
	* Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	* Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	* Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	*
	* @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	*/
	function tpl_preview__poll_answer_result()
	{
		return $this->poll('result');
	}

	/**
	* Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	* Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	* Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	*
	* @param  string			View type.
	* @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	*/
	function poll($section='')
	{
		$tpl = new ocp_tempcode();
		switch ($section)
		{
			case 'poll':
				foreach (placeholder_array() as $k=>$v)
				{
					$tpl->attach(do_lorem_template('POLL_ANSWER',array('PID'=>placeholder_id(),'I'=>"$k",'CAST'=>"$k",'VOTE_URL'=>placeholder_url(),'ANSWER'=>lorem_phrase(),'ANSWER_PLAIN'=>lorem_phrase())));
				}
				break;

			case 'result':
				foreach (placeholder_array() as $k=>$v)
				{
					$tpl->attach(do_lorem_template('POLL_ANSWER_RESULT',array('PID'=>placeholder_id(),'I'=>"$k",'VOTE_URL'=>placeholder_url(),'ANSWER'=>lorem_phrase(),'ANSWER_PLAIN'=>lorem_phrase(),'WIDTH'=>"$k",'VOTES'=>placeholder_number())));
				}
				break;

			default:
				foreach (placeholder_array() as $k=>$v)
				{
					$tpl->attach(do_lorem_template('POLL_ANSWER',array('PID'=>placeholder_id(),'I'=>"$k",'CAST'=>"$k",'VOTE_URL'=>placeholder_url(),'ANSWER'=>lorem_phrase(),'ANSWER_PLAIN'=>lorem_phrase())));
				}
				foreach (placeholder_array() as $k=>$v)
				{
					$tpl->attach(do_lorem_template('POLL_ANSWER_RESULT',array('PID'=>placeholder_id(),'I'=>"$k",'VOTE_URL'=>placeholder_url(),'ANSWER'=>lorem_phrase(),'ANSWER_PLAIN'=>lorem_phrase(),'WIDTH'=>"$k",'VOTES'=>placeholder_number())));
				}
		}

		$wrap_content = do_lorem_template('POLL',array('VOTE_URL'=>placeholder_url(),'SUBMITTER'=>lorem_phrase(),'RESULT_URL'=>placeholder_url(),'POLL_RESULTS'=>lorem_phrase(),'SUBMIT_URL'=>placeholder_url(),'ARCHIVE_URL'=>placeholder_url(),'POLLS'=>lorem_phrase(),'VIEW_ARCHIVE'=>placeholder_url(),'POLL'=>lorem_phrase(),'PID'=>placeholder_id(),'VIEW'=>lorem_phrase(),'COMMENT_COUNT'=>placeholder_number(),'QUESTION_PLAIN'=>lorem_phrase(),'QUESTION'=>lorem_phrase(),'CONTENT'=>$tpl,'VOTE'=>placeholder_number(),'ZONE'=>lorem_word(),'FULL_URL'=>placeholder_url()));

		return array(
			lorem_globalise(
				$wrap_content,NULL,'',true),
		);
	}
	/**
	* Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	* Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	* Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	*
	* @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	*/
	function tpl_preview__poll_list_entry()
	{
		return array(
			lorem_globalise(
				do_lorem_template('POLL_LIST_ENTRY',array(
					'QUESTION'=>lorem_phrase(),
					'STATUS'=>lorem_phrase(),
						)
			),NULL,'',true),
		);
	}
	/**
	* Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	* Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	* Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	*
	* @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	*/
	function tpl_preview__poll_screen()
	{
		require_lang('trackbacks');
		$trackbacks = new ocp_tempcode();
		foreach (placeholder_array(1) as $k=>$v)
		{
			$trackbacks->attach(do_lorem_template('TRACKBACK',array('ID'=>placeholder_id(),'TIME_RAW'=>placeholder_date_raw(),'TIME'=>placeholder_date(),'URL'=>placeholder_url(),'TITLE'=>lorem_phrase(),'EXCERPT'=>lorem_paragraph(),'NAME'=>lorem_phrase())));
		}
		$trackback_details = do_lorem_template('TRACKBACK_WRAPPER',array('TRACKBACKS'=>$trackbacks,'TRACKBACK_PAGE'=>placeholder_id(),'TRACKBACK_ID'=>placeholder_id(),'TRACKBACK_TITLE'=>lorem_phrase()));

		$rating_details = do_lorem_template('RATING',array('NUM_RATINGS'=>placeholder_id(),
					'RATING_FORM'=>lorem_word()));

		$tpl_post = do_lorem_template('POST',array('POSTER_ID'=>placeholder_id(),'EDIT_URL'=>placeholder_url(),'INDIVIDUAL_REVIEW_RATINGS'=>placeholder_array(),'HIGHLIGHT'=>lorem_word(),'TITLE'=>lorem_phrase(),'TIME_RAW'=>placeholder_date_raw(),'TIME'=>placeholder_date(),'POSTER_LINK'=>placeholder_url(),'POSTER_NAME'=>lorem_phrase(),'POST'=>lorem_phrase()));
		$comments = $tpl_post->evaluate();
		$comment_details = do_lorem_template('COMMENTS_WRAPPER',array('TYPE'=>lorem_word(),'ID'=>placeholder_id(),'REVIEW_RATING_CRITERIA'=>array(),'AUTHORISED_FORUM_LINK'=>placeholder_url(),'FORM'=>placeholder_form(),'COMMENTS'=>$comments));

		$poll_details = do_lorem_template('BLOCK_MAIN_POLL_IFRAME',array('RAND'=>placeholder_random(),'PARAM'=>"-1",'ZONE'=>lorem_word()));

		return array(
			lorem_globalise(
				do_lorem_template('POLL_SCREEN',array(
					'TITLE'=>lorem_title(),
					'DATE_RAW'=>placeholder_date_raw(),
					'ADD_DATE_RAW'=>placeholder_date_raw(),
					'EDIT_DATE_RAW'=>placeholder_date_raw(),
					'DATE'=>placeholder_time(),
					'ADD_DATE'=>placeholder_date(),
					'EDIT_DATE'=>placeholder_date(),
					'VIEWS'=>placeholder_number(),
					'TRACKBACK_DETAILS'=>$trackback_details,
					'RATING_DETAILS'=>$rating_details,
					'COMMENT_DETAILS'=>$comment_details,
					'EDIT_URL'=>placeholder_url(),
					'POLL_DETAILS'=>$poll_details,
					'ID'=>placeholder_id(),
						)
			),NULL,'',true),
		);
	}
}