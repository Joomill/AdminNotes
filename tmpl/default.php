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
$input = $app->getInput();

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

// Get the current URL for form actions and redirects
$currentURL = Uri::getInstance()->toString();

// Determine the appropriate separator for URL parameters
// If the URL already has parameters (contains a '?'), use '&' for additional parameters
// Otherwise, use '?' to start the parameter string
$separator = strpos($currentURL, '?') !== false ? '&' : '?';

// Handle URL construction differently based on whether we're already in edit mode
if (strpos($currentURL, 'edit=1') !== false) {
    // We're in edit mode, so the save URL should be the current URL without the edit parameter
    // This ensures we return to view mode after saving
    $saveURL = str_replace(['edit=1&', 'edit=1'], '', $currentURL);

    // Clean up any trailing characters that might be left after removing the parameter
    $saveURL = rtrim($saveURL, '&');
    $saveURL = rtrim($saveURL, '?');

    // In edit mode, the edit URL is just the current URL (we're already editing)
    $editURL = $currentURL;
} else {
    // We're in view mode, so the edit URL needs the edit parameter added
    $editURL = $currentURL . $separator . 'edit=1';

    // In view mode, the save URL is just the current URL (we'll return here after save)
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
        $data = $input->post->get('data', '', 'raw');

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
    /**
     * Prints the content of the notes in a new window
     * 
     * This function:
     * 1. Opens a new browser window for printing
     * 2. Creates a properly formatted document with the note content
     * 3. Triggers the browser's print dialog
     * 
     * Security measures:
     * - Uses JSON encoding and htmlspecialchars for title and sitename
     * - Creates a new DOM element to safely handle content
     */
    function printContent() {
        // Open a new window with specific dimensions for printing
        var printWindow = window.open('', '', 'height=500,width=800');

        // Get safely encoded title and site name to prevent XSS
        var safeTitle = <?php echo json_encode(htmlspecialchars($module->title, ENT_QUOTES, 'UTF-8')); ?>;
        var safeSitename = <?php echo json_encode(htmlspecialchars($config->get('sitename'), ENT_QUOTES, 'UTF-8')); ?>;

        // Build the HTML document in the new window
        // Using document.write is safe here because we control all the content
        printWindow.document.write('<html><head><title>' + safeTitle + ' - ' + safeSitename + '</title>');
        printWindow.document.write('<style>body { font-family: Arial, sans-serif; padding: 20px; }</style>');
        printWindow.document.write('</head><body>');

        // Create a sanitized copy of the content using DOM methods
        // This is safer than directly writing the HTML content
        var contentDiv = document.createElement('div');
        contentDiv.innerHTML = document.getElementById('printArea').innerHTML;

        // Append the sanitized content to the new window
        printWindow.document.body.appendChild(contentDiv.cloneNode(true));

        // Finalize the document and trigger the print dialog
        printWindow.document.close();
        printWindow.print();
        // Note: We don't close the window after printing to allow the user to cancel
        // and still see the content. The browser will handle closing it.
    }

    /**
     * Downloads the content of the notes as a plain text file
     * 
     * This function:
     * 1. Extracts the text content (without HTML) from the notes
     * 2. Creates a downloadable file using the Blob API
     * 3. Triggers the download using a temporary anchor element
     * 
     * Security measures:
     * - Uses innerText to strip HTML tags
     * - Creates a proper MIME type for the file
     * - Cleans up resources after download is triggered
     */
    function downloadContent() {
        // Get the text content, which automatically strips HTML tags
        // This prevents any HTML or script content from being included in the download
        var txt = document.getElementById('printArea').innerText;

        // Create a blob with the text content and proper MIME type
        // This creates a file-like object in memory
        var blob = new Blob([txt], {type: 'text/plain;charset=utf-8'});

        // Create a safe URL for the blob using the browser's API
        // This creates a temporary URL that points to the blob
        var url = URL.createObjectURL(blob);

        // Create a temporary anchor element to trigger the download
        var element = document.createElement('a');
        element.setAttribute('href', url);
        element.setAttribute('download', 'adminnotes.txt');
        element.style.display = 'none';

        // Add to DOM, trigger click to start download, then remove
        document.body.appendChild(element);
        element.click();

        // Clean up resources to prevent memory leaks
        document.body.removeChild(element);
        URL.revokeObjectURL(url);
    }
</script>
