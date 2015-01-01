<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2015

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    oc_simple_sums
 */

/**
 * Hook class.
 */
class Hook_addon_registry_oc_simple_sums
{
    /**
     * Get a list of file permissions to set
     *
     * @return array                    File permissions to set
     */
    public function get_chmod_array()
    {
        return array();
    }

    /**
     * Get the version of ocPortal this addon is for
     *
     * @return float                    Version number
     */
    public function get_version()
    {
        return ocp_version_number();
    }

    /**
     * Get the addon category
     *
     * @return string                   The category
     */
    public function get_category()
    {
        return 'New Features';
    }

    /**
     * Get the addon author
     *
     * @return string                   The author
     */
    public function get_author()
    {
        return 'Chris Graham';
    }

    /**
     * Find other authors
     *
     * @return array                    A list of co-authors that should be attributed
     */
    public function get_copyright_attribution()
    {
        return array();
    }

    /**
     * Get the addon licence (one-line summary only)
     *
     * @return string                   The licence
     */
    public function get_licence()
    {
        return 'Licensed on the same terms as ocPortal';
    }

    /**
     * Get the description of the addon
     *
     * @return string                   Description of the addon
     */
    public function get_description()
    {
        return 'A little calculator block that you could use to allow users to work out for example: how much money they might make. To include it use something like this Comcode on a page:
[code=\"Comcode\"][block message=\"You could be earning as much as $xxx per year after your first year\" equation=\"Math.pow((this.totalPerSale*this.numAverageSales)*this.numPerLevel,(1+this.levelsAchieved*(this.fractionPerLevel/100)))\" totalPerSale=\"Commission per sale in $\" numAverageSales=\"Number of sales per reseller per year\" numPerLevel=\"Number of partners per reseller per year\" levelsAchieved=\"The number of partner levels in a year\" fractionPerLevel=\"Relative partner commission per sale in %\"]main_calculator[/block][/code]

This is coded as a \"mini block\", and serves as a good example of how you can use PHP on a page. We have coded it into [tt]sources_custom/miniblocks/main_calculator.php[/tt]';
    }

    /**
     * Get a list of tutorials that apply to this addon
     *
     * @return array                    List of tutorials
     */
    public function get_applicable_tutorials()
    {
        return array();
    }

    /**
     * Get a mapping of dependency types
     *
     * @return array                    File permissions to set
     */
    public function get_dependencies()
    {
        return array(
            'requires' => array(),
            'recommends' => array(),
            'conflicts_with' => array()
        );
    }

    /**
     * Get a list of files that belong to this addon
     *
     * @return array                    List of files
     */
    public function get_file_list()
    {
        return array(
            'sources_custom/hooks/systems/addon_registry/oc_simple_sums.php',
            'sources_custom/miniblocks/main_calculator.php',
        );
    }
}
