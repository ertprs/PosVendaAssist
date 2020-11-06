<?php
function traducao_erro($msg_erro,$sistema_lingua) {
	if (!strpos($msg_erro,"ERROR: ") !== false)
		return $msg_erro;

	if($sistema_lingua == "ES") {
		if(strpos($msg_erro,"Número de Série deve ter o tamanho de 8 digitos"))
			return "El nº de serie debe tener 8 dígitos.";

		if (strpos($msg_erro,"anterior à data da Nota Fiscal")) 
			return "Fecha de Apertura de OS anterior a fecha de la Factura de Compra.";

		if (strpos($msg_erro,"Posto não possui autorização para lançar produtos da linha")) 
			return "Servicio no tiene autorización para lanzar las herramientas de la línea";

		if (strpos($msg_erro,"Posto não possui autorização para lançar produtos da família")) 
			return "Servicio no tiene autorización para lanzar las herramientas de la familia";

		if (strpos($msg_erro,"Produto fora da Garantia, vencida em")) {
			$aux = explode('em',$msg_erro);
			return "Herramienta fuera de garantía, vencida el ".$aux[1] . '.';
		}

		if (strpos($msg_erro,") não encontrado")) {
			$aux = explode('série',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "Número de serie (OS ".$aux[1].") no encontrado.";
		}

		if (strpos($msg_erro,"na lista básica deste produto")) {
			$aux = explode('Peça',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "Repuesto ".$aux[0]." no encontrado en la Lista de Materiales de la herramienta.";
		}

		if (strpos($msg_erro,"não encontrada na lista básica deste produto	")) {
			$aux = explode('Peça',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "Repuesto ".$aux[0]." no encontrado en la Lista de Materiales de la herramienta.";
		}

		if (strpos($msg_erro,"em quantidade superior à permitida.")) {
			$aux = explode('Peça',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "Repuesto ".$aux[0]." en cantidad superior a la permitida.";
		}

		if (strpos($msg_erro,"indisponível ou fora de linha'")) {
			$aux = explode('Peça',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "Repuesto ".$aux[0]."  no disponible o ya no se fabrica.";
		}

		if (strpos($msg_erro,"informada, não encontrada !")) {
			$aux = explode('Peça',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "Repuesto ".$aux[0]."informada , no encontrada.";
		}

		if (strpos($msg_erro,"indisponível ou fora de linha")) {
			$aux = explode('Peça',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "Repuesto ".$aux[0]." no disponible o fuera de línea.";
		}

		if (strpos($msg_erro,"como alternativa")) {
			$aux = explode('Referência',$msg_erro);
			$aux = explode(' ',trim($aux[1]));

			$aux1 = explode('peça',$msg_erro);
			$aux1 = explode(' ',trim($aux1[1]));
			return "Referencia ".$aux[0]." no disponible.<br>Tenemos el repuesto ".$aux1[0]." como alternativa.<br>Si lo desea, puede solicitar este.";
		}

		if (strpos($msg_erro,"Caso queira, favor substituir !")) {
			$aux = explode('Referência',$msg_erro);
			$aux = explode(' ',trim($aux[1]));

			$aux1 = explode('mudou para',$msg_erro);
			$aux1 = explode(' ',trim($aux1[1]));

			return "Referencia ".$aux[0]." cambió para ".$aux1[0].".<br>Si lo desea, puede solicitar ésta.";
		}

		if (strpos($msg_erro,"Produto informado, não encontrado")) {
			return "Producto informado no encontrado.";
		}

		if (strpos($msg_erro,"é maior que o permitido!")) {
			$aux = explode('itens',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "¡Cantidad ".$aux[0]." es mayor que la permitida!";
		}

		if (strpos($msg_erro,"não encontrada na lista básica para este produto!")) {
			$aux = explode('Peça',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "Repuesto ".$aux[0]." no encontrado en la Lista de Materiales para ese producto!";
		}

		if (strpos($msg_erro,"não está precificada. Favor entrar em contato com o fabricante!")) {
			$aux = explode('Peça',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "Repuesto ".$aux[0]." informado, sin precio. ¡Por favor, entrar en contacto con el fabricante!";
		}

		if (strpos($msg_erro,"Numero de serie obrigatorio para produto")) {
			return "Número de serie obligatorio para ese producto.";
		}

		if (strpos($msg_erro,"O número de série é composto apenas por números")) {
			$aux = explode('série',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "Número de serie ".$aux[0]." inválido. El número de serie está compuesto solo por números.";
		}

		if (strpos($msg_erro,"Segundo dígito incorreto")) {
			$aux = explode('série',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "Número de serie ".$aux[0]." inválido. Segundo dígito incorrecto.";
		}

		if (strpos($msg_erro,"Terceiro dígito incorreto")) {
			$aux = explode('série',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "Número de serie ".$aux[0]." inválido. Tercero dígito incorrecto.";
		}

		if (strpos($msg_erro,"Fabrição do produto não pode ser posterior a data de compra")) {
			$aux = explode('série',$msg_erro);
			$aux = explode(' ',trim($aux[1]));
			return "Número de serie ".$aux[0]." inválido. La fecha de fabricación del producto no puede ser posterior a fecha de compra.";
		}

		if (strpos($msg_erro,"data_fechamento_anterior_abertura")) {
			return "La fecha de cierre es anterior a la fecha de abertura.";
		}

	}
}

