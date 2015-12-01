<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		unit_testing
 */

/**
 * ocPortal test case class (unit testing).
 */
class input_filter_post_block_test_set extends ocp_test_case
{
	function testQuickInstaller()
	{
		// Make sure #1817 cannot happen again (POST checks for non-POST situations)

		$_SERVER['HTTP_REFERER']='http://evil.com/';
		post_param('test','default');
		// Should not die
	}
}
