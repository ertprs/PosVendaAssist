<script type="text/javascript" src="js/jquery-latest.pack.js"></script>
<link rel="stylesheet" type="text/css" href="js/datePicker.v1.css" title="default" media="screen" />
<script type="text/javascript" src="js/datePicker.v1.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<?php
$Dias = array(
	'pt-br'	=> array(
				0 => "Domingo",		"Segunda-feira","Terça-feira",
					 "Quarta-feira","Quinta-feira",	"Sexta-feira",
					 "Sábado"),
	'es'	=> array(
				0 => "Domingo",	"Lunes",	"Martes", "Miércoles",
					 "Jueves",	"Viernes",	"Sábado" ),
	'en-us'	=> array(
				0 => "Sunday",	"Monday", "Tuesday", "Wednesday",
					 "Thursday","Friday", "Saturday")
);

$meses_idioma = array(
	'pt-br'	=> array(1 => "Janeiro", "Fevereiro","Março",	"Abril",
						  "Maio",	 "Junho",	 "Julho",	"Agosto",
						  "Setembro","Outubro",	 "Novembro","Dezembro"),
	'es'	=> array(1 => "Enero",	  "Febrero","Marzo",	"Abril",
						  "Mayo",	  "Junio",	"Julio",	"Agosto",
						  "Septiembre", "Octubre",	"Noviembre","Diciembre"),
	'en-us'	=> array(1 => "January",	"February",	"March",	"April",
						  "May",		"June",		"July",		"August",
						  "September",	"October",	"November",	"December")
);
?>
<script type="text/javascript">

	var Actions = {
		p: '<?=traduz('anterior', $con)?>',
		n: '<?=traduz('proximo', $con)?>',
		c: '<?=traduz('fechar', $con)?>',
		b: '<?=traduz('abrir.calendario', $con)?>',
	};

	$(document).ready(initCal);
	function initCal(){
		$.datePicker.setDateFormat('dmy', '/');
		$.datePicker.setLanguageStrings(
			new Array('<?=implode("','", $Dias[$cook_idioma])?>'),
			new Array('<?=implode("','", $meses_idioma[$cook_idioma])?>'),
			Actions
		);
		
		//$('input.date-picker').datePicker();
		
		$('input.date-picker').datePicker({startDate:'05/03/2006'});

		//$('input.date-picker').datePicker({endDate:'05/11/2006'});

		//$('input.date-picker').datePicker({startDate:'05/03/2006', endDate:'05/11/2006'});

		//$('input#date1').datePicker();

		//$('input#date2').datePicker({startDate:'02/11/2006', endDate:'13/11/2006'});

		/*
		$('input#date1').bind(
			'change',
			function(){
				alert($(this).val());
			}
		);
		*/
	}
</script>



<?

#include "javascript_calendario_v1.php";
?>

<? if (1==2 ) { ?>
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
		width: 180
		*/
<?}?>
