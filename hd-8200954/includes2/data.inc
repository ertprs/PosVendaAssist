		<?php
		$mes_ext = array ("Janeiro","Favereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro");
		$dia_ext = array ("Domingo","Segunda","Terça","Quarta","Quinta","Sexta","Sábado");

		echo $dia_ext[(date ("w",time()))];
		echo ", ";
		echo date ("d",time());
		echo " de " ;
		echo $mes_ext [(date ("m",time())-1)];
		echo " de " ;
		echo date ("Y",time());
		echo ".";
		?>
