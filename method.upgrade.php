<?php
if (!defined('CMS_VERSION')) exit;

// No database schema is used. Re-register the frontend module plugin so an
// upgrade also repairs an older/missing registration.
$this->RegisterModulePlugin(true);
$this->CreatePermission(EasyRead::VIEW_EXTENSION_PERMISSION, 'View Easy Read in Extensions');

$this->InstallDesignTemplate();
