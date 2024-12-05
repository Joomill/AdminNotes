<?php
/*
 *  package: Checklist
 *  copyright: Copyright (c) 2024. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 2 or later
 *  link: https://www.joomill-extensions.com
 *
 *  this project is based on DB8 Site Dev for Joomla 3
 *  package: Com_Db8SiteDev
 *  copyright: Copyright (c) 2016. Peter Martin | DB8
 *  license: GNU General Public License version 2 or later
 *  link: https://db8.eu/download/component/db8-site-dev
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;

class Mod_Checklist_categoriesInstallerScript
{
	public function install($parent)
	{
		// Enable the extension
		$this->enableModule();
		return true;
	}
	private function enableModule()
	{
		// Check if Module has not been published yet
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'));
		$query->from($db->quoteName('#__modules'));
		$query->where($db->quoteName('module') . ' = ' . $db->quote('mod_checklist_categories'));
		$query->where($db->quoteName('published') . ' = 1');
		$query->where($db->quoteName('position') . ' = ' . $db->quote('icon'));
		$db->setQuery($query);
		$moduleId = $db->loadResult();

		// If the Module has not been published, publish + assign it

		if (empty($moduleId))
		{
			// Change Module settings to auto publish it on position cpanel
			$query      = $db->getQuery(true);
			$fields     = array(
				$db->quoteName('title') . ' = ' . $db->quote('Admin Checklist Categories'),
				$db->quoteName('published') . ' = 1',
				$db->quoteName('position') . ' = ' . $db->quote('icon'),
				$db->quoteName('access') . ' = 3',
				$db->quoteName('params') . ' = ' .
				$db->quote('{"layout":"_:default","moduleclass_sfx":"","cache":"0","module_tag":"div",' .
					'"bootstrap_size":"0","header_tag":"h2","header_class":"","style":"0","header_icon":"fa-solid fa-check"}'),
			);
			$conditions = array($db->quoteName('module') . ' = ' . $db->quote('mod_checklist_categories'));
			$query->update($db->quoteName('#__modules'))->set($fields)->where($conditions);
			$db->setQuery($query);
			$db->execute();

			// Get ID for module
			$query = $db->getQuery(true);
			$query->select($db->quoteName('id'));
			$query->from($db->quoteName('#__modules'));
			$query->where($db->quoteName('module') . ' = ' . $db->quote('mod_checklist_categories'));
			$db->setQuery($query);
			$moduleId = $db->loadResult();

			// Add to modules_menu
			$query  = $db->getQuery(true);
			$fields = array(
				$db->quoteName('moduleid') . ' = ' . $db->quote($moduleId),
				$db->quoteName('menuid') . ' = 0',
			);

			$query->insert($db->quoteName('#__modules_menu'))->set($fields);
			$db->setQuery($query);
			$db->execute();
		}
	}
}
