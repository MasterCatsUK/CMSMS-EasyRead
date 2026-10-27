<?php
if (!defined('CMS_VERSION')) exit;

if (!$this->VisibleToAdminUser()) exit;

// Read-only guidance shown from Extensions > Easy Read.
echo $this->GetHelp();
