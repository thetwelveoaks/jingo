<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=gb2312" />
	<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
	<title>Evaluate</title>
	<script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=57xrHyB1Wj5mLxWgra9GTrBYtoSCvK9i"></script>
	<!-- <script type="text/javascript" src="http://api.map.baidu.com/getscript?v=2.0&ak=57xrHyB1Wj5mLxWgra9GTrBYtoSCvK9i&services=&t=20170308134931"></script> -->
</head>
<body>
	
</body>

<script type="text/javascript">	

	function findMedian(data){
		data.sort(function(a, b) {
  			return a - b;
		});
		var mid = Math.floor(data.length / 2);
		if(data.length % 2 == 1){
			return data[mid];
		}

		return (data[mid] + data[mid - 1]) / 2;
	}

	function findMean(data){
		var sum = 0;
		for(var i = 0; i < data.length; ++i){
			sum += data[i];
		}
		return sum / data.length;
	}

	function searchRoute(){
		if(index < coordsU.length){
			transit.search(new BMap.Point(coordsU[index][0], coordsU[index][1]), 
				new BMap.Point(coordsV[index][0], coordsV[index][1]));
		}else{
			var update_handle = new XMLHttpRequest();
			update_handle.open("POST", "bdeval_jsrouter.php", true);
			update_handle.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

			var content = {opcode : 'UPDATE', BD_Median : findMedian(durations), BD_Mean : findMean(durations), 
				Median_Dist : findMedian(distances), TimeOfDay : jingoeval[0], 
				EdgeID : edgeid, Jingo_Est : jingoeval[1], res_table : res_table};

			var req_str = "content=" + JSON.stringify(content);
			
			update_handle.send(req_str);
			update_handle.onreadystatechange = function() {
	          	if (this.readyState == 4 && this.status == 200) {
	          		console.log(this.responseText);
	          		++edgeid;
					fetchPoints();
	          	}
	    	};
		}		
	}

	function fetchPoints(){
		if(edgeid >= edgeid_end){
			return;
		}

		durations = [];
		distances = [];
		index = 0;

		var fetch_handle = new XMLHttpRequest();
		fetch_handle.open("POST", "bdeval_jsrouter.php", true);
		fetch_handle.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

		var content = GET;
		content['edgeid'] = edgeid;
		content['opcode'] = 'FETCH';

		var req_str = "content=" + JSON.stringify(content);
		fetch_handle.send(req_str);

		fetch_handle.onreadystatechange = function() {
          	if (this.readyState == 4 && this.status == 200) {
          		// console.log(this.responseText);
          		var res = JSON.parse(this.responseText);
      			coordsU = res['bdeval'][0];
      			coordsV = res['bdeval'][1];
      			jingoeval = res['jingoeval'];
      			searchRoute();
          	}
    	};
	}

	var GET = <?php echo json_encode($_GET); ?>;
	var edgeid = parseInt(GET['edgeid_start']), edgeid_end = parseInt(GET['edgeid_end']), res_table = GET['res_table'];
	var coordsU, coordsV;
	var durations, distances;
	var jingoeval;
	var index;

	var searchComplete = function (results){
		if (transit.getStatus() == BMAP_STATUS_SUCCESS){
			var plan = results.getPlan(0);
			durations.push(plan.getDuration(false));
			distances.push(plan.getDistance(false));
		}else{
			console.log("Calculation Failed: " + transit.getStatus());
		}
		++index;
		searchRoute();
	}
	var transit = new BMap.DrivingRoute("北京", 
		{policy: BMAP_DRIVING_POLICY_LEAST_TIME, onSearchComplete: searchComplete});
	
	delete GET['edgeid_start'];
	delete GET['edgeid_end'];
	delete GET['res_table'];

	fetchPoints();

</script>
</html>

