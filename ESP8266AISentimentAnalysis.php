<?php 

$debug			=$_GET["debug"];
$screen_name	=$_GET["screen_name"];

require_once("vendor/autoload.php"); 

Use Sentiment\Analyzer;

$sentiment = new Sentiment\Analyzer();


$strings[0]=returnTweet()[0]['full_text'];
$strings[1]=returnTweet()[1]['full_text'];
$strings[2]=returnTweet()[2]['full_text'];
$strings[3]=returnTweet()[3]['full_text'];
$strings[4]=returnTweet()[4]['full_text'];
$strings[5]=returnTweet()[5]['full_text'];

//new words not in the dictionary
$newWords = [
	'rubbish'=> '-1.5',
	'mediocre' => '-1.0',
	'agressive' => '-0.5'
];

if ($debug=="1"){

?>	
   <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
   
  </head>
  
  <body>
  
  <h2>ESP8266 Tango AI MP3 Player Social Media Sentiment Analysis </h2>
  <i>Roni Bandini - Buenos Aires, November 2020</i><br>
  </body>
  <?
  	
	       
 
}
//Dynamically update the dictionary with the new words
$sentiment->updateLexicon($newWords);

$positiveCounter=0;
$negativeCounter=0;
$neutralCounter=0;

//Print results
foreach ($strings as $string) {
	
	if ($string!=""){
			// calculations:
			$scores = $sentiment->getSentiment($string);
				
			//print_r(json_encode($scores));	
			
			if ($scores['compound']>0){
				if ($debug=="1"){
					echo "<br><img src='images/twitter.png' width=20><font color=green>";
					echo "-@".$screen_name." ".$string."\n";					
					echo "</font>";
					echo "&#128516;".$scores['pos'];
				}
				$positiveCounter=$positiveCounter+$scores['pos'];
				
			}
			if ($scores['compound']<0)
			{
				if ($debug=="1"){
					echo "<br><img src='images/twitter.png' width=20><font color=red>";
					echo "-@".$screen_name." ".$string."\n";					
					echo "</font>";
					echo "&#128528; ".$scores['neg'];
				}
				$negativeCounter=$negativeCounter+$scores['neg'];
			}
			if ($scores['compound']==0)
			{		
				if ($debug=="1"){
					echo "<br><img src='images/twitter.png' width=20>-@".$screen_name." ".$string."\n";	
				}
				$neutralCounter=$neutralCounter+$scores['neu'];
			}
			//print("Comp: ".$scores['compound']);
	}

}


if ($debug=="1"){
	?>
<script type="text/javascript">
      google.charts.load('current', {'packages':['gauge']});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {

        var data = google.visualization.arrayToDataTable([
          ['Label', 'Value'],
          ['Positive', <?=$positiveCounter*100?>],
          ['Negative', <?=$negativeCounter*100?>]
        ]);

        var options = {
          width: 400, height: 120,
          redFrom: 0, redTo: 0,
          yellowFrom:0, yellowTo: 100,
          minorTicks: 5
        };
		
		 var options2 = {
          width: 400, height: 120,
          redFrom: 0, redTo: 100,
          yellowFrom:0, yellowTo: 0,
          minorTicks: 5
        };

        var chart = new google.visualization.Gauge(document.getElementById('chart_div'));

        chart.draw(data, options);

        setInterval(function() {
          data.setValue(0, <?=$positiveCounter*100?>, <?=$positiveCounter*100?> + Math.round(5 * Math.random()));
          chart.draw(data, options);
        }, 13000);
        setInterval(function() {
          data.setValue(1, <?=$negativeCounter*100?>, <?=$negativeCounter*100?> + Math.round(5 * Math.random()));
          chart.draw(data, options2);
        }, 5000);        
      }
    </script>	
	<hr>
    <div id="chart_div" style="width: 400px; height: 120px;"></div>
<?
}

if ($positiveCounter>$negativeCounter){
	
	if ($debug=="1"){
		echo "<h3>Positive evaluation &#128516; (Positive: ".$positiveCounter." - Negative: ".$negativeCounter.")</h3>";
	}
	else echo "#h";
}
else
{
	if ($debug=="1"){
		echo "<h3>Negative evaluation &#128528; (Positive: ".$positiveCounter." - Negative: ".$negativeCounter. ")</h3>";
	}
	else echo "#s";
}


function buildBaseString($baseURI, $method, $params) {
    $r = array();
    ksort($params);
    foreach($params as $key=>$value){
        $r[] = "$key=" . rawurlencode($value);
    }
    return $method."&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $r));
}

function buildAuthorizationHeader($oauth) {
    $r = 'Authorization: OAuth ';
    $values = array();
    foreach($oauth as $key=>$value)
        $values[] = "$key=\"" . rawurlencode($value) . "\"";
    $r .= implode(', ', $values);
    return $r;
}

function returnTweet(){
	
	$oauth_access_token = "";
    $oauth_access_token_secret = "";
    $consumer_key = "";
    $consumer_secret = "";
	
    

    $twitter_timeline           = "user_timeline";  //  mentions_timeline / user_timeline / home_timeline / retweets_of_me

    //  create request
        $request = array(
            'screen_name'       => 'YourAccountHere',			
			'tweet_mode'		=> 'extended',			
            'count'             => '5'
        );

    $oauth = array(
        'oauth_consumer_key'        => $consumer_key,
        'oauth_nonce'               => time(),
        'oauth_signature_method'    => 'HMAC-SHA1',
        'oauth_token'               => $oauth_access_token,
        'oauth_timestamp'           => time(),
        'oauth_version'             => '1.0'
    );

    //  merge request and oauth to one array
        $oauth = array_merge($oauth, $request);

    //  do some magic
        $base_info              = buildBaseString("https://api.twitter.com/1.1/statuses/$twitter_timeline.json", 'GET', $oauth);
        $composite_key          = rawurlencode($consumer_secret) . '&' . rawurlencode($oauth_access_token_secret);
        $oauth_signature            = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
        $oauth['oauth_signature']   = $oauth_signature;

    //  make request
        $header = array(buildAuthorizationHeader($oauth), 'Expect:');
        $options = array( CURLOPT_HTTPHEADER => $header,
                          CURLOPT_HEADER => false,
                          CURLOPT_URL => "https://api.twitter.com/1.1/statuses/$twitter_timeline.json?". http_build_query($request),
                          CURLOPT_RETURNTRANSFER => true,
                          CURLOPT_SSL_VERIFYPEER => false);

        $feed = curl_init();
        curl_setopt_array($feed, $options);
        $json = curl_exec($feed);
        curl_close($feed);

    return json_decode($json, true);
}
?>