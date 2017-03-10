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

	
	
	function searchRoute(){

		if(index < coordsU.length){
			transit.search(new BMap.Point(coordsU[index][0], coordsU[index][1]), 
				new BMap.Point(coordsV[index][0], coordsV[index][1]));
		}else{
			var update_handle = new XMLHttpRequest();
			update_handle.open("POST", "bdeval_jsrouter.php", true);
			update_handle.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

			var content = {opcode : 'UPDATE', durations : durations, distances : distances, 
				jingoeval : jingoeval, edgeid : edgeid, res_table : GET['res_table']};
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
			// console.log(JSON.stringify(durations));
			// console.log(distances);
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
	var edgeid = GET['edgeid_start'], edgeid_end = GET['edgeid_end'];
	var coordsU, coordsV;
	var durations, distances;
	var jingoeval;
	var index;

	var searchComplete = function (results){
		if (transit.getStatus() != BMAP_STATUS_SUCCESS){
			return ;
		}
		var plan = results.getPlan(0);
		durations.push(plan.getDuration(false));
		distances.push(plan.getDistance(false));
		++index;
		searchRoute();
	}
	var transit = new BMap.DrivingRoute("北京", 
		{policy: BMAP_DRIVING_POLICY_LEAST_TIME, onSearchComplete: searchComplete});
	

	delete GET['edgeid_start'];
	delete GET['edgeid_end'];


	fetchPoints();
	
	// console.log(edgeid_start + "\n" + edgeid_end);

	
	// function startEvaluation(GET_ARRAY){
	// 	GET = GET_ARRAY;
	// 	console.log(GET['opcode']);
	// 	// edgeid = edge_start;
	// 	// edge_limit = edge_end;

	// 	// fetchPoints();
	// 	// 			searchRoute(0, 0);
	// }
</script>

<!-- <?php
		// echo "<script type=\"text/javascript\">
		// 		startEvaluation({$_GET});
 	// 		</script>";
	?> -->
</html>

