<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2015

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    testing_platform
 */

/**
 * ocPortal test case class (unit testing).
 */
class moderation_test_set extends ocp_test_case
{
    public $mod_id;

    public function setUp()
    {
        parent::setUp();
        require_code('ocf_moderation_action');
        require_code('ocf_moderation_action2');

        $this->mod_id = ocf_make_multi_moderation('Test Moderation', 'Test', null, 0, 0, 0, '*', 'Nothing');

        // Test the forum was actually created
        $this->assertTrue('Test Moderation' == get_translated_text($GLOBALS['FORUM_DB']->query_select_value('f_multi_moderations', 'mm_name', array('id' => $this->mod_id)), $GLOBALS['FORUM_DB']));
    }

    public function testEditModeration()
    {
        // Test the forum edits
        ocf_edit_multi_moderation($this->mod_id, 'Tested', 'Something', null, 0, 0, 0, '*', 'Hello');

        // Test the forum was actually created
        $this->assertTrue('Tested' == get_translated_text($GLOBALS['FORUM_DB']->query_select_value('f_multi_moderations', 'mm_name', array('id' => $this->mod_id)), $GLOBALS['FORUM_DB']));
    }


    public function tearDown()
    {
        ocf_delete_multi_moderation($this->mod_id);
        parent::tearDown();
    }
}
