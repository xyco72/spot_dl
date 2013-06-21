<?php 

if(count($argv) < 6){
	die("ERROR");
}
include 'config.php';

$dl_path_filename = $argv[1];
$search_for = $argv[2];
$path = $argv[3];

$track = array();
$data = array();
$data["title"] = $argv[4];
$data["artist"] = $argv[5];
$data["album"] = $argv[6];
$data["track"] = $argv[7];

if(isset($argv[8])) $data["year"] = substr($argv[8], 0, 4);

if(!file_exists($dl_path_filename) or re_download == 1){
	$track['dl'] = $mrtz->search_by_string($search_for,1);

	if(isset($track['dl'][0]['dl_link'])){
		$dl_status = $mrtz->download($track, $path, $dl_path_filename);
		$id3->set_tags($dl_path_filename,$data);
	}
}

if(rewrite_id3 == 1) $id3->set_tags($dl_path_filename,$data);
