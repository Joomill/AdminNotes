<?php
/*
 *  package: Admin Notes
 *  copyright: Copyright (c) 2025. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 2 or later
 *  link: https://www.joomill-extensions.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\CMS\Session\Session;
use Joomill\Module\Adminnotes\Administrator\Helper\AdminnotesHelper;

$app = Factory::getApplication();
$input = $app->input;

$forceEditor = $params->get('forceEditor',0, 'INT');
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

$moduleId = $module->id;

// Get data to display in the form
$data = AdminnotesHelper::getData($moduleId);

$params = new Registry($module->params);
$canEdit = AdminnotesHelper::canEdit($params);
$canPrint = $params->get('print',0, 'INT');
$canDownload = $params->get('download',0, 'INT');

$config = Factory::getConfig();
$editor = Editor::getInstance($params->get('editor', 'tinymce', 'STRING'));

if ($input->getMethod() == 'POST' && $input->get('task') == 'save' && $canEdit) {
	if (!Session::checkToken('post')) {
		$app->enqueueMessage(Text::_('MOD_ADMINNOTES_INVALIDTOKEN'), 'error');
	} else {
		$data = $input->post->get('data', '', 'raw');
		if (AdminnotesHelper::saveData($moduleId, $data)) {
			$app->enqueueMessage(Text::_('MOD_ADMINNOTES_SAVED'), 'message');
			Factory::getApplication()->redirect($saveURL);
		} else {
			$app->enqueueMessage(Text::_('MOD_ADMINNOTES_FAILED'), 'error');
		}
	}
}
?>

<div id="adminnotes-module" class="m-3">
	<?php if (($canEdit) && ($showEditor)) : ?>
        <div id="printArea" style="display: none;"><?php echo $module->content; ?></div>
        <form action="<?php echo $currentURL; ?>" method="post" id="AdminnotesForm">
			<?php echo $editor->display('data', $module->content, '100%', '500', '60', '20', false, null, null); ?>
            <div class="buttons d-flex">
                <div class="">
                <?php if ($canEdit) { ?>
                    <button type="submit" class="btn btn-success mt-3" id="save-button"><?php echo Text::_('JAPPLY'); ?></button>
                <?php } ?>
                </div>
                <div class="ms-auto">
                    <?php if ($canPrint) { ?>
                        <button type="button" class="btn btn-primary mt-3" onclick="printContent()"><?php echo Text::_('MOD_ADMINNOTES_PRINT'); ?></button>
                    <?php } ?>
                    <?php if ($canDownload) { ?>
                        <button type="button" class="btn btn-primary mt-3" onclick="downloadContent()"><?php echo Text::_('MOD_ADMINNOTES_DOWNLOAD'); ?></button>
                    <?php } ?>
                </div>
            </div>
            <?php echo HTMLHelper::_('form.token'); ?>
            <input type="hidden" name="task" value="save">
        </form>
	<?php else : ?>
        <div id="printArea"><?php echo $module->content; ?></div>

        <div class="buttons d-flex">
            <div class="">
	            <?php if ($canEdit) { ?>
                    <button class="btn btn-success mt-3" id="editButton" onclick="window.location.href='<?php echo $editURL; ?>'">Edit</button>
	            <?php } ?>
            </div>
            <div class="ms-auto">
				<?php if ($canPrint) { ?>
                    <button type="button" class="btn btn-primary mt-3" onclick="printContent()"><?php echo Text::_('MOD_ADMINNOTES_PRINT'); ?></button>
				<?php } ?>
				<?php if ($canDownload) { ?>
                    <button type="button" class="btn btn-primary mt-3" onclick="downloadContent()"><?php echo Text::_('MOD_ADMINNOTES_DOWNLOAD'); ?></button>
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
        printWindow.document.write('<html><head><title><?php echo $module->title; ?> - <?php echo $config->get('sitename');?> </title>');
        printWindow.document.write('</head><body >');
        printWindow.document.write(document.getElementById('printArea').innerHTML);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.print();
    }
    function downloadContent() {
        var txt = document.getElementById('printArea').innerText;
        var element = document.createElement('a');
        element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(txt));
        element.setAttribute('download', 'adminnotes.txt');

        element.style.display = 'none';
        document.body.appendChild(element);

        element.click();

        document.body.removeChild(element);
    }
</script>
