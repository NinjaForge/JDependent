<?php
/**
 * @package		JDependent
 * @subpackage	Ninja
 * @copyright	Copyright (C) 2012 NinjaForge. All rights reserved.
 * @license 	GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link     	http://ninjaforge.com
 */
defined('_JEXEC') or die;

/**
 * Dependency Plugin Class for the nooku framework
 */
class JDependentPluginNinja extends JDependentPlugin
{
	/**
	 * The Plugin Name, which will be use when unzipping the dependent 
	 */
	protected $_name = 'ninja';

	/**
	 * Enviroment Checks for the Ninja Framework
	 */
	public function beforeInstall()
	{
		$user		= JFactory::getUser();
		$db			= JFactory::getDBO();
		$condition	= $user->authorize( 'com_config', 'manage' );
		$default 	= JText::_('LIB_JDEPENDENT_WHOOPS_SOMETHING_WENT_TERRIBLY_WRONG');
		$extension	= $this->getExtensionName();

		if(!version_compare('2', '5.0.41', '>=')) {
			JError::raiseWarning(500, $default);
			$message	= JText::_('LIB_JDEPENDENT_MYSQL_SERVER_INCOMPATIBILITY');
			$message	= sprintf($message, $extension, $db->getVersion());
			JError::raiseWarning(500, $condition ? $message : $default);
		}
		if(!version_compare(phpversion(), '5.2', '>=')) {
			$message	= JText::_('LIB_JDEPENDENT_PHP_INCOMPATIBILITY');
			$message	= sprintf($message, $extension, phpversion());
			$condition	= $user->authorize( 'com_config', 'manage' );
			JError::raiseWarning(500, $condition ? $message : $default);
		}
		if(!class_exists('mysqli')) {
			$message	= JText::_('LIB_JDEPENDENT_MYSQLI_MISSING');
			$message	= sprintf($message, $extension);
			JError::raiseWarning(500, $condition ? $message : $default);
		}

		// remove this as its possible it will still work?
		/*if(version_compare('5.3', phpversion(), '<=') && extension_loaded('ionCube Loader')) {

			if(ioncube_loader_iversion() < 40002) {
				$message	= JText::_('Your server is affected by a bug in ionCube Loader for PHP 5.3 that causes our template layout parsing to fail. Please update to a version later than ionCube Loader 4.0 (your server is %s) before using %s.');
				$message	= sprintf($message, ioncube_loader_version(), $extension);
				$condition	= $user->authorize( 'com_config', 'manage' );
				//Don't return this one, in case the site still works with ionCube loader present
				$notify($condition, $message);
			}
		}*/
	}

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
								array('name' => 'plg_ninja', 'from' => '/plugins/system'),
								array('name' => 'com_ninja', 'from' => '/')
							)
		);

		$this->_dependents = $items;

		return parent::install($exclude);
	}

	/**
	 * Make the changes needed to get com_ninja running
	 */
	public function afterInstall()
	{
		$db = JFactory::getDBO();

		// Disable the com_ninja admin menu, and make sure plg_ninja is enabled and set to ordering of 1 (koowa is ordering 0 by default)
		if (version_compare(JVERSION,'1.6.0','ge')) {
			$db->setQuery("UPDATE `#__extensions` SET `enabled` = '0' WHERE type = 'component' AND element = 'com_ninja';");
			$db->query();
			$db->setQuery("UPDATE `#__extensions` SET `enabled` = '1', `ordering` = '1' WHERE type = 'plugin' AND element = 'ninja';");
			$db->query();
		} else {
			$db->setQuery("UPDATE `#__components` SET `enabled` = '0' WHERE option = 'com_ninja';");
			$db->query();
			$db->setQuery("UPDATE `#__plugins` SET `enabled` = '1', `ordering` = '1' WHERE folder = 'system' AND element = 'ninja';");
			$db->query();
		}
	}
}