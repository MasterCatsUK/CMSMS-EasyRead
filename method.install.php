<?php
if (!defined('CMS_VERSION')) exit;

$config = cms_config::get_instance();
$dir = cms_join_path($config['uploads_path'], EasyRead::UPLOAD_SUBDIR);
if (!is_dir($dir)) {
    @mkdir($dir, 0775, true);
}

// Register {EasyRead ...} for the optional frontend decoder/default renderer.
$this->RegisterModulePlugin(true);
$this->CreatePermission(EasyRead::VIEW_EXTENSION_PERMISSION, 'View Easy Read in Extensions');

$this->InstallDesignTemplate();
