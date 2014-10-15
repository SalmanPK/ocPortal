<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    ecommerce
 */

require_code('crud_module');

/**
 * Module page class.
 */
class Module_admin_ecommerce extends standard_crud_module
{
    public $lang_type = 'USERGROUP_SUBSCRIPTION';
    public $select_name = 'TITLE';
    public $select_name_description = 'DESCRIPTION_TITLE';
    public $menu_label = 'ECOMMERCE';
    public $table = 'f_usergroup_subs';
    public $orderer = 's_title';
    public $title_is_multi_lang = true;

    public $javascript = "
        var _length_units=document.getElementById('length_units'),_length=document.getElementById('length');
        var adjust_lengths=function()
        {
            var length_units=_length_units.options[_length_units.selectedIndex].value,length=_length.value;
            if (document.getElementById('auto_recur').checked)
            {
                    // Limits based on https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/Appx_websitestandard_htmlvariables/
                    if ((length_units=='d') && ((length<1) || (length>90)))
                            _length.value=(length<1)?1:90;
                    if ((length_units=='w') && ((length<1) || (length>52)))
                            _length.value=(length<1)?1:52;
                    if ((length_units=='m') && ((length<1) || (length>24)))
                            _length.value=(length<1)?1:24;
                    if ((length_units=='y') && ((length<1) || (length>5)))
                            _length.value=(length<1)?1:5;
            } else
            {
                    if (length<1)
                            _length.value=1;
            }
        }
        _length_units.onchange=adjust_lengths;
        _length.onchange=adjust_lengths;
    ";

    /**
     * Find details of the module.
     *
     * @return ?array                   Map of module info (NULL: module is disabled).
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 2;
        $info['locked'] = false;
        return $info;
    }

    /**
     * Find entry-points available within this module.
     *
     * @param  boolean                  Whether to check permissions.
     * @param  ?MEMBER                  The member to check permissions as (NULL: current user).
     * @param  boolean                  Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name).
     * @param  boolean                  Whether to avoid any entry-point (or even return NULL to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "misc" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
     * @return ?array                   A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (NULL: disabled).
     */
    public function get_entry_points($check_perms = true,$member_id = null,$support_crosslinks = true,$be_deferential = false)
    {
        $ret = array(
            'misc' => array('CUSTOM_PRODUCT_USERGROUP','menu/adminzone/audit/ecommerce/subscriptions'),
        );
        $ret += parent::get_entry_points();
        return $ret;
    }

    public $title;

    /**
     * Module pre-run function. Allows us to know meta-data for <head> before we start streaming output.
     *
     * @param  boolean                  Whether this is running at the top level, prior to having sub-objects called.
     * @param  ?ID_TEXT                 The screen type to consider for meta-data purposes (NULL: read from environment).
     * @return ?tempcode                Tempcode indicating some kind of exceptional output (NULL: none).
     */
    public function pre_run($top_level = true,$type = null)
    {
        $type = get_param('type','misc');

        require_lang('ecommerce');

        set_helper_panel_tutorial('tut_ecommerce');

        if ($type == 'misc') {
            $also_url = build_url(array('page' => 'admin_ecommerce_logs','type' => 'misc'),get_module_zone('admin_ecommerce_logs'));
            attach_message(do_lang_tempcode('menus:ALSO_SEE_AUDIT',escape_html($also_url->evaluate())),'inform',true);

            $this->title = get_screen_title('CUSTOM_PRODUCT_USERGROUP');
        }

        if (($type == 'ad') || ($type == '_ad') || ($type == 'ed') || ($type == '_ed') || ($type == '__ed')) {
            if (get_forum_type() == 'ocf') {
                breadcrumb_set_parents(array(array('_SEARCH:admin_ocf_members:misc',do_lang_tempcode('MEMBERS'))));
            }
        }

        return parent::pre_run($top_level);
    }

    /**
     * Standard crud_module run_start.
     *
     * @param  ID_TEXT                  The type of module execution
     * @return tempcode                 The output of the run
     */
    public function run_start($type)
    {
        require_code('ecommerce');
        require_code('ecommerce2');

        if ((get_value('unofficial_ecommerce') != '1') && (count(find_all_hooks('systems','ecommerce')) == 8)) {
            if (get_forum_type() != 'ocf') {
                warn_exit(do_lang_tempcode('NO_OCF'));
            } else {
                ocf_require_all_forum_stuff();
            }
        }

        $this->add_one_label = do_lang_tempcode('ADD_USERGROUP_SUBSCRIPTION');
        $this->edit_this_label = do_lang_tempcode('EDIT_THIS_USERGROUP_SUBSCRIPTION');
        $this->edit_one_label = do_lang_tempcode('EDIT_USERGROUP_SUBSCRIPTION');

        if ($type == 'misc') {
            return $this->misc();
        }

        return new ocp_tempcode();
    }

    /**
     * The do-next manager for before setup management.
     *
     * @return tempcode                 The UI
     */
    public function misc()
    {
        require_code('templates_donext');
        return do_next_manager($this->title,comcode_lang_string('DOC_USERGROUP_SUBSCRIPTION'),
            array(
                ((get_forum_type() != 'ocf') && (get_value('unofficial_ecommerce') != '1'))?null:array('menu/_generic_admin/add_one',array('_SELF',array('type' => 'ad'),'_SELF'),do_lang('ADD_USERGROUP_SUBSCRIPTION')),
                ((get_forum_type() != 'ocf') && (get_value('unofficial_ecommerce') != '1'))?null:array('menu/_generic_admin/edit_one',array('_SELF',array('type' => 'ed'),'_SELF'),do_lang('EDIT_USERGROUP_SUBSCRIPTION')),
            ),
            do_lang('CUSTOM_PRODUCT_USERGROUP')
        );
    }

    /**
     * Get tempcode for adding/editing form.
     *
     * @param  SHORT_TEXT               The title
     * @param  LONG_TEXT                The description
     * @param  SHORT_TEXT               The cost
     * @param  integer                  The length
     * @param  SHORT_TEXT               The units for the length
     * @set    y m d w
     * @param  BINARY                   Auto-recur
     * @param  ?GROUP                   The usergroup that purchasing gains membership to (NULL: super members)
     * @param  BINARY                   Whether this is applied to primary usergroup membership
     * @param  BINARY                   Whether this is currently enabled
     * @param  ?LONG_TEXT               The text of the e-mail to send out when a subscription is start (NULL: default)
     * @param  ?LONG_TEXT               The text of the e-mail to send out when a subscription is ended (NULL: default)
     * @param  ?LONG_TEXT               The text of the e-mail to send out when a subscription cannot be renewed because the subproduct is gone (NULL: default)
     * @param  ?array                   Other e-mails to send (NULL: none)
     * @param  ?AUTO_LINK               ID of existing subscription (NULL: new)
     * @return array                    Tuple: The input fields, The hidden fields, The delete fields
     */
    public function get_form_fields($title = '',$description = '',$cost = '9.99',$length = 12,$length_units = 'm',$auto_recur = 1,$group_id = null,$uses_primary = 0,$enabled = 1,$mail_start = null,$mail_end = null,$mail_uhoh = null,$mails = null,$id = null)
    {
        if (($title == '') && (get_forum_type() == 'ocf')) {
            $add_usergroup_url = build_url(array('page' => 'admin_ocf_groups','type' => 'ad'),get_module_zone('admin_ocf_groups'));
            attach_message(do_lang_tempcode('ADD_USER_GROUP_FIRST',escape_html($add_usergroup_url->evaluate())),'inform',true);
        }

        $hidden = new ocp_tempcode();

        if (is_null($group_id)) {
            $group_id = get_param_integer('group_id',db_get_first_id()+3);
        }
        if (is_null($mail_start)) {
            $mail_start = do_lang('_PAID_SUBSCRIPTION_STARTED',get_option('site_name'));
        }
        if (is_null($mail_end)) {
            $_purchase_url = build_url(array('page' => 'purchase'),get_module_zone('purchase'),null,false,false,true);
            $purchase_url = $_purchase_url->evaluate();
            $mail_end = do_lang('_PAID_SUBSCRIPTION_ENDED',get_option('site_name'),$purchase_url);
        }
        if (is_null($mail_uhoh)) {
            $mail_uhoh = do_lang('_PAID_SUBSCRIPTION_UHOH',get_option('site_name'));
        }

        $fields = new ocp_tempcode();
        $fields->attach(form_input_line(do_lang_tempcode('TITLE'),do_lang_tempcode('DESCRIPTION_USERGROUP_SUBSCRIPTION_TITLE'),'title',$title,true));
        $fields->attach(form_input_text_comcode(do_lang_tempcode('DESCRIPTION'),do_lang_tempcode('DESCRIPTION_USERGROUP_SUBSCRIPTION_DESCRIPTION'),'description',$description,true));
        $fields->attach(form_input_float(do_lang_tempcode('COST'),do_lang_tempcode('DESCRIPTION_USERGROUP_SUBSCRIPTION_COST'),'cost',floatval($cost),true));

        $list = new ocp_tempcode();
        foreach (array('d','w','m','y') as $unit) {
            $list->attach(form_input_list_entry($unit,$unit == $length_units,do_lang_tempcode('LENGTH_UNIT_' . $unit)));
        }
        $fields->attach(form_input_list(do_lang_tempcode('LENGTH_UNITS'),do_lang_tempcode('DESCRIPTION_LENGTH_UNITS'),'length_units',$list));
        $fields->attach(form_input_integer(do_lang_tempcode('SUBSCRIPTION_LENGTH'),do_lang_tempcode('DESCRIPTION_USERGROUP_SUBSCRIPTION_LENGTH'),'length',$length,true));
        if (cron_installed()) {
            $fields->attach(form_input_tick(do_lang_tempcode('AUTO_RECUR'),do_lang_tempcode('DESCRIPTION_AUTO_RECUR'),'auto_recur',$auto_recur == 1));
        } else {
            $hidden->attach(form_input_hidden('auto_recur','1'));
        }

        $list = new ocp_tempcode();
        $groups = $GLOBALS['FORUM_DRIVER']->get_usergroup_list();
        if (get_forum_type() == 'ocf') {
            require_code('ocf_groups');
            $default_groups = ocf_get_all_default_groups(true,true);
        }
        foreach ($groups as $id => $group) {
            if (get_forum_type() == 'ocf') {
                if ((in_array($id,$default_groups)) && ($id !== $group_id)) {
                    continue;
                }
            }

            if ($id != $GLOBALS['FORUM_DRIVER']->get_guest_id()) {
                $list->attach(form_input_list_entry(strval($id),$id == $group_id,$group));
            }
        }
        $fields->attach(form_input_list(do_lang_tempcode('GROUP'),do_lang_tempcode('DESCRIPTION_USERGROUP_SUBSCRIPTION_GROUP'),'group_id',$list));

        $fields->attach(form_input_tick(do_lang_tempcode('USES_PRIMARY'),do_lang_tempcode('DESCRIPTION_USES_PRIMARY'),'uses_primary',$uses_primary == 1));

        $fields->attach(form_input_tick(do_lang_tempcode('ENABLED'),do_lang_tempcode('DESCRIPTION_USERGROUP_SUBSCRIPTION_ENABLED'),'enabled',$enabled == 1));

        $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('_GUID' => 'a03ec5b2afe5be764bd10694fc401fex','TITLE' => do_lang_tempcode('SUBSCRIPTION_EVENT_EMAILS'))));
        $fields->attach(form_input_text_comcode(do_lang_tempcode('MAIL_START'),do_lang_tempcode('DESCRIPTION_USERGROUP_SUBSCRIPTION_MAIL_START'),'mail_start',$mail_start,true,null,true));
        $fields->attach(form_input_text_comcode(do_lang_tempcode('MAIL_END'),do_lang_tempcode('DESCRIPTION_USERGROUP_SUBSCRIPTION_MAIL_END'),'mail_end',$mail_end,true,null,true));
        $fields->attach(form_input_text_comcode(do_lang_tempcode('MAIL_UHOH'),do_lang_tempcode('DESCRIPTION_USERGROUP_SUBSCRIPTION_MAIL_UHOH'),'mail_uhoh',$mail_uhoh,false,null,true));

        // Extra mails
        if (is_null($mails)) {
            $mails = array();
        }
        if (get_forum_type() == 'ocf') {
            for ($i = 0;$i<count($mails)+3/*Allow adding 3 on each edit*/;$i++) {
                $subject = isset($mails[$i])?$mails[$i]['subject']:'';
                $body = isset($mails[$i])?$mails[$i]['body']:'';
                $ref_point = isset($mails[$i])?$mails[$i]['ref_point']:'start';
                $ref_point_offset = isset($mails[$i])?$mails[$i]['ref_point_offset']:0;

                $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('TITLE' => do_lang_tempcode('EXTRA_SUBSCRIPTION_MAIL',integer_format($i+1)),'SECTION_HIDDEN' => ($subject == ''))));
                $fields->attach(form_input_line_comcode(do_lang_tempcode('SUBJECT'),do_lang_tempcode('DESCRIPTION_SUBSCRIPTION_SUBJECT'),'subject_' . strval($i),$subject,false));
                $fields->attach(form_input_text_comcode(do_lang_tempcode('BODY'),do_lang_tempcode('DESCRIPTION_SUBSCRIPTION_BODY'),'body_' . strval($i),$body,false,null,true));
                $radios = new ocp_tempcode();
                foreach (array('start','term_start','term_end','expiry') as $ref_point_type) {
                    $radios->attach(form_input_radio_entry('ref_point_' . strval($i),$ref_point_type,$ref_point == $ref_point_type,do_lang_tempcode('_SUBSCRIPTION_' . strtoupper($ref_point_type) . '_TIME')));
                }
                $fields->attach(form_input_radio(do_lang_tempcode('SUBSCRIPTION_REF_POINT'),do_lang_tempcode('DESCRIPTION_SUBSCRIPTION_REF_POINT'),'ref_point_' . strval($i),$radios,true));
                $fields->attach(form_input_integer(do_lang_tempcode('SUBSCRIPTION_REF_POINT_OFFSET'),do_lang_tempcode('DESCRIPTION_SUBSCRIPTION_REF_POINT_OFFSET'),'ref_point_offset_' . strval($i),$ref_point_offset,true));
            }
        }

        $delete_fields = null;
        if ($GLOBALS['SITE_DB']->query_select_value('subscriptions','COUNT(*)',array('s_type_code' => 'USERGROUP' . strval($id)))>0) {
            $delete_fields = new ocp_tempcode();
            $delete_fields->attach(form_input_tick(do_lang_tempcode('DELETE'),do_lang_tempcode('DESCRIPTION_DELETE_USERGROUP_SUB_DANGER'),'delete',false));
        }

        return array($fields,$hidden,$delete_fields,null,!is_null($delete_fields));
    }

    /**
     * Standard crud_module table function.
     *
     * @param  array                    Details to go to build_url for link to the next screen.
     * @return array                    A pair: The choose table, Whether re-ordering is supported from this screen.
     */
    public function create_selection_list_choose_table($url_map)
    {
        require_code('templates_results_table');

        $current_ordering = get_param('sort','s_title ASC');
        if (strpos($current_ordering,' ') === false) {
            warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
        }
        list($sortable,$sort_order) = explode(' ',$current_ordering,2);
        $sortables = array(
            's_title' => do_lang_tempcode('TITLE'),
            's_cost' => do_lang_tempcode('COST'),
            's_length' => do_lang_tempcode('SUBSCRIPTION_LENGTH'),
            's_group_id' => do_lang_tempcode('GROUP'),
            's_enabled' => do_lang('ENABLED'),
        );
        if (((strtoupper($sort_order) != 'ASC') && (strtoupper($sort_order) != 'DESC')) || (!array_key_exists($sortable,$sortables))) {
            log_hack_attack_and_exit('ORDERBY_HACK');
        }

        $header_row = results_field_title(array(
            do_lang_tempcode('TITLE'),
            do_lang_tempcode('COST'),
            do_lang_tempcode('SUBSCRIPTION_LENGTH'),
            do_lang_tempcode('GROUP'),
            do_lang('ENABLED'),
            do_lang_tempcode('ACTIONS'),
        ),$sortables,'sort',$sortable . ' ' . $sort_order);

        $fields = new ocp_tempcode();

        require_lang('ecommerce');

        require_code('form_templates');
        list($rows,$max_rows) = $this->get_entry_rows(false,$current_ordering,null,get_forum_type() != 'ocf');
        foreach ($rows as $r) {
            $edit_link = build_url($url_map+array('id' => $r['id']),'_SELF');

            $fields->attach(results_entry(array(get_translated_text($r['s_title'],$GLOBALS[(get_forum_type() == 'ocf')?'FORUM_DB':'SITE_DB']),$r['s_cost'],do_lang('_LENGTH_UNIT_' . $r['s_length_units'],integer_format($r['s_length'])),ocf_get_group_name($r['s_group_id']),($r['s_enabled'] == 1)?do_lang_tempcode('YES'):do_lang_tempcode('NO'),protect_from_escaping(hyperlink($edit_link,do_lang_tempcode('EDIT'),false,true,'#' . strval($r['id']))))),true);
        }

        return array(results_table(do_lang($this->menu_label),get_param_integer('start',0),'start',either_param_integer('max',20),'max',$max_rows,$header_row,$fields,$sortables,$sortable,$sort_order),false);
    }

    /**
     * Standard crud_module list function.
     *
     * @return tempcode                 The selection list
     */
    public function create_selection_list_entries()
    {
        $dbs_bak = $GLOBALS['NO_DB_SCOPE_CHECK'];
        $GLOBALS['NO_DB_SCOPE_CHECK'] = true;

        $_m = $GLOBALS[(get_forum_type() == 'ocf')?'FORUM_DB':'SITE_DB']->query_select('f_usergroup_subs',array('*'));
        $entries = new ocp_tempcode();
        foreach ($_m as $m) {
            $entries->attach(form_input_list_entry(strval($m['id']),false,get_translated_text($m['s_title'],$GLOBALS[(get_forum_type() == 'ocf')?'FORUM_DB':'SITE_DB'])));
        }

        $GLOBALS['NO_DB_SCOPE_CHECK'] = $dbs_bak;

        return $entries;
    }

    /**
     * Standard crud_module edit form filler.
     *
     * @param  ID_TEXT                  The entry being edited
     * @return array                    Tuple: The input fields, The hidden fields, The delete fields
     */
    public function fill_in_edit_form($id)
    {
        $dbs_bak = $GLOBALS['NO_DB_SCOPE_CHECK'];
        $GLOBALS['NO_DB_SCOPE_CHECK'] = true;

        $m = $GLOBALS['FORUM_DB']->query_select('f_usergroup_subs',array('*'),array('id' => intval($id)),'',1);
        if (!array_key_exists(0,$m)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }
        $r = $m[0];

        $_mails = $GLOBALS['FORUM_DB']->query_select('f_usergroup_sub_mails',array('*'),array('m_usergroup_sub_id' => intval($id)),'ORDER BY id');
        $mails = array();
        foreach ($_mails as $_mail) {
            $mails[] = array(
                'subject' => get_translated_text($_mail['m_subject'],$GLOBALS[(get_forum_type() == 'ocf')?'FORUM_DB':'SITE_DB']),
                'body' => get_translated_text($_mail['m_body'],$GLOBALS[(get_forum_type() == 'ocf')?'FORUM_DB':'SITE_DB']),
                'ref_point' => $_mail['m_ref_point'],
                'ref_point_offset' => $_mail['m_ref_point_offset'],
            );
        }

        $fields = $this->get_form_fields(
            get_translated_text($r['s_title'],$GLOBALS[(get_forum_type() == 'ocf')?'FORUM_DB':'SITE_DB']),
            get_translated_text($r['s_description'],$GLOBALS[(get_forum_type() == 'ocf')?'FORUM_DB':'SITE_DB']),
            $r['s_cost'],
            $r['s_length'],
            $r['s_length_units'],
            $r['s_auto_recur'],
            $r['s_group_id'],
            $r['s_uses_primary'],
            $r['s_enabled'],
            get_translated_text($r['s_mail_start'],$GLOBALS[(get_forum_type() == 'ocf')?'FORUM_DB':'SITE_DB']),
            get_translated_text($r['s_mail_end'],$GLOBALS[(get_forum_type() == 'ocf')?'FORUM_DB':'SITE_DB']),
            get_translated_text($r['s_mail_uhoh'],$GLOBALS[(get_forum_type() == 'ocf')?'FORUM_DB':'SITE_DB']),
            $mails,
            $id
        );

        $GLOBALS['NO_DB_SCOPE_CHECK'] = $dbs_bak;

        return $fields;
    }

    /**
     * Get a mapping of extra mails for the usergroup subscription.
     *
     * @return array                    Extra mails
     */
    public function _mails()
    {
        $mails = array();
        foreach (array_keys($_POST) as $key) {
            $matches = array();
            if (preg_match('#^subject_(\d+)$#',$key,$matches) != 0) {
                $subject = post_param('subject_' . $matches[1],'');
                $body = post_param('body_' . $matches[1],'');
                $ref_point = post_param('ref_point_' . $matches[1]);
                $ref_point_offset = post_param_integer('ref_point_offset_' . $matches[1]);
                if (($ref_point_offset<0) && ($ref_point != 'expiry')) {
                    $ref_point_offset = 0;
                    attach_message(do_lang_tempcode('SUBSCRIPTION_REF_POINT_OFFSET_NEGATIVE_ERROR'),'warn');
                }
                if ($subject != '' && $body != '') {
                    $mails[] = array(
                        'subject' => $subject,
                        'body' => $body,
                        'ref_point' => $ref_point,
                        'ref_point_offset' => $ref_point_offset,
                    );
                }
            }
        }
        return $mails;
    }

    /**
     * Standard crud_module add actualiser.
     *
     * @return array                    A pair: The entry added, Description about usage
     */
    public function add_actualisation()
    {
        if (has_actual_page_access(get_member(),'admin_config')) {
            $_config_url = build_url(array('page' => 'admin_config','type' => 'category','id' => 'ECOMMERCE'),get_module_zone('admin_config'));
            $config_url = $_config_url->evaluate();
            $config_url .= '#group_ECOMMERCE';

            $text = do_lang_tempcode('ECOM_ADDED_SUBSCRIP',escape_html($config_url));
        } else {
            $text = null;
        }

        $title = post_param('title');

        $mails = $this->_mails();

        $id = add_usergroup_subscription($title,post_param('description'),post_param('cost'),post_param_integer('length'),post_param('length_units'),post_param_integer('auto_recur',0),post_param_integer('group_id'),post_param_integer('uses_primary',0),post_param_integer('enabled',0),post_param('mail_start'),post_param('mail_end'),post_param('mail_uhoh'),$mails);
        return array(strval($id),$text);
    }

    /**
     * Standard crud_module edit actualiser.
     *
     * @param  ID_TEXT                  The entry being edited
     */
    public function edit_actualisation($id)
    {
        $title = post_param('title');

        $mails = $this->_mails();

        edit_usergroup_subscription(intval($id),$title,post_param('description'),post_param('cost'),post_param_integer('length'),post_param('length_units'),post_param_integer('auto_recur',0),post_param_integer('group_id'),post_param_integer('uses_primary',0),post_param_integer('enabled',0),post_param('mail_start'),post_param('mail_end'),post_param('mail_uhoh'),$mails);
    }

    /**
     * Standard crud_module delete actualiser.
     *
     * @param  ID_TEXT                  The entry being deleted
     */
    public function delete_actualisation($id)
    {
        $uhoh_mail = post_param('mail_uhoh');

        delete_usergroup_subscription(intval($id),$uhoh_mail);
    }
}
