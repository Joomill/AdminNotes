<?php
/*
 *  package: Admin Notes
 *  copyright: Copyright (c) 2025. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 2 or later
 *  link: https://www.joomill-extensions.com
 */

namespace Joomill\Module\Adminnotes\Administrator\Helper;

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Exception;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper class for Admin Notes module
 *
 * This class provides utility methods for the Admin Notes module, including
 * permission checking, data retrieval, and data saving functionality.
 *
 * @package     Joomill\Module\Adminnotes\Administrator\Helper
 * @since       1.2.0
 */
class AdminnotesHelper
{

    /**
     * Checks if the current user has permission to edit the notes.
     *
     * @param mixed $params The module parameters.
     *
     * @return bool Returns true if the user can edit, false otherwise.
     */
    public static function canEdit($params)
    {
        try {
            $user = Factory::getApplication()->getIdentity();
            $canEdit = false;

            $params = new Registry($params);
            $editUserGroups = $params->get('edit_user_groups', []);
            $editUsers = $params->get('edit_users');

            if ((!$editUserGroups) && (!$editUsers)) {
                $canEdit = true;
            }

            if (!is_array($editUserGroups)) {
                $editUserGroups = array_filter(explode(',', $editUserGroups));
            }

            // Check if user is in allowed groups
            if (!empty($editUserGroups)) {
                foreach ($editUserGroups as $groupId) {
                    if (in_array((int)$groupId, $user->groups)) {
                        $canEdit = true;
                        break;
                    }
                }
            }

            // Check if user is in allowed users
            if ($user->id == $editUsers) {
                $canEdit = true;
            }

            return $canEdit;
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage(Text::_('MOD_ADMINNOTES_FAILED') . ': ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Retrieves the content of a module from the database based on its ID.
     *
     * @param int $moduleId The ID of the module to retrieve the content from.
     *
     * @return string|null The content of the module, or null if no module with the given ID exists.
     */
    public static function getData($moduleId)
    {
        try {
            $db = Factory::getContainer()->get('DatabaseDriver');
            $query = $db->getQuery(true)
                ->select($db->quoteName('content'))
                ->from($db->quoteName('#__modules'))
                ->where($db->quoteName('id') . ' = ' . (int)$moduleId);
            $db->setQuery($query);

            return $db->loadResult();
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage(Text::_('MOD_ADMINNOTES_FAILED') . ': ' . $e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Saves the given data to the module with the specified ID in the database.
     *
     * @param int $moduleId The ID of the module to save the data to.
     * @param mixed $data The data to be saved.
     *
     * @return bool Returns true if the data was successfully saved, false otherwise.
     */
    public static function saveData($moduleId, $data)
    {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__modules'))
            ->set($db->quoteName('content') . ' = ' . $db->quote($data))
            ->where($db->quoteName('id') . ' = ' . (int)$moduleId);
        $db->setQuery($query);

        try {
            $result = $db->execute();

            // Clear the cache for this module
            $cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)->createCacheController();
            $cache->clean('com_modules', 'module', $moduleId);

            return $result;
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage(Text::_('MOD_ADMINNOTES_FAILED') . ': ' . $e->getMessage(), 'error');

            return false;
        }
    }
}
