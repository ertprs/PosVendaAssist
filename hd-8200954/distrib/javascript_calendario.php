<script type="text/javascript" src="../js/jquery-latest.pack.js"></script>
<link rel="stylesheet" type="text/css" href="../js/datePicker.v1.css" title="default" media="screen" />
<script type="text/javascript" src="../js/datePicker.v1.js"></script>
<script type="text/javascript" src="../js/jquery.maskedinput.js"></script>

<script type="text/javascript">

	$(document).ready(init);
	function init(){
		$.datePicker.setDateFormat('dmy', '/');
		$.datePicker.setLanguageStrings(
			['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'],
			['Janeiro', 'Fevereiro', 'Março', 'Abril', 'maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
			{p:'Anterior', n:'Próximo', c:'Fechar', b:'Abrir Calendário'}
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