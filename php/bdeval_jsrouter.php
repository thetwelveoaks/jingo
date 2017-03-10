<?php

class BDRouter{
	private $content;
	private $url;
	private $curl;

	public function __construct($content){
		$this->content = $content;
		$this->url = "";
		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->curl, CURLOPT_POST, true);
	}

	public function __destruct(){
		curl_close($this->curl);
	}

	private function sendRequest(){
		curl_setopt($this->curl, CURLOPT_URL, $this->url);
		$resp = curl_exec($this->curl);
		if($errno = curl_errno($this->curl)) {
	    	$error_message = curl_strerror($errno);
	    	echo "cURL error ({$errno}):\n {$error_message}\n";
		}
		return $resp;
	}

	public function startRouting(){
		$opcode = $this->content['opcode'];
		unset($this->content['opcode']);
		switch ($opcode) {
			case 'FETCH':
				$this->url = "http://localhost:8080/~weiyumou/jingo/php/bdeval_jsfetch.php";
				curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($this->content));
				break;
			case 'UPDATE':
				$this->url = "http://localhost:8080/~weiyumou/jingo/php/bdeval_jsupdate.php";
				curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($this->content));
				break;
			default:
				break;
		}
		echo $this->sendRequest();
	}
}

set_time_limit(0);
ini_set('memory_limit','2048M');

$content = json_decode($_POST['content'], true);
$bdRouter = new BDRouter($content);
$bdRouter->startRouting();

?>