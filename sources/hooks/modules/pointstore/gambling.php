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
 * @package    pointstore
 */

class Hook_pointstore_gambling
{
    /**
     * Standard pointstore item initialisation function.
     */
    public function init()
    {
    }

    /**
     * Standard pointstore item initialisation function.
     *
     * @return array                    The "shop fronts"
     */
    public function info()
    {
        $class = str_replace('hook_pointstore_','',strtolower(get_class($this)));

        if (get_option('is_on_' . $class . '_buy') == '0') {
            return array();
        }

        $next_url = build_url(array('page' => '_SELF','type' => 'action','id' => $class),'_SELF');
        return array(do_template('POINTSTORE_' . strtoupper($class),array('NEXT_URL' => $next_url)));
    }

    /**
     * Standard interface stage of pointstore item purchase.
     *
     * @return tempcode                 The UI
     */
    public function action()
    {
        $class = str_replace('hook_pointstore_','',strtolower(get_class($this)));

        if (get_option('is_on_' . $class . '_buy') == '0') {
            return new ocp_tempcode();
        }

        $title = get_screen_title('GAMBLING');

        $cost = intval(get_option('minimum_gamble_amount'));
        $points_left = available_points(get_member());
        $max = min(intval(get_option('maximum_gamble_amount')),$points_left);
        $next_url = build_url(array('page' => '_SELF','type' => 'action_done','id' => $class),'_SELF');

        // Check points
        if (($points_left<$cost) && (!has_privilege(get_member(),'give_points_self'))) {
            return warn_screen($title,do_lang_tempcode('_CANT_AFFORD',integer_format($cost),integer_format($points_left)));
        }

        require_code('form_templates');
        $fields = new ocp_tempcode();
        $fields->attach(form_input_integer(do_lang_tempcode('AMOUNT'),do_lang_tempcode('DESCRIPTION_GAMBLE_AMOUNT',integer_format($cost),integer_format($max)),'amount',$cost,true));

        $text = do_lang_tempcode('GAMBLE_A',integer_format($cost),integer_format($max),integer_format($points_left));

        return do_template('FORM_SCREEN',array('_GUID' => 'ae703225db618f2bc938290fbae4d6d8','TITLE' => $title,'TEXT' => $text,'URL' => $next_url,'FIELDS' => $fields,'HIDDEN' => '','SUBMIT_ICON' => 'buttons__proceed','SUBMIT_NAME' => do_lang_tempcode('PROCEED')));
    }

    /**
     * Standard actualisation stage of pointstore item purchase.
     *
     * @return tempcode                 The UI
     */
    public function action_done()
    {
        $class = str_replace('hook_pointstore_','',strtolower(get_class($this)));

        if (get_option('is_on_' . $class . '_buy') == '0') {
            return new ocp_tempcode();
        }

        $amount = post_param_integer('amount',-1);

        $title = get_screen_title('GAMBLING');

        // Check points
        $cost = intval(get_option('minimum_gamble_amount'));
        $points_left = available_points(get_member());
        $max = min(intval(get_option('maximum_gamble_amount')),$points_left);
        if ((!has_privilege(get_member(),'give_points_self')) || ($amount<0)) {
            if (($amount<$cost) || ($amount>$max)) {
                warn_exit(do_lang_tempcode('INVALID_GAMBLE_AMOUNT'));
            }
            if ($points_left<$amount) {
                return warn_screen($title,do_lang_tempcode('_CANT_AFFORD',integer_format($cost),integer_format($points_left)));
            }
        }

        // Calculate
        $average_gamble_multiplier = floatval(get_option('average_gamble_multiplier'))/100.0;
        $maximum_gamble_multiplier = floatval(get_option('maximum_gamble_multiplier'))/100.0;
        $above_average = (mt_rand(0,10)<5);
        if ($above_average) {
            //       $winnings=round($average_gamble_multiplier*$amount+mt_rand(0,round($maximum_gamble_multiplier*$amount-$average_gamble_multiplier*$amount)));   Even distribution is NOT wise
            $peak = $maximum_gamble_multiplier*$amount;
            $under = 0.0;
            $number = intval(round($average_gamble_multiplier*$amount+mt_rand(0,intval(round($maximum_gamble_multiplier*$amount-$average_gamble_multiplier*$amount)))));
            for ($x = 1;$x<intval($peak);$x++) { // Perform some discrete calculus: we need to find when we've reached the proportional probability area equivalent to our number
                $p = $peak*(1.0/pow(floatval($x)+0.4,2.0)-(1.0/pow($maximum_gamble_multiplier*floatval($amount),2.0))); // Using a 1/x^2 curve. 0.4 is a bit of a magic number to get the averaging right
                $under += $p;
                if ($under>floatval($number)) {
                    break;
                }
            }
            $winnings = intval(round($average_gamble_multiplier*$amount+$x*1.1)); // 1.1 is a magic number to make it seem a bit fairer
        } else {
            $winnings = mt_rand(0,intval(round($average_gamble_multiplier*$amount)));
        }

        // Actuate
        require_code('points2');
        charge_member(get_member(),$amount-$winnings,do_lang('GAMBLING'));
        $GLOBALS['SITE_DB']->query_insert('sales',array('date_and_time' => time(),'memberid' => get_member(),'purchasetype' => 'GAMBLING','details' => strval($amount),'details2' => ''));

        // Show message
        if ($winnings>$amount) {
            $result = do_lang_tempcode('GAMBLE_CONGRATULATIONS',integer_format($winnings-$amount),integer_format($amount));
        } else {
            $result = do_lang_tempcode('GAMBLE_COMMISERATIONS',integer_format($amount-$winnings),integer_format($amount));
        }
        $url = build_url(array('page' => '_SELF','type' => 'misc'),'_SELF');
        return redirect_screen($title,$url,$result);
    }
}
