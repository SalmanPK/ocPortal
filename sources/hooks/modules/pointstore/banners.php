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
 * @package    banners
 */

class Hook_pointstore_banners
{
    /**
     * Standard pointstore item initialisation function.
     */
    public function init()
    {
        require_code('banners');
        require_code('banners2');
        require_lang('banners');
    }

    /**
     * Standard pointstore item "shop front" function.
     *
     * @return array                    The "shop fronts"
     */
    public function info()
    {
        if (!addon_installed('banners')) {
            return array();
        }

        if (get_option('is_on_banner_buy') == '1') {
            $banner_url = build_url(array('page' => '_SELF','type' => 'bannerinfo','id' => 'banners'),get_module_zone('banners'));

            return array(do_template('POINTSTORE_BANNERS_2',array('_GUID' => '0b34fc7675b4a71fd6e530ad43213e70','BANNER_URL' => $banner_url)));
        }
        return array();
    }

    /**
     * Standard pointstore introspection.
     *
     * @return tempcode                 The UI
     */
    public function bannerinfo()
    {
        if (get_option('is_on_banner_buy') == '0') {
            return new ocp_tempcode();
        }

        $title = get_screen_title('TITLE_BANNER');

        $banner_name = $GLOBALS['SITE_DB']->query_select_value_if_there('sales','details',array('memberid' => get_member(),'purchasetype' => 'banner'));
        if (!is_null($banner_name)) {
            $activate = new ocp_tempcode();
            $upgrade_url = build_url(array('page' => '_SELF','type' => 'upgradebanner','id' => 'banners'),'_SELF');
            $upgrade = do_template('POINTSTORE_BANNERS_UPGRADE',array('_GUID' => '975688582e5acbfc0a84a4ef2c3b824e','UPGRADE_URL' => $upgrade_url));
        } else {
            $upgrade = new ocp_tempcode();
            $activate_url = build_url(array('page' => '_SELF','type' => 'newbanner','id' => 'banners'),'_SELF');
            $activate = do_template('POINTSTORE_BANNERS_ACTIVATE',array('_GUID' => '1f06d08517395e8c22607727fe9f5b91','ACTIVATE_URL' => $activate_url));
        }

        return do_template('POINTSTORE_BANNERS_SCREEN',array('_GUID' => '7879a4b736d5d98983fe2454677ae8d7','TITLE' => $title,'ACTIVATE' => $activate,'UPGRADE' => $upgrade));
    }

    /**
     * Checking to be sure we don't already have a banner.
     */
    public function handle_has_banner_already()
    {
        $member_id = get_member();
        $has_one = $GLOBALS['SITE_DB']->query_select_value_if_there('sales','details',array('memberid' => $member_id,'purchasetype' => 'banner'));
        if (!is_null($has_one)) {
            $myrows = $GLOBALS['SITE_DB']->query_select('banners',array('campaign_remaining','importance_modulus','name'),array('name' => $has_one),'',1);
            if (array_key_exists(0,$myrows)) {
                warn_exit(do_lang_tempcode('BANNER_ALREADY'));
            }
        }
    }

    /**
     * Standard stage of pointstore item purchase.
     *
     * @return tempcode                 The UI
     */
    public function newbanner()
    {
        if (get_option('is_on_banner_buy') == '0') {
            return new ocp_tempcode();
        }

        $this->handle_has_banner_already();

        // We can purchase a banner...
        $initial_hits = intval(get_option('initial_banner_hits'));
        $banner_price = intval(get_option('banner_setup'));
        $text = paragraph(do_lang_tempcode('BANNERS_DESCRIPTION',integer_format($initial_hits),integer_format($banner_price)));
        list($fields,$javascript) = get_banner_form_fields(true);
        $title = get_screen_title('ADD_BANNER');
        $post_url = build_url(array('page' => '_SELF','type' => '_newbanner','id' => 'banners','uploading' => 1),'_SELF');

        $hidden = new ocp_tempcode();
        handle_max_file_size($hidden,'image');

        return do_template('FORM_SCREEN',array('_GUID' => '45b8878d92712e07c4eb5497f1a33e33','HIDDEN' => $hidden,'TITLE' => $title,'TEXT' => $text,'FIELDS' => $fields,'URL' => $post_url,'SUBMIT_ICON' => 'buttons__proceed','SUBMIT_NAME' => do_lang_tempcode('ADD_BANNER'),'JAVASCRIPT' => $javascript));
    }

    /**
     * Standard stage of pointstore item purchase.
     *
     * @return tempcode                 The UI
     */
    public function _newbanner()
    {
        if (get_option('is_on_banner_buy') == '0') {
            return new ocp_tempcode();
        }

        require_code('uploads');

        $title = get_screen_title('ADD_BANNER');

        $member_id = get_member(); // the ID of the member who is logged in right now
        $cost = intval(get_option('banner_setup'));
        $points_after = available_points($member_id)-$cost; // the number of points this member has left

        // So we don't have to call these big ugly names, again...
        $name = post_param('name');
        $urls = get_url('image_url','file','uploads/banners',0,OCP_UPLOAD_IMAGE);
        $image_url = $urls[0];
        $site_url = post_param('site_url');
        $caption = post_param('caption');
        $direct_code = post_param('direct_code','');
        $notes = post_param('notes','');

        $this->check_afford_banner();

        $this->handle_has_banner_already();

        $banner = show_banner($name,'',comcode_to_tempcode($caption),$direct_code,(url_is_local($image_url)?(get_custom_base_url() . '/'):'') . $image_url,'',$site_url,'',get_member());
        $proceed_url = build_url(array('page' => '_SELF','type' => '__newbanner','id' => 'banners'),'_SELF');
        $cancel_url = build_url(array('page' => '_SELF'),'_SELF');
        $keep = new ocp_tempcode();
        $keep->attach(form_input_hidden('image_url',$image_url));
        $keep->attach(form_input_hidden('site_url',$site_url));
        $keep->attach(form_input_hidden('caption',$caption));
        $keep->attach(form_input_hidden('notes',$notes));
        $keep->attach(form_input_hidden('name',$name));

        return do_template('POINTSTORE_CONFIRM_SCREEN',array(
            '_GUID' => '6d5ac2177bd76e31b915d4358c0bbbf4',
            'ACTION' => '',
            'COST' => integer_format($cost),
            'POINTS_AFTER' => integer_format($points_after),
            'TITLE' => $title,
            'MESSAGE' => $banner,
            'PROCEED_URL' => $proceed_url,
            'CANCEL_URL' => $cancel_url,
            'KEEP' => $keep,
        ));
    }

    /**
     * Check that the implied transaction could be afforded.
     */
    public function check_afford_banner()
    {
        $after_deduction = available_points(get_member())-intval(get_option('banner_setup'));

        if (($after_deduction<0) && (!has_privilege(get_member(),'give_points_self'))) {
            warn_exit(do_lang_tempcode('CANT_AFFORD'));
        }
    }

    /**
     * Standard stage of pointstore item purchase.
     *
     * @return tempcode                 The UI
     */
    public function __newbanner()
    {
        if (get_option('is_on_banner_buy') == '0') {
            return new ocp_tempcode();
        }

        $this->check_afford_banner();

        // So we don't need to call these big ugly names, again...
        $image_url = post_param('image_url');
        $site_url = post_param('site_url');
        $caption = post_param('caption');
        $direct_code = post_param('direct_code','');
        $notes = post_param('notes','');
        $name = post_param('name');

        $cost = intval(get_option('banner_setup'));

        $this->handle_has_banner_already();

        check_banner();
        add_banner($name,$image_url,'',$caption,$direct_code,intval(get_option('initial_banner_hits')),$site_url,3,$notes,1,null,get_member(),0);
        $GLOBALS['SITE_DB']->query_insert('sales',array('date_and_time' => time(),'memberid' => get_member(),'purchasetype' => 'banner','details' => $name,'details2' => ''));
        require_code('points2');
        charge_member(get_member(),$cost,do_lang('ADD_BANNER'));

        // Send mail to staff
        require_code('submit');
        $edit_url = build_url(array('page' => 'cms_banners','type' => '_ed','name' => $name),get_module_zone('cms_banners'),null,false,false,true);
        if (addon_installed('unvalidated')) {
            send_validation_request('ADD_BANNER','banners',true,$name,$edit_url);
        }

        $title = get_screen_title('ADD_BANNER');
        $stats_url = build_url(array('page' => 'banners','type' => 'misc'),get_module_zone('banners'));
        $text = do_lang_tempcode('PURCHASED_BANNER');

        $_banner_type_row = $GLOBALS['SITE_DB']->query_select('banner_types',array('t_image_width','t_image_height'),array('id' => ''),'',1);
        if (array_key_exists(0,$_banner_type_row)) {
            $banner_type_row = $_banner_type_row[0];
        } else {
            $banner_type_row = array('t_image_width' => 728,'t_image_height' => 90);
        }
        $banner_code = do_template('BANNER_SHOW_CODE',array('_GUID' => 'c96f0ce22de97782b1ab9bee3f43c0ba','TYPE' => '','NAME' => $name,'WIDTH' => strval($banner_type_row['t_image_width']),'HEIGHT' => strval($banner_type_row['t_image_height'])));

        return do_template('BANNER_ADDED_SCREEN',array('_GUID' => '68725923b19d3df71c72276ada826183','TITLE' => $title,'TEXT' => $text,'BANNER_CODE' => $banner_code,'STATS_URL' => $stats_url,'DO_NEXT' => ''));
    }

    /**
     * Ensure the current member has a banner and return its row. If they do not have one, exit.
     *
     * @return array                    The banner row the current member has
     */
    public function handle_has_no_banner()
    {
        $member_id = get_member();

        $details = $GLOBALS['SITE_DB']->query_select_value_if_there('sales','details',array('memberid' => $member_id,'purchasetype' => 'banner'));

        // If we don't own a banner account, stop right here.
        if (is_null($details)) {
            warn_exit(do_lang_tempcode('NO_BANNER'));
        }

        $myrows = $GLOBALS['SITE_DB']->query_select('banners',array('campaign_remaining','importance_modulus','name'),array('name' => $details),'',1);
        if (!array_key_exists(0,$myrows)) {
            $GLOBALS['SITE_DB']->query_delete('sales',array('purchasetype' => 'banner','memberid' => $member_id),'',1);
            warn_exit(do_lang_tempcode('BANNER_DELETED_REMAKE'));
        }

        return $myrows[0];
    }

    /**
     * Standard stage of pointstore item purchase.
     *
     * @return tempcode                 The UI
     */
    public function upgradebanner()
    {
        if (get_option('is_on_banner_buy') == '0') {
            return new ocp_tempcode();
        }

        $title = get_screen_title('TITLE_BANNER_UPGRADE');

        $impcost = intval(get_option('banner_imp'));
        $hitcost = intval(get_option('banner_hit'));

        $this->handle_has_no_banner();

        // Screen
        require_code('form_templates');
        $post_url = build_url(array('page' => '_SELF','type' => '_upgradebanner','id' => 'banners'),'_SELF');
        $text = paragraph(do_lang_tempcode('IMPORTANCE_BUY',integer_format($hitcost),integer_format($impcost)));
        $fields = form_input_line(do_lang_tempcode('IMPORTANCE'),do_lang_tempcode('IMPORTANCE_UPGRADE_DESCRIPTION'),'importance','1',true);
        $fields->attach(form_input_line(do_lang_tempcode('EXTRA_HITS'),do_lang_tempcode('EXTRA_HITS_DESCRIPTION'),'hits','50',true));
        return do_template('FORM_SCREEN',array('_GUID' => '550b0368236dcf58726a1895162ad6c2','SUBMIT_ICON' => 'buttons__proceed','SUBMIT_NAME' => do_lang_tempcode('UPGRADE'),'HIDDEN' => '','URL' => $post_url,'TITLE' => $title,'FIELDS' => $fields,'TEXT' => $text));
    }

    /**
     * Standard stage of pointstore item purchase.
     *
     * @return tempcode                 The UI
     */
    public function _upgradebanner()
    {
        if (get_option('is_on_banner_buy') == '0') {
            return new ocp_tempcode();
        }

        $title = get_screen_title('TITLE_BANNER_UPGRADE');

        $member_id = get_member();
        $points_left = available_points($member_id);

        $myrow = $this->handle_has_no_banner();

        $curhit = $myrow['campaign_remaining'];
        $curimp = $myrow['importance_modulus'];
        $name = $myrow['name'];

        // So we don't have to call these big ugly names, again...
        $futhit = post_param_integer('hits');
        $futimp = post_param_integer('importance');

        // Checking to be sure we've ordered numbers that are positive
        if (!(($futimp >= 0) && ($futhit >= 0))) {
            return warn_screen($title,do_lang_tempcode('BAD_INPUT'));
        }

        // Checking to be sure we haven't ordered nothing...
        if (($futimp == 0) && ($futhit == 0)) {
            return warn_screen($title,do_lang_tempcode('SILLY_INPUT'));
        }

        // How many importance and hits will we have after this?
        $afthit = $curhit+$futhit;
        $aftimp = $curimp+$futimp;

        // Getting the prices of hits and importance...
        $impprice = intval(get_option('banner_imp'));
        $hitprice = intval(get_option('banner_hit'));

        // Figuring out the price of importance and hits, depedning on how many they bought.
        $impcost = $futimp*$impprice;
        $hitcost = $futhit*$hitprice;
        $total_price = $hitcost+$impcost;
        $points_after = $points_left-$total_price;

        // Check to see this isn't costing us more than we can afford
        if (($points_after<0) && (!has_privilege(get_member(),'give_points_self'))) {
            return warn_screen($title,do_lang_tempcode('CANT_AFFORD'));
        }

        // If this is *not* our first time through, do a confirmation screen. Else, make the purchase.
        $ord = post_param_integer('ord',0);
        if ($ord == 0) {
            $proceed_url = build_url(array('page' => '_SELF','type' => '_upgradebanner','id' => 'banners'),'_SELF');
            $keep = new ocp_tempcode();
            $keep->attach(form_input_hidden('hits',strval($futhit)));
            $keep->attach(form_input_hidden('importance',strval($futimp)));
            $keep->attach(form_input_hidden('ord','1'));
            $action = do_lang_tempcode('BANNER_UPGRADE_CONFIRM',integer_format($futimp),integer_format($futhit));

            return do_template('POINTSTORE_CONFIRM_SCREEN',array(
                '_GUID' => 'acdde0bd41ccd1459bbd7a1e9ca5ed68',
                'TITLE' => $title,
                'MESSAGE' => $action,
                'ACTION' => '',
                'COST' => integer_format($total_price),
                'POINTS_AFTER' => integer_format($points_after),
                'CANCEL_URL' => build_url(array('page' => '_SELF'),'_SELF'),
                'PROCEED_URL' => $proceed_url,
                'KEEP' => $keep,
            ));
        }

        // Our Query
        $GLOBALS['SITE_DB']->query_update('banners',array('campaign_remaining' => $afthit,'importance_modulus' => $aftimp),array('name' => $name),'',1);

        // Charge the user for their purchase
        require_code('points2');
        charge_member($member_id,$total_price,do_lang('BANNER_UPGRADE_LINE',integer_format($futhit),integer_format($futimp)));

        $url = build_url(array('page' => '_SELF','type' => 'misc'),'_SELF');
        return redirect_screen($title,$url,do_lang_tempcode('BANNER_UPGRADED'));
    }
}
