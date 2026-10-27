<?php
if (!defined('CMS_VERSION')) exit;

/*
 * Easy Read uninstall cleanup.
 *
 * Easy Read stores its block value as a normal CMSMS page property rather than
 * in a module-owned data table.  On uninstall we therefore remove only page
 * properties that can be identified as Easy Read data, then remove uploaded
 * Easy Read images and module preferences/plugin registration.
 */

$db = $this->GetDb();

/**
 * Return true only for JSON that has the Easy Read repeatable-block shape.
 * This keeps the cleanup deliberately conservative so unrelated page content
 * is not removed merely because it happens to contain JSON.
 */
$isEasyReadValue = static function ($value) {
    $value = trim((string)$value);
    if ($value === '') return false;

    $rows = json_decode($value, true);
    if (!is_array($rows)) return false;

    // Empty values cannot be identified by their contents. They are handled
    // separately for the two block names used by Easy Read releases so far.
    if (!$rows) return false;

    foreach ($rows as $row) {
        if (!is_array($row)) return false;

        $id = isset($row['id']) ? (string)$row['id'] : '';
        if ($id === '' || strpos($id, 'er_') !== 0) return false;

        // Easy Read rows use these fields. Image may legitimately be empty.
        if (!array_key_exists('image', $row) ||
            !array_key_exists('alt', $row) ||
            !array_key_exists('content', $row)) {
            return false;
        }

        $image = str_replace('\\', '/', trim((string)$row['image']));
        if ($image !== '' && strpos($image, EasyRead::UPLOAD_SUBDIR.'/') !== 0) {
            return false;
        }
    }

    return true;
};

try {
    // Page content blocks are stored by CMSMS in content_props.  Remove known
    // Easy Read block names (including empty [] values), plus any other custom
    // block name whose saved value has the unmistakable Easy Read data shape.
    $sql = 'SELECT content_id, prop_name, content FROM '.CMS_DB_PREFIX.'content_props';
    $rows = $db->GetArray($sql);

    if (is_array($rows)) {
        $deleteSql = 'DELETE FROM '.CMS_DB_PREFIX.'content_props WHERE content_id = ? AND prop_name = ?';

        foreach ($rows as $row) {
            $propName = isset($row['prop_name']) ? (string)$row['prop_name'] : '';
            $content = isset($row['content']) ? (string)$row['content'] : '';

            $knownName = in_array($propName, array('easy_read', 'easy_read_data'), true);
            $knownEmptyValue = $knownName && trim($content) === '[]';
            $looksLikeEasyRead = $isEasyReadValue($content);

            if ($knownEmptyValue || $looksLikeEasyRead) {
                $db->Execute($deleteSql, array((int)$row['content_id'], $propName));
            }
        }
    }
} catch (Exception $e) {
    // Do not leave the module half-uninstalled because cleanup could not scan
    // page properties. Log the issue and continue with the remaining cleanup.
    audit('', $this->GetName(), 'Uninstall page-data cleanup warning: '.$e->getMessage());
}

// Remove every picture uploaded by Easy Read and then its upload directory.
$config = cms_config::get_instance();
$uploadDir = cms_join_path($config['uploads_path'], EasyRead::UPLOAD_SUBDIR);

$removeTree = static function ($path) use (&$removeTree) {
    if (!file_exists($path) && !is_link($path)) return;

    if (is_link($path) || is_file($path)) {
        @unlink($path);
        return;
    }

    $items = @scandir($path);
    if (is_array($items)) {
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $removeTree(cms_join_path($path, $item));
        }
    }

    @rmdir($path);
};

// Safety guard: only ever remove the module's own child directory of uploads.
$uploadsPath = rtrim(str_replace('\\', '/', (string)$config['uploads_path']), '/');
$targetPath = rtrim(str_replace('\\', '/', $uploadDir), '/');
if ($uploadsPath !== '' && $targetPath === $uploadsPath.'/'.EasyRead::UPLOAD_SUBDIR) {
    $removeTree($uploadDir);
}

// Remove all module preferences (there are currently none, but this keeps
// future versions clean) and unregister the {EasyRead ...} Smarty plugin.
$this->RemovePreference();
$this->RemoveSmartyPlugin();
$this->RemovePermission(EasyRead::VIEW_EXTENSION_PERMISSION);

// Tell CMSMS that page content changed so caches/search-aware code can refresh.
try {
    ContentOperations::get_instance()->SetContentModified();
} catch (Exception $e) {
    audit('', $this->GetName(), 'Uninstall cache refresh warning: '.$e->getMessage());
}
