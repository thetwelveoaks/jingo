<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=gb2312" />
	<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
	<title>Get Street</title>
	<script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=57xrHyB1Wj5mLxWgra9GTrBYtoSCvK9i"></script>
	<script type="text/javascript">

		function geodecode(table, point, id){
			var myGeo = new BMap.Geocoder();
			myGeo.getLocation(point, function(rs){
				var req_str = "table=" + table + "&id=" + id + "&street=" + rs.addressComponents.street;
				var update_street = new XMLHttpRequest();
				update_street.open("POST", "../php/updatestreet.php", true);
				update_street.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				update_street.send(req_str);
			});
		}

		function get_street(table, start, end, limit_per_req, time_limit){
			if(start >= end){
				return;
			}
			var cond = "DataUnitID >= " + start + " and DataUnitID < " + end + " and Street is null limit " + limit_per_req;
			var get_street = new XMLHttpRequest();
			get_street.open("POST", "../php/fetchBDGPS.php", true);
        	get_street.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        	var req_str = "table=" + table + "&cond=" + cond;
        	get_street.send(req_str);

        	get_street.onreadystatechange = function() {
	          	if (this.readyState == 4 && this.status == 200) {
	          		var res = JSON.parse(this.responseText);
          			start += limit_per_req;
          			setTimeout(window.get_street.bind(null, table, start, end, limit_per_req, time_limit), time_limit);
	          		for(var i = 0; i != res.length; ++i){
						var point = new BMap.Point(res[i].BD09_LONG, res[i].BD09_LAT);
						geodecode(table, point, res[i].DataUnitID);
					}
	          	}
        	};
		}
	</script>
</head>

<body>
	<?php
		echo "<script type=\"text/javascript\">
				get_street({$_GET['table']}, {$_GET['start']}, {$_GET['end']}, {$_GET['reqlim']}, {$_GET['tlim']});
 			</script>";
	?>
</body>

</html>
