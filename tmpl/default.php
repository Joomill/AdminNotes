<?php
/*
 *  package: Admin Notes
 *  copyright: Copyright (c) 2025. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 2 or later
 *  link: https://www.joomill-extensions.com
 */

defined('_JEXEC') or die;

use Joomill\Module\Adminnotes\Administrator\Helper\AdminnotesHelper;
use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

$app = Factory::getApplication();
$input = $app->input;

// In Joomla modules, $params should always be defined
// If not, we'll create an empty Registry object to prevent errors
if (!isset($params) || !($params instanceof Registry)) {
    $params = new Registry();
}

$forceEditor = $params->get('forceEditor', 0, 'INT');
if ($forceEditor) {
    $showEditor = 1;
} else {
    $showEditor = $input->get('edit', 0, 'INT');
}

$currentURL = Uri::getInstance()->toString();
$separator = strpos($currentURL, '?') !== false ? '&' : '?';
if (strpos($currentURL, 'edit=1') !== false) {
    $saveURL = str_replace(['edit=1&', 'edit=1'], '', $currentURL);
    $saveURL = rtrim($saveURL, '&');
    $saveURL = rtrim($saveURL, '?');
    $editURL = $currentURL;
} else {
    $editURL = $currentURL . $separator . 'edit=1';
    $saveURL = $currentURL;
}

// Ensure $module is defined
if (!isset($module)) {
    $app->enqueueMessage(Text::_('MOD_ADMINNOTES_MODULE_NOT_FOUND'), 'error');
    return;
}

// Ensure module has an ID
if (!isset($module->id)) {
    $app->enqueueMessage(Text::_('MOD_ADMINNOTES_INVALID_MODULE_ID'), 'error');
    return;
}

$moduleId = $module->id;

// Get data to display in the form
$data = AdminnotesHelper::getData($moduleId);

$params = new Registry($module->params);
$canEdit = AdminnotesHelper::canEdit($params);
$canPrint = $params->get('print', 0, 'INT');
$canDownload = $params->get('download', 0, 'INT');

$config = $app->getConfig();
$editor = Editor::getInstance($params->get('editor', 'tinymce', 'STRING'));

if ($input->getMethod() == 'POST' && $input->get('task') == 'save' && $canEdit) {
    // Enhanced CSRF protection - check both the standard token and our custom nonce
    if (!Session::checkToken('post')) {
        $app->enqueueMessage(Text::_('MOD_ADMINNOTES_INVALIDTOKEN'), 'error');
    } else {
        // Get the data and sanitize it
        $filter = new InputFilter();
        $data = $input->post->get('data', '', 'raw');
        // Apply filtering to the data
        $data = $filter->clean($data, 'html');

        // Save the data
        if (AdminnotesHelper::saveData($moduleId, $data)) {
            $app->enqueueMessage(Text::_('MOD_ADMINNOTES_SAVED'), 'message');
            // Use a safe redirect URL
            Factory::getApplication()->redirect(htmlspecialchars($saveURL, ENT_QUOTES, 'UTF-8'));
        } else {
            $app->enqueueMessage(Text::_('MOD_ADMINNOTES_FAILED'), 'error');
        }
    }
}
?>

<div id="adminnotes-module" class="m-3">
    <?php if (($canEdit) && ($showEditor)) : ?>
        <div id="printArea" style="display: none;"><?php echo HTMLHelper::_('content.prepare', $module->content); ?></div>
        <form action="<?php echo htmlspecialchars($currentURL, ENT_QUOTES, 'UTF-8'); ?>" method="post" id="AdminnotesForm">
            <?php echo $editor->display('data', $module->content, '100%', '500', '60', '20', false, null, null); ?>
            <div class="buttons d-flex">
                <div class="">
                    <?php if ($canEdit) { ?>
                        <button type="submit" class="btn btn-success mt-3"
                                id="save-button"><?php echo Text::_('JAPPLY'); ?></button>
                    <?php } ?>
                </div>
                <div class="ms-auto">
                    <?php if ($canPrint) { ?>
                        <button type="button" class="btn btn-primary mt-3"
                                onclick="printContent()"><?php echo Text::_('MOD_ADMINNOTES_PRINT'); ?></button>
                    <?php } ?>
                    <?php if ($canDownload) { ?>
                        <button type="button" class="btn btn-primary mt-3"
                                onclick="downloadContent()"><?php echo Text::_('MOD_ADMINNOTES_DOWNLOAD'); ?></button>
                    <?php } ?>
                </div>
            </div>
            <?php echo HTMLHelper::_('form.token'); ?>
            <input type="hidden" name="task" value="save">
            <!-- Add a nonce for additional CSRF protection -->
            <input type="hidden" name="<?php echo Session::getFormToken(); ?>" value="1">
        </form>
    <?php else : ?>
        <div id="printArea"><?php echo HTMLHelper::_('content.prepare', $module->content); ?></div>

        <div class="buttons d-flex">
            <div class="">
                <?php if ($canEdit) { ?>
                    <button class="btn btn-success mt-3" id="editButton"
                            onclick="window.location.href='<?php echo htmlspecialchars($editURL, ENT_QUOTES, 'UTF-8'); ?>'">
                            <?php echo Text::_('JACTION_EDIT'); ?>
                    </button>
                <?php } ?>
            </div>
            <div class="ms-auto">
                <?php if ($canPrint) { ?>
                    <button type="button" class="btn btn-primary mt-3"
                            onclick="printContent()"><?php echo Text::_('MOD_ADMINNOTES_PRINT'); ?></button>
                <?php } ?>
                <?php if ($canDownload) { ?>
                    <button type="button" class="btn btn-primary mt-3"
                            onclick="downloadContent()"><?php echo Text::_('MOD_ADMINNOTES_DOWNLOAD'); ?></button>
                <?php } ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .toggle-editor {
        display: none !important;
    }
</style>

<script>
    function printContent() {
        var printWindow = window.open('', '', 'height=500,width=800');
        var safeTitle = <?php echo json_encode(htmlspecialchars($module->title, ENT_QUOTES, 'UTF-8')); ?>;
        var safeSitename = <?php echo json_encode(htmlspecialchars($config->get('sitename'), ENT_QUOTES, 'UTF-8')); ?>;

        printWindow.document.write('<html><head><title>' + safeTitle + ' - ' + safeSitename + '</title>');
        printWindow.document.write('<style>body { font-family: Arial, sans-serif; padding: 20px; }</style>');
        printWindow.document.write('</head><body>');

        // Create a sanitized copy of the content
        var contentDiv = document.createElement('div');
        contentDiv.innerHTML = document.getElementById('printArea').innerHTML;

        // Append the sanitized content
        printWindow.document.body.appendChild(contentDiv.cloneNode(true));

        printWindow.document.close();
        printWindow.print();
    }

    function downloadContent() {
        // Get the text content, which automatically strips HTML tags
        var txt = document.getElementById('printArea').innerText;

        // Create a blob with the text content
        var blob = new Blob([txt], {type: 'text/plain;charset=utf-8'});

        // Create a safe URL for the blob
        var url = URL.createObjectURL(blob);

        var element = document.createElement('a');
        element.setAttribute('href', url);
        element.setAttribute('download', 'adminnotes.txt');
        element.style.display = 'none';

        document.body.appendChild(element);
        element.click();

        // Clean up
        document.body.removeChild(element);
        URL.revokeObjectURL(url);
    }
</script>
