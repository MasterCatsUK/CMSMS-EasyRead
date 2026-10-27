<?php
if (!defined('CMS_VERSION')) exit;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$reply = array('success' => false);

if (strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '') !== 'POST') {
    http_response_code(405);
    $reply['error'] = 'Easy Read: the upload request must use POST.';
    echo json_encode($reply);
    exit;
}

// admin/moduleinterface.php has already validated both the logged-in admin
// session and the CMSMS secure request key. Keep an explicit session check here
// as defence in depth if the action is ever invoked another way.
if (!get_userid(false)) {
    http_response_code(403);
    $reply['error'] = 'Easy Read: your CMSMS session has expired. Reload the page and try again.';
    echo json_encode($reply);
    exit;
}

$file = null;
if (isset($_FILES[$id.'file']) && is_array($_FILES[$id.'file'])) {
    $file = $_FILES[$id.'file'];
} else {
    // Be tolerant of proxies or CMSMS versions which normalise the action id.
    foreach ($_FILES as $candidate) {
        if (is_array($candidate)) {
            $file = $candidate;
            break;
        }
    }
}

$imageUrl = isset($_POST[$id.'image_url']) ? $_POST[$id.'image_url'] : (isset($_POST['eruploadimage_url']) ? $_POST['eruploadimage_url'] : '');
if ($file) {
    $result = $this->handleImageUpload($file);
} elseif (is_string($imageUrl) && trim($imageUrl) !== '') {
    require_once __DIR__.'/lib/import_image.php';
    $result = easyread_import_image(trim($imageUrl));
} else {
    $result = array('error' => 'Choose an image file or paste a direct image link.');
}

if (isset($result['error'])) {
    http_response_code(400);
    $reply['error'] = $result['error'];
    echo json_encode($reply);
    exit;
}

$image = isset($result['image']) ? $result['image'] : '';
$reply = array(
    'success' => true,
    'image' => $image,
    'url' => $this->storedImageUrl($image),
);

echo json_encode($reply, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
exit;
