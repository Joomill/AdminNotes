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
            // Get the current user from the application
            $user = Factory::getApplication()->getIdentity();
            $canEdit = false;

            // Ensure we have a Registry object for parameter handling
            // This is important because parameters might come in different formats
            if (!($params instanceof Registry)) {
                $params = new Registry($params);
            }

            // Get the configured user groups and users who can edit
            // These are set in the module's configuration
            $editUserGroups = $params->get('edit_user_groups', []);
            $editUsers = $params->get('edit_users', '');

            // If no restrictions are set in the module configuration,
            // we allow editing for all users as a default behavior
            if (empty($editUserGroups) && empty($editUsers)) {
                $canEdit = true;
            }

            // Convert editUserGroups to an array if it's not already
            // This handles cases where the parameter might be stored as a comma-separated string
            if (!is_array($editUserGroups)) {
                $editUserGroups = array_filter(explode(',', $editUserGroups));
            }

            // Check if the current user belongs to any of the allowed groups
            // As soon as we find a match, we can grant edit permission
            if (!empty($editUserGroups) && !empty($user->groups)) {
                foreach ($editUserGroups as $groupId) {
                    if (in_array((int)$groupId, $user->groups)) {
                        $canEdit = true;
                        break; // Exit the loop once we find a match
                    }
                }
            }

            // Check if the current user is specifically allowed by user ID
            // The editUsers parameter might be a comma-separated string of user IDs
            if (!empty($editUsers)) {
                $allowedUsers = array_filter(explode(',', $editUsers));
                if (in_array($user->id, $allowedUsers)) {
                    $canEdit = true;
                }
            }

            // Super Users (members of group 8) always have access regardless of settings
            // This is a security measure to ensure admins can't lock themselves out
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
     * This method handles the entire save process including:
     * - Input validation
     * - Rate limiting to prevent abuse
     * - Data sanitization
     * - Database update
     * - Cache clearing
     * - Error handling
     *
     * @param int $moduleId The ID of the module to save the data to.
     * @param mixed $data The data to be saved.
     *
     * @return bool Returns true if the data was successfully saved, false otherwise.
     */
    public static function saveData(int $moduleId, mixed $data): bool
    {
        // Input validation - ensure moduleId is a positive integer
        // This prevents SQL injection and invalid database operations
        if (!is_int($moduleId) || $moduleId <= 0) {
            Factory::getApplication()->enqueueMessage(Text::_('MOD_ADMINNOTES_INVALID_MODULE_ID'), 'error');
            return false;
        }

        // Rate limiting - check if the user has made too many save requests
        // This prevents abuse of the save functionality and potential DoS attacks
        if (!self::checkRateLimit()) {
            Factory::getApplication()->enqueueMessage(Text::_('MOD_ADMINNOTES_RATE_LIMIT_EXCEEDED'), 'error');
            return false;
        }

        // Sanitize the data using Joomla's built-in filtering
        // This prevents XSS attacks and ensures data integrity
        $filter = new InputFilter();
        $safeData = $filter->clean($data, 'html');

        // Prepare the database query to update the module content
        // Using Joomla's query builder for proper escaping and security
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__modules'))
            ->set($db->quoteName('content') . ' = ' . $db->quote($safeData))
            ->where($db->quoteName('id') . ' = ' . (int)$moduleId);
        $db->setQuery($query);

        try {
            // Execute the query and get the result
            $result = $db->execute();

            // Clear the cache for this module to ensure the updated content is displayed
            // Without this, users might see stale content until the cache expires
            $cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)->createCacheController();
            $cache->clean('com_modules', 'module', $moduleId);

            // Record successful save for rate limiting
            // This helps track usage patterns and enforce rate limits
            self::recordSaveAttempt(true);

            return $result;
        } catch (Exception $e) {
            // Log the error and display a user-friendly message
            // The actual exception details are only shown to help with debugging
            Factory::getApplication()->enqueueMessage(Text::_('MOD_ADMINNOTES_FAILED') . ': ' . $e->getMessage(), 'error');

            // Record failed save for rate limiting
            // Failed attempts also count toward rate limits to prevent brute force attacks
            self::recordSaveAttempt(false);

            return false;
        }
    }
    /**
     * Checks if the current user has exceeded the rate limit for save operations.
     * 
     * This method implements a sliding window rate limiting algorithm to prevent
     * abuse of the save functionality. It tracks save attempts in the user's session
     * and limits the number of operations within a specific time window.
     *
     * @return bool Returns true if the user has not exceeded the rate limit, false otherwise.
     */
    private static function checkRateLimit(): bool
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $userId = $user->id;

        // Get the session object to store rate limiting data
        // Using the session allows us to track attempts without database overhead
        $session = $app->getSession();

        // Get the save attempts from the session
        // This is an array of timestamps when save attempts were made
        $saveAttempts = $session->get('adminnotes.save_attempts', []);

        // Define rate limit parameters
        // These could be moved to configuration if needed for different environments
        $maxAttempts = 10; // Maximum number of attempts allowed in the time window
        $timeWindow = 60;  // Time window in seconds (1 minute)

        // Clean up old attempts that are outside the current time window
        // This implements a sliding window approach rather than a fixed window
        $now = time();
        $saveAttempts = array_filter($saveAttempts, function($attempt) use ($now, $timeWindow) {
            return ($now - $attempt) < $timeWindow;
        });

        // Check if the user has exceeded the rate limit
        // If they've made too many attempts in the time window, deny the operation
        if (count($saveAttempts) >= $maxAttempts) {
            return false;
        }

        // User is within the rate limit, allow the operation
        return true;
    }

    /**
     * Records a save attempt for rate limiting purposes.
     * 
     * This method is called after each save operation (both successful and failed)
     * to record the attempt in the user's session. This data is then used by the
     * checkRateLimit() method to enforce rate limiting.
     *
     * @param bool $success Whether the save attempt was successful.
     *                      Currently not used, but could be used to implement
     *                      different handling for successful vs. failed attempts.
     * @return bool Always returns true
     */
    private static function recordSaveAttempt(bool $success): bool
    {
        $app = Factory::getApplication();
        $session = $app->getSession();

        // Get the save attempts from the session
        // If this is the first attempt, an empty array will be returned
        $saveAttempts = $session->get('adminnotes.save_attempts', []);

        // Add the current attempt with the current timestamp
        // This allows us to track when each attempt was made
        $saveAttempts[] = time();

        // Store the updated attempts in the session for future rate limit checks
        // This persists across page loads within the same session
        $session->set('adminnotes.save_attempts', $saveAttempts);

        return true;
    }
}
