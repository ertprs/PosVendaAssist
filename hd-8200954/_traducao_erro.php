<?php
function traducao_erro($msg_erro,$sistema_lingua) {
	if (!strpos($msg_erro,"ERROR: ") !== false)
		return $msg_erro;

	if($sistema_lingua == "ES") {
		if(strpos($msg_erro,"N�mero de S�rie deve ter o tamanho de 8 digitos"))
			return "El n� de serie debe tener 8 d�gitos.";

		if (strpos($msg_erro,"anterior � data da Nota Fiscal")) 
			return "Fecha de Apertura de OS anterior a fecha de la Factura de Compra.";

		if (strpos($msg_erro,"Posto n�o possui autoriza��o para lan�ar produtos da linha")) 
			return "Servicio no tiene autorizaci�n para lanzar las herramientas de la l�nea";

		if (strpos($msg_erro,"Posto n�o possui autoriza��o para lan�ar produtos da fam�lia")) 
			return "Servicio no tiene autorizaci�n para lanzar las herramientas de la familia";

		if (strpos($msg_erro,"Produto fora da Garantia, vencida em")) {
			$aux = explode('em',$msg_erro);
			return "Herramienta fuera de garant�a, vencida el ".$aux[1] . '.';
		}

		if (strpos($msg_erro,") n�o encontrado")) {
			$aux = explode('s�rie',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "N�mero de serie (OS ".$aux[1].") no encontrado.";
		}

		if (strpos($msg_erro,"na lista b�sica deste produto")) {
			$aux = explode('Pe�a',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "Repuesto ".$aux[0]." no encontrado en la Lista de Materiales de la herramienta.";
		}

		if (strpos($msg_erro,"n�o encontrada na lista b�sica deste produto	")) {
			$aux = explode('Pe�a',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "Repuesto ".$aux[0]." no encontrado en la Lista de Materiales de la herramienta.";
		}

		if (strpos($msg_erro,"em quantidade superior � permitida.")) {
			$aux = explode('Pe�a',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "Repuesto ".$aux[0]." en cantidad superior a la permitida.";
		}

		if (strpos($msg_erro,"indispon�vel ou fora de linha'")) {
			$aux = explode('Pe�a',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "Repuesto ".$aux[0]."  no disponible o ya no se fabrica.";
		}

		if (strpos($msg_erro,"informada, n�o encontrada !")) {
			$aux = explode('Pe�a',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "Repuesto ".$aux[0]."informada , no encontrada.";
		}

		if (strpos($msg_erro,"indispon�vel ou fora de linha")) {
			$aux = explode('Pe�a',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "Repuesto ".$aux[0]." no disponible o fuera de l�nea.";
		}

		if (strpos($msg_erro,"como alternativa")) {
			$aux = explode('Refer�ncia',$msg_erro);
			$aux = explode(' ',trim($aux[1]));

			$aux1 = explode('pe�a',$msg_erro);
			$aux1 = explode(' ',trim($aux1[1]));
			return "Referencia ".$aux[0]." no disponible.<br>Tenemos el repuesto ".$aux1[0]." como alternativa.<br>Si lo desea, puede solicitar este.";
		}

		if (strpos($msg_erro,"Caso queira, favor substituir !")) {
			$aux = explode('Refer�ncia',$msg_erro);
			$aux = explode(' ',trim($aux[1]));

			$aux1 = explode('mudou para',$msg_erro);
			$aux1 = explode(' ',trim($aux1[1]));

			return "Referencia ".$aux[0]." cambi� para ".$aux1[0].".<br>Si lo desea, puede solicitar �sta.";
		}

		if (strpos($msg_erro,"Produto informado, n�o encontrado")) {
			return "Producto informado no encontrado.";
		}

		if (strpos($msg_erro,"� maior que o permitido!")) {
			$aux = explode('itens',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "�Cantidad ".$aux[0]." es mayor que la permitida!";
		}

		if (strpos($msg_erro,"n�o encontrada na lista b�sica para este produto!")) {
			$aux = explode('Pe�a',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "Repuesto ".$aux[0]." no encontrado en la Lista de Materiales para ese producto!";
		}

		if (strpos($msg_erro,"n�o est� precificada. Favor entrar em contato com o fabricante!")) {
			$aux = explode('Pe�a',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "Repuesto ".$aux[0]." informado, sin precio. �Por favor, entrar en contacto con el fabricante!";
		}

		if (strpos($msg_erro,"Numero de serie obrigatorio para produto")) {
			return "N�mero de serie obligatorio para ese producto.";
		}

		if (strpos($msg_erro,"O n�mero de s�rie � composto apenas por n�meros")) {
			$aux = explode('s�rie',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "N�mero de serie ".$aux[0]." inv�lido. El n�mero de serie est� compuesto solo por n�meros.";
		}

		if (strpos($msg_erro,"Segundo d�gito incorreto")) {
			$aux = explode('s�rie',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "N�mero de serie ".$aux[0]." inv�lido. Segundo d�gito incorrecto.";
		}

		if (strpos($msg_erro,"Terceiro d�gito incorreto")) {
			$aux = explode('s�rie',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "N�mero de serie ".$aux[0]." inv�lido. Tercero d�gito incorrecto.";
		}

		if (strpos($msg_erro,"Fabri��o do produto n�o pode ser posterior a data de compra")) {
			$aux = explode('s�rie',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "N�mero de serie ".$aux[0]." inv�lido. La fecha de fabricaci�n del producto no puede ser posterior a fecha de compra.";
		}

		if (strpos($msg_erro,"data_fechamento_anterior_abertura")) {
			return "La fecha de cierre es anterior a la fecha de abertura.";
		}

	}
}

