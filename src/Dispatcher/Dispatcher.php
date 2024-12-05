<?php

namespace Joomill\Module\Adminnotes\Administrator\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Dispatcher class for mod_adminnotes
 *
 * @since  5.1.0
 */
class Dispatcher extends AbstractModuleDispatcher
{
	/**
	 * Returns the layout data.
	 *
	 * @return  array
	 *
	 * @since   5.1.0
	 */
	protected function getLayoutData()
	{
		$data = parent::getLayoutData();
		return $data;
	}
}
