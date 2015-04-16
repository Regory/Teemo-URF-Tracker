<?php
	include_once "loadTeemoTracker.php";
?>

<html>
	<head>
		<script src="jquery.js"></script>
	</head>
	<body>
		<h1>Teemo Tracker - Batch Update</h1>
		<script>
			
			var interval = <?php echo MIN_SECONDS_BETWEEN_CALLS ?> * 1000;
			var toggle = 0;
			
			function flipToggle(x){
				if(typeof timerhandle !== 'undefined'){
					clearTimeout(timerhandle);
				}
				if(toggle == x){
					toggle = 0;
				}else{
					toggle = x;
					$('#status').html("Loading");
				}
				timerhandle = setTimeout("nextUpdate()", interval);
			}
			
			function nextUpdate(){
				if(toggle == 1){
					$.post("ajax_getNextBucket.php").done(
						function(data){
							$('#status').html(data+"<br />");
							timerhandle = setTimeout("nextUpdate()", interval);
						}
					);
				}
				if(toggle == 2){
					$.post("ajax_checkNextMatch.php").done(
						function(data){
							$('#status').html(data+"<br />");
							timerhandle = setTimeout("nextUpdate()", interval);
						}
					);
				}
				if(toggle == 3){
					$.post("ajax_analyzeNextMatch.php").done(
						function(data){
							$('#status').html(data+"<br />");
							timerhandle = setTimeout("nextUpdate()", interval);
						}
					);
				}
			}
			
			
			
		</script>
		
		<input type='button' onclick='flipToggle(1)' value='Check Buckets'>
		<input type='button' onclick='flipToggle(2)' value='Check Matches'>
		<input type='button' onclick='flipToggle(3)' value='Analyze Matches'>
		
		<br /><br />
		
		<div id='status'>
		</div>
		
		
		
	</body>
</html>