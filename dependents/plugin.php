<?php
/**
 * @package		JDependent
 * @subpackage	Plugin
 * @copyright	Copyright (C) 2012 NinjaForge. All rights reserved.
 * @license 	GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link     	http://ninjaforge.com
 */
defined('_JEXEC') or die;

/**
 * Dependency Plugin Class
 * @todo put some error checking in and make multi lingual
 */
class JDependentPlugin extends JDependent
{
	/**
	 * Array of dependents to install
	 *
	 * @var    array
	 */
	protected $_dependents = array();

	/**
	 * Method for running any dependent specific code after we do any installations
	 *
	 * @return JDependentPlguin
	 */
	public function afterInstall()
	{
		return $this;
	}

	/**
	 * Method for running any dependent specific code before we do any installations
	 *
	 * @return JDependentPlguin
	 */
	public function beforeInstall()
	{
		return $this;
	}

	/**
	 * Method for running any dependent specific code after we uninstall
	 *
	 * @return JDependentPlguin
	 */
	public function afterUninstall()
	{
		return $this;
	}

	/**
	 * Method for running any dependent specific code before we uninstall
	 *
	 * @return JDependentPlguin
	 */
	public function beforeUninstall()
	{
		return $this;
	}

	/**
	 * Method for extracting a package
	 *
	 * @return JDependentPlugin
	 */
	public function extract()
	{
		$file = $this->config['dependents_path'].$this->getName().'.zip';
		if (JFile::exists($file)) {
			JArchive::extract($file, $this->config['dependents_path'].$this->getName());
		}
	}

	/**
	 * Method for determining the extension name by type
	 */
	public function getExtensionName()
	{
		$manifest 	= $this->installer->getManifest();
		$type		= $manifest->getAttribute('type');

		// Its either a component a plugin or a module
		if ($type == 'component') {
			$name = 'com_';
		} elseif ($type == 'plugin') {
			$name = 'plg_';
		} else {
			$name = 'mod_';
		}

		return $name.strtolower($manifest->name);
	}

	/**
	 * Method for returning the plugin name
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
     * Method for installing dependencies
     *
     * @param  array an array of items to exclude from installation
     * @return JDependentPlugin
     */
	public function install($exclude = array())
	{
		$this->beforeInstall();
		$this->extract();

		foreach ($exclude as $key => $type) {
			unset($this->_dependents[$key]);
		}

		$this->installExtensions()
			->moveFolders()
			->moveFiles()
			->registerDependency($this->getExtensionName(), $this->getName());

		$this->afterInstall();

		return $this;
	}

	/**
	 * Method for installing extensions though JInstaller
	 *
	 * @return JDependentPlugin
	 */
	public function installExtensions()
	{
		if (isset($this->_dependents['extensions'])) {
			foreach ($this->_dependents['extensions'] as $extension) {
				$path = $this->config['dependents_path'].$this->getName().$extension['from'];
				if (JFolder::exists($path)) {
					$installer = new JInstaller;
					$installer->install($path);
				}
			}
		}

		return $this;
	}

	/**
	 * Method for moving files to the filesystem
	 *
	 * @return JDependentPlugin
	 */
	public function moveFiles()
	{
		if (isset($this->_dependents['files'])) {
			foreach ($this->_dependents['files'] as $file) {
				$path = $this->config['dependents_path'].$this->getName().$file['from'];

				if (JFile::exists($path)) {
					$to = isset($file['to']) ? $file['to'] : $file['from'];
					// make sure the to directory exists
					$folder = JPATH_ROOT.dirname($to);
					if(!JFolder::exists($folder)) JFolder::create($folder);
					JFile::copy($path, JPATH_ROOT.$to);
				}
			}
		}

		return $this;
	}

	/**
	 * Method for moving folders to the filesystem
	 *
	 * @return JDependentPlugin
	 */
	public function moveFolders()
	{
		if (isset($this->_dependents['folders'])) {
			foreach ($this->_dependents['folders'] as $folder) {
				$path = $this->config['dependents_path'].$this->getName().$folder['from'];
				// make sure we exist
				if (JFolder::exists($path)) {
					// if the folder does not have a to location, then assume we are moving it to the same location
					$to = isset($folder['to']) ? $folder['to'] : $folder['from'];
					// force copy the folder to joomla
					JFolder::copy($path, JPATH_ROOT.$to, '', true);
				}
			}
		}
		return $this;
	}

	/**
	 * Method for removing files to the filesystem
	 *
	 * @return JDependentPlugin
	 */
	public function removeFiles()
	{
		if (isset($this->_dependents['files'])) {
			foreach ($this->_dependents['files'] as $file) {
				$path		= isset($file['to']) ? $file['to'] : $file['from'];

				if (JFile::exists(JPATH_ROOT.$path)) {
					JFile::delete(JPATH_ROOT.$path);
				}
			}
		}

		return $this;
	}

	/**
	 * Method for removing folders to the filesystem
	 *
	 * @return JDependentPlugin
	 */
	public function removeFolders()
	{
		if (isset($this->_dependents['folders'])) {
			foreach ($this->_dependents['folders'] as $folder) {
				$path = isset($folder['to']) ? $folder['to'] : $folder['from'];
				// make sure we exist
				if (JFolder::exists(JPATH_ROOT.$path)) {
					JFolder::delete(JPATH_ROOT.$path);
				}
			}
		}
	}

	/**
     * Method for uninstalling dependencies
     *
     * @param  array an array of items to exclude from installation
     * @return JDependentPlugin
     */
	public function uninstall($exclude = array())
	{
		// we only remove things if nothing else relies on it
		if (!$this->checkDependencyRegistry($this->getExtensionName(), $this->getName())) {
			$this->beforeUninstall();

			foreach ($exclude as $key => $type) {
				unset($this->_dependents[$key]);
			}

			$this->uninstallExtensions()
				->removeFolders()
				->removeFiles()
				->deregisterDependency($this->getExtensionName(), $this->getName());

			$this->afterUninstall();
		}

		return $this;
	}

	/**
	 * Method for uninstalling extensions though JInstaller
	 *
	 * @return JDependentPlugin
	 */
	public function uninstallExtensions()
	{
		if (isset($this->_dependents['extensions'])) {
			foreach ($this->_dependents['extensions'] as $extension) {
				$installer = new JInstaller;
				$installer->uninstall($this->install->getManifest()->getAttribute('type'), $this->getExtensionName());
			}
		}

		return $this;
	}
}