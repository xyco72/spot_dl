<?php 
define('daemon_socket', '/tmp/spotcommander-xyco.socket');

class spotify{

	function __construct(){
		$this->inet = new WebBrowser();
	}

	private function spotify_is_running(){
		return (intval(exec(__DIR__ . '/bin/spotify-ps')) == 1);
	}

	private function remote_control($action, $data){
		if($this->spotify_is_running() || $action == 'spotify_launch' || $action == 'get_spotify_playlists' || $action == 'get_spotify_guistate' || $action == 'get_spotify_user'){
			$socket_connect = stream_socket_client('unix://' . daemon_socket, $errno, $errstr);
			if($socket_connect){
				fwrite($socket_connect, json_encode(array($action, $data)) . "\n");
				$return = stream_get_contents($socket_connect);
				fclose($socket_connect);
				return json_decode($return, true);
			}else
				return "Fehler beim verbinden";

		}else
			return "Spotify läuft nicht";
	}

	private function get_uri_type($uri){
		if(preg_match('/^spotify:artist:\w{22}$/', $uri) || preg_match('/^http:\/\/open.spotify.com\/artist\/\w{22}$/', $uri))
		{
			return 'artist';
		}
		elseif(preg_match('/^spotify:track:\w{22}$/', $uri) || preg_match('/^http:\/\/open.spotify.com\/track\/\w{22}$/', $uri))
		{
			return 'track';
		}
		elseif(preg_match('/^spotify:local:[^:]+:[^:]*:[^:]+:\d+$/', $uri) || preg_match('/^http:\/\/open.spotify.com\/local\/[^\/]+\/[^\/]*\/[^\/]+\/\d+$/', $uri))
		{
			return 'local';
		}
		elseif(preg_match('/^spotify:album:\w{22}$/', $uri) || preg_match('/^http:\/\/open.spotify.com\/album\/\w{22}$/', $uri))
		{
			return 'album';
		}
		elseif(preg_match('/starred/', $uri) || preg_match('/^spotify:user:[^:]+:playlist:\w{22}$/', $uri) || preg_match('/^http:\/\/open.spotify.com\/user\/[^\/]+\/playlist\/\w{22}$/', $uri))
		{
			return 'playlist';
		}
		elseif(preg_match('/^http:\/\/o.scdn.co\/\d+\/\w+$/', $uri))
		{
			return 'cover_art';
		}

		return 'unknown';
	}


	private function url_to_uri($url){
		if(preg_match('/^' . str_replace('/', '\/', 'http://open.spotify.com/') . '/', $url)){
			if(get_uri_type($url) == 'artist')
			{
				$url = str_replace('http://open.spotify.com/artist/', '', $url);
				$url = 'spotify:track:' . $url;
			}
			elseif(get_uri_type($url) == 'track')
			{
				$url = str_replace('http://open.spotify.com/track/', '', $url);
				$url = 'spotify:track:' . $url;
			}
			elseif(get_uri_type($url) == 'local')
			{
				$url = str_replace('http://open.spotify.com/local/', '', $url);
				$url = str_replace('/', ':', $url);
				$url = 'spotify:local:' . $url;
			}
			elseif(get_uri_type($url) == 'album')
			{
				$url = str_replace('http://open.spotify.com/album/', '', $url);
				$url = 'spotify:album:' . $url;
			}
			elseif(get_uri_type($url) == 'playlist')
			{
				$url = str_replace('http://open.spotify.com/user/', '', $url);
				$url = str_replace('/', ':', $url);
				$url = 'spotify:user:' . $url;
			}
		}

		return $url;
	}

	private function url_to_playlist_tracks($uris){
		$return = NULL;

		$uris = explode(' ', $uris);



		foreach($uris as $uri)	{

			$uri = $this->url_to_uri($uri);


			if($this->get_uri_type($uri) == 'playlist')	{

				$this->inet->get('https://embed.spotify.com/?uri=' . $uri);
				$get = $this->inet->result;

				preg_match_all('#<ul class="track-info">\n(.*)\n(.*)\n(.*)\n#',$get,$treffer_tracks);
				preg_match('/<title>(.*?)<\/title>/', $get, $name);


				if(!empty($name[1])){
					$name = htmlspecialchars_decode(strstr($name[1], ' by', true), ENT_QUOTES);

					if(!is_array($return)) $return = array();

					$return['title'] = $name;
					$return['tracks'] = array();
					foreach ($treffer_tracks[0] as $item){


						preg_match("# [0-9A-Za-z]+ #",$item,$match);
						if(isset($match[0])) $track_id = trim($match[0]);

						$item2 = explode("\n" , trim( trim( strip_tags( $item ) ) ,"\t") );




						$akt_track = count($return['tracks']);

						if(isset($item[1]) && isset($item[2])){

							if(isset($track_id)) {
								$return['tracks'][$akt_track]['trackid'] = $track_id;
								// get Album
								$return['tracks'][$akt_track]['more_infos'] = $this->get_track_infos_by_trackid($track_id);
							}


							// FIXME 14.05.2013 - hier hat sich was geändert
							if(isset($item2[2])){
								$return['tracks'][$akt_track]['artist'] = trim($item2[2]);
							}
								
								

							if(isset($item2[1])){
								$return['tracks'][$akt_track]['track_titel'] = trim(preg_replace("#/#"," ",preg_replace("#\((.*)\)#","",preg_replace("#[0-9]+\. #","",$item2[1]))));
								$return['tracks'][$akt_track]['track_titel'] = preg_replace("#\[(.*)\]#","",$return['tracks'][$akt_track]['track_titel']);
							}
						}
					}
				}
			}
		}

		return $return;
	}

	private function starred_playlist(){
		$user = $this->remote_control('get_spotify_user', '');
		$url = "spotify:user:".$user . ':starred';

		$playlist = $this->url_to_playlist_tracks($url);
		return $playlist;

	}

	function get_spotify_playlists(){

		$import = array();

		$guistate = $this->remote_control('get_spotify_guistate', '');



		if(!empty($guistate['views'][0]['uri']))	{
			foreach($guistate['views'] as $playlist){
				$akt = count($import);
				$uri = $playlist['uri'];
				if($this->get_uri_type($uri) == 'playlist')	{
					$import[$akt] = $this->url_to_playlist_tracks($uri);
				}
			}

		}

		$akt = count($import);
		$import[$akt] = $this->starred_playlist();
			
		return $import;

	}


	public function get_track_infos_by_trackid($track_id){
		$url = 'http://open.spotify.com/track/'.$track_id;
		$this->inet->get($url);
		$get = $this->inet->result;

		preg_match_all('#<meta property="music:(.*)#',$get,$treffer);

		if(count($treffer[1]) < 1) return "unbekannt";

		foreach ($treffer[1] as $item ){
			unset($value,$get2,$treffer2);
			$ar = explode(" ", $item);
			$key = trim($ar[0],'"');
			$value = trim(trim(preg_replace("#content=#","",$ar[1]),'>'),'"');
			if($key == 'album'){
				$this->inet->get($value);
				$get2 = $this->inet->result;
				preg_match('#<meta property="og:title" content="(.*)#', $get2,$treffer2);

				if(isset($treffer2[1])){
					$album_title = trim($treffer2[1],'>');
					$album_title = trim($album_title,'"');
					$album_title = preg_replace("#\((.*)\)#","",$album_title);
					$album_title = trim($album_title);

					// DEBUG
					$return['debug']['album_url'] = $value;
					$return['debug']['album_treffer'] = $treffer2;
					$return['debug']['md5_url'] = md5($value);

					$value = $album_title;
				}else{
					$value = "Unbekannt";
				}
			}elseif($key == '' or !isset($key)){
				die(var_export($ar));
			}
			$return[$key] = $value;
		}


		return $return;


	}

}

























