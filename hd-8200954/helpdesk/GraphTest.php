<?
	include('phpHtmlChart.php');

	$aGraphData = Array
		(array('Apples', 25, 'f'),
		 array('Oranges', 50, 'f'),
		 array('Limes', 15, 'f'),
		 array('Grapes', 11, 'f'),
		 array('Mangos', 32, 'f'),
		 array('Bannans', 17, 'f'),
		 array('Star Fruits', 32, 'f'),
		 array('Pears', 10.5, 'f'),
		 array('Plums', 10, 'f'),
		 array('Peaches', 5, 'f'),
		);

	echo phpHtmlChart($aGraphData, 'H', 'Counting as a function of fruit', 'Numbers of fruit', '8pt', 400, 'px', 15, 'px');
?>