<?php

class TestPub {
	
	private $data='';
	public $points;
	private $arr;
	public $speed;
	private $debug=true;



	private function GetDist ($a,$b) {
		$lat1 = $a[0] * M_PI / 180;
		$lat2 = $b[0] * M_PI / 180;
		$long1 = $a[1] * M_PI / 180;
		$long2 = $b[1] * M_PI / 180;

		$cl1 = cos($lat1);
		$cl2 = cos($lat2);
		$sl1 = sin($lat1);
		$sl2 = sin($lat2);

		$delta = $long2 - $long1;
		$cdelta = cos($delta);
		$sdelta = sin($delta);
		 

		$y = sqrt(pow($cl2 * $sdelta, 2) + pow($cl1 * $sl2 - $sl1 * $cl2 * $cdelta, 2));
		$x = $sl1 * $sl2 + $cl1 * $cl2 * $cdelta;
		$ad = atan2($y, $x);
		$dist = $ad * 6372;
		return $dist;
	}
	
	function __construct($config) {
		if (strlen($config)>0 ) {$this->data=$config;}
		$this->arr=json_decode($this->data,true);


		$this->points=sizeof($this->arr["tr"]);
		$this->speed=$this->arr["speed"];
	}
	
	function getDistance() {
		$distance=0;
		for ($c=0;$c<$this->points-1;$c++) {
			$distance+=$this->GetDist($this->arr["tr"][$c],$this->arr["tr"][$c+1]);
		}
		if ($this->debug) {echo("getDistance call, return $distance\n");}
		return $distance;
	}

	function getTimeArrival(DateTime $dt) {
		$dtout=$dt;
		$dtout->modify("+".intval($this->getDistance()/$this->speed*3600)." seconds");
		if ($this->debug) {echo("getTimeArrival call, delta ".intval($this->getDistance()/$this->speed*3600).", speed $this->speed\n");}
		return $dtout;
	}

	function getPartDistance(int $n){

		return 0;
	}

	function getPartTimeArrival(DateTime $dt, int $n) {

		return 0;
	}

	function play() {

	}

	function getPlace() {
        	
	}


}
	
//предполагается - скорость км/ч, координаты широты/долготы в градусах.
$test= new TestPub('{"tr": [[ 33, 33 ], [ 37, 24 ], [ 43, 45 ]], "speed": "400" }');

print_r(new DateTime());
print_r($test->getTimeArrival(new DateTime()));


?>