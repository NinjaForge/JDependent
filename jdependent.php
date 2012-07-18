<?php
/**
 * @package		JDependent
 * @copyright	Copyright (C) 2012 NinjaForge. All rights reserved.
 * @license 	GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link     	http://ninjaforge.com
 */
defined('_JEXEC') or die;

/**
 * Dependency Class for Joomla 3rd Party Extensions
 */
class JDependent
{
	/**
     * Boolean value to determine if we created the table
     *
     * @var bool
     */
    protected $_table;

    /**
     * the path to the dependent files
     */
    public $config;

    /**
     * The JInstaller Instace
     */
    public $installer;

    /**
	 * Class constructor.
	 *
	 * @param   array  $config  a configuration array
	 */
	public function __construct($config = array())
	{
		$this->config 		= $config;
		$this->installer 	= $this->getInstaller();

		if (!isset($config['dependents_path']))
			$this->config['dependents_path'] = $this->installer->getPath('source').'/dependents/';


		$lang =& JFactory::getLanguage();
		$lang->load('lib_jdependent', dirname(__FILE__), 'en-GB');
		$lang->load('lib_jdependent', dirname(__FILE__), $lang->getDefault(), true);

		$this->createTable();
	}


	/**
	 * Method for installing a requisite
	 *
	 * @param  string 	the identifiying class name
	 * @param  array 	an array of files/folders/extensions to exclude
	 *
	 * @return JDependent
	 */
	public function install($identifier, $excludes = array())
	{
		$requisite = $this->_instantiate($identifier);

		$requisite->install($excludes);

		return $this;
	}

	/**
	 * Method for uninstalling a requisite
	 *
	 * @param  string 	the identifiying class name
	 * @param  array 	an array of files/folders/extensions to exclude
	 *
	 * @return JDependent
	 */
	public function uninstall($identifier, $excludes = array())
	{
		$requisite = $this->_instantiate($identifier);

		$requisite->uninstall($excludes);

		return $this;
	}

	/**
	 * Method for adding the joomla_dependents table if it does not exist
	 *
	 * @return void
	 */
	public function createTable()
	{
		// Attempt to create the table if we have not already tried
		if (!$this->_table) {
			$db = JFactory::getDBO();

			// @todo move this to an sql file and write a parser, just in case we want to add more tables later
			$query = 
				'CREATE TABLE IF NOT EXISTS `#__joomla_dependents` ('.
					'id bigint(20) unsigned NOT NULL auto_increment,'.
					'dependent VARCHAR( 255 ) NOT NULL,'.
					'requisite VARCHAR( 255 ) NOT NULL,'.
					'PRIMARY KEY  (`id`)'.
				') ENGINE=MyISAM DEFAULT CHARSET=utf8;';

			$db->setQuery($query);

			if (!$db->query())
				JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $db->stderr(true)));
			else
				$this->_table = true;
		}
	}

	/**
	 * Method for registering a dependency in the database
	 *
	 * @param  string 	the dependent extension name eg: com_ninjaboard
	 * @param  string 	the required extension/library/framework eg: nooku
	 *
	 * @return JDependent
	 */
	public function registerDependency($dependent, $requisite)
	{
		$db = JFactory::getDBO();

		$query = "SELECT id FROM `#__joomla_dependents` WHERE `dependent` = '".$dependent."' AND `requisite` = '".$requisite."';";

		$db->setQuery($query);

		//only register this item if it has not been already
		if (!$db->loadResult()) {
			$query = "INSERT INTO `#__joomla_dependents` (`id` ,`dependent` ,`requisite`) VALUES (NULL,  '".$dependent."',  '".$requisite."');";
			$db->setQuery($query);

			if (!$db->query())
				JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $db->stderr(true)));
		}

		return $this;
	}

	/**
	 * Method for deregistering a dependency in the database
	 *
	 * @param  string 	the dependent extension name eg: com_ninjaboard
	 * @param  string 	the required extension/library/framework eg: nooku
	 *
	 * @return JDependent
	 */
	public function deregisterDependency($dependent, $requisite)
	{
		$db 	= JFactory::getDBO();
		$query 	= "DELETE FROM `#__joomla_dependents` WHERE `dependent` = '".$dependent."' AND  `requisite` = '".$requisite."';";
		$db->setQuery($query);

		if (!$db->query()) {
			JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $db->stderr(true)));
		}

		return $this;
	}

	/**
	 * Method for checking to see if there are other items that require this dependency
	 *
	 * @param  string 	the dependent extension name eg: com_ninjaboard
	 * @param  string 	the required extension/library/framework eg: nooku
	 * @return boolean	true if we have other dependent extensions else its false
	 */
	public function checkDependencyRegistry($dependent, $requisite)
	{
		$db 	= JFactory::getDBO();
		$query 	= "SELECT COUNT(*) FROM `#__joomla_dependents` WHERE `dependent` != '".$dependent."' AND `requisite` = '".$requisite."';";
		$db->setQuery($query);

		$result = $db->loadResult();
		// simply if there are entries in the db then there must be other extensions using this dependent
		if ($result) return true;

		return false;
	}

	/**
     * Get an instance of a class based on a class identifier
     *
     * @param   string  identifier
     *
     * @return  object  Return object on success, throws exception on failure
     *
     * @throws  JException
     */
	protected function _instantiate($identifier = null)
	{
		include_once dirname(__FILE__).'/dependents/plugin.php';
		
		$path = dirname(__FILE__).'/dependents/'.$identifier.'.php';
		if (file_exists($path))
			include_once $path;

		$result = false;

		// determine wether we passed the full class string
		if (!strstr($identifier, 'JDependentPlugin'))
			$identifier = 'JDependentPlugin'.ucfirst($identifier);

		// make sure the class exists
		if (class_exists($identifier))
		{
			$result = new $identifier($this->config);
		}

		// throw an exception if it doesnt
		if(!is_object($result)) {
			throw new JException('Cannot instantiate object from identifier : '.$identifier);
		}

		return $result;

	}

	/**
	 * Methods for getting the Jinstaller Instance
	 *
	 * @return JInstaller The JInstaller instance
	 */
	public function getInstaller() {
	    if(!isset($this->installer)) {
	    	$this->installer = JInstaller::getInstance();
	    }
	    return $this->installer;
	}
}