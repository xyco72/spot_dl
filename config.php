<?php 

define('debug', '0');
define('info','1');
define('dev','0');


define('re_download', '0');
define('rewrite_id3','1');
define('use_subprogress','0');
define('use_stream_dl','0');
define('workfolder','__DIR__');
define('download_folder',__DIR__.'/Downloads');
define('playlist_muscic_location','/home/xyco/Musik/');
define('stop_file','/temp/stop.txt');
define('curl_cookie',"temp/curl_cookie");
define('curl_referer',"Mozilla/5.0 (Windows; U; Windows NT 6.0; de; rv:1.9.0.9) Gecko/2009040821 Firefox/3.0.9 (.NET CLR 3.5.30729)");

include 'class.spotify.php';
include 'class.mrtzcmp3.php';
include 'class.internet.php';
include 'class.id3.php';

$sp = new spotify;
$mrtz = new mrtzcmp3;
$inet = new WebBrowser;
$id3 = new id3;

$max_progress = 20;

// vll unnötig
$stop_file = '/home/xyco/Arbeitsfläche/spotify/temp/stop.txt';

// test