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
 * @package		unit_testing
 */

/**
 * ocPortal test case class (unit testing).
 */
class flagrant_test_set extends ocp_test_case
{
	var $flag_id;

	function setUp()
	{
		parent::setUp();

		require_code('flagrant');

		$this->flag_id=add_flagrant('test',3,'Welcome to ocPortal',1);

		$this->assertTrue('Welcome to ocPortal'==$GLOBALS['SITE_DB']->query_value('text','notes',array('id'=>$this->flag_id)));
	}

	function testEditFlagrant()
	{
		edit_flagrant($this->flag_id,'Tested','Thank you',0);

		$this->assertTrue('Thank you'==$GLOBALS['SITE_DB']->query_value('text','notes',array('id'=>$this->flag_id)));
	}

	function tearDown()
	{
		delete_flagrant($this->flag_id);
		parent::tearDown();
	}
}
