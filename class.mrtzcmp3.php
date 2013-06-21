<?php 


class mrtzcmp3 {


	function search_by_string($search,$limit=5){

		$this->limit = $limit;
		$this->browser = new WebBrowser();


		$this->baseurl = "http://www.mrtzcmp3.net/";
		$post = array(
				'q'				=>	$search,
				'groovybtn1'	=>	'Search!',
			//	's'				=>	'Y'
		);

		
		
		$this->browser->post($this->baseurl, $post);

		if(preg_match('#Location: (.*)#', $this->browser->result, $r)){
			$l = $this->baseurl.trim($r[1]);
			$this->browser->get($l,1);
		}

	

		return $this->get_result($this->browser->result);

	}

	private function get_url_from_string($string){
		$string = preg_replace("#(.*)href=\"#","",$string);
		$string = preg_replace("#\" (.*)#","",$string);
		$string = preg_replace("#(.*)\"#", "", $string);

		return $string;

	}

	private function remove_useless_char($string,$special_allow='',$strp_tags=1){
		if($strp_tags == 1) 	$string = strip_tags($string);

		$string = preg_replace('/\\\s\\\s+/', ' ', $string);
		if($special_allow != ' ') $string = preg_replace("# #",".",$string);
		$string = preg_replace("/[^A-Z\.0-9a-z-_".$special_allow."]/i","",$string);
		$string = trim($string);
		$string = trim($string,".");

		$string = preg_replace("#\.\.+#",".",$string);

		return $string;
	}

	private function get_dl_link($url,$stream=0){
		// Step 1
		$url = $this->baseurl.$url;
		$this->browser->get($url);
		$subject = $this->browser->result;
		$pattern = '/MRTZC\?[a-zA-Z0-9]+/';

		if($stream == 1){
			$pattern = '#mp3: "[ a-zA-Z0-9-_\?]+"#';
		}

		if(!preg_match($pattern, $subject)){
			if(debug == 1) return array( 'error' => true, 0 => $url);
			return false;
		}

		preg_match($pattern, $subject,$match);
		$dl_step1 = $this->baseurl.$match[0];
		$dl_step1 = trim(trim(preg_replace("#mp3: \"#","",$dl_step1),"\""));

		if($stream == 0){
			// Step 2
			$this->browser->get($dl_step1);
			$subject2 = $this->browser->result;
			$pattern2 = '/[a-zA-Z0-9]+\.mrtzcmp3/';

			if(!preg_match($pattern2, $subject2)){
				if(debug == 1) return array( 'error2' => true, 0 => $url);
				return false;
			}

			preg_match($pattern2, $subject2,$match2);
			$dl_step2 = $this->baseurl.$match2[0];
		}else {
			$dl_step2 = $dl_step1;
		}
		
		if(debug == 1){
			$debug[] = $url;
			$debug[] = $dl_step1;
			$debug[] = $dl_step2;
			return $debug;
		}

		return $dl_step2;
	}

	private function get_result($content){
		$return = array();
		$content = preg_replace("/(\<\!\-\-.*\-\-\>)/sU", "", $content);

		mb_internal_encoding('UTF-8');
		mb_regex_encoding('UTF-8');

		preg_match_all("#<tr>\n(.*)<td>[0-9]+</td>\n(.*)\n(.*)\n(.*)\n(.*)\n(.*)\n(.*)\n</tr>#", $content, $matches);

		
		foreach ($matches[0] as $found){
			$akt = count($return);

			if(!preg_match('/&#10[78]\d/', mb_encode_numericentity($found, array(0x0, 0x2FFFF, 0, 0xFFFF), 'UTF-8'))){



				$found_ar = explode("\n", $found);

				if(debug == 1){
						echo "search_by_string => get_result: ".var_export($found_ar,1)."\n";
				}

				$return[$akt]['titel'] = $this->remove_useless_char($found_ar[3]," ");
				$return[$akt]['artist'] = $this->remove_useless_char($found_ar[2]);
				$return[$akt]['runtime'] = $this->remove_useless_char($found_ar[4],":");

				
				$return[$akt]['dl_link'] = $this->get_dl_link($this->get_url_from_string($found_ar[7]));
				$return[$akt]['stream_link'] = $this->get_dl_link($this->get_url_from_string($found_ar[6]),1);
				
				if($akt == $this->limit - 1) break;
			}
		}

		if(count($return) > 0)	return $return;
		else return false;
	}

	public function download($track,$path,$dl_path_filename){
		global $inet;
		exec('mkdir -p "'.$path.'"',$return);
	
		
		if(use_stream_dl == 1){
			if(debug == 1){
				$download_status = $inet->download($track['dl'][0]['stream_link'][1],$dl_path_filename);
			}else{
				$download_status = $inet->download($track['dl'][0]['stream_link'],$dl_path_filename);
			}
		}else{
			if(debug == 1){
				$download_status = $inet->download($track['dl'][0]['dl_link'][2],$dl_path_filename);
			}else{
				$download_status = $inet->download($track['dl'][0]['dl_link'],$dl_path_filename);
			}
		}

		if($download_status !== true) {
			exec('echo "'.date("d.m.Y H:m:s").var_export($download_status,1)."\n\n".'" >> /home/xyco/Arbeitsfläche/spotify/temp/stop.txt');
			die("Download fehlgeschlagen: DL-Limit erreicht");
		}
		
		if(dev == 1){
			die("DEV: ".var_export($track,1).var_export($download_status,1).use_stream_dl);
		}
		
		return $download_status;
		
	}

}