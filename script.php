<?php
/*
 *  package: Admin Notes
 *  copyright: Copyright (c) 2025. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 2 or later
 *  link: https://www.joomill-extensions.com
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;

/**
 * Installation script for Admin Notes module
 *
 * This class handles the installation, update, and uninstallation processes
 * for the Admin Notes module. It performs version checks, displays messages,
 * and automatically enables and configures the module upon installation.
 *
 * @package     Joomill\Module\Adminnotes
 * @since       1.2.0
 */
class Mod_AdminnotesInstallerScript
{
    /**
     * Minimum Joomla version to check
     *
     * @var     string
     * @since   1.2.0
     */
    private $minimumJoomlaVersion = '4.0';

    /**
     * Minimum PHP version to check
     *
     * @var     string
     * @since   1.2.0
     */
    private $minimumPHPVersion = JOOMLA_MINIMUM_PHP;

    /**
     * Function called before extension installation/update/removal procedure commences
     *
     * Checks for minimum PHP and Joomla versions before allowing the installation to proceed.
     *
     * @param   string            $type    The type of change (install, update or discover_install, not uninstall)
     * @param   InstallerAdapter  $parent  The class calling this method
     *
     * @return  boolean  True on success
     * @throws  Exception
     * @since   1.2.0
     */
    public function preflight(string $type, InstallerAdapter $parent): bool
    {
        if ($type !== 'uninstall') {
            // Check for the minimum PHP version before continuing
            if (!empty($this->minimumPHPVersion) && version_compare(PHP_VERSION, $this->minimumPHPVersion, '<')) {
                Log::add(
                    Text::sprintf('JLIB_INSTALLER_MINIMUM_PHP', $this->minimumPHPVersion),
                    Log::WARNING,
                    'jerror'
                );

                return false;
            }
            // Check for the minimum Joomla version before continuing
            if (!empty($this->minimumJoomlaVersion) && version_compare(JVERSION, $this->minimumJoomlaVersion, '<')) {
                Log::add(
                    Text::sprintf('JLIB_INSTALLER_MINIMUM_JOOMLA', $this->minimumJoomlaVersion),
                    Log::WARNING,
                    'jerror'
                );

                return false;
            }
        }

        return true;
    }

    /**
     * Function called after extension installation/update/removal procedure commences
     *
     * Displays information and social media links after installation or uninstallation.
     *
     * @param   string            $type    The type of change (install, update or discover_install, not uninstall)
     * @param   InstallerAdapter  $parent  The class calling this method
     *
     * @return  boolean  True on success
     * @since   1.2.0
     */
    public function postflight(string $type, InstallerAdapter $parent): bool
    {
        if ($type === 'install') {
            echo '<style>a[target="_blank"]::before {display: none};</style>';
            echo '<div class="mb-3 text-center"><img src="https://www.joomill.nl/images/logo_joomill.png" alt="Joomill Logo" /></div>';
            echo '<div class="mb-3 text-center"><strong>' . Text::_('MOD_ADMINNOTES_XML_DESCRIPTION') . '</strong></div>';
            echo '<hr>';
            echo '<div class="text-center">' . Text::_('MOD_ADMINNOTES_INSTALL_FOLLOWME') . ':</div>';
            echo '<div class="text-center">';
            echo '<a class="m-2" href="https://www.linkedin.com/in/jeroenmoolenschot/" target="_blank"><i class="fa-brands fa-linkedin"> </i></a>';
            echo '<a class="m-2" href="https://www.facebook.com/Joomill" target="_blank"><i class="fa-brands fa-facebook-f"> </i></a>';
            echo '<a class="m-2" href="https://www.instagram.com/Joomill" target="_blank"><i class="fa-brands fa-instagram"> </i></a>';
            echo '<a class="m-2" href="https://bsky.app/profile/joomill.bsky.social" target="_blank"><i class="fa-brands fa-bluesky"> </i></a>';
            echo '<a class="m-2" href="https://joomla.social/@joomill" target="_blank"><i class="fa-brands fa-mastodon"></i></a>';
            echo '<a class="m-2" href="https://www.threads.net/@joomill" target="_blank"><i class="fa-brands fa-threads"></i></a>';
            echo '<a class="m-2" href="https://www.twitter.com/Joomill" target="_blank"><i class="fa-brands fa-brands fa-x-twitter"> </i></a>';
            echo '<a class="m-2" href="https://community.joomla.org/service-providers-directory/listings/67:joomill.html" target="_blank"><i class="fa-brands fa-joomla"> </i></a>';
            echo '</div>';
        }
        if ($type === 'uninstall') {
            echo '<style>a[target="_blank"]::before {display: none};</style>';
            echo '<div class="mb-3 text-center"><img src="https://www.joomill.nl/images/logo_joomill.png" alt="Joomill Logo" /></div>';
            echo '<br>';
            echo '<h3 class="text-center">' . Text::_('MOD_ADMINNOTES_UNINSTALL_THANKYOU') . '</h3>';
            echo '<br>';
            echo '<div class="text-center">' . Text::_('MOD_ADMINNOTES_INSTALL_FOLLOWME') . ':</div>';
            echo '<div class="text-center">';
            echo '<a class="m-2" href="https://www.linkedin.com/in/jeroenmoolenschot/" target="_blank"><i class="fa-brands fa-linkedin"> </i></a>';
            echo '<a class="m-2" href="https://www.facebook.com/Joomill" target="_blank"><i class="fa-brands fa-facebook-f"> </i></a>';
            echo '<a class="m-2" href="https://www.instagram.com/Joomill" target="_blank"><i class="fa-brands fa-instagram"> </i></a>';
            echo '<a class="m-2" href="https://bsky.app/profile/joomill.bsky.social" target="_blank"><i class="fa-brands fa-bluesky"> </i></a>';
            echo '<a class="m-2" href="https://joomla.social/@joomill" target="_blank"><i class="fa-brands fa-mastodon"></i></a>';
            echo '<a class="m-2" href="https://www.threads.net/@joomill" target="_blank"><i class="fa-brands fa-threads"></i></a>';
            echo '<a class="m-2" href="https://www.twitter.com/Joomill" target="_blank"><i class="fa-brands fa-brands fa-x-twitter"> </i></a>';
            echo '<a class="m-2" href="https://community.joomla.org/service-providers-directory/listings/67:joomill.html" target="_blank"><i class="fa-brands fa-joomla"> </i></a>';
            echo '</div>';
        }

        return true;
    }

    /**
     * Function called during extension installation
     *
     * Enables the module by calling the enableModule method.
     *
     * @param   InstallerAdapter  $parent  The class calling this method
     *
     * @return  boolean  True on success
     * @since   1.2.0
     */
    public function install(InstallerAdapter $parent): bool
    {
        // Enable the extension
        $this->enableModule();

        return true;
    }

    /**
     * Enables the module by publishing it and assigning it to the cpanel position
     *
     * This method automatically configures the module with default settings,
     * publishes it, and assigns it to the cpanel position in the administrator area.
     * It also sets appropriate access levels and parameters.
     *
     * The process follows these steps:
     * 1. Check if the module is already published in the cpanel position
     * 2. If not published, update the module with default settings
     * 3. Get the module ID for the menu assignment
     * 4. Create a menu assignment record to make the module visible
     *
     * @return  void
     * @since   1.2.0
     */
    private function enableModule()
    {
        try {
            // Get database connection from Joomla's dependency injection container
            $db = Factory::getContainer()->get('DatabaseDriver');

            // First, check if the module has already been published to avoid duplicate configuration
            // We look for a module with our module name that is already published in the cpanel position
            $query = $db->getQuery(true);
            $query->select($db->quoteName('id'));
            $query->from($db->quoteName('#__modules'));
            $query->where($db->quoteName('module') . ' = ' . $db->quote('mod_adminnotes'));
            $query->where($db->quoteName('published') . ' = 1');
            $query->where($db->quoteName('position') . ' = ' . $db->quote('cpanel'));
            $db->setQuery($query);
            $moduleId = $db->loadResult();

            // If the Module has not been published yet, we need to configure and publish it
            if (empty($moduleId)) {
                try {
                    // Update the module with default settings for optimal user experience
                    // - Set a user-friendly title
                    // - Publish it (set published=1)
                    // - Position it in the cpanel (admin dashboard)
                    // - Set access level 3 (Special - only admins and super users)
                    // - Configure default parameters for editor, icons, etc.
                    $query = $db->getQuery(true);
                    $fields = array(
                        $db->quoteName('title') . ' = ' . $db->quote('Notes'),
                        $db->quoteName('published') . ' = 1',
                        $db->quoteName('position') . ' = ' . $db->quote('cpanel'),
                        $db->quoteName('access') . ' = 3',
                        $db->quoteName('params') . ' = ' .
                        $db->quote('{"edit_users":"","editor":"tinymce","forceEditor":"1","print":"1","download":"1","header_icon":"fa-regular fa-note-sticky","module_tag":"div","bootstrap_size":"0","header_tag":"h2","header_class":"","style":"0"}'),
                    );
                    $conditions = array($db->quoteName('module') . ' = ' . $db->quote('mod_adminnotes'));
                    $query->update($db->quoteName('#__modules'))->set($fields)->where($conditions);
                    $db->setQuery($query);
                    $db->execute();

                    // Get the module ID after updating - we need this for the menu assignment
                    // The module record was created during installation, but we need its ID
                    $query = $db->getQuery(true);
                    $query->select($db->quoteName('id'));
                    $query->from($db->quoteName('#__modules'));
                    $query->where($db->quoteName('module') . ' = ' . $db->quote('mod_adminnotes'));
                    $db->setQuery($query);
                    $moduleId = $db->loadResult();

                    // Add an entry to the modules_menu table to make the module visible
                    // Setting menuid=0 makes the module appear on all pages
                    // Without this step, the module would be configured but not visible anywhere
                    $query = $db->getQuery(true);
                    $fields = array(
                        $db->quoteName('moduleid') . ' = ' . $db->quote($moduleId),
                        $db->quoteName('menuid') . ' = 0',
                    );

                    $query->insert($db->quoteName('#__modules_menu'))->set($fields);
                    $db->setQuery($query);
                    $db->execute();
                } catch (Exception $e) {
                    // Log any errors that occur during the publishing process
                    // This helps with troubleshooting installation issues
                    Log::add('Error publishing module: ' . $e->getMessage(), Log::ERROR, 'jerror');
                }
            }
        } catch (Exception $e) {
            // Log any errors that occur during the initial module status check
            // This is a separate try-catch to distinguish between different types of errors
            Log::add('Error checking module status: ' . $e->getMessage(), Log::ERROR, 'jerror');
        }
    }
}
