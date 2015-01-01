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
 * @package    core
 */

/**
 * Standard code module initialisation function.
 */
function init__tempcode_compiler__runtime()
{
    if (!defined('SYMBOL_PARSE_NAME')) {
        define('SYMBOL_PARSE_NAME', 0);
        define('SYMBOL_PARSE_PARAM', 1);
    }
}

/**
 * Convert template text into tempcode format.
 *
 * @param  string                       $text The template text
 * @param  integer                      $symbol_pos The position we are looking at in the text
 * @param  boolean                      $inside_directive Whether this text is infact a directive, about to be put in the context of a wider template
 * @param  ID_TEXT                      $codename The codename of the template (e.g. foo)
 * @param  ?ID_TEXT                     $theme The theme it is for (null: current theme)
 * @param  ?ID_TEXT                     $lang The language it is for (null: current language)
 * @return mixed                        The converted/compiled template as tempcode, OR if a directive, encoded directive information
 */
function template_to_tempcode_static($text, $symbol_pos = 0, $inside_directive = false, $codename = '', $theme = null, $lang = null)
{
    $text = substr($text, 0, $symbol_pos) . substitute_comment_encapsulated_tempcode(substr($text, $symbol_pos));

    if (is_null($theme)) {
        $theme = is_null($GLOBALS['FORUM_DRIVER']) ? 'default' : $GLOBALS['FORUM_DRIVER']->get_theme();
    }

    $out = new Tempcode();
    $continuation = '';
    $symbol_len = strlen($text);
    while (true) {
        $jump_to = strpos($text, '{', $symbol_pos);
        $jump_to_b = strpos($text, '\\', $symbol_pos);
        if (($jump_to_b !== false) && (($jump_to === false) || ($jump_to_b < $jump_to))) {
            $continuation .= substr($text, $symbol_pos, $jump_to_b - $symbol_pos);
            $symbol_pos = $jump_to_b + 1;
            $nn = @$text[$symbol_pos];

            if (($nn == '{') || ($nn == '}') || ($nn == ',')) {
                continue; // Used to escape. We will pick up on in next iteration and also re-detect it is escaped.
            } else {
                $continuation .= '\\'; // Just a normal slash, not for escaping.
                continue;
            }
        }

        if ($jump_to === false) {
            $continuation .= substr($text, $symbol_pos);
            break;
        } else { // We're opening a variable if we meet a { followed by [\dA-Z\$\+\!\_]
            $continuation .= substr($text, $symbol_pos, $jump_to - $symbol_pos);

            $symbol_pos = $jump_to + 1;

            $nn_pre = ($symbol_pos >= 2) ? $text[$symbol_pos - 2] : '';
            $nn = @$text[$symbol_pos];
            if ((preg_match('#[\dA-Z\$\+\!\_]#', $nn) != 0) && ($nn_pre !== '\\')) {
                if ($continuation != '') {
                    $out->attach($continuation);
                }
                $continuation = '';
                $ret = read_single_uncompiled_variable($text, $symbol_pos, $symbol_len, $theme);
                if ($ret[1] == TC_DIRECTIVE) {
                    if ($ret[2] == 'START') {
                        $temp = template_to_tempcode_static($text, $symbol_pos, true);
                        if (is_array($temp)) {
                            list($_out, $symbol_pos) = $temp;
                            if ($ret[3] === null) { // Error, but don't die
                                $ret[3][] = $_out; // The inside of the directive becomes the final parameter
                                $out->bits[] = array($ret[0], TC_DIRECTIVE, '', $ret[3]);
                            } else {
                                $name = array_shift($ret[3]);
                                $ret[3][] = $_out; // The inside of the directive becomes the final parameter
                                $directive_type = $name->evaluate();
                                $out->bits[] = array($ret[0], TC_DIRECTIVE, $directive_type, $ret[3]);
                            }
                        }
                    } elseif ($ret[2] == 'END') {
                        if ($inside_directive) {
                            return array($out, $symbol_pos);
                        }
                        if (!$GLOBALS['LAX_COMCODE']) {
                            require_code('site');
                            attach_message(make_string_tempcode(do_lang('UNMATCHED_DIRECTIVE') . ' just before... ' . substr($text, $symbol_pos, 100) . ' [' . $codename . ']'), 'warn');
                        }
                    } else {
                        $out->bits[] = $ret;
                    }
                } else {
                    if (($ret[1] != TC_SYMBOL) || ($ret[2] != '')) {
                        $out->bits[] = $ret;
                    }
                }
            } else {
                $continuation .= '{';
            }
        }
    }
    if ($continuation != '') {
        $out->attach($continuation);
    }
    return $out;
}

/**
 * Convert and return an uncompiled textual variable (as used in templates, and Comcode) into tempcode (these pieces are attached to other pieces, in a certain way, forming a tempcode tree for a template or a variable-in-Comcode).
 *
 * @param  string                       $text The full text that is being parsed (we only look into this - the whole thing will be passed into PHP by reference, hence avoiding us a memory block copy)
 * @param  integer                      $symbol_pos The position we are looking at in the text
 * @param  integer                      $symbol_len The length of our variable
 * @param  ?ID_TEXT                     $theme The theme it is for (null: current theme)
 * @return array                        The tempcode variable array that our variable gets represented of
 */
function read_single_uncompiled_variable($text, &$symbol_pos, $symbol_len, $theme = null)
{
    if (is_null($theme)) {
        $theme = $GLOBALS['FORUM_DRIVER']->get_theme();
    }

    $type = TC_PARAMETER;
    $escaped = array();
    $mode = SYMBOL_PARSE_NAME;
    $name = '';
    $param = array();
    $params = 0;
    $starting = true;
    $matches = array();
    $dirty_param = false;

    while (true) {
        /*if ($quicker)    Possibly flaky, we don't need this code to be super-fast so we'll do the full parse
        {
            preg_match('#[^\\\\\{\}\,\+\!\'\$:%\*;\#~\.\^|\&/@=`]*#m',$text,$matches,0,$symbol_pos); // Guarded by $quicker, as only works on newer PHP versions
            $next=$matches[0];
        } else */
        $next = '';
        if ($next != '') {
            $symbol_pos += strlen($next);
        } else {
            $next = $text[$symbol_pos];
            ++$symbol_pos;
        }

        switch ($mode) {
            case SYMBOL_PARSE_PARAM:
                switch ($next) {
                    case '}':
                        if (($type == TC_SYMBOL) && ($name == 'IMG') && (count($param) == 1)) {
                            $param[] = '0';
                            $param[] = $theme;
                        }
                        $ret = array($escaped, $type, $name, $param);
                        return $ret;

                    case ',':
                        $dirty_param = ($type == TC_DIRECTIVE);
                        if ($dirty_param) {
                            $param[$params] = new Tempcode();
                        } else {
                            $param[$params] = '';
                        }
                        ++$params;
                        break;

                    case '{':
                        if (!$dirty_param) {
                            $param_string = $param[$params - 1];
                            $t = new Tempcode(); // For some very odd reason, PHP 4.3 doesn't allow you to do $param[$params-1]=new Tempcode(); $param[$params-1]->attach($param_string); (causes memory corruption apparently)
                            $t->attach($param_string);
                            $param[$params - 1] = $t;
                            $dirty_param = true;
                        }
                        $ret = read_single_uncompiled_variable($text, $symbol_pos, $symbol_len, $theme);
                        $param[$params - 1]->bits[] = $ret;
                        break;

                    case '\\':
                        if (($text[$symbol_pos] == '{') || ($text[$symbol_pos] == '}') || ($text[$symbol_pos] == ',')) {
                            $next = $text[$symbol_pos];
                            ++$symbol_pos;
                        }
                        // intentionally rolls on...
                    default:
                        if (!$dirty_param) {
                            $param[$params - 1] .= $next;
                        } else {
                            $param[$params - 1]->attach($next);
                        }
                }
                break;

            case SYMBOL_PARSE_NAME:
                if ($starting) {
                    $starting = false;
                    switch ($next) {
                        case '+':
                            $type = TC_DIRECTIVE;
                            continue 2;

                        case '!':
                            $type = TC_LANGUAGE_REFERENCE;
                            continue 2;

                        case '$':
                            $type = TC_SYMBOL;
                            continue 2;
                    }
                }
                switch ($next) {
                    case '}':
                        $ret = array($escaped, $type, $name, null);
                        return $ret;

                    case ',':
                        $dirty_param = ($type == TC_DIRECTIVE);
                        $mode = SYMBOL_PARSE_PARAM;
                        if ($dirty_param) {
                            $param[$params] = new Tempcode();
                        } else {
                            $param[$params] = '';
                        }
                        ++$params;
                        break;

                    case '%':
                        $escaped[] = NAUGHTY_ESCAPED;
                        break;

                    case '*':
                        $escaped[] = ENTITY_ESCAPED;
                        break;

                    case '=':
                        $escaped[] = FORCIBLY_ENTITY_ESCAPED;
                        break;

                    case ';':
                        $escaped[] = SQ_ESCAPED;
                        break;

                    case '#':
                        $escaped[] = DQ_ESCAPED;
                        break;

                    case '~':
                        $escaped[] = NL_ESCAPED;
                        break;

                    case '^':
                        $escaped[] = NL2_ESCAPED;
                        break;

                    case '|':
                        $escaped[] = ID_ESCAPED;
                        break;

                    case '\'':
                        $escaped[] = CSS_ESCAPED;
                        break;

                    case '&':
                        $escaped[] = UL_ESCAPED;
                        break;

                    case '.':
                        $escaped[] = UL2_ESCAPED;
                        break;

                    case '/':
                        $escaped[] = JSHTML_ESCAPED;
                        break;

                    case '`':
                        $escaped[] = NULL_ESCAPED;
                        break;

                    case '+':
                        $escaped[] = PURE_STRING;
                        break;

                    case '@':
                        if (in_array($text[$symbol_pos], array(':', '&', '/', '^', ';', '~', '#', '}', ','))) {
                            $escaped[] = CC_ESCAPED;
                            break;
                        }

                    default:
                        $name .= $next;
                }
                break;
        }

        if ($symbol_pos >= $symbol_len) {
            if (!$GLOBALS['LAX_COMCODE']) {
                require_code('site');
                attach_message(do_lang_tempcode('UNCLOSED_SYMBOL'), 'warn');
            }
            return array(array(), TC_KNOWN, '', null);
        }
    }
    return array(array(), TC_KNOWN, '', null);
}

/**
 * Static implementation of Tempcode.
 * @package    core
 */
class Tempcode_static
{
    // An array of bits where each bit is array($escape,$type,$value)
    //   NB: 'escape' doesn't apply for tempcode-typed-parameters or language-references
    public $bits;

    public $codename = ':container'; // The name of the template it came from

    /**
     * Constructor of tempcode
     */
    public function __construct()
    {
        $this->bits = array();
    }

    /**
     * Find whether a variable within this Tempcode is parameterless.
     *
     * @param  integer                  $at Offset to the variable
     * @return boolean                  Whether it is parameterless
     */
    public function parameterless($at)
    {
        return ((!array_key_exists(3, $this->bits[$at])) || (count($this->bits[$at][3]) == 0));
    }

    /**
     * Parse a single symbol from an input stream and append it.
     *
     * @param  string                   $code Code string (input stream)
     * @param  integer                  $pos Read position
     * @param  integer                  $len Length of input string
     */
    public function parse_from(&$code, &$pos, &$len)
    {
        $this->bits = array(read_single_uncompiled_variable($code, $pos, $len));
    }

    /**
     * Set the embedment of a directive within this Tempcode.
     *
     * @param  integer                  $at Offset to directive
     * @param  tempcode                 $set New embedment
     */
    public function set_directive_embedment($at, $set)
    {
        $this->bits[$at][3][count($this->bits[$at][3]) - 1] = $set;
    }

    /**
     * Find the identifier component of a variable within this Tempcode.
     *
     * @param  integer                  $at Offset to the variable
     * @return string                   Variable component
     */
    public function extract_identifier_at($at)
    {
        return $this->bits[$at][2];
    }

    /**
     * Attach the specified tempcode to the right of the current tempcode object.
     *
     * @param  mixed                    $attach The tempcode/string to attach
     * @param  ?array                   $escape Extra escaping (null: none)
     * @param  boolean                  $avoid_children_merge If we've already merged the children from what we're attaching into the child tree (at bind stage)
     */
    public function attach($attach, $escape = null, $avoid_children_merge = false)
    {
        if ($attach === '') {
            return;
        }

        $last = count($this->bits) - 1;

        if (is_object($attach)) { // Consider it another piece of tempcode
            if (is_null($escape)) {
                $escape = array();
            }
            $simple_escaped = array(ENTITY_ESCAPED);

            foreach ($attach->bits as $bit) {
                if ($escape != array()) {
                    $bit[0] = array_merge($escape, $bit[0]);
                }

                // Can we add into another string at our edge
                if (($last == -1) || ($bit[1] != TC_KNOWN) || ($this->bits[$last][1] != TC_KNOWN) || (($this->bits[$last][0] != $bit[0]) && (((array_merge($bit[0], $this->bits[$last][0])) != $simple_escaped) || (preg_match('#[&<>"\']#', $bit[2]) != 0)))) { // No
                    $this->bits[] = $bit;
                    $last++;
                } else { // Yes
                    $this->bits[$last][2] .= $bit[2];
                }
            }
        } else { // Consider it a string
            // Can we add into another string at our edge
            if (is_null($escape)) {
                $escape = array();
            }
            if (($last == -1) || ($this->bits[$last][1] != TC_KNOWN) || (($this->bits[$last][0] != $escape) && (((array_merge($escape, $this->bits[$last][0])) != array(ENTITY_ESCAPED)) || (preg_match('#[&<>:��"\']#', $attach) != 0)))) { // No
                $this->bits[] = array($escape, TC_KNOWN, $attach, null);
            } else { // Yes
                $this->bits[$last][2] .= $attach;
            }
        }
    }

    /**
     * Find whether the current tempcode object is blank or not.
     *
     * @return boolean                  Whether the tempcode object is empty
     */
    public function is_empty()
    {
        return count($this->bits) == 0;
    }

    /**
     * Parses the current tempcode object, then return the parsed string
     *
     * @param  ?LANGUAGE_NAME           $lang The language to evaluate with (null: current user's language)
     * @param  mixed                    $_escape Whether to escape the tempcode object (children may be recursively escaped regardless if those children/parents are marked to be)
     * @return string                   The evaluated thing. Voila, it's all over!
     */
    public function evaluate($lang = null, $_escape = false)
    {
        $empty_array = array();
        $out = '';

        foreach ($this->bits as $bit) {
            $bit_0 = $bit[0];
            if ($_escape !== false) {
                array_unshift($bit_0, $_escape);
            }

            if ($bit[1] == TC_KNOWN) { // Just pick up the string
                apply_tempcode_escaping($bit_0, $bit[2]);
                $out .= $bit[2];
            } else {
                $bit_3 = $bit[3];
                if (($bit_3) && ($bit[1] != TC_DIRECTIVE)) {
                    foreach ($bit_3 as $i => $decode_bit) {
                        if (is_object($decode_bit)) {
                            $bit_3[$i] = $decode_bit->evaluate($lang);
                        }
                    }
                }

                $out .= ecv($lang, $bit_0, $bit[1], $bit[2], is_null($bit_3) ? array() : $bit_3);
            }
        }

        return $out;
    }
}

/**
 * A template has not been structurally cached, so compile it and store in the cache.
 *
 * @param  ID_TEXT                      $theme The theme the template is in the context of
 * @param  PATH                         $path The path to the template file
 * @param  ID_TEXT                      $codename The codename of the template (e.g. foo)
 * @param  ID_TEXT                      $_codename The actual codename to use for the template (e.g. thin_foo)
 * @param  LANGUAGE_NAME                $lang The language the template is in the context of
 * @param  string                       $suffix File type suffix of template file
 * @param  ?ID_TEXT                     $theme_orig The theme to cache in (null: main theme)
 * @return tempcode                     The compiled tempcode
 */
function _do_template($theme, $path, $codename, $_codename, $lang, $suffix, $theme_orig = null)
{
    if (is_null($theme_orig)) {
        $theme_orig = $theme;
    }

    $base_dir = ((($theme == 'default') && (($suffix != '.css') || (strpos($path, '/css_custom') === false))) ? get_file_base() : get_custom_file_base()) . '/themes/';

    global $CACHE_TEMPLATES, $FILE_ARRAY, $IS_TEMPLATE_PREVIEW_OP_CACHE, $PERSISTENT_CACHE;

    if (isset($FILE_ARRAY)) {
        $html = unixify_line_format(file_array_get('themes/' . $theme . $path . $codename . $suffix));
    } else {
        $_path = $base_dir . filter_naughty($theme . $path . $codename) . $suffix;
        $tmp = fopen($_path, 'rb');
        @flock($tmp, LOCK_SH);
        $html = unixify_line_format(file_get_contents($_path));
        @flock($tmp, LOCK_UN);
        fclose($tmp);
    }

    if (strpos($html, '/*{$,Parser hint: pure}*/') !== false) {
        return make_string_tempcode(preg_replace('#\{\$,.*\}#U', '/*no minify*/', $html));
    }

    if (($GLOBALS['SEMI_DEV_MODE']) && (strpos($html, '.innerHTML') !== false) && (strpos($html, 'Parser hint: .innerHTML okay') === false)) {
        require_code('site');
        attach_message('Do not use the .innerHTML property in your JavaScript because it will not work in true XHTML (when the browsers real XML parser is in action). Use ocPortal\'s global set_inner_html/get_inner_html functions.', 'warn');
    }

    // Strip off trailing final lines from single lines templates. Editors often put these in, and it causes annoying "visible space" issues
    if ((substr($html, -1, 1) == "\n") && (substr_count($html, "\n") == 1)) {
        $html = substr($html, 0, strlen($html) - 1);
    }

    if ($IS_TEMPLATE_PREVIEW_OP_CACHE) {
        $test = post_param($codename, null);
        if (!is_null($test)) {
            $html = post_param($test . '_new');
        }
    }

    $result = template_to_tempcode($html, 0, false, $codename, $theme, $lang);
    if (($CACHE_TEMPLATES) && (($suffix == '.tpl') || ($codename == 'no_cache'))) {
        if (!is_null($PERSISTENT_CACHE)) {
            persistent_cache_set(array('TEMPLATE', $theme, $lang, $_codename), $result->to_assembly(), strpos($path, 'default/templates/') !== false);
        } else {
            $path2 = get_custom_file_base() . '/themes/' . $theme_orig . '/templates_cached/' . filter_naughty($lang);
            $_path2 = $path2 . '/' . filter_naughty($_codename) . $suffix . '.tcd';
            $myfile = @fopen($_path2, GOOGLE_APPENGINE ? 'wb' : 'ab');
            if ($myfile === false) {
                if (@mkdir($path2, 0777, true)) {
                    require_code('files');
                    fix_permissions($path2, 0777);
                } else {
                    if (file_exists($path2 . '/' . filter_naughty($_codename) . $suffix . '.tcd')) {
                        warn_exit(do_lang_tempcode('WRITE_ERROR', $path2 . '/' . filter_naughty($_codename) . $suffix . '.tcd'));
                    } else {
                        warn_exit(do_lang_tempcode('WRITE_ERROR_CREATE', $path2 . '/' . filter_naughty($_codename) . $suffix . '.tcd'));
                    }
                }

                $myfile = @fopen($_path2, GOOGLE_APPENGINE ? 'wb' : 'ab');
            }

            @flock($myfile, LOCK_EX);
            if (!GOOGLE_APPENGINE) {
                ftruncate($myfile, 0);
            }
            fwrite($myfile, $result->to_assembly($lang));
            @flock($myfile, LOCK_UN);
            fclose($myfile);
            fix_permissions($path2 . '/' . filter_naughty($_codename) . $suffix . '.tcd');
        }
    }

    return $result;
}

/**
 * Convert template text into tempcode format.
 *
 * @param  string                       $text The template text
 * @param  integer                      $symbol_pos The position we are looking at in the text
 * @param  boolean                      $inside_directive Whether this text is infact a directive, about to be put in the context of a wider template
 * @param  ID_TEXT                      $codename The codename of the template (e.g. foo)
 * @param  ?ID_TEXT                     $theme The theme it is for (null: current theme)
 * @param  ?ID_TEXT                     $lang The language it is for (null: current language)
 * @param  boolean                      $tolerate_errors Whether to tolerate errors
 * @return mixed                        The converted/compiled template as tempcode, OR if a directive, encoded directive information
 */
function template_to_tempcode($text, $symbol_pos = 0, $inside_directive = false, $codename = '', $theme = null, $lang = null, $tolerate_errors = false)
{
    return template_to_tempcode_static($text, $symbol_pos, $inside_directive, $codename, $theme, $lang);
}
