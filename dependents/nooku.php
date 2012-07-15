<?php
/**
 * @category	JDependent
 * @package		Plugins
 * @subpackage	Nooku
 * @copyright	Copyright (C) 2011 NinjaForge. All rights reserved.
 * @license 	GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link     	http://ninjaforge.com
 */
defined('_JEXEC') or die;

require_once dirname(__FILE__).'/plugin.php';

/**
 * Dependency Plugin Class for the nooku framework
 */
class JDependentPluginNooku extends JDependentPlugin
{
	/**
	 * The Plugin Name, which will be use when unzipping the dependent 
	 */
	protected $_name = 'nooku';

	/**
	 * Method for installing the dependent
	 *
	 * @param 	array 	an array of dependents to exclude eg: array('extensions', 'folders', 'files')
	 * @return	JDependentPlugin
	 */
	public function install($exclude = array())
	{
		$items = array(
			'extensions' 	=> array(
								array('name' => 'plg_koowa', 'from' => '/plugins/system'),
							),
			'folders'		=> array(
								array('from' => '/libraries/koowa'),
								array('from' => '/plugins/koowa'),
								array('from' => '/media/com_default'),
								array('from' => '/media/lib_koowa'),
								array('from' => '/site/components/com_default', 'to' => '/components/com_default'),
								array('from' => '/site/modules/mod_default', 'to' => '/modules/mod_default'),
								array('from' => '/administrator/components/com_default'),
								array('from' => '/administrator/modules/mod_default')
							)
		);

		$this->_dependents = $items;

		return parent::install($exclude);
	}
}