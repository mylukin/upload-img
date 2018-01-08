<?php
/**
 * Created by PhpStorm.
 * User: lukin
 * Date: 04/12/2017
 * Time: 15:14
 */
include __DIR__ . '/vendor/autoload.php';

use \OSS\OssClient;
use \OSS\Core\OssException;

$response = [
    'status' => 'ok',
];
header('Content-Type: application/json');

if (isset($_POST['filedata'])) {
    $file_data = $_POST['filedata'];
    $file_data_arr = explode(',', $file_data);
    $file_type = $file_data_arr[0];
    $file_data = $file_data_arr[1];
    if (preg_match('@data:image\/(\w+);base64@i', $file_type, $args)) {
        $file_ext = $args[1];
        $file_data = base64_decode($file_data);
    } else {
        $response = [
            'status' => 'err',
            'message' => '不允许上传此类文件',
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
$folder = trim(file_get_contents(__DIR__.'/.path'));
try {
    $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
    $bucket = "mogo-static-files";
    $object = sprintf('%s/%s.%s', $folder, md5($file_data), $file_ext);
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