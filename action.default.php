<?php
if (!defined('CMS_VERSION')) exit;

$data = isset($params['data']) ? $params['data'] : '';
$blocks = $this->prepareFrontendItems($data);

/*
 * When to= is supplied, assign the decoded array to the current Smarty
 * template scope and produce no frontend HTML.
 */
if (isset($params['to']) && trim((string)$params['to']) !== '') {
    $name = preg_replace('/[^A-Za-z0-9_]/', '', trim((string)$params['to']));
    if ($name !== '') {
        $smarty->assign($name, $blocks);
    }
    return '';
}

// Data-only mode above stays compatible with existing page templates.
$templateName = isset($params['template']) ? trim((string)$params['template']) : '';
if ($templateName !== '') {
    $layout = CmsLayoutTemplate::load($templateName);
} else {
    try {
        $layout = CmsLayoutTemplate::load_dflt_by_type('EasyRead::summary');
    } catch (CmsDataNotFoundException $e) {
        $layout = null;
    }
}
if (!$layout) {
    // Allows the old renderer to work until the module upgrade is run.
    $smarty->assign('easyread_blocks', $blocks);
    echo $this->ProcessTemplate('default.tpl');
    return;
}
$tpl = $smarty->CreateTemplate($this->GetTemplateResource($layout->get_name()), null, null, $smarty);
$tpl->assign('easyread_blocks', $blocks);
echo $tpl->fetch();
