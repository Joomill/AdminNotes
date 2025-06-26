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
use Joomla\CMS\Filter\InputFilter;
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
    public static function canEdit(mixed $params): bool
    {
        try {
            $user = Factory::getApplication()->getIdentity();
            $canEdit = false;

            // Ensure we have a Registry object
            if (!($params instanceof Registry)) {
                $params = new Registry($params);
            }

            $editUserGroups = $params->get('edit_user_groups', []);
            $editUsers = $params->get('edit_users', '');

            // If no restrictions are set, allow editing
            if (empty($editUserGroups) && empty($editUsers)) {
                $canEdit = true;
            }

            // Ensure editUserGroups is an array
            if (!is_array($editUserGroups)) {
                $editUserGroups = array_filter(explode(',', $editUserGroups));
            }

            // Check if user is in allowed groups
            if (!empty($editUserGroups) && !empty($user->groups)) {
                foreach ($editUserGroups as $groupId) {
                    if (in_array((int)$groupId, $user->groups)) {
                        $canEdit = true;
                        break;
                    }
                }
            }

            // Check if user is in allowed users
            // Handle multiple users by splitting the string
            if (!empty($editUsers)) {
                $allowedUsers = array_filter(explode(',', $editUsers));
                if (in_array($user->id, $allowedUsers)) {
                    $canEdit = true;
                }
            }

            // Super Users (members of group 8) always have access
            if (in_array(8, $user->groups)) {
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
    public static function getData(int $moduleId)
    {
        // Input validation - ensure moduleId is a positive integer
        if (!is_int($moduleId) || $moduleId <= 0) {
            Factory::getApplication()->enqueueMessage(Text::_('MOD_ADMINNOTES_INVALID_MODULE_ID'), 'error');
            return null;
        }

        try {
            $db = Factory::getContainer()->get('DatabaseDriver');
            $query = $db->getQuery(true)
                ->select($db->quoteName('content'))
                ->from($db->quoteName('#__modules'))
                ->where($db->quoteName('id') . ' = ' . (int)$moduleId);
            $db->setQuery($query);

            $content = $db->loadResult();

            // Ensure we're returning a string or null
            return is_string($content) ? $content : null;
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
    public static function saveData(int $moduleId, mixed $data): bool
    {
        // Input validation - ensure moduleId is a positive integer
        if (!is_int($moduleId) || $moduleId <= 0) {
            Factory::getApplication()->enqueueMessage(Text::_('MOD_ADMINNOTES_INVALID_MODULE_ID'), 'error');
            return false;
        }

        // Rate limiting - check if the user has made too many save requests
        if (!self::checkRateLimit()) {
            Factory::getApplication()->enqueueMessage(Text::_('MOD_ADMINNOTES_RATE_LIMIT_EXCEEDED'), 'error');
            return false;
        }

        // Sanitize the data using Joomla's built-in filtering
        $filter = new InputFilter();
        $safeData = $filter->clean($data, 'html');

        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__modules'))
            ->set($db->quoteName('content') . ' = ' . $db->quote($safeData))
            ->where($db->quoteName('id') . ' = ' . (int)$moduleId);
        $db->setQuery($query);

        try {
            $result = $db->execute();

            // Clear the cache for this module
            $cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)->createCacheController();
            $cache->clean('com_modules', 'module', $moduleId);

            // Record successful save for rate limiting
            self::recordSaveAttempt(true);

            return $result;
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage(Text::_('MOD_ADMINNOTES_FAILED') . ': ' . $e->getMessage(), 'error');

            // Record failed save for rate limiting
            self::recordSaveAttempt(false);

            return false;
        }
    }
    /**
     * Checks if the current user has exceeded the rate limit for save operations.
     *
     * @return bool Returns true if the user has not exceeded the rate limit, false otherwise.
     */
    private static function checkRateLimit(): bool
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $userId = $user->id;

        // Get the session object
        $session = $app->getSession();

        // Get the save attempts from the session
        $saveAttempts = $session->get('adminnotes.save_attempts', []);

        // Define rate limit parameters
        $maxAttempts = 10; // Maximum number of attempts
        $timeWindow = 60; // Time window in seconds (1 minute)

        // Clean up old attempts
        $now = time();
        $saveAttempts = array_filter($saveAttempts, function($attempt) use ($now, $timeWindow) {
            return ($now - $attempt) < $timeWindow;
        });

        // Check if the user has exceeded the rate limit
        if (count($saveAttempts) >= $maxAttempts) {
            return false;
        }

        return true;
    }

    /**
     * Records a save attempt for rate limiting purposes.
     *
     * @param bool $success Whether the save attempt was successful.
     * @return bool Always returns true
     */
    private static function recordSaveAttempt(bool $success): bool
    {
        $app = Factory::getApplication();
        $session = $app->getSession();

        // Get the save attempts from the session
        $saveAttempts = $session->get('adminnotes.save_attempts', []);

        // Add the current attempt
        $saveAttempts[] = time();

        // Store the updated attempts in the session
        $session->set('adminnotes.save_attempts', $saveAttempts);

        return true;
    }
}
