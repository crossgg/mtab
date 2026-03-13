<?php
//程序版本号，请勿修改
const app_version = '2.9.5';
//程序内部更新版本代码，请勿修改
const app_version_code = 295;
// 应用公共文件
function validateEmail($email): bool
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    } else {
        return true;
    }
}

function uuid(): string
{
    $chars = md5(uniqid(mt_rand(), true));
    return substr($chars, 0, 8) . '-'
        . substr($chars, 8, 4) . '-'
        . substr($chars, 12, 4) . '-'
        . substr($chars, 16, 4) . '-'
        . substr($chars, 20, 12);
}

function renderToken($t = 'tab'): string
{
    $s = uuid() . strval(time()) . $t;
    return md5($s);
}

function joinPath($path1, $path2='')
{
    return preg_replace("#/+/#", "/", $path1 . $path2);
}

function getRealIp(): string
{
    $ip1 = request()->header('x-forwarded-for', false);
    if ($ip1) {
        $arr = explode(",", $ip1);
        if (count($arr) > 0) {
            return trim($arr[0]);
        }
    }
    return request()->ip();
}

function plugins_path($path = ''): string
{
    if (mb_strlen($path) > 0) {
        if (strpos($path, "/") == 0) {
            return $_ENV['plugins_dir_name'] . $path;
        }
        return $_ENV['plugins_dir_name'] . '/' . $path;
    }
    return $_ENV['plugins_dir_name'] . "/";
}

function is_demo_mode($is_exit = false)
{
    if (env('demo_mode')) {
        if ($is_exit) {
            json(["msg" => "演示模式，部分功能受限,禁止更新或删除！", "code" => 0])->send();
            exit();
        }
        return true;
    }
    return false;
}

function modifyImageUrls($htmlContent, $newBaseUrl): string
{
    try {
        $dom = new DOMDocument();
        $htmlContent = mb_convert_encoding($htmlContent, 'HTML-ENTITIES', 'UTF-8');
        libxml_use_internal_errors(true);
        $wrappedContent = '<div>' . $htmlContent . '</div>';
        $dom->loadHTML($wrappedContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $img) {
            $oldSrc = $img->getAttribute('src');
            if (!preg_match('/^http/', $oldSrc)) {
                $newSrc = $newBaseUrl . joinPath('/',$oldSrc);
                $img->setAttribute('src', $newSrc);
            }
        }

        // 返回修改后的 HTML，去掉根节点
        $newHtmlContent = '';
        foreach ($dom->documentElement->childNodes as $child) {
            $newHtmlContent .= $dom->saveHTML($child);
        }
        return $newHtmlContent;
    } catch (Exception $e) {
        return $htmlContent;
    }
}

function removeImagesUrls($htmlContent, $newBaseUrl)
{
    try {
        $dom = new DOMDocument();
        $htmlContent = mb_convert_encoding($htmlContent, 'HTML-ENTITIES', 'UTF-8');
        libxml_use_internal_errors(true);
        $wrappedContent = '<div>' . $htmlContent . '</div>';
        $dom->loadHTML($wrappedContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $domain = $newBaseUrl;
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $img) {
            $oldSrc = $img->getAttribute('src');
            $newSrc = str_replace($domain, '', $oldSrc);
            $img->setAttribute('src', $newSrc);
        }

        // 返回修改后的 HTML，去掉根节点
        $newHtmlContent = '';
        foreach ($dom->documentElement->childNodes as $child) {
            $newHtmlContent .= $dom->saveHTML($child);
        }
        return $newHtmlContent;
    } catch (Exception $e) {
        return $htmlContent;
    }
}

function getFileExtByContent($path): string
{
    if (!file_exists($path) || !is_readable($path)) {
        throw new InvalidArgumentException("File does not exist or is not readable: {$path}");
    }

    $info = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($info, $path);
    finfo_close($info);

    $supportedMimeTypes = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/svg+xml' => 'svg',
        'application/pdf' => 'pdf',
        'text/plain' => 'txt',
        // 可继续扩展其他 MIME 类型
    ];

    return $supportedMimeTypes[$mimeType] ?? 'unknown';
}
