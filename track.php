<? 
/******************** 

DB Stuff...

********************/
$dbhost = "";
$dbname = "";
$dbuser = "";
$dbpass = "";

$db = new mysqli($dbhost, $dbuser, $dbpass, $dbname);

if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
    die;
}

/******************** 

Get locations.
Restrict if needed

********************/
$lastlocq = $db->query("SELECT * FROM latlng ORDER BY ts"); //WHERE ts > '2015-06-30 00:00:00' AND ts < '2015-06-30 00:00:00'
$locarr = array();
$dateArr = array();
$i = 0;
while($lastloc = $lastlocq->fetch_assoc()) {
		$locarr[$i]['ts'] = $lastloc['ts'];
		$locarr[$i]['lat'] = $lastloc['lat'];
		$locarr[$i]['lng'] = $lastloc['lng'];
		$i++;
	$dateArr[] = date("Y-m-d", strtotime($lastloc['ts']));
	
}
$dateArr = array_unique($dateArr);


/******************** 

Get distance between points. Used to calculate distances in popovers

********************/
function getDistance($lat1,$lng1,$lat2,$lng2){
	$theta = $lng1 - $lng2;
	$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
	$dist = acos($dist);
	$dist = rad2deg($dist);
	$km = $dist*60*1.1515*1.609344;
    return $km;  
}


/******************** 

Get city name of locations.
The app displays city names on marker popups. This is the function responsable for that. 

********************/
function get_api ($lat, $long) {
    $get_API = "http://maps.googleapis.com/maps/api/geocode/json?latlng=";
    $get_API .= round($lat,2).",";
    $get_API .= round($long,2);         

    $jsonfile = file_get_contents($get_API.'&sensor=false');
    $jsonarray = json_decode($jsonfile);        

    if (isset($jsonarray->results[1]->address_components[1]->long_name)) {
        return($jsonarray->results[1]->address_components[1]->long_name);
    }
    else {
        return('');
    }
}
?>

<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
    <meta charset="utf-8">
    <meta name="author" content="Özgür Akman">
    <title>Tracker</title>
    <style>
      html, body, #map-canvas {
        height: 100%;
        margin: 0px;
        padding: 0px
      }
      .ticker {
      	-webkit-user-select: none;
      	user-select: none;
      	background-attachment: scroll;
      	background-clip: border-box;
      	background-color: rgba(255, 255, 255, 0.496094);
      	color: rgb(68, 68, 68);
      	direction: ltr;
      	display: block;
      	font-family: Roboto, Arial, sans-serif;
      	font-size: 10px;
      	padding: 2px 5px;
      	line-height: 10px;
      	white-space: nowrap;
      	z-index: 0; 
      	position: absolute; 
      }
      .bottomleft {
      	text-align: left;
      	bottom: 0px;
      	left: 0px;
      	height: 10px;
      }
      .topleft {
      	text-align: left;
      	top: 0px;
      	left: 0px;
      }
      a {
      	text-decoration: none;
      	color: rgb(68, 68, 68);
      }
    </style>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&libraries=geometry&signed_in=true"></script>
    <script>
	$(window).ready(function(){
		initialize();	
	});
	function initialize() {
		var map = new google.maps.Map(document.getElementById('map-canvas'),{zoom: 12,center: new google.maps.LatLng(<?= $locarr[$i-1]['lat'] ?>,<?= $locarr[$i-1]['lng'] ?>),streetViewControl: false,panControl: false,mapTypeControl: false});
		var polyOptions = {geodesic: true,strokeColor: '#131540',strokeOpacity: 0.7,strokeWeight: 5}
		poly = new google.maps.Polyline(polyOptions);
		poly.setMap(map);
		var path = poly.getPath();
		
		var marker = new google.maps.Marker({position: new google.maps.LatLng(<?= $locarr[0]['lat'] ?>,<?= $locarr[0]['lng'] ?>),map:map,title:'Başlangıç',icon: 'marker/number_0.png'});
		var content = '<b>Başlangıç<br/><?= date("Y-m-d", strtotime($locarr[0]['ts'])) ?></b>';
		var infowindow = new google.maps.InfoWindow();
		google.maps.event.addListener(marker,'click', (function(marker,content,infowindow){ 
			return function() {
				infowindow.setContent(content);
				infowindow.open(map,marker);
			};
		})(marker,content,infowindow)); 
		<? 
		$dailyDistance = 0;
		$totalDistance = 0;
		$i = 0;
		$day = 0;
		
		
		/******************** 
		
		Location path loop
		
		********************/
		
		foreach ($locarr as $key => $value) { ?>
			path.push(new google.maps.LatLng(<?= $locarr[$i]['lat'] ?>, <?= $locarr[$i]['lng'] ?>));
			<? if ($i>0) {
				$distance = getDistance($locarr[$i-1]['lat'],$locarr[$i-1]['lng'],$locarr[$i]['lat'],$locarr[$i]['lng']);
				$dailyDistance +=$distance;
				$totalDistance +=$distance;
				if (date("Y-m-d", strtotime($locarr[$i]['ts'])) != date("Y-m-d", strtotime($locarr[$i-1]['ts']))) {
				     $datediff = strtotime($locarr[$i]['ts']) - strtotime($locarr[$i-1]['ts']);
				     $day += ceil($datediff/(60*60*24)); ?>
					var marker = new google.maps.Marker({position: new google.maps.LatLng(<?= $locarr[$i]['lat'] ?>,<?= $locarr[$i]['lng'] ?>),map:map,title:'GÜN: <?= $day ?>',icon: 'marker/number_<?= $day ?>.png'});
					var content = '<b><?= $day ?>. Gün<br/><?= date("Y-m-d", strtotime($locarr[$i-1]['ts'])) ?></b><br/><?= get_api($locarr[$i]['lat'],$locarr[$i]['lng']) ?><br/>Günlük: <?= ceil($dailyDistance) ?> km<br/><b>Toplam: <?= ceil($totalDistance) ?> KM</b>';
					var infowindow = new google.maps.InfoWindow();
					google.maps.event.addListener(marker,'click', (function(marker,content,infowindow){ 
						return function() {
							infowindow.setContent(content);
							infowindow.open(map,marker);
						};
					})(marker,content,infowindow)); 
			<? $dailyDistance = 0;
			} } $i++; } ?>
		
		
		/******************** 
		
		Last location marker.
		Can bounce if desired :)
		
		********************/
		
		var marker = new google.maps.Marker({position: new google.maps.LatLng(<?= $locarr[$i-1]['lat'] ?>,<?= $locarr[$i-1]['lng'] ?>),map:map,title:'Şu Anda',icon: 'marker/now.png'});
		var content = '<b><?= date("Y-m-d H:m", strtotime($locarr[$i-1]['ts'])) ?></b><br/><?= get_api($locarr[$i-1]['lat'],$locarr[$i-1]['lng']) ?><br/>Günlük: <?= ceil($dailyDistance) ?> km<br/><b>Toplam: <?= ceil($totalDistance) ?> KM</b>';
		var infowindow = new google.maps.InfoWindow();
		google.maps.event.addListener(marker,'click', (function(marker,content,infowindow){ 
			return function() {
				infowindow.setContent(content);
				infowindow.open(map,marker);
			};
		})(marker,content,infowindow)); 
		//marker.setAnimation(google.maps.Animation.BOUNCE);
	}
	  
	</script>
  </head>
  <body>
    <div id="map-canvas"></div>
    <div class="ticker bottomleft">Toplam: <?= ceil($totalDistance) ?> KM | <?= $day ?> Gün</div>
    
  </body>
</html>