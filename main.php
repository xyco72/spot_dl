<?php 

session_start();
error_reporting(E_ALL);


include 'config.php';

// Clean
exec('find '.download_folder.' -type f -size 0b -exec rm -f {} \;');
exec('find '.download_folder.' -depth -type d -empty -exec rmdir {} \;');
exec('find '.download_folder.' -type f -size 1M -exec rm {} \;');


if(file_exists(workfolder.stop_file)) exec("rm ".workfolder.stop_file);

function check_progress(){
	global $max_progress;

	if(file_exists(workfolder.stop_file)) die("Limit erreicht");

	$befehl = "ps aux | grep 'php5 sub_progress.php' | grep -v 'watch' | grep -v 'grep php5 '";

	exec($befehl,$return);
	if(count($return) > $max_progress) return "1";
	else return "0";
}


if(info == 1 || debug == 1){
	$count_not_found = 0;
	$count_dl = 0;
	$count_f_exists = 0;
}

$playlists = $sp->get_spotify_playlists();

foreach($playlists as $item){

	$playlist_content = NULL;
	$playlist_ar = NULL;

	if(isset($item['title'])){
		$playlist_titel = $item['title'];

		foreach ($item['tracks'] as $track){
			$data = array('note' => '');
			$playlist_inc = 0;

			if(isset($track['artist']) && isset($track['track_titel']) && isset($track['more_infos']['album']) ){

					
					
				$search_for = $track['artist']." - ".$track['track_titel'];




				/*
				 * Pfad zusammensetzen
				*/
				// FIXME ist es überhaupt mp3 ???

				$filename = $track['track_titel'].".mp3";
				//$path = dirname(__FILE__)."/Downloads/".$track['artist']."/".$track['more_infos']['album'];
				$path = download_folder."/".$track['artist']."/".$track['more_infos']['album'];
				$dl_path_filename = $path."/".$filename;


				// FIXME
				/*

				Es kann passieren das ein Lied mehrere Artists hat
				Problem1: Lied wird mehrfach runtergeladen
				Problem2: Album ist nicht an einem Ort


				Ein Lied kann auch in mehreren Alben vorkommen, nur einmal runterladen

				*/

				// Wenn mehrere Artists vorhanden sind
				$artists_ar = explode(", ",$track['artist']);

				if(count($artists_ar) > 1 && ( !file_exists($dl_path_filename) or re_download == 1) ){
					if(debug == 1) echo "Mehrere Interpreten: ".var_export($artists_ar,1)."\n";
					/*
					 // Gibt es bereits einen Ordner mit dem Namen des Albums?
					exec('find Downloads/ -type d -name "'.$track['more_infos']['album'].'"',$album_check_dup);
					if(isset($album_check_dup[0]) && !is_array($album_check_dup[0])){
					if(info == 1) echo "Album gefunden: ".var_export($album_check_dup,1)."\n";

					$artist_search = preg_replace("#Downloads/#","",$album_check_dup[0]);
					$artist_search = trim(trim($artist_search,","));
					$artist_search = preg_replace("#/".$track['more_infos']['album']."#","",$artist_search);

					// Kommt der bisherige Artist im neun vor?
					if(preg_match("#".$artist_search."#i",$track['artist'])){
					if(info == 1) echo "Gleicher Interpret: ".$artist_search."\n";
					// Dann Pfad umschreiben
					$path = $album_check_dup[0];
					$dl_path_filename = trim($path."/".$filename,"/");

					$data["note"] = $track['artist'];
					$track['artist'] = $artist_search;


					if(info == 1) echo "Neuer Pfad: ".$dl_path_filename."\n";

					}else{
					if(info == 1) echo "Nicht der gleiche Interpret: ".$artist_search."!=".$track['artist']."\n";
					}
					}else{
					if(info == 1) echo "Album nicht gefunden: ".$track['more_infos']['album']."\n";
					}
					*/

					// Test bei mehreren Artists den ersten nehmen

					$path = download_folder."/".$artists_ar[0]."/".$track['more_infos']['album'];
					$dl_path_filename = $path."/".$filename;
					$data["note"] = $track['artist'];
					$track['artist'] = $artists_ar[0];

				}
				if(use_subprogress == 0){


					if(!file_exists($dl_path_filename) or re_download == 1){
						$track['dl'] = $mrtz->search_by_string($search_for,1);

						if(isset($track['dl'][0]['dl_link'])){
							$data["title"] = $track['track_titel'];
							$data["artist"] = $track['artist'];
							$data["album"] = $track['more_infos']['album'];
							$data["track"] = $track['more_infos']['album:track'];
							$data["year"] = substr($track['more_infos']['release_date'], 0, 4);

							$dl_status = $mrtz->download($track, $path, $dl_path_filename);
							$id3->set_tags($dl_path_filename,$data);
							$playlist_inc = 1;
							$count_dl++;
						}elseif(info == 1 || debug == 1){
							$count_not_found++;
							echo 'Kein Download für "'.$search_for.'" gefunden'."\n";
						}
					}else{
						if(info == 1 || debug == 1) {
							$count_f_exists++;
							echo "Datei: ".$dl_path_filename." wurde bereits runtergeladen.\n";
						}
						$playlist_inc = 1;
					}


					if($playlist_inc == 1) $playlist_ar[] = array('file' => preg_replace("#".download_folder."/#",playlist_muscic_location,$dl_path_filename), 'title' => $track['track_titel'], 'Length' => '111');

					if(rewrite_id3 == 1){

						$data["title"] = $track['track_titel'];
						$data["artist"] = $track['artist'];
						$data["album"] = $track['more_infos']['album'];
						$data["track"] = $track['more_infos']['album:track'];
						$data["year"] = substr($track['more_infos']['release_date'], 0, 4);

						$id3->set_tags($dl_path_filename,$data);

					}
				}else{
					$befehl = 'php5 sub_progress.php "'.$dl_path_filename .'" "'.$search_for.'" "'.$path.'" "'.$track['track_titel'].'" "'.$track['artist'].'" "'.$track['more_infos']['album'].'" "'.$track['more_infos']['album:track'].'" "'.$track['more_infos']['release_date'].'"  > /dev/null &';
					exec($befehl);

					$reched_max_progress = check_progress();
					while($reched_max_progress == 1){
						sleep(2);
						$reched_max_progress = check_progress();
						echo date("d.m.Y H:m:s")." - Warten!\n";
					}
				}

			}else{
				//die(var_export($track));
			}

		}





	}



	if(isset($playlist_ar) && count($playlist_ar) > 0){
		// Playlist erzeugen
		$playlist_content = "[playlist]\n";
		foreach ($playlist_ar as $key => $item){
			$num = $key+1;
			$playlist_content .= "File".$num."=".$item['file']."\n";
			$playlist_content .= "Title".$num."=".$item['title']."\n";
			$playlist_content .= "Length".$num."=".$item['Length']."\n";
		}
		$playlist_content = $playlist_content."NumberOfEntries=".count($playlist_ar)."\nVersion=2";

		// Playlist schreiben
		$playlist_filename = dirname(__FILE__)."/Playlists/".$playlist_titel.".pls";
		$p_datei = fopen($playlist_filename,"w+");
		fwrite($p_datei, $playlist_content);
		fclose($p_datei);
	}






}
if(info == 1 || debug == 1) {
	echo "Kein Download gefunden: ".$count_not_found."\n";
	echo "Datei runtergeladen: ".$count_dl."\n";
	echo "Datei bereits runtergeladen: ".$count_f_exists."\n";
}

// Clean
exec('find '.download_folder.' -type f -size 0b -exec rm -f {} \;');
exec('find '.download_folder.' -depth -type d -empty -exec rmdir {} \;');
exec('find '.download_folder.' -type f -size 1M -exec rm {} \;');



