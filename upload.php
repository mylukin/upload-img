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

use Intervention\Image\ImageManager;

$response = [
    'status' => 'ok',
];
header('Content-Type: application/json');

$file_name = '';
if (isset($_POST['filedata'])) {
    $file_data = $_POST['filedata'];
    $src_file_name = $_POST['file_name'];
    $src_file_ext = pathinfo($src_file_name, PATHINFO_EXTENSION);
    $file_data_arr = explode(',', $file_data);
    $file_type = $file_data_arr[0];
    $file_data = $file_data_arr[1];
    if (preg_match('@data:' . preg_quote($allow_type, '@') . '([\w\.\-]+);base64@i', $file_type, $args) || isset($file_types[$src_file_ext])) {
        if (isset($args[1])) {
            $file_ext = $args[1];
            if (isset($file_types[$file_ext])) {
                $file_ext = $file_types[$file_ext];
            }
        } elseif (isset($file_types[$src_file_ext])) {
            $file_ext = $src_file_ext;
        } else {
            $file_ext = '';
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
        // android包文件名不变
        if ($file_ext == 'apk' || $file_ext == 'json') {
            $file_name = basename($src_file_name, '.' . $file_ext);
        }

        if (strlen($file_data) > $allow_size) {
            $response = [
                'status' => 'err',
                'message' => '不允许上传大于' . nice_number($allow_size) . '的文件',
                'size_upload' => strlen($file_data),
                'file_type' => $file_ext,
            ];
            exit(json_encode($response));
        }
        $folder = trim($up_folder);
        $full_path = sprintf('%s/%s.%s', $folder, $file_name, $file_ext);
        // 上传成功
        mkdir($folder);
        file_put_contents($full_path, $file_data);
        $response['path'] = $full_path;
        $response['size_upload'] = strlen($file_data);
        $response['file_type'] = $src_file_ext;

        exit(json_encode($response));

    } else {
        $response = [
            'status' => 'err',
            'message' => '不允许上传此类文件',
            'file_type' => $src_file_ext,
            'size_upload' => strlen($file_data)
        ];
        exit(json_encode($response));
    }

} else {
    $response = [
        'status' => 'err',
        'message' => 'no data',
    ];
    exit(json_encode($response));
}


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