<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=gb2312" />
	<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
	<title>Evaluate</title>
	<!-- <script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=57xrHyB1Wj5mLxWgra9GTrBYtoSCvK9i"></script> -->
	<script type="text/javascript" src="http://api.map.baidu.com/getscript?v=2.0&ak=57xrHyB1Wj5mLxWgra9GTrBYtoSCvK9i&services=&t=20170308134931"></script>
	<!-- <script type="text/javascript">
		(function(){ 
			window.BMap_loadScriptTime = (new Date("October 13, 2014 20:13:00")).getTime(); 
			document.write('<script type="text/javascript" src="http://api.map.baidu.com/getscript?v=2.0&ak=57xrHyB1Wj5mLxWgra9GTrBYtoSCvK9i&services=&t=20170302134931"><\/script>');})();
	</script> -->

	<script type="text/javascript">	
		var points_u, points_v;
		function searchRoute(idxu, idxv){
			var output = "";
			var searchComplete = function (results){
				if (transit.getStatus() != BMAP_STATUS_SUCCESS){
					return ;
				}
				var plan = results.getPlan(0);
				output += plan.getDuration(false) + "\n";                //获取时间
				output += "总路程为：" ;
				output += plan.getDistance(false) + "\n";             //获取距离

				console.log(output);
			}
			var transit = new BMap.DrivingRoute("北京", 
				{policy: BMAP_DRIVING_POLICY_LEAST_TIME, onSearchComplete: searchComplete});
			transit.search(points_u[idxu], points_v[idxv]);

			console.log("u: " + idxu);
			console.log("v: " + idxv);

			++idxv;
			if(idxv == points_v.length){
				++idxu;
				idxv = 0;
			}
			
			if(idxu < points_u.length){
				setTimeout(window.searchRoute.bind(null, idxu, idxv), 500);
			}
			
		}

		function fetchPoints(ldmk){
			points_u = [new BMap.Point(116.44060475580,39.94103167998), new BMap.Point(116.44037528907,39.94651487610)];
			points_v = [new BMap.Point(116.44034296037,39.95010934190), new BMap.Point(116.44026139847,39.95327782944)];
		}
		function startEvaluation(edgeid_start, edgeid_end){
			// for(var edgeid = edgeid_start; edgeid != edgeid_end; ++edgeid){
				fetchPoints("");
				// for(var idxu = 0; idxu != points_u.length; ++idxu){
				// 	for(var idxv = 0; idxv != points_v.length; ++idxv){
						searchRoute(0, 0);
				// 	}
				// }
			// }
		}
	</script>
</head>
<body>
	<?php
		echo "<script type=\"text/javascript\">
				startEvaluation({$_GET['edgeid_start']}, {$_GET['edgeid_end']});
 			</script>";
	?>
</body>
</html>
