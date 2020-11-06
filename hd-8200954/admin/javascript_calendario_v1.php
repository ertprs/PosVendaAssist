<script type="text/javascript" src="js/jquery-latest.pack.js"></script>
<link rel="stylesheet" type="text/css" href="js/datePicker.v1.css" title="default" media="screen" />
<script type="text/javascript" src="js/datePicker.v1.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>

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