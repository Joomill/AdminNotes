<?php
/*
 *  package: Joomill Admin Notes
 *  copyright: Copyright (c) 2026. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 3 or later
 *  link: https://www.joomill-extensions.com
 */

namespace Joomill\Module\Adminnotes\Administrator\Helper;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Exception;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper class for Admin Notes module
 *
 * This class provides utility methods for the Admin Notes module, including
 * permission checking, data retrieval, and data saving functionality. It is an
 * instance class resolved through Joomla's HelperFactory: the application and
 * module parameters are injected via the constructor and the database driver is
 * injected through the DatabaseAwareInterface.
 *
 * @package     Joomill\Module\Adminnotes\Administrator\Helper
 * @since       1.2.0
 */
class AdminnotesHelper implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    /**
     * The application object.
     *
     * @var    CMSApplicationInterface
     * @since  1.4.0
     */
    protected CMSApplicationInterface $app;

    /**
     * The module parameters.
     *
     * @var    Registry
     * @since  1.4.0
     */
    protected Registry $params;

    /**
     * Helper class constructor.
     *
     * @param   array  $config  The layout data passed by the HelperFactory
     *                          (contains at least 'app' and 'params').
     *
     * @since   1.4.0
     */
    public function __construct(array $config)
    {
        $this->app    = $config['app'];
        $this->params = $config['params'] instanceof Registry
            ? $config['params']
            : new Registry($config['params'] ?? []);
    }

    /**
     * Checks if the current user has permission to edit the notes.
     *
     * @param   User|null  $user  The user to check, or null for the current user.
     *
     * @return  bool  Returns true if the user can edit, false otherwise.
     *
     * @since   1.2.0
     */
    public function canEdit(?User $user = null): bool
    {
        try {
            // Get the current user from the application when no user is supplied
            $user    = $user ?: $this->app->getIdentity();
            $canEdit = false;

            // Get the configured user groups and users who can edit
            // These are set in the module's configuration
            $editUserGroups = $this->params->get('edit_user_groups', []);
            $editUsers      = $this->params->get('edit_users', '');

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
                    if (in_array((int) $groupId, $user->groups)) {
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

            // Super Users always have access regardless of settings
            // This is a security measure to ensure admins can't lock themselves out.
            // Use the ACL (core.admin) instead of a hardcoded group id, because a site
            // can have any number of Super User groups with arbitrary ids.
            if ($user->authorise('core.admin')) {
                $canEdit = true;
            }

            return $canEdit;
        } catch (Exception $e) {
            $this->app->enqueueMessage(Text::_('MOD_ADMINNOTES_FAILED') . ': ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Retrieves the content of a module from the database based on its ID.
     *
     * @param   int  $moduleId  The ID of the module to retrieve the content from.
     *
     * @return  string|null  The content of the module, or null if no module with the given ID exists.
     *
     * @since   1.2.0
     */
    public function getData(int $moduleId): ?string
    {
        // Input validation - ensure moduleId is a positive integer
        if ($moduleId <= 0) {
            $this->app->enqueueMessage(Text::_('MOD_ADMINNOTES_INVALID_MODULE_ID'), 'error');
            return null;
        }

        try {
            $db    = $this->getDatabase();
            $query = $db->getQuery(true)
                ->select($db->quoteName('content'))
                ->from($db->quoteName('#__modules'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $moduleId, ParameterType::INTEGER);
            $db->setQuery($query);

            $content = $db->loadResult();

            // Ensure we're returning a string or null
            return is_string($content) ? $content : null;
        } catch (Exception $e) {
            $this->app->enqueueMessage(Text::_('MOD_ADMINNOTES_FAILED') . ': ' . $e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Saves the given data to the module with the specified ID in the database.
     *
     * This method handles the entire save process including:
     * - Input validation
     * - Server-side permission check
     * - Rate limiting to prevent abuse
     * - ACL-based content filtering
     * - Database update
     * - Cache clearing
     * - Error handling
     *
     * @param   int    $moduleId  The ID of the module to save the data to.
     * @param   mixed  $data      The data to be saved.
     *
     * @return  bool  Returns true if the data was successfully saved, false otherwise.
     *
     * @since   1.2.0
     */
    public function saveData(int $moduleId, mixed $data): bool
    {
        // Input validation - ensure moduleId is a positive integer
        // This prevents SQL injection and invalid database operations
        if ($moduleId <= 0) {
            $this->app->enqueueMessage(Text::_('MOD_ADMINNOTES_INVALID_MODULE_ID'), 'error');
            return false;
        }

        // Server-side permission check (defense in depth)
        // The view layer already hides the editor for users without edit rights,
        // but we must never rely on the view alone: re-verify here so the save
        // cannot be triggered by a crafted request.
        if (!$this->canEdit()) {
            $this->app->enqueueMessage(Text::_('MOD_ADMINNOTES_NOT_AUTHORISED'), 'error');
            return false;
        }

        // Rate limiting - check if the user has made too many save requests
        // This prevents abuse of the save functionality and potential DoS attacks
        if (!$this->checkRateLimit()) {
            $this->app->enqueueMessage(Text::_('MOD_ADMINNOTES_RATE_LIMIT_EXCEEDED'), 'error');
            return false;
        }

        // ACL-based content filtering to prevent stored XSS.
        // Super Users (core.admin) are trusted to store raw HTML. Everyone else
        // gets their input run through Joomla's InputFilter, which strips script,
        // iframe and other dangerous tags before the note is stored and later
        // rendered in the administrator back-end of every admin user.
        if (!$this->app->getIdentity()->authorise('core.admin')) {
            $data = InputFilter::getInstance()->clean((string) $data, 'html');
        }

        // Using Joomla's query builder for proper escaping and security
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__modules'))
            ->set($db->quoteName('content') . ' = :content')
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':content', $data)
            ->bind(':id', $moduleId, ParameterType::INTEGER);
        $db->setQuery($query);

        try {
            // Execute the query and get the result
            $result = $db->execute();

            // Clear the cache for this module to ensure the updated content is displayed
            // Without this, users might see stale content until the cache expires
            $cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)->createCacheController();
            $cache->clean('com_modules', 'module', $moduleId);

            // Record successful save for rate limiting
            $this->recordSaveAttempt();

            return $result;
        } catch (Exception $e) {
            // Log the error and display a user-friendly message
            $this->app->enqueueMessage(Text::_('MOD_ADMINNOTES_FAILED') . ': ' . $e->getMessage(), 'error');

            // Record failed save for rate limiting
            // Failed attempts also count toward rate limits to prevent brute force attacks
            $this->recordSaveAttempt();

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
     * @return  bool  Returns true if the user has not exceeded the rate limit, false otherwise.
     *
     * @since   1.2.0
     */
    private function checkRateLimit(): bool
    {
        // Get the session object to store rate limiting data
        // Using the session allows us to track attempts without database overhead
        $session = $this->app->getSession();

        // Get the save attempts from the session
        // This is an array of timestamps when save attempts were made
        $saveAttempts = $session->get('adminnotes.save_attempts', []);

        // Define rate limit parameters
        // These could be moved to configuration if needed for different environments
        $maxAttempts = 10; // Maximum number of attempts allowed in the time window
        $timeWindow  = 60;  // Time window in seconds (1 minute)

        // Clean up old attempts that are outside the current time window
        // This implements a sliding window approach rather than a fixed window
        $now          = time();
        $saveAttempts = array_filter($saveAttempts, static function ($attempt) use ($now, $timeWindow) {
            return ($now - $attempt) < $timeWindow;
        });

        // Check if the user has exceeded the rate limit
        // If they've made too many attempts in the time window, deny the operation
        return count($saveAttempts) < $maxAttempts;
    }

    /**
     * Records a save attempt for rate limiting purposes.
     *
     * This method is called after each save operation (both successful and failed)
     * to record the attempt in the user's session. This data is then used by the
     * checkRateLimit() method to enforce rate limiting.
     *
     * @return  void
     *
     * @since   1.2.0
     */
    private function recordSaveAttempt(): void
    {
        $session = $this->app->getSession();

        // Get the save attempts from the session
        // If this is the first attempt, an empty array will be returned
        $saveAttempts = $session->get('adminnotes.save_attempts', []);

        // Add the current attempt with the current timestamp
        // This allows us to track when each attempt was made
        $saveAttempts[] = time();

        // Store the updated attempts in the session for future rate limit checks
        // This persists across page loads within the same session
        $session->set('adminnotes.save_attempts', $saveAttempts);
    }
}
