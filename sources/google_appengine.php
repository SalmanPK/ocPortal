<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/*EXTRA FUNCTIONS: stream_context_set_default*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    google_appengine
 */

/**
 * Standard code module initialisation function.
 */
function init__google_appengine()
{
    $uri = preg_replace('#\?.*#','',$_SERVER['REQUEST_URI']);
    if (substr($uri,0,1) == '/') {
        $uri = substr($uri,1);
    }
    $matches = array();

    // RULES START
    if (preg_match('#^([^=]*)pages/(modules|modules\_custom)/([^/]*)\.php$#',$uri,$matches) != 0) {
        _roll_gae_redirect($matches,'$1index.php\?page=$3');
        return NULL;
    }
    if (preg_match('#^([^=]*)pg/s/([^\&\?]*)/index\.php$#',$uri,$matches) != 0) {
        _roll_gae_redirect($matches,'$1index.php\?page=wiki&id=$2');
        return NULL;
    }
    if (preg_match('#^([^=]*)pg/([^/\&\?]*)/([^/\&\?]*)/([^\&\?]*)/index\.php(.*)$#',$uri,$matches) != 0) {
        _roll_gae_redirect($matches,'$1index.php\?page=$2&type=$3&id=$4$5');
        return NULL;
    }
    if (preg_match('#^([^=]*)pg/([^/\&\?]*)/([^/\&\?]*)/index\.php(.*)$#',$uri,$matches) != 0) {
        _roll_gae_redirect($matches,'$1index.php\?page=$2&type=$3$4');
        return NULL;
    }
    if (preg_match('#^([^=]*)pg/([^/\&\?]*)/index\.php(.*)$#',$uri,$matches) != 0) {
        _roll_gae_redirect($matches,'$1index.php\?page=$2$3');
        return NULL;
    }
    if (preg_match('#^([^=]*)pg/index\.php(.*)$#',$uri,$matches) != 0) {
        _roll_gae_redirect($matches,'$1index.php\?page=$3');
        return NULL;
    }
    if (preg_match('#^([^=]*)pg/s/([^\&\?]*)$#',$uri,$matches) != 0) {
        _roll_gae_redirect($matches,'$1index.php\?page=wiki&id=$2');
        return NULL;
    }
    if (preg_match('#^([^=]*)pg/([^/\&\?]*)/([^/\&\?]*)/([^\&\?]*)/$#',$uri,$matches) != 0) {
        _roll_gae_redirect($matches,'$1index.php\?page=$2&type=$3&id=$4');
        return NULL;
    }
    if (preg_match('#^([^=]*)pg/([^/\&\?]*)/([^/\&\?]*)/([^\&\?]*)$#',$uri,$matches) != 0) {
        _roll_gae_redirect($matches,'$1index.php\?page=$2&type=$3&id=$4');
        return NULL;
    }
    if (preg_match('#^([^=]*)pg/([^/\&\?]*)/([^/\&\?]*)$#',$uri,$matches) != 0) {
        _roll_gae_redirect($matches,'$1index.php\?page=$2&type=$3');
        return NULL;
    }
    if (preg_match('#^([^=]*)pg/([^/\&\?]*)$#',$uri,$matches) != 0) {
        _roll_gae_redirect($matches,'$1index.php\?page=$2');
        return NULL;
    }
    if (preg_match('#^([^=]*)pg/s/([^\&\?\.]*)&(.*)$#',$uri,$matches) != 0) {
        _roll_gae_redirect($matches,'$1index.php\?$3&page=wiki&id=$2');
        return NULL;
    }
    if (preg_match('#^([^=]*)pg/([^/\&\?\.]*)/([^/\&\?\.]*)/([^/\&\?\.]*)&(.*)$#',$uri,$matches) != 0) {
        _roll_gae_redirect($matches,'$1index.php\?$5&page=$2&type=$3&id=$4');
        return NULL;
    }
    if (preg_match('#^([^=]*)pg/([^/\&\?\.]*)/([^/\&\?\.]*)&(.*)$#',$uri,$matches) != 0) {
        _roll_gae_redirect($matches,'$1index.php\?$4&page=$2&type=$3');
        return NULL;
    }
    if (preg_match('#^([^=]*)pg/([^/\&\?\.]*)&(.*)$#',$uri,$matches) != 0) {
        _roll_gae_redirect($matches,'$1index.php\?$3&page=$2');
        return NULL;
    }
    if (preg_match('#^(site|forum|adminzone|cms|collaboration|docs)/s/([^\&\?]*)\.htm$#',$uri,$matches) != 0) {
        _roll_gae_redirect($matches,'$1/index.php\?page=wiki&id=$2');
        return NULL;
    }
    if (preg_match('#^s/([^\&\?]*)\.htm$#',$uri,$matches) != 0) {
        _roll_gae_redirect($matches,'index\.php\?page=wiki&id=$1');
        return NULL;
    }
    if (preg_match('#^(site|forum|adminzone|cms|collaboration|docs)/([^/\&\?]+)/([^/\&\?]*)/([^\&\?]*)\.htm$#',$uri,$matches) != 0) {
        _roll_gae_redirect($matches,'$1/index.php\?page=$2&type=$3&id=$4');
        return NULL;
    }
    if (preg_match('#^(site|forum|adminzone|cms|collaboration|docs)/([^/\&\?]+)/([^/\&\?]*)\.htm$#',$uri,$matches) != 0) {
        _roll_gae_redirect($matches,'$1/index.php\?page=$2&type=$3');
        return NULL;
    }
    if (preg_match('#^(site|forum|adminzone|cms|collaboration|docs)/([^/\&\?]+)\.htm$#',$uri,$matches) != 0) {
        _roll_gae_redirect($matches,'$1/index.php\?page=$2');
        return NULL;
    }
    if (preg_match('#^([^/\&\?]+)/([^/\&\?]*)/([^\&\?]*)\.htm$#',$uri,$matches) != 0) {
        _roll_gae_redirect($matches,'index.php\?page=$1&type=$2&id=$3');
        return NULL;
    }
    if (preg_match('#^([^/\&\?]+)/([^/\&\?]*)\.htm$#',$uri,$matches) != 0) {
        _roll_gae_redirect($matches,'index.php\?page=$1&type=$2');
        return NULL;
    }
    if (preg_match('#^([^/\&\?]+)\.htm$#',$uri,$matches) != 0) {
        _roll_gae_redirect($matches,'index.php\?page=$1');
        return NULL;
    }

    //if (preg_match('#^(site|forum|adminzone|cms|collaboration|docs)/s/([^\&\?]*)$#',$uri,$matches)!=0)
    //{
    //   _roll_gae_redirect($matches,'$1/index.php\?page=wiki&id=$2');
    //   return NULL;
    //}
    //if (preg_match('#^s/([^\&\?]*)$#',$uri,$matches)!=0)
    //{
    //   _roll_gae_redirect($matches,'index\.php\?page=wiki&id=$1');
    //   return NULL;
    //}
    //if (preg_match('#^(site|forum|adminzone|cms|collaboration|docs)/([^/\&\?]+)/([^/\&\?]*)/([^\&\?]*)$#',$uri,$matches)!=0)
    //{
    //   _roll_gae_redirect($matches,'$1/index.php\?page=$2&type=$3&id=$4');
    //   return NULL;
    //}
    //if (preg_match('#^(site|forum|adminzone|cms|collaboration|docs)/([^/\&\?]+)/([^/\&\?]*)$#',$uri,$matches)!=0)
    //{
    //   _roll_gae_redirect($matches,'$1/index.php\?page=$2&type=$3');
    //   return NULL;
    //}
    //if (preg_match('#^(site|forum|adminzone|cms|collaboration|docs)/([^/\&\?]+)$#',$uri,$matches)!=0)
    //{
    //   _roll_gae_redirect($matches,'$1/index.php\?page=$2');
    //   return NULL;
    //}
    //if (preg_match('#^([^/\&\?]+)/([^/\&\?]*)/([^\&\?]*)$#',$uri,$matches)!=0)
    //{
    //   _roll_gae_redirect($matches,'index.php\?page=$1&type=$2&id=$3');
    //   return NULL;
    //}
    //if (preg_match('#^([^/\&\?]+)/([^/\&\?]*)$#',$uri,$matches)!=0)
    //{
    //   _roll_gae_redirect($matches,'index.php\?page=$1&type=$2');
    //   return NULL;
    //}
    //if (preg_match('#^([^/\&\?]+)$#',$uri,$matches)!=0)
    //{
    //   _roll_gae_redirect($matches,'index.php\?page=$1');
    //   return NULL;
    //}
    // RULES END

    /*if (isset($_GET['gae_stop'])) Useful for debugging crashes on live Google App Engine
    {
        declare(ticks=1);
        register_tick_function('gae_debugger');
    }*/
}

/**
 * Find whether the current user is an admin, from the perspective of the Google Console.
 *
 * @param  array                        URL segments matched
 * @param  string                       Redirect pattern
 */
function _roll_gae_redirect($matches,$to)
{
    $to = str_replace('\\?','?',$to);
    foreach ($matches as $i => $match) {
        $to = str_replace('$' . strval($i),urlencode($match),$to);
    }

    $qs = preg_replace('#^[^\?]*(\?|$)#','',$to);
    $_SERVER['QUERY_STRING'] = $qs;
    if ($qs != '') {
        if (strpos($_SERVER['REQUEST_URI'],'?') === false) {
            $_SERVER['REQUEST_URI'] .= '?' . $qs;
        } else {
            $_SERVER['REQUEST_URI'] .= '&' . $qs;
        }
    }
    $arr = array();
    parse_str($qs,$arr);
    $_GET += $arr;
}

/**
 * Find whether the current user is an admin, from the perspective of the Google Console.
 *
 * @return boolean                      Current user is admin
 */
function gae_is_admin()
{
    require_once('google/appengine/api/users/User.php');
    require_once('google/appengine/api/users/UserService.php');

    $_userservice = 'google\appengine\api\users\UserService';
    $userservice = new $_userservice();

    $user = $userservice->getCurrentUser();

    if ($user !== NULL) {
        return $userservice->isCurrentUserAdmin();
    }
    return false;
}

/**
 * Tick function for stepping through GAE code, using ?gae_stop=<code-pos>.
 */
function gae_debugger()
{
    static $i = 0;
    $i++;
    static $stop = -1;
    if ($stop === -1) {
        if ((!isset($_GET['gae_stop'])) || (!gae_is_admin())) {
            $_GET['gae_stop'] = null;
            return;
        }
        $stop = intval($_GET['gae_stop']);
    }

    if ($i === $stop) {
        debug_print_backtrace();
        exit();
    }
}

/**
 * Enable/Disable GAE optimistic cache, meaning it avoids need to check the Cloud Storage if a file is updated.
 * We only set this to enabled if we are sure the persistent cache would receive a flush if the referenced file changed state.
 *
 * @param  boolean                      Whether the cache is enabled
 */
function gae_optimistic_cache($enabled)
{
    static $gs_options_enabled = array('gs' => array(
        'enable_optimistic_cache' => true,
        'read_cache_expiry_seconds' => 180,
    ));
    static $gs_options_disabled = array('gs' => array(
        'enable_optimistic_cache' => false,
        'read_cache_expiry_seconds' => 3600,
    ));
    stream_context_set_default($enabled?$gs_options_enabled:$gs_options_disabled);
}
