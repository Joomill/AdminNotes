<?php
/*
 *  package: Joomill Admin Notes
 *  copyright: Copyright (c) 2026. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 3 or later
 *  link: https://www.joomill-extensions.com
 */

namespace Joomill\Module\Adminnotes\Administrator\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomill\Module\Adminnotes\Administrator\Helper\AdminnotesHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Dispatcher class for mod_adminnotes
 *
 * This class handles the module's dispatching process, extending Joomla's
 * AbstractModuleDispatcher. It resolves the module helper through the
 * HelperFactory, processes the save request and exposes the prepared data
 * to the layout, keeping all business logic out of the template.
 *
 * @package     Joomill\Module\Adminnotes\Administrator\Dispatcher
 * @since       1.2.0
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    /**
     * Returns the layout data.
     *
     * @return  array
     *
     * @since   1.2.0
     */
    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();

        /** @var AdminnotesHelper $helper */
        $helper = $this->getHelperFactory()->getHelper('AdminnotesHelper', $data);

        $app      = $data['app'];
        $input    = $app->getInput();
        $module   = $data['module'];
        $moduleId = (int) ($module->id ?? 0);

        $canEdit = $helper->canEdit();

        // Handle the save request before the layout is rendered.
        // Authorisation, rate limiting and content filtering all happen inside saveData().
        if ($canEdit && $input->getMethod() === 'POST' && $input->get('task') === 'save') {
            if (!Session::checkToken('post')) {
                $app->enqueueMessage(Text::_('MOD_ADMINNOTES_INVALIDTOKEN'), 'error');
            } elseif ($helper->saveData($moduleId, $input->post->get('data', '', 'raw'))) {
                $app->enqueueMessage(Text::_('MOD_ADMINNOTES_SAVED'), 'message');

                // Redirect back to view mode so the freshly stored note is shown
                $returnUrl = Uri::getInstance()->toString();
                $returnUrl = str_replace(['edit=1&', 'edit=1'], '', $returnUrl);
                $returnUrl = rtrim(rtrim($returnUrl, '&'), '?');

                $app->redirect($returnUrl);
            }
        }

        $data['canEdit'] = $canEdit;
        $data['helper']  = $helper;

        return $data;
    }
}
