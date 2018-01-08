<?php
/**
 * Created by PhpStorm.
 * User: lukin
 * Date: 04/12/2017
 * Time: 15:14
 */
include __DIR__ . '/vendor/autoload.php';

include __DIR__ . '/config.php';


if (!isset($allow_type)) {
    $allow_type = 'image/';
}

if (!isset($allow_size)) {
    $allow_size = 1 * 1024 * 1024;
}

if (!isset($up_folder)) {
    $up_folder = '';
}

if (!isset($file_types)) {
    $file_types = [];
}

use \OSS\OssClient;
use \OSS\Core\OssException;
use Intervention\Image\ImageManager;

$response = [
    'status' => 'ok',
];
header('Content-Type: application/json');

$file_name = '';
if (isset($_POST['filedata'])) {
    $file_data = $_POST['filedata'];
    $file_data_arr = explode(',', $file_data);
    $file_type = $file_data_arr[0];
    $file_data = $file_data_arr[1];
    if (preg_match('@data:' . preg_quote($allow_type, '@') . '(\w+);base64@i', $file_type, $args)) {
        $file_ext = $args[1];
        if (isset($file_types[$file_ext])) {
            $file_ext = $file_types[$file_ext];
        }

        $file_data = base64_decode($file_data);
        $file_name = md5($file_data);
        if ($allow_type == 'image/') {
            $file_path = sprintf('/tmp/%s_zip.%s', $file_name, $file_ext);
            $manager = new ImageManager(array('driver' => 'imagick'));
            $manager->make($file_data)->save($file_path, 90);
            $file_data = file_get_contents($file_path);
            $file_name = md5($file_data);
            @unlink($file_path);
        }
        if (strlen($file_data) > $allow_size) {
            $response = [
                'status' => 'err',
                'message' => '不允许上传大于' . nice_number($allow_size) . '的文件',
                'size_upload' => strlen($file_data)
            ];
            echo json_encode($response);
            exit;
        }

    } else {
        $response = [
            'status' => 'err',
            'message' => '不允许上传此类文件' . $file_type,
            'size_upload' => strlen($file_data)
        ];
        echo json_encode($response);
        exit;
    }

} else {
    $response = [
        'status' => 'err',
        'message' => 'no data',
    ];
    echo json_encode($response);
    exit;
}


$accessKeyId = "LTAItYZgeQvwSThi";
$accessKeySecret = "tFtDfNRuDEKJRskikVRTtpNaclS1PL";
$endpoint = "oss-cn-hangzhou.aliyuncs.com";
$folder = trim($up_folder);
try {
    $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
    $bucket = "mogo-static-files";
    $object = sprintf('%s/%s.%s', $folder, $file_name, $file_ext);
    $content = $file_data;
    try {
        $file_info = $ossClient->putObject($bucket, $object, $content);
        $response['data'] = $file_info;
        $response['path'] = $object;

    } catch (OssException $e) {
        $response = [
            'status' => 'err',
            'message' => $e->getMessage(),
        ];
        echo json_encode($response);
        exit;
    }
} catch (OssException $e) {
    $response = [
        'status' => 'err',
        'message' => $e->getMessage(),
    ];
    echo json_encode($response);
    exit;
}


echo json_encode($response);


/**
 * 漂亮的数字
 *
 * @param $n
 * @return bool|string
 */
function nice_number($n)
{
    // first strip any formatting;
    $n = (0 + str_replace(",", "", $n));

    // is this a number?
    if (!is_numeric($n)) return false;

    // now filter it;
    if ($n > 1000000000000) return round(($n / 1000000000000), 1) . 'T';
    else if ($n > 1000000000) return round(($n / 1000000000), 1) . 'B';
    else if ($n > 1000000) return round(($n / 1000000), 1) . 'M';
    else if ($n > 1000) return round(($n / 1000), 1) . 'K';

    return number_format($n);
}