<?php
/**
 * @package		JDependent
 * @subpackage	Nooku
 * @copyright	Copyright (C) 2012 NinjaForge. All rights reserved.
 * @license 	GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link     	http://ninjaforge.com
 */
defined('_JEXEC') or die;

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
	 * Method for setting up dependent files/folders and extensions
	 */
	public function getDependents()
	{
		$dependents = array(
			'extensions' 	=> array(
								array('type' => 'plugin', 'name' => 'koowa', 'from' => '/plugins/system'),
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

		return $dependents;
	}

	/**
	 * Enviroment Checks for the Ninja Framework
	 */
	public function beforeInstall()
	{
		$user		= JFactory::getUser();
		$db			= JFactory::getDBO();
		$extension	= $this->getExtensionName();

		if(!version_compare($db->getVersion(), '5.0.41', '>=')) {
			$message	= JText::_('LIB_JDEPENDENT_MYSQL_SERVER_INCOMPATIBILITY');
			$message	= sprintf($message, $extension, $db->getVersion());
			JError::raiseWarning(500, $message);
			return false;
		}
		if(!version_compare(phpversion(), '5.2', '>=')) {
			$message	= JText::_('LIB_JDEPENDENT_PHP_INCOMPATIBILITY');
			$message	= sprintf($message, $extension, phpversion());
			$condition	= $user->authorize( 'com_config', 'manage' );
			JError::raiseWarning(500, $message);
			return false;
		}
		if(!class_exists('mysqli')) {
			$message	= JText::_('LIB_JDEPENDENT_MYSQLI_MISSING');
			$message	= sprintf($message, $extension);
			JError::raiseWarning(500, $message);
			return false;
		}

		return true;
	}

	/**
	 * Make the changes needed to get com_ninja running
	 */
	public function afterInstall()
	{
		$db = JFactory::getDBO();

		if(JFactory::getApplication()->getCfg('dbtype') != 'mysqli')
		{
			$conf = JFactory::getConfig();
			$path = JPATH_CONFIGURATION.DS.'configuration.php';
			if(JFile::exists($path)) {
				JPath::setPermissions($path, '0644');
				$search  = JFile::read($path);
				$replace = str_replace('var $dbtype = \'mysql\';', 'var $dbtype = \'mysqli\';', $search);
				JFile::write($path, $replace);
				JPath::setPermissions($path, '0444');
			}
		}

		// Disable the com_ninja admin menu, and make sure plg_ninja is enabled and set to ordering of 1 (koowa is ordering 0 by default)
		if (version_compare(JVERSION,'1.6.0','ge')) {
			$db->setQuery("UPDATE `#__extensions` SET `enabled` = '1', `ordering` = '0' WHERE type = 'plugin' AND element = 'koowa';");
			$db->query();
		} else {
			$db->setQuery("UPDATE `#__plugins` SET `published` = '1', `ordering` = '0' WHERE folder = 'system' AND element = 'koowa';");
			$db->query();
		}
	}
}