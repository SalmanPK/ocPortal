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
 * @package    core
 */

/**
 * Standard code module initialisation function.
 */
function init__templates()
{
    global $SKIP_TITLING;
    /** Whether we actually don't want to show screen titles in our output -- put them out as empty.
     * @global boolean $SKIP_TITLING
     */
    $SKIP_TITLING = false;
}

/**
 * Get the tempcode for a standard box (CSS driven), with the specified content entered. Please rarely use this function; it is not good to assume people want anythings in one of these boxes... use templates instead
 *
 * @param  tempcode                     The content being put inside the box
 * @param  mixed                        The title of the standard box, string or Tempcode (blank: titleless standard box)
 * @param  ID_TEXT                      The type of the box. Refers to a template (STANDARDBOX_type)
 * @param  string                       The CSS width
 * @param  string                       '|' separated list of options (meaning dependant upon templates interpretation)
 * @param  string                       '|' separated list of meta information (key|value|key|value|...)
 * @param  string                       '|' separated list of link information (linkhtml|...)
 * @param  string                       Link to be added to the header of the box
 * @return tempcode                     The contents, put inside a standard box, according to the other parameters
 */
function put_in_standard_box($content,$title = '',$type = 'default',$width = '',$options = '',$meta = '',$links = '',$top_links = '')
{
    if ($type == '') {
        $type = 'default';
    }

    $_meta = array();
    if ($meta != '') {
        $meta_bits = explode('|',$meta);
        if (count($meta_bits)%2 == 1) {
            unset($meta_bits[count($meta_bits)-1]);
        }
        for ($i = 0;$i<count($meta_bits);$i += 2) {
            $_meta[] = array('KEY' => $meta_bits[$i+0],'VALUE' => $meta_bits[$i+1]);
        }
    }

    $_links = array();
    if ($links != '') {
        $_links = explode('|',$links);
        if ($_links[count($_links)-1] == '') {
            array_pop($_links);
        }
    }

    $_options = explode('|',$options);

    if ($width == 'auto') {
        $width = '';
    }
    if (is_numeric($width)) {
        $width = strval(intval($width)) . 'px';
    }

    return do_template('STANDARDBOX_' . filter_naughty($type),array('WIDTH' => $width,'CONTENT' => $content,'LINKS' => $_links,'META' => $_meta,'OPTIONS' => $_options,'TITLE' => $title,'TOP_LINKS' => $top_links),null,true);
}

/**
 * Get the tempcode for a page title. (Ones below the page header, not in the browser title bar.)
 *
 * @sets_output_state
 *
 * @param  mixed                        The title to use (usually, a language string code, see below)
 * @param  boolean                      Whether the given title is actually a language string code, and hence gets dereferenced
 * @param  ?array                       Parameters sent to the language string (NULL: none)
 * @param  ?tempcode                    Separate title to put into the 'currently viewing' data (NULL: use $title)
 * @param  ?array                       Awards to say this has won (NULL: none)
 * @return tempcode                     The title tempcode
 */
function get_screen_title($title,$dereference_lang = true,$params = null,$user_online_title = null,$awards = null)
{
    global $TITLE_CALLED;
    $TITLE_CALLED = true;

    global $SKIP_TITLING;
    if ($SKIP_TITLING) {
        return new ocp_tempcode();
    }

    if (($dereference_lang) && (strpos($title,' ') !== false)) {
        $dereference_lang = false;
    }

    if ($params === NULL) {
        $params = array();
    }

    if ($dereference_lang) {
        $_title = do_lang_tempcode($title,array_shift($params),array_shift($params),$params);
    } else {
        $_title = is_object($title)?$title:make_string_tempcode($title);
    }

    if (function_exists('get_session_id')) {
        if (!$GLOBALS['SITE_DB']->table_is_locked('sessions')) {
            $change_map = array(
                'last_activity' => time(),
            );
            if (get_value('no_member_tracking') === '1') {
                $change_map += array(
                    'the_title' => '',
                    'the_zone' => '',
                    'the_page' => '',
                    'the_type' => '',
                    'the_id' => '',
                );
            } else {
                $change_map += array(
                    'the_title' => is_null($user_online_title)?substr($_title->evaluate(),0,255):$user_online_title->evaluate(),
                    'the_zone' => get_zone_name(),
                    'the_page' => substr(get_page_name(),0,80),
                    'the_type' => substr(get_param('type','',true),0,80),
                    'the_id' => substr(get_param('id','',true),0,80),
                );
            }
            $session_id = get_session_id();
            global $SESSION_CACHE;
            if ((get_value('disable_user_online_counting') !== '1') || (get_option('session_prudence') == '0') || (!isset($SESSION_CACHE[$session_id])) || ($SESSION_CACHE[$session_id]['last_activity']<time()-60*60*5)) {
                $GLOBALS['SITE_DB']->query_update('sessions',$change_map,array('the_session' => $session_id),'',1,null,false,true);
            }
        }
    }

    global $DISPLAYED_TITLE;
    $DISPLAYED_TITLE = $_title;

    if ($awards === NULL) {
        $awards = array();
    }

    return do_template('SCREEN_TITLE',array('_GUID' => '847ffbe4823eca6d2d5eac42828ee552','AWARDS' => $awards,'TITLE' => $_title));
}

/**
 * Get the tempcode for a hyperlink.
 *
 * @param  mixed                        The URL to put in the hyperlink (URLPATH or tempcode)
 * @param  mixed                        The hyperlinks caption (either tempcode or string)
 * @param  boolean                      Whether the link is an external one (by default, the external template makes it open in a new window)
 * @param  boolean                      Whether to escape the hyperlink caption (only applies if it is not passed as tempcode)
 * @param  mixed                        Link title (either tempcode or string) (blank: none)
 * @param  ?string                      The access key to use (NULL: none)
 * @param  ?tempcode                    Data to post (NULL: an ordinary link)
 * @param  ?string                      Rel (link type) (NULL: no special type)
 * @param  ?ID_TEXT                     Open in overlay with the default link/form target being as follows (e.g. _top or _self) (NULL: an ordinary link)
 * @return tempcode                     The generated hyperlink
 */
function hyperlink($url,$caption,$external = false,$escape = false,$title = '',$accesskey = null,$post_data = null,$rel = null,$overlay = null)
{
    if (((is_object($caption)) && ($caption->is_empty())) || ((!is_object($caption)) && ($caption == ''))) {
        $caption = do_lang('NA');
    }

    if ($post_data !== NULL) {
        $tpl = 'HYPERLINK_BUTTON';
    } else {
        $tpl = 'HYPERLINK';
    }
    return do_template($tpl,array('OVERLAY' => $overlay,'REL' => $rel,'POST_DATA' => $post_data,'ACCESSKEY' => $accesskey,'NEW_WINDOW' => $external,'TITLE' => $title,'URL' => $url,'CAPTION' => $escape?escape_html($caption):$caption));
}

/**
 * Get the tempcode for a paragraph. This function should only be used with escaped text strings that need to be put into a paragraph, not with sections of HTML. Remember, paragraphs are literally that, and should only be used with templates that don't assume that they are going to put the given parameters into paragraphs themselves.
 *
 * @param  mixed                        The text to put into the paragraph (string or tempcode)
 * @param  string                       GUID for call
 * @param  ?string                      CSS classname (NULL: none)
 * @return tempcode                     The generated paragraph
 */
function paragraph($text,$guid = '',$class = null)
{
    return do_template('PARAGRAPH',array('_GUID' => $guid,'TEXT' => $text,'CLASS' => $class));
}

/**
 * Get the tempcode for a div. Similar to paragraph, but may contain more formatting (such as <br />'s)
 *
 * @param  tempcode                     The tempcode to put into a div
 * @param  string                       GUID for call
 * @return tempcode                     The generated div with contents
 */
function div($tempcode,$guid = '')
{
    return do_template('DIV',array('_GUID' => '2b0459e48a6b6420b716e05f21a933ad','TEMPCODE' => $tempcode));
}

/**
 * Get the tempcode for an info page.
 *
 * @param  tempcode                     The title of the info page
 * @param  mixed                        The text to put on the info page (string, or language-tempcode)
 * @param  boolean                      Whether match key messages / redirects should be supported
 * @return tempcode                     The info page
 */
function inform_screen($title,$text,$support_match_key_messages = false)
{
    require_code('failure');

    $tmp = _look_for_match_key_message(is_object($text)?$text->evaluate():$text,false,!$support_match_key_messages);
    if (!is_null($tmp)) {
        $text = $tmp;
    }

    return do_template('INFORM_SCREEN',array('_GUID' => '6e0aec9eb8a1daca60f322f213ddd2ee','TITLE' => $title,'TEXT' => $text));
}

/**
 * Get the tempcode for a warn page.
 *
 * @param  tempcode                     The title of the warn page
 * @param  mixed                        The text to put on the warn page (either tempcode or string)
 * @param  boolean                      Whether to provide a back button
 * @param  boolean                      Whether match key messages / redirects should be supported
 * @return tempcode                     The warn page
 */
function warn_screen($title,$text,$provide_back = true,$support_match_key_messages = false)
{
    require_code('failure');
    return _warn_screen($title,$text,$provide_back,$support_match_key_messages);
}

/**
 * Get the tempcode for a hidden form element.
 *
 * @param  ID_TEXT                      The name which this input field is for
 * @param  string                       The value for this input field
 * @return tempcode                     The input field
 */
function form_input_hidden($name,$value)
{
    return do_template('FORM_SCREEN_INPUT_HIDDEN' . ((strpos($value,"\n") !== false)?'_2':''),array('_GUID' => '1b39e13d1a09573c67522e2f3b7ebf14','NAME' => $name,'VALUE' => $value));
}

/**
 * Get the tempcode for a group of list entry. May be attached directly to form_input_list_entry (i.e. this is a group node in a shared tree), and also fed into form_input_list.
 *
 * @param  mixed                        The title for the group
 * @param  tempcode                     List entries for group
 * @return tempcode                     The group
 */
function form_input_list_group($title,$entries)
{
    if (browser_matches('ios')) { // Workaround to iOS bug
        $entries2 = new ocp_tempcode();
        $entries2->attach(form_input_list_entry('',false,$title,false,true));
        $entries2->attach($entries);
        return $entries2;
    }

    return do_template('FORM_SCREEN_INPUT_LIST_GROUP',array('_GUID' => 'dx76a2685d0fba5f819ef160b0816d03','TITLE' => $title,'ENTRIES' => $entries));
}

/**
 * Get the tempcode for a list entry. (You would gather together the outputs of several of these functions, then put them in as the $content in a form_input_list function call).
 *
 * @param  string                       The value for this entry
 * @param  boolean                      Whether this entry is selected by default or not (Note: if nothing else is selected and this is the first, it will be selected by default anyway)
 * @param  mixed                        The text associated with this choice (blank: just use name for text)
 * @param  boolean                      Whether this entry will be put as red (marking it as important somehow)
 * @param  boolean                      Whether this list entry is disabled (like a header in a list)
 * @return tempcode                     The input field
 */
function form_input_list_entry($value,$selected = false,$text = '',$red = false,$disabled = false)
{
    if ((!is_object($text)) && ($text == '')) {
        $text = $value;
    }

    /* Causes a small performance hit and very unlikely to be needed
    if (function_exists('filter_form_field_default')) // Don't include just for this (may not be used on a full input form), preserve memory
        $selected=(filter_form_field_default($value,$selected?'1':'')=='1');*/

    return do_template('FORM_SCREEN_INPUT_LIST_ENTRY',array('_GUID' => 'dd76a2685d0fba5f819ef160b0816d03','SELECTED' => $selected,'DISABLED' => $disabled,'CLASS' => $red?'criticalfield':'','NAME' => is_integer($value)?strval($value):$value,'TEXT' => $text));
}

/**
 * Display some raw text so that it is repeated as raw visually in HTML.
 *
 * @param  string                       Input
 * @return tempcode                     Output
 */
function with_whitespace($in)
{
    if ($in == '') {
        return new ocp_tempcode();
    }
    return do_template('WITH_WHITESPACE',array('_GUID' => 'be3b74901d5522d4e67ff6313ad61643','CONTENT' => $in));
}

/**
 * Redirect the user - transparently, storing a message that will be shown on their destination page.
 *
 * @param  tempcode                     Title to display on redirect page
 * @param  mixed                        Destination URL (may be Tempcode)
 * @param  ?mixed                       Message to show (may be Tempcode) (NULL: standard redirection message)
 * @param  boolean                      For intermediary hops, don't mark so as to read status messages - save them up for the next hop (which will not be intermediary)
 * @param  ID_TEXT                      Code of message type to show
 * @set    warn inform fatal
 * @return tempcode                     Redirection message (likely to not actually be seen due to instant redirection)
 */
function redirect_screen($title,$url,$text = null,$intermediary_hop = false,$msg_type = 'inform')
{
    require_code('templates_redirect_screen');
    return _redirect_screen($title,$url,$text,$intermediary_hop,$msg_type);
}
