<?php 


class id3{

	function get_tags($filename){
		$tag = id3_get_tag( $filename,ID3_BEST );
		return $tag;
	}

	function set_tags($filename,$data,$note=''){

		// || isset($data['artist']) || isset($data['album']) isset($data['track']) || isset( $data['year'] ) || isset( $note ) )

		$befehl =  'eyeD3 -Q --to-v2.4 ';

		if( isset($data['title']) ){
			$befehl .= '-t "'.$data['title'].'" "';
		}
		if( isset($data['artist']) ){
			$befehl .= '-a "'.$data['artist'].'" "';
		}
		if( isset($data['album']) ){
			$befehl .= '-A "'.$data['album'].'" "';
		}
		if( isset($data['track']) ){
			$befehl .= '-n "'.$data['track'].'" "';
		}
		if( isset($data['year']) ){
			$befehl .= '-Y "'.$data['year'].'" "';
		}
		if( isset($note) ){
			$befehl .= '--comment="de::'.$note.'"';
		}

		$befehl .= ' '.$filename.' ';



	}


}