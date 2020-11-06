<script type="text/javascript" src="js/firebug.js"></script>
<script type="text/javascript" src="js/jquery-latest.pack.js"></script>
<script type="text/javascript" src="js/date.js"></script>
<script type="text/javascript" src="js/jquery.dimensions.min.js"></script>
<!--[if IE]><script type="text/javascript" src="js/jquery.bgiframe.min.js"></script><![endif]-->
<script type="text/javascript" src="js/jquery.datePicker.js"></script>
<link rel="stylesheet" type="text/css" media="screen" href="js/datePicker.css">
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>




<style type="text/css">
/*
	p {
		margin: 1em 0;
	}
	ul {
		margin: 0 0 0 20px;
	}
	dt {
		margin: 1em 0 .2em;
		font-weight: bold;
	}
	dd {
		margin: .2em 0 1em;
	}
*/
	#container {
		width: 758px;
		margin: 0 auto;
		padding: 10px 20px;
		background: #fff;
	}
/*
	fieldset {
		margin: 1em 0;
		padding: 0 10px;
		width: 180px;
	}
	label {
		width: 160px;
		display: inline-block;
		line-height: 1.8;
		vertical-align: top;
	}
*/
	#chooseDateForm li {
		list-style: none;
		padding: 5px;
		clear: both;
	}
	/*
	select {
		width: 100px;
	}
	*/

	
	input {
		/*width: 170px;*/

	}
	
	input.dp-applied {
		/*width: 140px;*/
		float: left;
		margin: 5px 0;
	}

	a.dp-choose-date {
		float: left;
		width: 16px;
		height: 16px;
		padding: 0;
		margin: 5px 3px 0;
		display: block;
		text-indent: -2000px;
		overflow: hidden;
		background: url(js/calendar.png) no-repeat; 
	}
	a.dp-choose-date.dp-disabled {
		background-position: 0 -20px;
		cursor: default;
	}

	#calendar-me {
		margin: 20px;
	}
</style>