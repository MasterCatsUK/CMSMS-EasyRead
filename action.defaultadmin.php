<?php
/** SPDX-License-Identifier: GPL-2.0-or-later. See the module docs/LICENSE file. */
if (!defined('CMS_VERSION')) exit;

if (!$this->CheckPermission(EasyRead::VIEW_EXTENSION_PERMISSION)) exit;

// Read-only guidance shown from Extensions > Easy Read.
echo $this->GetHelp();
