<?php
/** SPDX-License-Identifier: GPL-2.0-or-later. See the module docs/LICENSE file. */
if (!defined('CMS_VERSION')) exit;

/** Download one public image; never follow redirects or access internal services. */
function easyread_import_image($url)
{
    $error = function ($message) { return array('error' => $message); };
    if (!function_exists('curl_init')) return $error('Image import needs PHP cURL enabled on the server. You can still upload a file.');
    if (strlen($url) > 4096 || !filter_var($url, FILTER_VALIDATE_URL)) return $error('Please enter a valid direct image link.');
    $parts = parse_url($url);
    if (!$parts || !isset($parts['scheme'], $parts['host']) || !in_array(strtolower($parts['scheme']), array('http', 'https'), true)
        || isset($parts['user']) || isset($parts['pass'])) return $error('Use a public http:// or https:// image link without login details.');
    $scheme = strtolower($parts['scheme']);
    $port = $scheme === 'https' ? 443 : 80;
    if (isset($parts['port']) && (int)$parts['port'] !== $port) return $error('This image link uses an unsupported port.');
    $host = strtolower($parts['host']);
    // Resolve once, validate every address, then pin cURL to the validated address.
    if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) $addresses = array($host);
    elseif (preg_match('/^[a-z0-9][a-z0-9.-]*\.[a-z]{2,}$/i', $host)) $addresses = gethostbynamel($host);
    else return $error('Use a public website image link.');
    if (!$addresses) return $error('The image website could not be found.');
    foreach ($addresses as $address) {
        if (!filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
            || strpos($address, '169.254.') === 0
            || (ip2long($address) >= ip2long('100.64.0.0') && ip2long($address) <= ip2long('100.127.255.255'))) {
            return $error('Only images from public websites can be imported.');
        }
    }
    $body = '';
    $tooLarge = false;
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_PROXY => '',
        CURLOPT_RESOLVE => array($host.':'.$port.':'.$addresses[0]),
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_USERAGENT => 'EasyRead/0.1.18 image import',
        CURLOPT_WRITEFUNCTION => function ($curl, $chunk) use (&$body, &$tooLarge) {
            if (strlen($body) + strlen($chunk) > EasyRead::MAX_UPLOAD_BYTES) {
                $tooLarge = true;
                return 0;
            }
            $body .= $chunk;
            return strlen($chunk);
        },
    ));
    $ok = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($tooLarge) return $error('Pictures must be smaller than 8 MB.');
    if ($status >= 300 && $status < 400) return $error('This link redirects. Open it in your browser and copy the final direct image link, or upload the downloaded image.');
    if ($ok === false || $status !== 200 || $body === '') return $error('The image could not be downloaded. Check the direct link or upload the image instead.');
    $info = @getimagesizefromstring($body);
    $types = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp');
    if (!$info || empty($info['mime']) || !isset($types[$info['mime']])) return $error('That link is not a valid JPG, PNG, GIF or WebP image. Use the image link, not a webpage link.');
    $config = cms_config::get_instance();
    $dir = cms_join_path($config['uploads_path'], EasyRead::UPLOAD_SUBDIR);
    if ((!is_dir($dir) && !@mkdir($dir, 0775, true)) || !is_writable($dir)) return $error('CMSMS cannot write to the EasyRead uploads folder.');
    $name = 'import-'.bin2hex(random_bytes(12)).'.'.$types[$info['mime']];
    $path = cms_join_path($dir, $name);
    $handle = @fopen($path, 'xb');
    if (!$handle) return $error('The imported picture could not be saved.');
    $written = fwrite($handle, $body);
    fclose($handle);
    if ($written !== strlen($body)) {
        @unlink($path);
        return $error('The imported picture could not be saved completely.');
    }
    @chmod($path, 0644);
    return array('image' => EasyRead::UPLOAD_SUBDIR.'/'.$name);
}
