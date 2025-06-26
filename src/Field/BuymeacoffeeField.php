<?php
/*
 *  package: Admin Notes
 *  copyright: Copyright (c) 2025. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 2 or later
 *  link: https://www.joomill-extensions.com
 */

namespace Joomill\Module\Adminnotes\Administrator\Field;

use Joomla\CMS\Form\FormField;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Buy Me a Coffee field for the Admin Notes module
 *
 * This field adds a "Buy Me a Coffee" button to the module's configuration page,
 * allowing users to support the developer through donations.
 *
 * @package     Joomill\Module\Adminnotes\Administrator\Field
 * @since       1.2.0
 */
class BuymeacoffeeField extends FormField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  1.2.0
     */
    protected $type = 'Buymeacoffee';

    /**
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     *
     * @since   1.2.0
     */
    protected function getInput()
    {
        // Add the Buy Me a Coffee script
        $html = '<script data-name="BMC-Widget" data-cfasync="false" src="https://cdnjs.buymeacoffee.com/1.0.0/widget.prod.min.js" data-id="joomill" data-description="Support me on Buy me a coffee!" data-message="Enjoying this free extension? Support future development with a coffee!" data-color="#FFDD00" data-position="Right" data-x_margin="18" data-y_margin="18"></script>';

        return $html;
    }
}
