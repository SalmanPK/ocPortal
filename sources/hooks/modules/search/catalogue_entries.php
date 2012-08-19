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

class Hook_search_catalogue_entries
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		if (!module_installed('catalogues')) return NULL;
		if ($GLOBALS['SITE_DB']->query_select_value('catalogue_entries','COUNT(*)')==0) return NULL;

		global $SEARCH_CATALOGUE_ENTRIES_CATALOGUES;
		$SEARCH_CATALOGUE_ENTRIES_CATALOGUES=array();

		require_lang('catalogues');
		require_code('catalogues');

		$info=array();
		$info['lang']=do_lang_tempcode('CATALOGUE_ENTRIES');
		$info['default']=false;
		$info['category']='cc_id';
		$info['integer_category']=true;

		$extra_sort_fields=array();
		$catalogue_name=get_param('catalogue_name',NULL);
		if (!is_null($catalogue_name))
		{
			require_code('fields');

			$rows=$GLOBALS['SITE_DB']->query_select('catalogue_fields',array('id','cf_name','cf_type','cf_default'),array('c_name'=>$catalogue_name,'cf_searchable'=>1,'cf_visible'=>1),'ORDER BY cf_order');
			foreach ($rows as $i=>$row)
			{
				$ob=get_fields_hook($row['cf_type']);
				$temp=$ob->inputted_to_sql_for_search($row,$i);
				if (is_null($temp)) // Standard direct 'substring' search
				{
					$extra_sort_fields['f'.strval($i).'_actual_value']=get_translated_text($row['cf_name']);
				}
			}
		}
		$info['extra_sort_fields']=$extra_sort_fields;

		return $info;
	}

	/**
	 * Get details for an ajax-tree-list of entries for the content covered by this search hook.
	 *
	 * @return array			A pair: the hook, and the options
	 */
	function ajax_tree()
	{
		$catalogue_name=get_param('catalogue_name','');
		if ($catalogue_name=='')
		{
			@ob_end_clean();

			$tree=nice_get_catalogues(NULL,true);
			if ($tree->is_empty())
			{
				inform_exit(do_lang_tempcode('NO_ENTRIES'));
			}

			require_code('form_templates');
			$fields=form_input_list(do_lang_tempcode('NAME'),'','catalogue_name',$tree,NULL,true);
			if (running_script('iframe'))
			{
				$post_url=get_self_url_easy();
			} else
			{
				$post_url=get_self_url(false,false,NULL,false,true);
			}
			$submit_name=do_lang_tempcode('PROCEED');
			$hidden=build_keep_post_fields();

			$title=get_screen_title('SEARCH');
			$tpl=do_template('FORM_SCREEN',array('_GUID'=>'a2812ac8056903811f444682d45ee448','TARGET'=>'_self','GET'=>true,'SKIP_VALIDATION'=>true,'HIDDEN'=>$hidden,'TITLE'=>$title,'TEXT'=>'','URL'=>$post_url,'FIELDS'=>$fields,'SUBMIT_NAME'=>$submit_name));
			$echo=globalise($tpl,NULL,'',true);
			$echo->evaluate_echo();
			exit();
		}

		return array('choose_catalogue_entry',array('catalogue_name'=>$catalogue_name));
	}

	/**
	 * Get a list of extra fields to ask for.
	 *
	 * @return array			A list of maps specifying extra fields
	 */
	function get_fields()
	{
		$fields=array();
		$catalogue_name=get_param('catalogue_name');
		$rows=$GLOBALS['SITE_DB']->query_select('catalogue_fields',array('id','cf_name','cf_type','cf_default'),array('c_name'=>$catalogue_name,'cf_searchable'=>1,'cf_visible'=>1),'ORDER BY cf_order');
		require_code('fields');
		foreach ($rows as $row)
		{
			$ob=get_fields_hook($row['cf_type']);
			$temp=$ob->get_search_inputter($row);
			if (is_null($temp))
			{
				$type='_TEXT';
				$special=get_param('option_'.strval($row['id']),'');
				$extra='';
				$display=get_translated_text($row['cf_name']);
				if (strpos($display,do_lang('RANGE_REQUIRED_TAG'))!==false) // FUDGEFUDGE: But leave
				{
					$display=str_replace(do_lang('RANGE_REQUIRED_TAG'),do_lang('RANGE_REQUIRED_SEARCH_DESCRIP'),$display);
					if (!is_guest())
					{
						$dob_year=intval($GLOBALS['FORUM_DRIVER']->get_member_row_field(get_member(),'m_dob_year'));
						if ($dob_year!=0) $special=integer_format(intval(date('Y',utctime_to_usertime(time())))-$dob_year); // number_format'ing this is kind of funny actually
					}
					$extra='_ranged';
				}
				$fields[]=array('NAME'=>strval($row['id']).$extra,'DISPLAY'=>$display,'TYPE'=>$type,'SPECIAL'=>$special);
			} else $fields=array_merge($fields,$temp);
		}
		return $fields;
	}

	/**
	 * Standard modular run function for search results.
	 *
	 * @param  string			Search string
	 * @param  boolean		Whether to only do a META (tags) search
	 * @param  ID_TEXT		Order direction
	 * @param  integer		Start position in total results
	 * @param  integer		Maximum results to return in total
	 * @param  boolean		Whether only to search titles (as opposed to both titles and content)
	 * @param  string			Where clause that selects the content according to the main search string (SQL query fragment) (blank: full-text search)
	 * @param  SHORT_TEXT	Username/Author to match for
	 * @param  ?MEMBER		Member-ID to match for (NULL: unknown)
	 * @param  TIME			Cutoff date
	 * @param  string			The sort type (gets remapped to a field in this function)
	 * @set    title add_date
	 * @param  integer		Limit to this number of results
	 * @param  string			What kind of boolean search to do
	 * @set    or and
	 * @param  string			Where constraints known by the main search code (SQL query fragment)
	 * @param  string			Comma-separated list of categories to search under
	 * @param  boolean		Whether it is a boolean search
	 * @return array			List of maps (template, orderer)
	 */
	function run($content,$only_search_meta,$direction,$max,$start,$only_titles,$content_where,$author,$author_id,$cutoff,$sort,$limit_to,$boolean_operator,$where_clause,$search_under,$boolean_search)
	{
		unset($limit_to);

		if (!module_installed('catalogues')) return array();

		$remapped_orderer='';
		switch ($sort)
		{
			case 'rating':
				$remapped_orderer='_rating:catalogues:id';
				break;

			case 'title':
				$remapped_orderer='b_cv_value';
				break;

			case 'add_date':
				$remapped_orderer='ce_add_date';
				break;

			case 'relevance':
				break;

			default:
				$remapped_orderer=$sort;
				break;
		}

		require_code('catalogues');
		require_lang('catalogues');

		// Calculate our where clause (search)
		$sq=build_search_submitter_clauses('ce_submitter',$author_id,$author);
		if (is_null($sq)) return array(); else $where_clause.=$sq;
		if (!is_null($cutoff))
		{
			$where_clause.=' AND ';
			$where_clause.='r.ce_add_date>'.strval($cutoff);
		}
		if (!$GLOBALS['FORUM_DRIVER']->is_super_admin(get_member()))
		{
			$where_clause.=' AND ';
			$where_clause.='z.category_name IS NOT NULL';
			$where_clause.=' AND ';
			$where_clause.='p.category_name IS NOT NULL';
		}
		if (!has_privilege(get_member(),'see_unvalidated'))
		{
			$where_clause.=' AND ';
			$where_clause.='ce_validated=1';
		}

		$g_or=_get_where_clause_groups(get_member());

		// Calculate and perform query
		$catalogue_name=get_param('catalogue_name','');
		$ranges=array();
		if ($catalogue_name!='')
		{
			$extra_select='';

			$rows=$GLOBALS['SITE_DB']->query_select('catalogue_fields',array('id','cf_name','cf_type','cf_default'),array('c_name'=>$catalogue_name,'cf_searchable'=>1),'ORDER BY cf_order');
			$table='catalogue_entries r';
			$trans_fields=array('!');
			$nontrans_fields=array();
			$title_field=mixed();
			require_code('fields');
			foreach ($rows as $i=>$row)
			{
				$ob=get_fields_hook($row['cf_type']);
				$temp=$ob->inputted_to_sql_for_search($row,$i);
				if (is_null($temp)) // Standard direct 'substring' search
				{
					list(,,$row_type)=$ob->get_field_value_row_bits($row);
					switch ($row_type)
					{
						case 'long_trans':
							$trans_fields[]='f'.strval($i).'.cv_value';
							$table.=' JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'catalogue_efv_long_trans f'.strval($i).' ON (f'.strval($i).'.ce_id=r.id AND f'.strval($i).'.cf_id='.strval($row['id']).')';
							$search_field='t'.strval(count($trans_fields)-1).'.text_original';
							break;
						case 'short_trans':
							$trans_fields[]='f'.strval($i).'.cv_value';
							$table.=' JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'catalogue_efv_short_trans f'.strval($i).' ON (f'.strval($i).'.ce_id=r.id AND f'.strval($i).'.cf_id='.strval($row['id']).')';
							$search_field='t'.strval(count($trans_fields)-1).'.text_original';
							break;
						case 'long':
							$nontrans_fields[]='f'.strval($i).'.cv_value';
							$table.=' JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'catalogue_efv_long f'.strval($i).' ON (f'.strval($i).'.ce_id=r.id AND f'.strval($i).'.cf_id='.strval($row['id']).')';
							$search_field='f'.strval($i).'.cv_value';
							break;
						case 'short':
							$nontrans_fields[]='f'.strval($i).'.cv_value';
							$table.=' JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'catalogue_efv_short f'.strval($i).' ON (f'.strval($i).'.ce_id=r.id AND f'.strval($i).'.cf_id='.strval($row['id']).')';
							$search_field='f'.strval($i).'.cv_value';
							break;
						case 'float':
							$table.=' JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'catalogue_efv_float f'.strval($i).' ON (f'.strval($i).'.ce_id=r.id AND f'.strval($i).'.cf_id='.strval($row['id']).')';
							$search_field='f'.strval($i).'.cv_value';
							break;
						case 'integer':
							$table.=' JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'catalogue_efv_integer f'.strval($i).' ON (f'.strval($i).'.ce_id=r.id AND f'.strval($i).'.cf_id='.strval($row['id']).')';
							$search_field='f'.strval($i).'.cv_value';
							break;
					}

					$param=get_param('option_'.strval($row['id']),'');
					if ($param!='')
					{
						$where_clause.=' AND ';

						if ((substr($param,0,1)=='=') || ($row_type=='integer') || ($row_type=='float'))
						{
							$where_clause.=db_string_equal_to($search_field,substr($param,1));
						} else
						{
							if ((db_has_full_text($GLOBALS['SITE_DB']->connection_read)) && (method_exists($GLOBALS['SITE_DB']->static_ob,'db_has_full_text_boolean')) && ($GLOBALS['SITE_DB']->static_ob->db_has_full_text_boolean()) && (!is_under_radar($param)))
							{
								$temp=db_full_text_assemble($param,true);
							} else
							{
								$temp=db_like_assemble($param);
							}
							$where_clause.=preg_replace('#\?#',$search_field,$temp);
						}
					} else
					{
						$param=get_param('option_'.strval($row['id']).'_ranged','');
						if ($param!='') $ranges[$row['id']]=$param;
					}
				} else
				{
					$table.=$temp[2];
					$search_field=$temp[3];
					if ($temp[4]!='')
					{
						$where_clause.=' AND ';
						$where_clause.=$temp[4];
					} else
					{
						$trans_fields=array_merge($trans_fields,$temp[0]);
						$non_trans_fields=array_merge($nontrans_fields,$temp[1]);
					}
				}
				if ($i==0) $title_field=$search_field;
			}

			$where_clause.=' AND ';
			$where_clause.=db_string_equal_to('r.c_name',$catalogue_name);

			if (is_null($title_field)) return array(); // No fields in catalogue -- very odd
			if ($g_or=='')
			{
				$rows=get_search_rows('catalogue_entry','id',$content,$boolean_search,$boolean_operator,$only_search_meta,$direction,$max,$start,$only_titles,$table,$trans_fields,$where_clause,$content_where,str_replace('b_cv_value',$title_field,$remapped_orderer),'r.*,r.id AS id,r.cc_id AS r_cc_id,'.$title_field.' AS b_cv_value'.$extra_select,$nontrans_fields);
			} else
			{
				$rows=get_search_rows('catalogue_entry','id',$content,$boolean_search,$boolean_operator,$only_search_meta,$direction,$max,$start,$only_titles,$table.' LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'group_category_access z ON ('.db_string_equal_to('z.module_the_name','catalogues_category').' AND z.category_name=r.cc_id AND '.str_replace('group_id','z.group_id',$g_or).') LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'group_category_access p ON ('.db_string_equal_to('p.module_the_name','catalogues_catalogue').' AND p.category_name=r.c_name AND '.str_replace('group_id','p.group_id',$g_or).')',$trans_fields,$where_clause,$content_where,str_replace('b_cv_value',$title_field,$remapped_orderer),'r.*,r.id AS id,r.cc_id AS r_cc_id,'.$title_field.' AS b_cv_value'.$extra_select,$nontrans_fields);
			}
		} else
		{
			if ($GLOBALS['SITE_DB']->query_select_value('translate','COUNT(*)')>10000) // Big sites can't do indescriminate catalogue translatable searches for performance reasons
			{
				$trans_fields=array();
				$join=' JOIN '.get_table_prefix().'catalogue_efv_short c ON (r.id=c.ce_id AND f.id=c.cf_id)';
				$_remapped_orderer=str_replace('b_cv_value','c.cv_value',$remapped_orderer);
				$extra_select='';
				$non_trans_fields=array('c.cv_value');
			} else
			{
				$join=' LEFT JOIN '.get_table_prefix().'catalogue_efv_short_trans a ON (r.id=a.ce_id AND f.id=a.cf_id) LEFT JOIN '.get_table_prefix().'catalogue_efv_long_trans b ON (r.id=b.ce_id AND f.id=b.cf_id) LEFT JOIN '.get_table_prefix().'catalogue_efv_long d ON (r.id=d.ce_id AND f.id=d.cf_id) LEFT JOIN '.get_table_prefix().'catalogue_efv_short c ON (r.id=c.ce_id AND f.id=c.cf_id)';
				//' LEFT JOIN '.get_table_prefix().'catalogue_efv_float g ON (r.id=g.ce_id AND f.id=g.cf_id) LEFT JOIN '.get_table_prefix().'catalogue_efv_integer h ON (r.id=h.ce_id AND f.id=h.cf_id)';
				$trans_fields=array('a.cv_value','b.cv_value');
				$_remapped_orderer=str_replace('b_cv_value','b.cv_value',$remapped_orderer);
				$extra_select=',b.cv_value AS b_cv_value';
				$non_trans_fields=array('c.cv_value','d.cv_value'/*,'g.cv_value','h.cv_value'*/);
			}

			$where_clause.=' AND ';
			$where_clause.='r.c_name NOT LIKE \'\_%\''; // Don't want results drawn from the hidden custom-field catalogues

			if ($g_or=='')
			{
				$rows=get_search_rows('catalogue_entry','id',$content,$boolean_search,$boolean_operator,$only_search_meta,$direction,$max,$start,$only_titles,'catalogue_fields f LEFT JOIN '.get_table_prefix().'catalogue_entries r ON (r.c_name=f.c_name)'.$join,$trans_fields,$where_clause,$content_where,$_remapped_orderer,'r.*,r.id AS id,r.cc_id AS r_cc_id'.$extra_select,$non_trans_fields);
			} else
			{
				$rows=get_search_rows('catalogue_entry','id',$content,$boolean_search,$boolean_operator,$only_search_meta,$direction,$max,$start,$only_titles,'catalogue_fields f LEFT JOIN '.get_table_prefix().'catalogue_entries r ON (r.c_name=f.c_name)'.$join.' LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'group_category_access z ON ('.db_string_equal_to('z.module_the_name','catalogues_category').' AND z.category_name=r.cc_id AND '.str_replace('group_id','z.group_id',$g_or).') LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'group_category_access p ON ('.db_string_equal_to('p.module_the_name','catalogues_catalogue').' AND p.category_name=r.c_name AND '.str_replace('group_id','p.group_id',$g_or).')',$trans_fields,$where_clause,$content_where,$_remapped_orderer,'r.*,r.id AS id,r.cc_id AS r_cc_id'.$extra_select,$non_trans_fields);
			}
		}

		$out=array();
		if (count($rows)==0) return array();

		global $SEARCH_CATALOGUE_ENTRIES_CATALOGUES;
		$query='SELECT c.* FROM '.get_table_prefix().'catalogues c';
		if (can_arbitrary_groupby())
			$query.=' JOIN '.get_table_prefix().'catalogue_entries e ON e.c_name=c.c_name GROUP BY c.c_name';
		$_catalogues=$GLOBALS['SITE_DB']->query($query);
		foreach ($_catalogues as $catalogue)
		{
			$SEARCH_CATALOGUE_ENTRIES_CATALOGUES[$catalogue['c_name']]=$catalogue;
		}
		if (count($ranges)!=0) // Unfortunately we have to actually load these up to tell if we can use
		{
			foreach ($rows as $i=>$row)
			{
				$catalogue_name=$row['c_name'];
				$tpl_set=$catalogue_name;
				$display=get_catalogue_entry_map($row,$SEARCH_CATALOGUE_ENTRIES_CATALOGUES[$catalogue_name],'PAGE',$tpl_set,-1);
				foreach ($ranges as $range_id=>$range_key)
				{
					$bits=explode('-',$display['_FIELD_'.strval($range_id)]);
					if (count($bits)==2)
					{
						if ((intval($bits[0])>=intval($range_key)) || (intval($bits[1])<=intval($range_key)))
						{
							$out[$i]['restricted']=true;
							continue 2;
						}
					}
				}

				if (($remapped_orderer!='') && (array_key_exists($remapped_orderer,$row))) $out[$i]['orderer']=$row[$remapped_orderer]; elseif (substr($remapped_orderer,0,7)=='_rating') $out[$i]['orderer']=$row['compound_rating'];
			}
		} else
		{
			foreach ($rows as $i=>$row)
			{
				$out[$i]['data']=$row;
				unset($rows[$i]);
				if (($remapped_orderer!='') && (array_key_exists($remapped_orderer,$row))) $out[$i]['orderer']=$row[$remapped_orderer]; elseif (substr($remapped_orderer,0,7)=='_rating') $out[$i]['orderer']=$row['compound_rating'];
			}	
		}

		return $out;
	}

	/**
	 * Standard modular run function for rendering a search result.
	 *
	 * @param  array		The data row stored when we retrieved the result
	 * @return ?tempcode	The output (NULL: compound output)
	 */
	function render($row)
	{
		global $SEARCH_CATALOGUE_ENTRIES_CATALOGUES;

		require_css('catalogues');

		$catalogue_name=$row['c_name'];
		if (!array_key_exists($catalogue_name,$SEARCH_CATALOGUE_ENTRIES_CATALOGUES)) return new ocp_tempcode();
		$display_type=$SEARCH_CATALOGUE_ENTRIES_CATALOGUES[$catalogue_name]['c_display_type'];
		if (($display_type==C_DT_FIELDMAPS) || ($display_type==C_DT_GRID) || (get_param_integer('specific',0)==0)) // Singular results
		{
			$tpl_set=$catalogue_name;
			$display=get_catalogue_entry_map($row,$SEARCH_CATALOGUE_ENTRIES_CATALOGUES[$catalogue_name],'SEARCH',$tpl_set,-1);

			$tpl=do_template('CATALOGUE_'.$tpl_set.'_ENTRY_EMBED',$display,NULL,false,'CATALOGUE_DEFAULT_ENTRY_EMBED');

			$breadcrumbs=catalogue_category_breadcrumbs($row['cc_id'],NULL,false);
			if (!$breadcrumbs->is_empty()) $tpl->attach(paragraph(do_lang_tempcode('LOCATED_IN',$breadcrumbs)));

			return do_template('SIMPLE_PREVIEW_BOX',array('TITLE'=>do_lang_tempcode('CATALOGUE_ENTRY'),'SUMMARY'=>$tpl));
		} else // Compound results
		{
			global $CATALOGUE_ENTRIES_BUILDUP;
			if (!array_key_exists($catalogue_name,$CATALOGUE_ENTRIES_BUILDUP))
				$CATALOGUE_ENTRIES_BUILDUP[$catalogue_name]=array();
			$CATALOGUE_ENTRIES_BUILDUP[$catalogue_name][]=$row;
		}
		return NULL;
	}

}


