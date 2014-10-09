<?php
//includes оставлены на хост
class TestPub {
	
	private $data='';
	public $points;
	//кэшируется средствами любого акселератора или store в духе redis/memcache, чтобы избежать json_parse
	private $arr;
	public $speed;
	private $mc;
	private $redis;
	private $debug=false;



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

		//основной shm xcache, не требует инициализации
/*		$this->mc = new Memcache();
		$this->mc->connect("localhost");

		$this->redis = new Redis();
		$this->redis->connect("localhost");

*/
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
		if ((n>$this->points)||(n<2)) {return 0;} else {
		$distance=0;
		for ($c=0;$c<($n-2);$c++) {
			$distance+=$this->GetDist($this->arr["tr"][$c],$this->arr["tr"][$c+1]);
		}
		if ($this->debug) {echo("getPartDistance call, return $distance, point $n\n");}
		return $distance;
		}
	}

	function getPartTimeArrival(DateTime $dt, int $n) {
		$dtout=$dt;
		$dtout->modify("+".intval($this->getPartDistance($n)/$this->speed*3600)." seconds");
		if ($this->debug) {echo("getPartTimeArrival call, delta ".intval($this->getDistance()/$this->speed*3600).", point $n, speed $this->speed\n");}
		return $dtout;
	}

	function play() {
		//функция возвращает id сессии которая стартовала, ну и не забывая о том, что тут тоже можно даже данные маршрута сразу запихать в кэш, только опять некрасиво будет, придётся каждый раз при создании класса и вводные повторять
		$id=uniqid("TestClass_",true);
		//основной shm xcache
		xcache_set("$id_start",new DateTime(),($this->getDistance()/$this->speed*3600));
		//$this->mc->set("$id_start",new DateTime(),0,$this->getDistance()/$this->speed*3600));
		//$this->redis->setex("$id_start",$this->getDistance()/$this->speed*3600),new DateTime());
		return $id;
	}

	function getPlace($id) {
	//тут неясно что возвращать, но вернём всё в массиве [lat,lon,%tonextpoint], оптимизировать функции в духе disttonextpoint можно очень сильно, но код будет уже некрасивый
		if (xcache_isset($id)) {
//		if ($this->mc->get($id)!=false) {
//		if ($this->redis->exists($id)) {
		$dt=xcache_get($id);
//		$dt=$this->mc->get($id);
//		$dt=$this->redis->get($id);
		$dt2=new DateTime();
		//не очень корректно на будущее т.к. unix_timestamp, но в новых php оно уже достаточного размера
		$delta=$dt2->getTimestamp()-$dt->getTimestamp();
		$dist=delta/3600*$this->speed;
		$flag=2;
		$distance=0;
		$prevdist=0;
			while (flag<$this->points) {
				$distance=$this->getPartDistance($flag);
				if $distance>=$dist {
					$n=$flag;
					$percent=($distance-$prevdist)/($this->speed*($delta-$prevtime))*100;
					break 1;
				} else {$prevdist=$distance;$prevtime=$prevdist/$this->speed*3600;}
			}
        	} else {return 0;}
		$result[]=$this->arr["tr"][$n];
		$result[]=$percent;
		return $result;
	}


}
	
//предполагается - скорость км/ч, координаты широты/долготы в градусах.

//$test= new TestPub('{"tr": [[ 33, 33 ], [ 37, 24 ], [ 43, 45 ]], "speed": "400" }');

//print_r(new DateTime());
//print_r($test->getTimeArrival(new DateTime()));


?>