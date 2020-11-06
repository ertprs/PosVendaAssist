<?php

function email_pedido($nome, $f_nome, $pedido, $idioma = 'pt-br', $admin = false, $tela = 'nova') {

	if ($demo = strpos($tipo, 'demo')) {
		$tipo = str_replace('_demo', '', $tipo);
    }
    
    $url_final = null;
    if(!$admin){
        if($tela == 'nova'){
            $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $url_primaria = str_replace('cadastro_pedido.php', 'pedido_finalizado.php', $url);
    
            $url_final = $url_primaria."?pedido=" .$pedido;
        }elseif($tela == 'antiga'){
            $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $url_primaria = str_replace('pedido_cadastro.php', 'pedido_finalizado.php', $url);
    
            $url_final = $url_primaria."?pedido=" .$pedido;
        }
        

		switch ($idioma) {
	      case "es":
				$body = "<p>
							<strong>Nota: Este mensaje es automático. **** POR FAVOR NO RESPONDA ESTE MENSAJE ****</strong>
							<br/>
							Apreciado/a $nome,
							<br/>
							Fue finalizado por su puesto la solicitud no. {$pedido} para la Fábrica <strong>{$f_nome}</strong>.
							<br/><br/>
                            <a href={$url_final}>Haga clic aquí para ver el pedido</a>
						</p>";
	    	break;
         case "en":
				$body = "<p>
							<strong>Note: This e-mail is sent automatically. **** PLEASE DO NOT ANSWER THIS MESSAGE ****</strong>
							<br/>
							Dear $nome,
							<br/>
							The request was finalized by his post No. {$pedido} for the Factory <strong>{$f_nome}</strong>.
							<br/><br/>
                            <a href={$url_final}>Click Here to View the Order</a>
						</p>";
	    	break;
	        case "de":
				$body = "<p>
                            <strong>Anm.: Diese mail wurde automatisch erstellt. **** BITTE NICHT ANTWORTEN ****</strong>
                            <br/>
                            Sehr geehrte $nome,
                            <br/>
                            Der Antrag wurde durch seinen Posten abgeschlossen Nr. {$pedido} für die Fabrik <strong>{$f_nome}</strong>.
                            <br/><br/>
                            <a href={$url_final}>Klicken Sie hier, um die Bestellung anzuzeigen</a>
                        </p>";
			break;
			default:
				$body = "<p>
                            Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****
                            <br/>
                            Caro {$nome},
                            <br/>
                            Foi finalizado pelo seu posto o pedido nº {$pedido} para a Fábrica <strong>{$f_nome}</strong>.
                            <br/><br/>
                            <a href={$url_final}>Clique Aqui para Visualizar o Pedido</a>
                        </p>";
		}
    }elseif($admin){

        if($tela == 'nova'){
            $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		    $url_primaria = str_replace('admin/cadastro_pedido.php', 'pedido_finalizado.php', $url);
    
            $url_final = $url_primaria."?pedido=" .$pedido;
        }elseif($tela == 'antiga'){
            $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $url_primaria = str_replace('admin/pedido_cadastro.php', 'pedido_finalizado.php', $url);
    
            $url_final = $url_primaria."?pedido=" .$pedido;
        }

		switch ($idioma) {
	      case "es":
				$body = "<p>
							<strong>Nota: Este mensaje es automático. **** POR FAVOR NO RESPONDA ESTE MENSAJE ****</strong>
							<br/>
							Apreciado/a $nome,
							<br/>
							Fue terminado por la fábrica <strong>{$f_nome}</strong> la solicitud No {$pedido}.
							<br/><br/>
                            <a href={$url_final}>Haga clic aquí para ver el pedido</a>
						</p>";
	    	break;
         case "en":
				$body = "<p>
							<strong>Note: This e-mail is sent automatically. **** PLEASE DO NOT ANSWER THIS MESSAGE ****</strong>
							<br/>
							Dear $nome,
							<br/>
							It was finished by the factory <strong>{$f_nome}</strong> the request no: {$pedido}.
							<br/><br/>
                            <a href={$url_final}>Click Here to View the Order</a>
						</p>";
	    	break;
	        case "de":
				$body = "<p>
                            <strong>Anm.: Diese mail wurde automatisch erstellt. **** BITTE NICHT ANTWORTEN ****</strong>
                            <br/>
                            Sehr geehrte $nome,
                            <br/>
                            Es wurde von der Fabrik fertig gestellt <strong>{$f_nome}</strong> die Anfrage Nr: {$pedido}.
                            <br/><br/>
                            <a href={$url_final}>Klicken Sie hier, um die Bestellung anzuzeigen</a>
                        </p>";
			break;
			default:
				$body = "<p>
                            Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****
                            <br/>
                            Caro {$nome},
                            <br/>
                            Foi finalizado pela fábrica <strong>{$f_nome}</strong> o pedido nº {$pedido}.
                            <br/><br/>
                            <a href={$url_final}>Clique Aqui para Visualizar o Pedido</a>
                        </p>";
		}

    }
        $body.= "<br><br>\n".
			"Suporte Telecontrol Networking.<br>suporte@telecontrol.com.br\n".
			"</p>";

	return $body;

}

function email_pedido_blackeredecker($nome, $f_nome, $pedido, $pedido_blackedecker, $idioma = 'pt-br', $admin = false) {

	if ($demo = strpos($tipo, 'demo')) {
		$tipo = str_replace('_demo', '', $tipo);
    }
    
    $url_final = null;
    if(!$admin){
        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $url_primaria = str_replace('pedido_blackedecker_cadastro.php?finalizar=1&linha=$linha&unificar=t&demanda=true&motivo=%20&obs_motivo=', 'pedido_blackedecker_finalizado_new.php', $url);
        $url_final = $url_primaria ."?msg=$msg&pedido=".$pedido."&bloq=$bloqueio_pedido&hd_chamado=$hd_chamado";
        
		switch ($idioma) {
	      case "es":
				$body = "<p>
							<strong>Nota: Este mensaje es automático. **** POR FAVOR NO RESPONDA ESTE MENSAJE ****</strong>
							<br/>
							Apreciado/a $nome,
							<br/>
							Fue finalizado por su puesto la solicitud no. {$pedido_blackedecker} para la Fábrica <strong>{$f_nome}</strong>.
							<br/><br/>
                            <a href={$url_final}>Haga clic aquí para ver el pedido</a>
						</p>";
	    	break;
         case "en":
				$body = "<p>
							<strong>Note: This e-mail is sent automatically. **** PLEASE DO NOT ANSWER THIS MESSAGE ****</strong>
							<br/>
							Dear $nome,
							<br/>
							The request was finalized by his post No. {$pedido_blackedecker} for the Factory <strong>{$f_nome}</strong>.
							<br/><br/>
                            <a href={$url_final}>Click Here to View the Order</a>
						</p>";
	    	break;
	        case "de":
				$body = "<p>
                            <strong>Anm.: Diese mail wurde automatisch erstellt. **** BITTE NICHT ANTWORTEN ****</strong>
                            <br/>
                            Sehr geehrte $nome,
                            <br/>
                            Der Antrag wurde durch seinen Posten abgeschlossen Nr. {$pedido_blackedecker} für die Fabrik <strong>{$f_nome}</strong>.
                            <br/><br/>
                            <a href={$url_final}>Klicken Sie hier, um die Bestellung anzuzeigen</a>
                        </p>";
			break;
			default:
				$body = "<p>
                            Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****
                            <br/>
                            Caro {$nome},
                            <br/>
                            Foi finalizado pelo seu posto o pedido nº {$pedido_blackedecker} para a Fábrica <strong>{$f_nome}</strong>.
                            <br/><br/>
                            <a href={$url_final}>Clique Aqui para Visualizar o Pedido</a>
                        </p>";
		}
    }elseif($admin){
        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $url_primaria = str_replace('admin/pedido_cadastro_blackedecker.php', 'pedido_blackedecker_finalizado_new.php', $url);
        $url_final = $url_primaria ."?msg=$msg&pedido=".$pedido."&bloq=$bloqueio_pedido&hd_chamado=$hd_chamado";
        
		switch ($idioma) {
	      case "es":
				$body = "<p>
							<strong>Nota: Este mensaje es automático. **** POR FAVOR NO RESPONDA ESTE MENSAJE ****</strong>
							<br/>
							Apreciado/a $nome,
							<br/>
							Fue terminado por la fábrica <strong>{$f_nome}</strong> la solicitud No {$pedido_blackedecker}.
							<br/><br/>
                            <a href={$url_final}>Haga clic aquí para ver el pedido</a>
						</p>";
	    	break;
         case "en":
				$body = "<p>
							<strong>Note: This e-mail is sent automatically. **** PLEASE DO NOT ANSWER THIS MESSAGE ****</strong>
							<br/>
							Dear $nome,
							<br/>
							It was finished by the factory <strong>{$f_nome}</strong> the request no: {$pedido_blackedecker}.
							<br/><br/>
                            <a href={$url_final}>Click Here to View the Order</a>
						</p>";
	    	break;
	        case "de":
				$body = "<p>
                            <strong>Anm.: Diese mail wurde automatisch erstellt. **** BITTE NICHT ANTWORTEN ****</strong>
                            <br/>
                            Sehr geehrte $nome,
                            <br/>
                            Es wurde von der Fabrik fertig gestellt <strong>{$f_nome}</strong> die Anfrage Nr: {$pedido_blackedecker}.
                            <br/><br/>
                            <a href={$url_final}>Klicken Sie hier, um die Bestellung anzuzeigen</a>
                        </p>";
			break;
			default:
				$body = "<p>
                            Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****
                            <br/>
                            Caro {$nome},
                            <br/>
                            Foi finalizado pela fábrica <strong>{$f_nome}</strong> o pedido nº {$pedido_blackedecker}.
                            <br/><br/>
                            <a href={$url_final}>Clique Aqui para Visualizar o Pedido</a>
                        </p>";
		}

    }
        $body.= "<br><br>\n".
			"Suporte Telecontrol Networking.<br>suporte@telecontrol.com.br\n".
			"</p>";

	return $body;

}
?>
