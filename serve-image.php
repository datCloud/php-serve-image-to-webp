<?php

function kill_process(){
	header("HTTP/1.0 404 Not Found");
	die();
}

function validate_and_sanitize($q, $a){
	$filter_result = filter_var_array($q, $a);
	if(in_array(false, $filter_result)) kill_process();
	return $filter_result;
}

function filter_default_dimensions(&$w, &$h){
	if($w < 0 && $h < 0) kill_process();
	elseif ($w < 0) $w = null;
	elseif ($h < 0) $h = null;
}

function is_valid_image(&$img_path, &$img_mime){
	$img_path = './imagens/'.end(explode('/imagens/', $img_path));
	$img_mime = mime_content_type($img_path);
	if(!file_exists($img_path) || strpos($img_mime, 'image') === false) kill_process();
}

function is_cached($cache_img){
	return file_exists($cache_img);
}

function create_image($src_img, $dest_img){
	$temp_image = imagecreatefromstring(file_get_contents($src_img));
    imagepalettetotruecolor($temp_image);
    imagealphablending($temp_image, true);
    imagesavealpha($temp_image, true);
    imagewebp($temp_image, $dest_img, 80);
    imagedestroy($temp_image);
}

function src_path_to_cache($img_path){
	return './thumbs/'.end(explode('/imagens/', pathinfo($img_path, PATHINFO_DIRNAME)));
}

function create_cache_dir($cache_img_path, $img_filename){
    mkdir($cache_img_path, 0755, true);
}

$supported_mime = [
	'image/jpeg' => 'jpg',
	'image/png' => 'png',
	'image/webp' => 'webp'
];

define('SUPPORTED_MIME', $supported_mime);
define('CACHE_PATH', './thumbs/');

$query_params = [
    'url' => $_GET['src'],
    'width' => $_GET['w'],
    'height' => $_GET['h'],
    'zoom_crop' => $_GET['zc']
];

$filter_args = [
	'url' => FILTER_VALIDATE_URL,
	'width' => [
		'filter' => FILTER_VALIDATE_INT,
		'options' => [
			'default' => -1,
			'min_range' => 10,
			'max_range' => 1280
		]
	],
	'height' => [
		'filter' => FILTER_VALIDATE_INT,
		'options' => [
			'default' => -1,
			'min_range' => 10,
			'max_range' => 1280
		]
	],
	'zoom_crop' => [
		'filter' => FILTER_VALIDATE_INT,
		'options' => [
			'default' => 1,
			'min_range' => 1,
			'max_range' => 3
		]
	]
];

$img_info = validate_and_sanitize($query_params, $filter_args);
filter_default_dimensions($img_info['width'], $img_info['height']);
is_valid_image($img_info['url'], $img_info['mime']);
$img_info['filename'] = basename($img_info['url']);
$img_info['extension'] = pathinfo($img_info['url'], PATHINFO_EXTENSION);

$img_info['cached_path'] = src_path_to_cache($img_info['url']);
$img_info['cached_file'] = $img_info['cached_path'].'/'.str_replace($img_info['extension'], 'webp', $img_info['filename']);
$img_info['is_cached'] = is_cached($img_info['cached_file']);

if(!$img_info['is_cached']){
	create_cache_dir($img_info['cached_path'], $img_info['filename']);
	create_image($img_info['url'], $img_info['cached_file']);
}

header('Content-Type: image/webp');
readfile($img_info['cached_file']);
// die(
// 	json_encode(
// 		[
// 			'img_info' => $img_info
// 		]
// 	)
// );

?>
