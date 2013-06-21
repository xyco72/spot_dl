<?php 

/**
 * Simple CURL Wrapper
 *
 */
class WebBrowser {
	/**
	 * saves curl session
	 *
	 * @var cURL
	 */
	private $ch = null;

	/**
	 * saves referer
	 *
	 * @var string
	 */
	private $ref = "";

	/**
	 * saves results
	 */
	public $result = "";

	/**
	 * Download Location
	 */
	public $dl_dir = false;


	/**
	 * init curl
	 *
	 */
	public function __construct() {
		$this->ch = curl_init();
	}

	/**
	 * destruct
	 *
	 */
	public function __destruct() {
		curl_close($this->ch);
	}

	/**
	 * post
	 *
	 * @param string $url
	 * @param string $data
	 */
	public function post($url, $data) {
		$this->result = "";
		curl_setopt($this->ch, CURLOPT_URL, $url);
		curl_setopt($this->ch, CURLOPT_POST, 1);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
		$this->refer($url);
		$this->setopts();
		ob_start();
		$this->result = curl_exec($this->ch);
		ob_end_clean();
	}

	/**
	 * get
	 *
	 * @param string $url
	 */
	public function get($url,$dl=0) {
		exec('mkdir -p temp/curl');
		if(file_exists($url)){
			$this->result = file_get_contents($url);
			return;
		}

		$this->result = "";

		// Filecaching
		$cache_file = 'temp/curl/'.md5($url).".txt";

		if(file_exists($cache_file) && filectime($cache_file) + 86400 > time() && $dl == 0){
			$temp = file_get_contents($cache_file);
			if(strlen($temp) > 0){
				$this->result = $temp;
				return;
			}
		}


		curl_setopt($this->ch, CURLOPT_URL, $url);
		$this->refer($url);
		$this->setopts();
		ob_start();
		$this->result = curl_exec($this->ch);
		ob_end_clean();

		if($dl == 0){
			$datei = fopen($cache_file, "w+");
			fwrite($datei, $this->result);
			fclose($datei);
		}
		

	}

	/**
	 * download
	 *
	 * @param string $url
	 * @param string $filename
	 */
	public function download($url,$filename){
		$this->get($url,1);

		$content = $this->result;

		// Check Limit
		if(preg_match("#http://www.mrtzcplayer.com/register.php?reached=1#",$content)){
			return false;
		}

		if(!file_exists($filename)){
			$path = '';
			$temp = explode("/",$filename);
			array_pop($temp);
			$folder = implode("/", $temp);
			
			exec('mkdir -p "'.$folder.'"');
			
		}
		
		$datei = fopen($filename,"w");
		fwrite($datei, $this->result);
		fclose($datei);

		return true;
	}

	/**
	 * update referer
	 *
	 * @param string $url
	 */
	private function refer($url) {
		curl_setopt ($this->ch, CURLOPT_REFERER, $this->ref);
		$this->ref = $url;
	}

	/**
	 * set global opts
	 *
	 */
	private function setopts() {
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		// you may want to change this
		curl_setopt($this->ch, CURLOPT_USERAGENT, curl_referer);
		curl_setopt($this->ch, CURLOPT_COOKIEJAR, curl_cookie);
		curl_setopt($this->ch, CURLOPT_COOKIEFILE, curl_cookie);
		curl_setopt($this->ch, CURLOPT_HEADER, true);
	}
}
?>