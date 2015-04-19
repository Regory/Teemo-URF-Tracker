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
					$.post("ajax_batchUpdate.php", {type:'bucket'}).done(
						function(data){
							$('#status').html(data+"<br />");
							timerhandle = setTimeout("nextUpdate()", interval);
						}
					);
				}
				if(toggle == 2){
					$.post("ajax_batchUpdate.php", {type:'check'}).done(
						function(data){
							$('#status').html(data+"<br />");
							timerhandle = setTimeout("nextUpdate()", interval);
						}
					);
				}
				if(toggle == 3){
					$.post("ajax_batchUpdate.php", {type:'analyze'}).done(
						function(data){
							$('#status').html(data+"<br />");
							timerhandle = setTimeout("nextUpdate()", interval);
						}
					);
				}
				if(toggle == 4){
					$.post("ajax_batchUpdate.php", {type:'statistics'}).done(
						function(data){
							$('#status').html(data+"<br />");
						}
					);
				}
			}
			
			
			
		</script>
		
		<input type='button' onclick='flipToggle(1)' value='Check Buckets'>
		<input type='button' onclick='flipToggle(2)' value='Check Matches'>
		<input type='button' onclick='flipToggle(3)' value='Analyze Matches'>
		<input type='button' onclick='flipToggle(4)' value='Update Statistics'>
		
		<br /><br />
		
		<div id='status'>
		</div>
		
		
		
	</body>
</html>