<?php
/**
 * Created by PhpStorm.
 * User: lukin
 * Date: 08/01/2018
 * Time: 13:11
 */

// 图片
$allow_type = 'image/';
// 1M
$allow_size = 1 * 1024 * 1024;
// 文件夹名
$up_folder = 'appimg';
// 文件类型对应的扩展名
$file_types = [
    'vnd.android.package-archive' => 'apk',
    'apk' => true,
];