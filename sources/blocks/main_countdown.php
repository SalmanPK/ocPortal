<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2015

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    counting_blocks
 */

/**
 * Block class.
 */
class Block_main_countdown
{
    /**
     * Find details of the block.
     *
     * @return ?array                   Map of block info (null: block is disabled).
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
        $info['parameters'] = array('param', 'precision', 'tailing');
        return $info;
    }

    /**
     * Execute the block.
     *
     * @param  array                    $map A map of parameters.
     * @return tempcode                 The result of execution.
     */
    public function run($map)
    {
        require_css('counting_blocks');

        require_lang('dates');

        $precision = array_key_exists('precision', $map) ? intval($map['precision']) : 4;
        $tailing = array_key_exists('tailing', $map) ? intval($map['tailing']) : 4;

        // Counting from/to what?
        $target = mixed();
        $target = array_key_exists('param', $map) ? $map['param'] : '';
        if ($target == '') {
            $target = '25th December';
        }
        if (!is_numeric($target)) {
            $target = strtotime($target);
            if ($target === false) {
                return paragraph(do_lang_tempcode('OUT_OF_BOUNDS_TIME'), '', 'red_alert');
            }
        } else {
            $target = intval($target); // Let's accept either a timestamp or human strings that PHP's 'strtotime' can understand
        }

        // Work out our numbers
        $seconds_to_go = $target - tz_time(time(), get_site_timezone());
        if ($seconds_to_go >= 0) {
            $positive_seconds_to_go = $seconds_to_go;
        } else {
            $positive_seconds_to_go = -$seconds_to_go;
        }
        if ($tailing >= 4) {
            $years = intval(floor($positive_seconds_to_go / 60.0 / 60.0 / 24.0 / 365.0));
            $positive_seconds_to_go -= $years * 60 * 60 * 24 * 365;
        } else {
            $years = 0;
        }
        if ($tailing >= 3) {
            $days = intval(floor($positive_seconds_to_go / 60.0 / 60.0 / 24.0));
            $positive_seconds_to_go -= $days * 60 * 60 * 24;
        } else {
            $days = 0;
        }
        if ($tailing >= 2) {
            $hours = intval(floor($positive_seconds_to_go / 60.0 / 60.0));
            $positive_seconds_to_go -= $hours * 60 * 60;
        } else {
            $years = 0;
        }
        if ($tailing >= 1) {
            $minutes = intval(floor($positive_seconds_to_go / 60.0));
            $positive_seconds_to_go -= $minutes * 60;
        } else {
            $minutes = 0;
        }
        $seconds = $positive_seconds_to_go;

        // The output display
        $time = new Tempcode();
        if (($tailing >= 4) && ($precision >= 0)) {
            $time->attach(do_lang_tempcode((($years != 0)) ? 'COUNTDOWN_SEP' : 'COUNTDOWN_SEP_ZERO', strval($years), ($years == 1) ? do_lang_tempcode('YEAR') : do_lang_tempcode('DPLU_YEARS')));
        }
        if (($tailing >= 3) && ($precision >= 1)) {
            $time->attach(do_lang_tempcode((($years != 0) || ($days != 0)) ? 'COUNTDOWN_SEP' : 'COUNTDOWN_SEP_ZERO', strval($days), ($days == 1) ? do_lang_tempcode('DAY') : do_lang_tempcode('DPLU_DAYS')));
        }
        if (($tailing >= 2) && ($precision >= 2)) {
            $time->attach(do_lang_tempcode((($years != 0) || ($days != 0) || ($hours != 0)) ? 'COUNTDOWN_SEP' : 'COUNTDOWN_SEP_ZERO', strval($hours), ($hours == 1) ? do_lang_tempcode('HOUR') : do_lang_tempcode('DPLU_HOURS')));
        }
        if (($tailing >= 1) && ($precision >= 3)) {
            $time->attach(do_lang_tempcode((($years != 0) || ($days != 0) || ($hours != 0) || ($minutes != 0)) ? 'COUNTDOWN_SEP' : 'COUNTDOWN_SEP_ZERO', strval($minutes), ($minutes == 1) ? do_lang_tempcode('MINUTE') : do_lang_tempcode('DPLU_MINUTES')));
        }
        if ($precision >= 4) {
            $time->attach(do_lang_tempcode((($years != 0) || ($days != 0) || ($hours != 0) || ($minutes != 0) || ($seconds != 0)) ? 'COUNTDOWN_SEP' : 'COUNTDOWN_SEP_ZERO', strval($seconds), ($seconds == 1) ? do_lang_tempcode('SECOND') : do_lang_tempcode('DPLU_SECONDS')));
        }

        $positive = ($seconds_to_go >= 0);
        $lang = do_lang_tempcode($positive ? 'COUNTDOWN_PLUS' : 'COUNTDOWN_MINUS', $time);

        $milliseconds_for_precision = 1000;
        $distance_for_precision = 1;
        switch ($precision) {
            case 0:
                $milliseconds_for_precision = 1000 * 60 * 60 * 24 * 365;
                $distance_for_precision = 1 * 60 * 60 * 24 * 365;
                break;
            case 1:
                $milliseconds_for_precision = 1000 * 60 * 60 * 24;
                $distance_for_precision = 1 * 60 * 60 * 24;
                break;
            case 2:
                $milliseconds_for_precision = 1000 * 60 * 60;
                $distance_for_precision = 1 * 60 * 60;
                break;
            case 3:
                $milliseconds_for_precision = 1000 * 60;
                $distance_for_precision = 1 * 60;
                break;
            case 4:
                $milliseconds_for_precision = 1000;
                $distance_for_precision = 1;
                break;
        }

        return do_template('BLOCK_MAIN_COUNTDOWN', array(
            '_GUID' => '7bfbaf09b256edcdfc0aeb04d282c3b8',
            'TAILING' => strval($tailing),
            'LANG' => $lang,
            'POSITIVE' => $positive,
            'PRECISION' => strval($precision),
            'MILLISECONDS_FOR_PRECISION' => strval($milliseconds_for_precision),
            'DISTANCE_FOR_PRECISION' => strval($distance_for_precision),
        ));
    }
}
