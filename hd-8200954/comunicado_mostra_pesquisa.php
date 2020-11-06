<?#
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

$title = mb_strtoupper(traduz('comunicados', $con) . ' ' . $login_fabrica_nome);
$layout_menu = "tecnica";

include 'cabecalho.php';
include "javascript_pesquisas.php";

if ($S3_sdk_OK) {
	include_once S3CLASS;

	$s3 = new anexaS3('ve', (int) $login_fabrica);
	$S3_online = is_object($s3);
}
?>

<script src="js/jquery-1.6.2.js" ></script>
<script src="js/jquery.blockUI_2.39.js" ></script>
<script>
    $(function () {
        $("a[name=prod_ve]").click(function () {
			var attr       = $(this).attr("rel").split("/");
			var comunicado = attr[0];
            var tipo       = attr[1];

            $.ajaxSetup({
                async: true
            });

            $.blockUI({ message: "Aguarde..." });

            $.get("verifica_s3_comunicado.php", { comunicado: comunicado, fabrica: "<?=$login_fabrica?>", tipo: tipo }, function (data) {
    		  	if (data.length > 0) {
	            	var nav = window.navigator.userAgent;
					var newWin = window.open(data, "_blank", "menubar=no, titleblar=no, status=no, location=no, resizable=yes");

					if (nav.match(/Chrome/gi) && nav.match(/Safari/gi)) {
						popupBlockerChecker.check(newWin);
					} else {
						if (!newWin) {
		                    Shadowbox.init();

		                    Shadowbox.open({
		                	    content :   "popup_bloqueado.php",
		                    	player  :   "iframe",
								title   :   "POPUP BLOQUEADO",
								width   :   800,
								height  :   600
							});
						}
					}
				} else {
                    alert("Arquivo não encontrado!");
                }

                $.unblockUI();
            });
        });
    });

	var popupBlockerChecker = {
		check: function(popup_window) {
			var _scope = this;

			if (popup_window) {
				if (/chrome/.test(navigator.userAgent.toLowerCase())) {
					setTimeout(function() {
						_scope._is_popup_blocked(_scope, popup_window);
					}, 500);
				}else{
					popup_window.onload = function() {
						_scope._is_popup_blocked(_scope, popup_window);
					};
				}
			}else{
				_scope._displayMsg();
			}
		},
		_is_popup_blocked: function(scope, popup_window){
			if ((popup_window.screenX > 0) == false) {
				scope._displayMsg();
		    }
		},
		_displayMsg: function() {
			Shadowbox.init();

			Shadowbox.open({
				content :   "popup_bloqueado.php",
				player  :   "iframe",
				title   :   "POPUP BLOQUEADO",
				width   :   800,
				height  :   600
			});
		}
	};

function MostraEsconde(dados,dados2,imagem,titulo,conteudo){
	if (document.getElementById){
		var style2 = document.getElementById(dados);
		var style3 = document.getElementById(dados2);
		var img    = document.getElementById(imagem);
		var title  = document.getElementById(titulo);
		var body   = document.getElementById(conteudo);
		if (style2.style.display){
			img.src='imagens/mais.gif';
			$(title).addClass('esconde');
			$(body).addClass('esconde');
			style2.style.display = "";
			style3.style.display = "";
		}else{
			img.src='imagens/menos.gif';
			$(title).removeClass('esconde');
			$(body).removeClass('esconde');
			style2.style.display = "block";
			style3.style.display = "block";
		}
	}
}

</script>

<style type="text/css">
.chapeu {
	color: #0099FF;
	padding: 2px;
	margin-bottom: 4px;
	margin-top: 10px;
	background-image: url(http://img.terra.com.br/i/terramagazine/tracejado3.gif);
	background-repeat: repeat-x;
	background-position: bottom;
	font-size: 13px;
	font-weight: bold;
}
.menu {
	font-size: 11px;
}
.detalhes{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 12px;
	color: #333399;
}
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #507196;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.exibe_Titulo{
	display:none;
	text-align: center;
	font-family: Verdana, Tahoma, Arial, Geneva, Helvetica, sans-serif;
	font-size: 11 px;
	font-weight: bold;
	background-color: #D9E2EF
}
.exibe_Conteudo{
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	display:none;
}
.esconde{
	display:none;
}

.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

</style>

<?
include "verifica_adobe.php";
?>

<?
if (strlen($_GET["acao"]) > 0) $acao = $_GET["acao"];

	if($_GET["data_inicial"])       $data_inicial       = $_GET["data_inicial"];
	if($_GET["data_final"])         $data_final         = $_GET["data_final"];
	if($_GET["tipo"])               $tipo               = $_GET["tipo"];
	if($_GET["descricao"])          $descricao          = $_GET["descricao"];
	if($_GET["produto_referencia"]) $produto_referencia = $_GET["produto_referencia"];
	if($_GET["produto_descricao"])  $produto_descricao  = $_GET["produto_descricao"];
	if($_GET["produto_voltagem"])   $produto_voltagem   = $_GET["produto_voltagem"];
	if($_GET["administrativo"])     $administrativo     = $_GET["administrativo"];

	if (empty($data_inicial) && empty($data_final) && empty($tipo) && empty($descricao) && empty($produto_referencia) && empty($produto_descrica)) {
		$erro = "Selecione algum campo para pesquisa!";
	}

	if($login_fabrica == 30){
		if($_GET["familia"])     		  $familia     		 = $_GET["familia"];
	}

    if($login_fabrica == 15){
        if( empty($data_inicial) AND empty($data_final) AND empty($tipo ) AND empty($descricao) AND empty($produto_referencia ) AND empty($produto_descricao ) AND empty($produto_voltagem ) AND empty($administrativo) ){
            $mostraCemPrimeiros = true;
        }else{
            $mostraCemPrimeiros = false;
        }
    }
if ($acao == "PESQUISAR") {
	$ok = "";


if(strlen($erro)==0){


//PEGA O TIPO DO POSTO PARA MOSTRAR OS COMUNICADOS DOS MESMOS. QDO COMU = NULL TODOS PODEM VER
	$sql2 = "SELECT tbl_posto_fabrica.codigo_posto           ,
					tbl_posto_fabrica.tipo_posto             ,
					tbl_posto_fabrica.pedido_em_garantia     ,
					tbl_posto_fabrica.pedido_faturado        ,
					tbl_posto_fabrica.digita_os              ,
					tbl_posto_fabrica.reembolso_peca_estoque
			FROM	tbl_posto
			LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
			AND     tbl_posto.posto   = $login_posto ";


	$res2 = pg_query($con,$sql2);

	if (pg_num_rows($res2) > 0) {
		$tipo_posto             = trim(pg_fetch_result($res2,0,'tipo_posto'));
		$pedido_em_garantia     = trim(pg_fetch_result($res2,0,'pedido_em_garantia'));
		$pedido_faturado        = trim(pg_fetch_result($res2,0,'pedido_faturado'));
		$digita_os              = trim(pg_fetch_result($res2,0,'digita_os'));
		$reembolso_peca_estoque = trim(pg_fetch_result($res2,0,'reembolso_peca_estoque'));
	}

	if ($login_fabrica==1) {		//HD 10983
		$sql_cond1 = ' tbl_comunicado.pedido_em_garantia     IS ';
		$sql_cond2 = ' tbl_comunicado.pedido_faturado        IS ';
		$sql_cond3 = ' tbl_comunicado.digita_os              IS ';
		$sql_cond4 = ' tbl_comunicado.reembolso_peca_estoque IS ';
		
		$sql_cond5=" AND (tbl_comunicado.destinatario_especifico = '$categoria' or tbl_comunicado.destinatario_especifico = '') ";
		$sql_cond6=" AND (tbl_comunicado.tipo_posto = '$tipo_posto' or tbl_comunicado.tipo_posto is null) ";

		$sql_cond1 .= ($pedido_em_garantia     == "t") ? " NOT FALSE " : 'NULL ';
		$sql_cond2 .= ($pedido_faturado        == "t") ? " NOT FALSE " : 'NULL ';
		$sql_cond3 .= ($digita_os              == "t") ? " TRUE "      : 'NULL ';
		$sql_cond4 .= ($reembolso_peca_estoque == "t") ? " TRUE "      : 'NULL ';
		$sql_cond_total="AND ( $sql_cond1 or $sql_cond2 or $sql_cond3 or $sql_cond4) ";
	}
	$sql_cond_linha = "
			AND (tbl_comunicado.linha IN
				(
					SELECT tbl_linha.linha
					FROM tbl_posto_linha
					JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha
					WHERE fabrica =$login_fabrica
						AND posto = $login_posto
				)
				OR (
						tbl_comunicado.produto IS NULL AND
						tbl_comunicado.comunicado IN (
							SELECT tbl_comunicado_produto.comunicado
							FROM tbl_comunicado_produto
							JOIN tbl_produto ON tbl_comunicado_produto.produto = tbl_produto.produto
							JOIN tbl_posto_linha on tbl_posto_linha.linha = tbl_produto.linha
							WHERE fabrica_i =$login_fabrica AND
								  tbl_posto_linha.posto = $login_posto

						)

				)
				OR
				    (
					tbl_comunicado.linha IS NULL AND
					tbl_comunicado.produto in
						(
							SELECT tbl_produto.produto
							FROM tbl_produto
							JOIN tbl_posto_linha ON tbl_produto.linha = tbl_posto_linha.linha
							WHERE fabrica_i = $login_fabrica AND
							posto = $login_posto
						)
						)
				OR (
							tbl_comunicado.linha IS NULL AND tbl_comunicado.produto IS NULL AND
							(tbl_comunicado.comunicado IN (
								SELECT tbl_comunicado_produto.comunicado
								FROM tbl_comunicado_produto
								JOIN tbl_produto ON tbl_comunicado_produto.produto = tbl_produto.produto
								JOIN tbl_posto_linha on tbl_posto_linha.linha = tbl_produto.linha
								WHERE fabrica_i =$login_fabrica AND
									  tbl_posto_linha.posto = $login_posto

								)
								or tbl_comunicado_produto.comunicado isnull
							)
						)
					)";

		if (in_array($login_fabrica,array(15,42)))
			$sql_cond_linha = "";

	    $sql = "SELECT  tbl_comunicado.comunicado                                       ,
					CASE WHEN tbl_comunicado.produto IS NULL THEN prod.voltagem   ELSE tbl_produto.voltagem   END AS produto_voltagem,
					CASE WHEN tbl_comunicado.produto IS NULL THEN prod.referencia ELSE tbl_produto.referencia END AS produto_referencia,
					CASE WHEN tbl_comunicado.produto IS NULL THEN prod.descricao  ELSE tbl_produto.descricao  END AS produto_descricao,
					tbl_comunicado.descricao                                        ,
					tbl_comunicado.mensagem                                         ,
					tbl_comunicado.tipo                                             ,
                    tbl_comunicado.video                                            ,
					tbl_comunicado.link_externo                                            ,
					TO_CHAR(tbl_comunicado.data,'DD/MM/YYYY') AS data,
					tbl_comunicado.extensao
			FROM      tbl_comunicado
			LEFT JOIN tbl_comunicado_produto ON tbl_comunicado_produto.comunicado = tbl_comunicado.comunicado
			LEFT JOIN tbl_produto            ON tbl_produto.produto               = tbl_comunicado.produto
			LEFT JOIN tbl_produto prod       ON prod.produto                      = tbl_comunicado_produto.produto
			LEFT JOIN tbl_posto_fabrica      ON tbl_posto_fabrica.posto           = $login_posto
										    AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_comunicado.fabrica      = $login_fabrica
			AND    (tbl_comunicado.tipo_posto = $tipo_posto    OR tbl_comunicado.tipo_posto IS NULL)
			AND   ((tbl_comunicado.posto      = $login_posto)  OR (tbl_comunicado.posto     IS NULL))
			AND tbl_comunicado.ativo IS TRUE ";

        if(!$mostraCemPrimeiros or $login_fabrica != 15){
            if ($data_inicial != "dd/mm/aaaa" && strlen($data_inicial) == 10 && $data_final != "dd/mm/aaaa" && strlen($data_final) == 10) {
                list($dia, $mes, $ano) = explode("/", $data_inicial);
                $data_inicial = $ano . "-" . $mes . "-" . $dia;
                list($dia, $mes, $ano) = explode("/", $data_final);
                $data_final = $ano . "-" . $mes . "-" . $dia;

                $sql .= " AND tbl_comunicado.data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'";
                $ok = "ok";
            }
            if (strlen($tipo) > 0) {
                if($tipo == 'todos') {
                    $sql .=" ";
                }elseif($tipo =='zero'){
                    $sql .= " AND tbl_comunicado.tipo IS NULL ";
                }else{
                    $sql .= " AND tbl_comunicado.tipo = '$tipo'";
                }
                $ok = "ok";
            }
            if (strlen($descricao) > 0) {
                $sql .= " AND tbl_comunicado.descricao ILIKE '%$descricao%'";
                $ok = "ok";
            }
        }
		if ($login_fabrica==14 or $login_fabrica == 66) {    //29/03/2010 MLG - HD 220853
			$sql .=" AND 	CASE WHEN tbl_comunicado.tipo_posto IS NULL THEN TRUE
			                    ELSE
									CASE WHEN tbl_posto_fabrica.tipo_posto = tbl_comunicado.tipo_posto
									THEN TRUE
									ELSE FALSE
									END
							END ";
		}
		if (!$mostraCemPrimeiros OR $login_fabrica != 15) {
			if (strlen($produto_referencia) > 0 or strlen($produto_descricao) > 0 or strlen($produto_voltagem) > 0) {

				if (strlen($produto_referencia) > 0) {
					// HD 16189
					$sqlx = "SELECT tbl_produto.produto, tbl_produto.familia
							   FROM tbl_produto
							   JOIN tbl_linha ON tbl_produto.linha  = tbl_linha.linha
							  WHERE tbl_produto.referencia_pesquisa = '$produto_referencia'
							    AND tbl_linha.fabrica = $login_fabrica";
					if (strlen($produto_voltagem) > 0) {
						$sqlx .=" AND tbl_produto.voltagem = '$produto_voltagem' ";
					}
					$resx = pg_query($con,$sqlx);
					if (pg_num_rows($resx) > 0){
						$produto = pg_fetch_result($resx,0,produto);

						//hd 53987
						if ($login_fabrica == 3) {
							$familia = pg_fetch_result($resx,0,familia);
							if (strlen($familia) > 0 && $familia == 1281) {
								$sql .= "AND ( (tbl_comunicado.familia = $familia and tbl_comunicado.produto is null) or (";
							}
							else {
								$familia = "";
							}
						}
					}

					if (strlen($familia) == 0) {
						$sql .= " AND ";
					}

					$sql .= " (tbl_produto.referencia ILIKE '%$produto_referencia%' OR prod.referencia ILIKE '%$produto_referencia%') ";
					$ok = "ok";
				}

				if (strlen($produto_descricao) > 0) {
					$sql .= " AND (tbl_produto.descricao ILIKE '%$produto_descricao%' OR prod.descricao ILIKE '%$produto_descricao%')";
					$ok = "ok";
				}
				if (strlen($produto_voltagem) > 0) {
					$sql .= " AND (tbl_produto.voltagem ILIKE '%$produto_voltagem%' OR prod.voltagem ILIKE '%$produto_voltagem%')";
					$ok = "ok";
				}

				if (strlen($familia) > 0) {
					$sql .= " )) ";
				}
			}
		}
		if($login_fabrica == 30 and strlen($familia) > 0 ){
			$sql .= " and tbl_comunicado.familia = '$familia' ";
		}
		if($login_fabrica == 20){
			$sql .= " and tbl_comunicado.pais = '$login_pais' ";
		}
		//HD 10983
		if($login_fabrica==1){
			$sql.=" $sql_cond_total ";
			$sql.= $sql_cond5;
			$sql.= $sql_cond6;
		}
		if(!$mostraCemPrimeiros && $login_fabrica != 15){
			if(strlen($administrativo) > 0) {
				$sql .=  " AND tbl_comunicado.produto IS NULL
			AND ((tbl_comunicado.tipo_posto =  $tipo_posto)  OR (tbl_comunicado.tipo_posto IS NULL))
			AND ((tbl_comunicado.posto      =  $login_posto) OR (tbl_comunicado.posto      IS NULL))
			AND tbl_comunicado.ativo        IS TRUE
			AND (tbl_comunicado.linha       IN
							(
								SELECT tbl_linha.linha
								FROM tbl_posto_linha
								JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha
								WHERE fabrica =$login_fabrica
								AND posto = $login_posto
							)
							OR tbl_comunicado.linha IS NULL
						) ";
				$ok = "ok";
			}
		}
		$sql.=" $sql_cond_linha ";
		if($login_fabrica==19){
			$sql .= " ORDER BY tbl_comunicado.tipo, tbl_comunicado.data DESC";
		}else{
			$sql .= " ORDER BY tbl_comunicado.data DESC";
		}
	}

	if ($mostraCemPrimeiros) {
		$sql .= " LIMIT 100 ";

		$ok = "ok";
	}

	#if($ip=='187.39.215.117') echo nl2br($sql);

	if (strlen($ok) == 0) {
		echo "<table width='700' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
		if(strlen($erro)>0){
			echo "<tr class='msg_erro'><td>".$erro."</td></tr>";
		}
		echo "<tr class='Conteudo' bgcolor='#D9E2EF' height='15'>";
		echo "<td colspan='4' align='center'>&nbsp;<br><b>" .
			 traduz(mb_strtoupper('informe.os.parametros.corretos.para.consulta'), $con) .
			 "</b><br>&nbsp;</td>";
		echo "</tr>";
		echo "<tr bgcolor='#D9E2EF' height='15'>";
		echo "<td colspan='4' align='center'><a href='comunicado_mostra.php'>";
		echo traduz(mb_strtoupper('clique.aqui.para.fazer.uma.nova.busca'), $con);
		echo "</a></td></tr>";
		echo "</table>";
	}else{
		if($login_fabrica<>19 ){
            if(!$mostraCemPrimeiros){
                // ##### PAGINACAO ##### //
                $sqlCount  = "SELECT count(*) FROM (" . $sql . ") AS count";
                require "_class_paginacao.php";

                // definicoes de variaveis
                $max_links = 11;				// máximo de links à serem exibidos
                $max_res   = 20;				// máximo de resultados à serem exibidos por tela ou pagina
                $mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
                $mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

                $res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

                // ##### PAGINACAO ##### //
                echo "<font size='1' color='#A02828'>".traduz("se.voce.nao.possui.o.acrobat.reader",$con,$cook_idioma)." &reg;, <a href='http://www.adobe.com/products/acrobat/readstep2.html'>".traduz("instale.agora",$con,$cook_idioma)."</a>.</font><br>";
                echo "<table width='650' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
                if($login_fabrica <> 14) { // HD 44360
                    echo "<tr bgcolor='#D9E2EF' height='15'>";
                    echo "<td colspan='5' align='center'><a href='comunicado_mostra.php'>";
                    echo traduz(mb_strtoupper('clique.aqui.para.fazer.uma.nova.busca'), $con);
                    echo "</a></td></tr>";
                }

                if (@pg_num_rows($res) > 0) {
                    echo "<tr class='Titulo' height='15'>";
                    echo "<td id='teste1' colspan='5' bgcolor='#507196' >".strtoupper(traduz("clique.em.abrir.arquivo.para.visualizar",$con,$cook_idioma))."<br></td>";

                    echo "</tr>";
                    echo "<tr class='Titulo' height='15'>";
                    echo "<td>".strtoupper(traduz("data",             $con, $cook_idioma))."</td>";
                    echo "<td>".strtoupper(traduz("produto",          $con, $cook_idioma))."</td>";
                    echo "<td>".strtoupper(traduz("Titulo/descricao", $con, $cook_idioma))."</td>";
                    echo "<td>".strtoupper(traduz("tipo",             $con, $cook_idioma))."</td>";
                    echo "<td>".strtoupper(traduz("arquivo",          $con, $cook_idioma))."</td>";
                    echo "</tr>";

                    for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
                        $comunicado          = trim(pg_fetch_result($res,$i,'comunicado'));
                        $produto_referencia  = trim(pg_fetch_result($res,$i,'produto_referencia'));
                        $produto_descricao   = trim(pg_fetch_result($res,$i,'produto_descricao'));
                        $produto_voltagem    = trim(pg_fetch_result($res,$i,'produto_voltagem'));
                        if(strlen($produto_referencia) OR strlen($produto_descricao) > 0 ){
                            $produto_completo    = $produto_referencia . " - " . $produto_descricao;
                        }else{
                            $produto_completo = "";
                        }
                        $descricao           = trim(pg_fetch_result($res,$i,'descricao'));
                        $tipo                = trim(pg_fetch_result($res,$i,'tipo'));
                        $comunicado_mensagem = trim(pg_fetch_result($res,$i,'mensagem'));
                        $video               = trim(pg_fetch_result($res,$i,'video'));
                        $link                = trim(pg_fetch_result($res,$i,'link_externo'));
                        $data                = trim(pg_fetch_result($res,$i,'data'));
                        $extensao            = pg_fetch_result($res, $i, "extensao");

                        if(strlen($tipo) == 0) $tipo='Sem título' ;

                        $cor = ($i % 2 == 0) ? "#F1F4FA" : "#FEFEFE";

                        echo "<tr class='Conteudo' bgcolor='$cor'>";
                        echo "<td nowrap align='center'><a href =\"javascript:MostraEsconde('dados_$tipo-$i','dados2_$tipo-$i','visualizar_$i','titulo_tr-$i','conteudo_tr-$i')\">" . $data . "</a></td>";
                        echo "<td nowrap><a href =\"javascript:MostraEsconde('dados_$tipo-$i','dados2_$tipo-$i','visualizar_$i','titulo_tr-$i','conteudo_tr-$i')\"><img src='imagens/mais.gif' id='visualizar_$i'><acronym title='".strtoupper(traduz("referencia",$con,$cook_idioma)).": $produto_referencia\n Descrição: $produto_descricao\n".strtoupper(traduz("voltagem",$con,$cook_idioma)).": $produto_voltagem' style='cursor: help;'>" . substr($produto_completo,0,25) . "</acronym></a></td>";
                        echo "<td nowrap><a href =\"javascript:MostraEsconde('dados_$tipo-$i','dados2_$tipo-$i','visualizar_$i','titulo_tr-$i','conteudo_tr-$i')\"><acronym title=' " . nl2br($descricao) . "' style='cursor: help;'>";
                        if($login_fabrica == 14 or $login_fabrica == 3) {
                            echo "$descricao";
                        }else{
                            echo " ".substr($descricao,0,37) ;
                        }
                        echo "</a></td>";
                        echo "<td nowrap align='center'>" .
							"<a href =\"javascript:MostraEsconde('dados_$tipo-$i','dados2_$tipo-$i','visualizar_$i','titulo_tr-$i','conteudo_tr-$i')\">" .
							$tipo;
                        echo "</a>\n</td>";
                        //				echo "<td nowrap align='center'>" . $data . "</td>";

                        echo "<td align='center' nowrap>";
                        echo "&nbsp;";

                        if ($tipo != 'auditoria_online') {
                            $aTag = '';
                            if ($S3_online and $tipo != 'auditoria_online') {
								$anexo = "";
								if($extensao) {
									$aTag = "<a href='JavaScript:void(0);' name='prod_ve' rel='$comunicado/$tipo'>";
								}else{
									$sqlc = "SELECT tdocs_id from tbl_tdocs where
										fabrica = $login_fabrica
										and referencia_id = $comunicado
										and contexto = 'comunicados'
										and situacao = 'ativo'";
									$resc = pg_query($con,$sqlc);				
									if(pg_num_rows($resc) > 0) {
										$tdocs_id = pg_fetch_result($resc,0, 'tdocs_id') ;
										$link = "https://api2.telecontrol.com.br/tdocs/document/id/$tdocs_id";
										$aTag = "<a href='$link' target='_blank'>";
									}else{
										$aTag = null;
									}
								}
                            } else {
                                //HD 15634 +JPG
                                $filexts = array('gif', 'jpg', 'pdf', 'doc', 'rtf', 'xls', 'zip', 'pps');

								$sqlc = "SELECT tdocs_id from tbl_tdocs where
										fabrica = $login_fabrica
										and referencia_id = $comunicado
										and contexto = 'comunicados'
										and situacao = 'ativo'";
								$resc = pg_query($con,$sqlc);				
								if(pg_num_rows($resc) > 0) {
									$tdocs_id = pg_fetch_result($resc,0, 'tdocs_id') ;
									$link = "https://api2.telecontrol.com.br/tdocs/document/id/$tdocs_id";
                                    $aTag = "<a href='$link' target='_blank'>";
								}else{
									$aTag = null;
								}

                            }
                            echo $aTag;
                        } else {
                            echo '<a href="geraPDF_auditoria.php?comunicado='.$comunicado.'">';
                        }

                        if (strlen($comunicado_mensagem) == 0) {
                            $comunicado_mensagem = "<center>".traduz("nao.ha.mensagem.cadastrada",$con,$cook_idioma)."</center>";
                        }

                        if($tipo == "Contrato"){

                        	echo "</a>";

                        	echo "<a href='download_contrato_posto.php?prestacao_servico=true' target='_blank'>";
                        		fecho ("download.contrato",$con,$cook_idioma);
                        	echo "</a>";

                        }else{

                        	fecho ("abrir.arquivo",$con,$cook_idioma);
                        	echo "</a>";

                        }
                        
                        echo "</td>";
                        echo "</tr>";
                        echo "<tr class='esconde' id='titulo_tr-$i'>";
                        echo "<td colspan='5'>";
                        echo "<div class='exibe_Titulo' id ='dados_$tipo-$i'>";
                        fecho ("mensagem",$con,$cook_idioma);
                        if (in_array($login_fabrica, array(11,50)) and $video<>""){ ?>
                            <P><?=$descricao?><BR>
                                   <A href="javascript:window.open('video.php?video=<?=$video?>','_blank','location=no, status=no, menubar=no, scrollbars=no, resizable=yes, width=460, height=380');void(0);">
                                   Assistir vídeo anexado</A>
                                   </P>
<?	                    }
                        if($login_fabrica == 42 && strlen($link) > 0){
?>
                            <P><?=$descricao?><BR>
                            <a href="<?=$link?>" target="_blank">Clique aqui para acessar ao link</a>
<?
                        }
                        echo "</div>";
                        echo "</td>";
                        echo "</tr>";
                        echo "<tr class='esconde' id='conteudo_tr-$i'>";
                        echo "<td colspan='5'><br>";
                        echo "<div class='exibe_Conteudo' id='dados2_$tipo-$i'>";

                        	if($tipo == "Contrato"){

                        		echo "
                                    <table width='80%' style='margin: 0 auto;'>
                                        <tr>
                                            <td> <img src='logos/logo_black_2016.png' alt='logo' width='300px'> </td>
                                            <td align='right'> Uberaba, 2 de maio de 2017 </td>
                                        </tr>
                                        <tr>
                                            <td colspan='2' align='center'>
                                                <br />
                                                <h4>Prezado parceiro,</h4>
                                                <br />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan='2'>
                                                Com o objetivo de estabelecer os direitos e deveres de nossa parceria e obter 100% dessas informações online, disponibilizamos o ACORDO DE PRESTAÇÃO DE SERVIÇOS revisado. 
                                                <br /> <br />
                                                Portanto, gentileza imprimir este comunicado e seguir as etapas abaixo para liberação do sistema:
                                                <br /> <br />
                                                1. O <strong>Representante Legal</strong> da empresa deverá: <br />
                                                &nbsp; &nbsp; &nbsp; 1.1. Imprimir uma cópia do Acordo anexado ao comunicado <br />
                                                &nbsp; &nbsp; &nbsp; 1.2. Rubricar (vistar) as páginas 1 e 2 <br />
                                                &nbsp; &nbsp; &nbsp; 1.3. Assinar e carimbar a página 3 (conforme RG) <br />
                                                2. A <u>Testemunha</u> da empresa deverá: <br />
                                                &nbsp; &nbsp; &nbsp; 2.1. Rubricar (vistar) as páginas 1 e 2 <br />
                                                &nbsp; &nbsp; &nbsp; 2.2. Na página 3, preencher o nome completo no campo da \"testemunha 2\" <br />
                                                &nbsp; &nbsp; &nbsp; 2.3. Informar o RG ou CPF <br />
                                                3. Após assinatura do representante e testemunha: <br />
                                                3.1. Anexar o acordo completo no Telecontrol (passo a passo abaixo) <br />
                                                3.2. Anexar o Contrato Social da empresa ou Requerimento de Empresário <br />
                                                3.3. Anexar RG frente e verso do Representante Legal/Administrador <br />
                                                <strong>PASSO A PASO</strong>: Menu inicial > Cadastro > Informações do posto > Upload De Contratos
                                                <br /> <br />
                                                <strong>Observações importantes:</strong> Caso seu contrato apresente erro ou as informações não estejam de acordo (apenas nesses casos), gentileza abrir um chamado escolhendo o tipo de solicitação \"Atualização de cadastro\" com o contrato anexado. 
                                                Se os dados estiverem corretos, solicitamos que o contrato de prestação de serviço seja anexado em um arquivo PDF e o contrato social da empresa / RG frente e verso em outro arquivo PDF. <br />
                                                O sistema Telecontrol será bloqueado automaticamente após 30 dias corridos caso não tivermos retorno da solicitação acima. 
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan='2' align='center'>
                                                <br /> <br />
                                                Contamos com a colaboração de todos. <br /> <br />
                                                Qualquer dúvida, gentileza entrar em contato com o suporte de sua região. <br /> <br />
                                                Departamento de Assistência Técnica <br />
                                                STANLEY BLACK&DECKER
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan='2' align='center'>
                                                <br />";
                                                echo "<a href='download_contrato_posto.php?prestacao_servico=true' target='_blank' style='text-transform: uppercase;'>";
                                                    fecho ("realizar.o.download.do.contrato",$con,$cook_idioma);
                                                echo "</a>";
                                                echo "
                                                <br /> <br />
                                            </td>
                                        </tr>
                                    </table>
                                    <br />
                                ";

                        	}else{

                        		echo nl2br($comunicado_mensagem);

                        	}

                        echo "</div><br></td>";
                        echo "</tr>";
                    }
                }else{
                    echo "<tr class='Conteudo' bgcolor='#D9E2EF' height='15'>";
                    echo "<td colspan='5' align='center'>&nbsp;<br><b>".strtoupper(traduz("nenhum.resultado.encontrado",$con,$cook_idioma))."</b><br>&nbsp;</td>";
                    echo "</tr>";
                }
                if($login_fabrica <> 14) { // HD 44360
                    echo "<tr bgcolor='#D9E2EF' height='15'>";
                    echo "<td colspan='5' align='center'><a href='comunicado_mostra.php'>";
                    echo traduz(mb_strtoupper('clique.aqui.para.fazer.uma.nova.busca'), $con);
                    echo "</a></td></tr>";
                }
                echo "</table>";

                // ##### PAGINACAO ##### //

                // links da paginacao
                echo "<br>";

                echo "<div>";

                if($pagina < $max_links) {
                    $paginacao = pagina + 1;
                }else{
                    $paginacao = pagina;
                }

                // paginacao com restricao de links da paginacao

                // pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
                $todos_links		= $mult_pag->Construir_Links("strings", "sim");

                // função que limita a quantidade de links no rodape
                $links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

                for ($n = 0; $n < count($links_limitados); $n++) {
                    echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
                }

                echo "</div>";

                $resultado_inicial = ($pagina * $max_res) + 1;
                $resultado_final   = $max_res + ( $pagina * $max_res);
                $registros         = $mult_pag->Retorna_Resultado();

                $valor_pagina   = $pagina + 1;
                $numero_paginas = intval(($registros / $max_res) + 1);

                if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

                if ($registros > 0){
                    echo "<br>";
                    echo "<div>";
                    echo traduz("resultados.de.%.a.%.do.total.de.%.registros",$con,$cook_idioma,array($resultado_inicial,$resultado_final,$registros)).".";
                    echo "<font color='#cccccc' size='1'>";
                    echo "(".traduz("pagina.%.de.%",$con,$cook_idioma,array($valor_pagina,$numero_paginas)).")";
                    echo "</font>";
                    echo "</div>";
                }
                // ##### PAGINACAO ##### //
            }else{
                $res = pg_query($con, $sql);
                if(pg_num_rows($res) > 0 ){
                    echo "<table width='650' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
                    if($login_fabrica <> 14) { // HD 44360
                        echo "<tr bgcolor='#D9E2EF' height='15'>";
                        echo "<td colspan='5' align='center'><a href='comunicado_mostra.php'>";
                        echo mb_strtoupper(traduz('clique.aqui.para.fazer.uma.nova.busca', $con));
                        echo "</a></td></tr>";
                    }

                    if (@pg_num_rows($res) > 0) {
                        echo "<tr class='Titulo' height='15'>";
                        echo "<td id='teste1' colspan='5' bgcolor='#507196' >".strtoupper(traduz("clique.em.abrir.arquivo.para.visualizar",$con,$cook_idioma))."<br></td>";

                        echo "</tr>";
                        echo "<tr class='Titulo' height='15'>";
                        echo "<td>".strtoupper(traduz("data",             $con, $cook_idioma))."</td>";
                        echo "<td>".strtoupper(traduz("produto",          $con, $cook_idioma))."</td>";
                        echo "<td>".strtoupper(traduz("Titulo/descricao", $con, $cook_idioma))."</td>";
                        echo "<td>".strtoupper(traduz("tipo",             $con, $cook_idioma))."</td>";
                        echo "<td>".strtoupper(traduz("arquivo",          $con, $cook_idioma))."</td>";
                        echo "</tr>";

                        for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
                            $comunicado          = trim(pg_fetch_result($res,$i,'comunicado'));
                            $produto_referencia  = trim(pg_fetch_result($res,$i,'produto_referencia'));
                            $produto_descricao   = trim(pg_fetch_result($res,$i,'produto_descricao'));
                            $produto_voltagem    = trim(pg_fetch_result($res,$i,'produto_voltagem'));
                            if (strlen($produto_referencia) OR strlen($produto_descricao) > 0) {
                                $produto_completo    = $produto_referencia . " - " . $produto_descricao;
                            }else{
                                $produto_completo = "";
                            }
                            $descricao           = trim(pg_fetch_result($res,$i,'descricao'));
                            $tipo                = trim(pg_fetch_result($res,$i,'tipo'));
                            $comunicado_mensagem = trim(pg_fetch_result($res,$i,'mensagem'));
                            $video               = trim(pg_fetch_result($res,$i,'video'));
                            $data                = trim(pg_fetch_result($res,$i,'data'));
                            $extensao            = pg_fetch_result($res, $i, 'extensao');

                            if(strlen($tipo) == 0) $tipo='Sem título' ;

                            $cor = ($i % 2 == 0) ? "#F1F4FA" : "#FEFEFE";

                            echo "<tr class='Conteudo' bgcolor='$cor'>";
                            echo "<td nowrap align='center'><a href =\"javascript:MostraEsconde('dados_$tipo-$i','dados2_$tipo-$i','visualizar_$i','titulo_tr-$i','conteudo_tr-$i')\">" . $data . "</a></td>";
                            echo "<td nowrap><a href =\"javascript:MostraEsconde('dados_$tipo-$i','dados2_$tipo-$i','visualizar_$i','titulo_tr-$i','conteudo_tr-$i')\"><img src='imagens/mais.gif' id='visualizar_$i'><acronym title='".strtoupper(traduz("referencia",$con,$cook_idioma)).": $produto_referencia\n Descrição: $produto_descricao\n".strtoupper(traduz("voltagem",$con,$cook_idioma)).": $produto_voltagem' style='cursor: help;'>" . substr($produto_completo,0,25) . "</acronym></a></td>";
                            echo "<td nowrap><a href =\"javascript:MostraEsconde('dados_$tipo-$i','dados2_$tipo-$i','visualizar_$i','titulo_tr-$i','conteudo_tr-$i')\"><acronym title=' " . nl2br($descricao) . "' style='cursor: help;'>";
                            if($login_fabrica == 14 or $login_fabrica == 3) {
                                echo "$descricao";
                            }else{
                                echo " ".substr($descricao,0,25) ;
                            }
                            echo "</a></td>";
                            echo "<td nowrap align='center'>" .
                                "<a href =\"javascript:MostraEsconde('dados_$tipo-$i','dados2_$tipo-$i','visualizar_$i','titulo_tr-$i','conteudo_tr-$i')\">" .
                                $tipo;
                            echo "</a>\n</td>";
                            //				echo "<td nowrap align='center'>" . $data . "</td>";

                            echo "<td align='center' nowrap>";
                            echo "&nbsp;";

                            if (is_object($s3) and $tipo != 'auditoria_online') {
                                if (strlen(trim($extensao)) > 0) {
                                    echo "<a href='JavaScript:void(0);' name='prod_ve' rel='$comunicado/$tipo'>";
                                }
                            } else {
                                $gif = 'comunicados/$comunicado.gif';
                                $jpg = 'comunicados/$comunicado.jpg';
                                $pdf = 'comunicados/$comunicado.pdf';
                                $doc = 'comunicados/$comunicado.doc';
                                $rtf = 'comunicados/$comunicado.rtf';
                                $xls = 'comunicados/$comunicado.xls';
                                $zip = 'comunicados/$comunicado.zip';
                                $pps = 'comunicados/$comunicado.pps';

                                if (file_exists($zip) == true) {
                                    echo "<a href='comunicados/$comunicado.zip' target='_blank'>";
                                }
                                if (file_exists($pps) == true) {
                                    echo "<a href='comunicados/$comunicado.pps' target='_blank'>";
                                }

                                if (file_exists($gif) == true) {
                                    echo "<a href=$PHP_SELF?comunicado=$comunicado>";
                                }
                                //HD 15634
                                if (file_exists($jpg) == true) {
                                    echo "<a href=$PHP_SELF?comunicado=$comunicado>";
                                }

                                if (file_exists($doc) == true) {
                                    echo "<a href='comunicados/$comunicado.doc' target='_blank'>";
                                }

                                if (file_exists($rtf) == true) {
                                    echo "<a href='comunicados/$comunicado.rtf' target='_blank'>";
                                }

                                if (file_exists($xls) == true) {
                                    echo "<a href='comunicados/$comunicado.xls' target='_blank'>";
                                }

                                if (file_exists($pdf) == true) {
                                    echo "<a href='comunicados/$comunicado.pdf' target='_blank'>";
                                }
                            }
                            if ($tipo == 'auditoria_online') {
                                echo '<a href="geraPDF_auditoria.php?comunicado='.$comunicado.'">';
                            }

                            if (strlen($comunicado_mensagem) == 0) {
                                $comunicado_mensagem = "<center>".traduz("nao.ha.mensagem.cadastrada",$con,$cook_idioma)."</center>";
                            }

                            fecho ("abrir.arquivo",$con,$cook_idioma);
                            echo "</a>";
                            echo "</td>";
                            echo "</tr>";
                            echo "<tr class='esconde' id='titulo_tr-$i'>";
                            echo "<td colspan='5'>";
                            echo "<div class='exibe_Titulo' id ='dados_$tipo-$i'>";
                            fecho ("mensagem",$con,$cook_idioma);
                            if ($login_fabrica == 50 and $video<>""){ ?>
                                <P><?=$descricao?><BR>
                                       <A href="javascript:window.open('/assist/video.php?video=<?=$video?>','_blank','location=no, status=no, menubar=no, scrollbars=no, resizable=yes, width=460, height=380');void(0);">
                                       Assistir vídeo anexado</A>
                                       </P>
<?	}
                                       echo "</div>";
                                       echo "</td>";
                                       echo "</tr>";
                                       echo "<tr class='esconde' id='conteudo_tr-$i'>";
                                       echo "<td colspan='5'><br>";
                                       echo "<div class='exibe_Conteudo' id='dados2_$tipo-$i'>";
                                       echo nl2br($comunicado_mensagem) . "</div><br></td>";
                                       echo "</tr>";
                            }
                        }else{
                            echo "<tr class='Conteudo' bgcolor='#D9E2EF' height='15'>";
                            echo "<td colspan='5' align='center'>&nbsp;<br><b>".strtoupper(traduz("nenhum.resultado.encontrado",$con,$cook_idioma))."</b><br>&nbsp;</td>";
                            echo "</tr>";
                        }
                        if($login_fabrica <> 14) { // HD 44360
                            echo "<tr bgcolor='#D9E2EF' height='15'>";
                            echo "<td colspan='5' align='center'><a href='comunicado_mostra.php'>";
                            echo mb_strtoupper(traduz('clique.aqui.para.fazer.uma.nova.busca', $con));
                            echo "</a></td></tr>";
                        }
                        echo "</table>";
                }else{
                        echo "<tr class='Conteudo' bgcolor='#D9E2EF' height='15'>";
                        echo "<td colspan='5' align='center'>&nbsp;<br><b>".strtoupper(traduz("nenhum.resultado.encontrado",$con,$cook_idioma))."</b><br>&nbsp;</td>";
                        echo "</tr>";
                }
            }
		}else{
			$res = pg_query($con,$sql);
			if (pg_num_rows($res) > 0) {
				$tipo_anterior = "";
				$cor = '#fafafa';
				for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
					$tipo                 = trim(pg_fetch_result($res,$i,tipo));
					$comunicado           = trim(pg_fetch_result($res,$i,comunicado));
					$produto_referencia   = trim(pg_fetch_result($res,$i,produto_referencia));
					$produto_descricao    = trim(pg_fetch_result($res,$i,produto_descricao));
					$comunicado_descricao = trim(pg_fetch_result($res,$i,descricao));
					$extensao = pg_fetch_result($res, $i, "extensao");
					if(strlen($tipo)==0) $tipo = 'Sem título';

					if($tipo<>$tipo_anterior){
						if($i>0){
							echo "<br></td></tr></table>";
						}
						$cor = ($cor == '#efefef') ? '#fafafa' : '#efefef';
						echo "<table width='700' border='0' cellspacing='0' cellpadding='0' align='center'>";
						echo "<tr bgcolor = $cor>";
						echo "<td rowspan='3' width='20' valign='top'><img src='imagens/marca25.gif'></td>";
						echo "<td class='chapeu' colspan='2' >$tipo</td>";
						echo "</tr>";
						echo "<tr bgcolor = $cor><td colspan='2' height='5'></td></tr>";
						echo "<tr bgcolor = $cor>";
						echo "<td valign='top' class='menu'>";
						echo "<dl>";
					}

					echo "<br><dd>&nbsp;&nbsp;<b>-»</b> ";

					if (is_object($s3) and $tipo != 'auditoria_online') {
					   if (strlen(trim($extensao)) > 0) {
						   echo "<a href='JavaScript:void(0);' name='prod_ve' rel='$comunicado/$tipo'>";
					   }
					} else {
						$gif = "comunicados/$comunicado.gif";
						$jpg = "comunicados/$comunicado.jpg";
						$pdf = "comunicados/$comunicado.pdf";
						$doc = "comunicados/$comunicado.doc";
						$rtf = "comunicados/$comunicado.rtf";
						$xls = "comunicados/$comunicado.xls";
						$ppt = "comunicados/$comunicado.ppt";
						$zip = "comunicados/$comunicado.zip";

						if (file_exists($gif) == true) echo "<a href='comunicados/$comunicado.gif' target='_blank'>";
						if (file_exists($jpg) == true) echo "<a href='comunicados/$comunicado.jpg' target='_blank'>";
						if (file_exists($cod) == true) echo "<a href='comunicados/$comunicado.cod' target='_blank'>";
						if (file_exists($xls) == true) echo "<a href='comunicados/$comunicado.xls' target='_blank'>";
						if (file_exists($rtf) == true) echo "<a href='comunicados/$comunicado.rtf' target='_blank'>";
						if (file_exists($xls) == true) echo "<a href='comunicados/$comunicado.xls' target='_blank'>";
						if (file_exists($pdf) == true) echo "<a href='comunicados/$comunicado.pdf' target='_blank'>";
						if (file_exists($ppt) == true) echo "<a href='comunicados/$comunicado.ppt' target='_blank'>";
						if (file_exists($zip) == true) echo "<a href='comunicados/$comunicado.zip' target='_blank'>";
					}

					if(strlen($produto_referencia)>0) echo "$produto_referencia - ";

					if (strlen ($produto_descricao) > 0) {
						echo $produto_descricao;
					}else{
						if(strlen($comunicado_descricao)==0){
							echo "Comunicado Sem título";
						}else{
							echo $comunicado_descricao;
						}
					}

					echo"</a></dd>";
					$tipo_anterior = $tipo;
				}
				echo "<br>";
			}else{
				echo "<br><dt>&nbsp;&nbsp;<b>»</b>" . traduz('nao.ha.comunicados.disponiveis', $con) . "<br></dt>";
			}
			echo "<br>";
			echo "</td>";
			echo "<td rowspan='2' class='detalhes' width='1'></td>";
			echo "</tr>";
			echo "<tr bgcolor='#D9E2EF'>";
			echo "<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>";
			echo "</tr>";
			echo "</table><br>";
			echo "<center><a href='comunicado_mostra.php'>";
			echo mb_strtoupper(traduz('clique.aqui.para.fazer.uma.nova.busca', $con));
			echo "</a>";
		}
	}
	$comunicado = "";
}


if (strlen($_GET["comunicado"]) > 0) $comunicado = $_GET["comunicado"];

if (strlen($comunicado) > 0) {

	//PEGA O TIPO DO POSTO PARA MOSTRAR OS COMUNICADOS DOS MESMOS. QDO COMU = NULL TODOS PODEM VER
	$sql2 = "SELECT tbl_posto_fabrica.codigo_posto           ,
					tbl_posto_fabrica.tipo_posto             ,
					tbl_posto_fabrica.pedido_em_garantia     ,
					tbl_posto_fabrica.pedido_faturado        ,
					tbl_posto_fabrica.digita_os              ,
					tbl_posto_fabrica.reembolso_peca_estoque
			FROM	tbl_posto
			LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
			AND     tbl_posto.posto   = $login_posto ";
// echo $sql2;
	$res2 = pg_query($con,$sql2);

	if (pg_num_rows($res2) > 0) {
		$tipo_posto             = trim(pg_fetch_result($res2, 0, 'tipo_posto'));
		$pedido_em_garantia     = trim(pg_fetch_result($res2, 0, 'pedido_em_garantia'));
		$pedido_faturado        = trim(pg_fetch_result($res2, 0, 'pedido_faturado'));
		$digita_os              = trim(pg_fetch_result($res2, 0, 'digita_os'));
		$reembolso_peca_estoque = trim(pg_fetch_result($res2, 0, 'reembolso_peca_estoque'));
	}

	if($login_fabrica==1){		//HD 10983
		$sql_cond1 = " tbl_comunicado.pedido_em_garantia     IS NULL ";
		$sql_cond2 = " tbl_comunicado.pedido_faturado        IS NULL ";
		$sql_cond3 = " tbl_comunicado.digita_os              IS NULL ";
		$sql_cond4 = " tbl_comunicado.reembolso_peca_estoque IS NULL ";

		if ($pedido_em_garantia     == "t") $sql_cond1 = " tbl_comunicado.pedido_em_garantia     IS NOT FALSE ";
		if ($pedido_faturado        == "t") $sql_cond2 = " tbl_comunicado.pedido_faturado        IS NOT FALSE ";
		if ($digita_os              == "t") $sql_cond3 = " tbl_comunicado.digita_os              IS TRUE ";
		if ($reembolso_peca_estoque == "t") $sql_cond4 = " tbl_comunicado.reembolso_peca_estoque IS TRUE ";
		$sql_cond_total="AND ( $sql_cond1 or $sql_cond2 or $sql_cond3 or $sql_cond4) ";
	}

	$sql =	"SELECT
					CASE WHEN tbl_comunicado.produto IS NULL THEN prod.voltagem   ELSE tbl_produto.voltagem   END AS produto_voltagem,
					CASE WHEN tbl_comunicado.produto IS NULL THEN prod.referencia ELSE tbl_produto.referencia END AS produto_referencia,
					CASE WHEN tbl_comunicado.produto IS NULL THEN prod.descricao  ELSE tbl_produto.descricao  END AS produto_descricao,
					tbl_comunicado.descricao,
					tbl_comunicado.mensagem,
					tbl_comunicado.tipo,
					TO_CHAR(tbl_comunicado.data,'DD/MM/YYYY') AS data
			FROM    tbl_comunicado
			LEFT JOIN tbl_comunicado_produto ON tbl_comunicado_produto.comunicado = tbl_comunicado.comunicado
			LEFT JOIN tbl_produto            ON tbl_produto.produto               = tbl_comunicado.produto
			LEFT JOIN tbl_produto prod       ON prod.produto                      = tbl_comunicado_produto.produto
			WHERE tbl_comunicado.fabrica      = $login_fabrica
			AND    (tbl_comunicado.tipo_posto = $tipo_posto    OR tbl_comunicado.tipo_posto IS NULL)
			AND   ((tbl_comunicado.posto      = $login_posto)  OR (tbl_comunicado.posto     IS NULL))
			AND   tbl_comunicado.comunicado   = $comunicado";

	if($login_fabrica==1){ // HD 10983
		$sql.=" $sql_cond_total ";
	}

	$res = pg_query($con,$sql);

	echo "<table width='600' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'  align='center'>";
	echo "<tr bgcolor='#D9E2EF' height='15'>";
	echo "<td colspan='4' align='center'><a href='comunicado_mostra.php'>";
	echo mb_strtoupper(traduz('clique.aqui.para.fazer.uma.nova.busca', $con));
	echo "</a></td>";	echo "</tr>";

	if (pg_num_rows($res) == 1) {
		$produto_referencia  = trim(pg_fetch_result($res,$i,'produto_referencia'));
		$produto_descricao   = trim(pg_fetch_result($res,$i,'produto_descricao'));
		$produto_voltagem    = trim(pg_fetch_result($res,$i,'produto_voltagem'));
		$produto_completo    = $produto_referencia . ' - ' . $produto_descricao;
		$descricao           = trim(pg_fetch_result($res,$i,'descricao'));

		$tipo                = trim(pg_fetch_result($res,$i,'tipo'));
		$data                = trim(pg_fetch_result($res,$i,'data'));

		echo "<tr class='Conteudo' bgcolor='#D9E2EF' height='15'>";
		echo "<td>";
		echo "&nbsp;";
		echo "<p align='center'><img border='0' src='imagens/cab_comunicado.gif'></p>";
		echo "<p align='center'>$tipo  -  $data</p>";

		if (strlen($descricao) > 0) {
			echo "<table width='550' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
			echo "<tr class='Conteudo' bgcolor='#D9E2EF' height='15'>";
			echo "<td>" . nl2br($descricao) . "</td>";
			echo "</tr>";
			echo "</table>";
		}
		if (strlen($comunicado_mensagem) > 0) {
			echo "<table width='550' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
			echo "<tr class='Conteudo' bgcolor='#D9E2EF' height='15'>";
			echo "<td>" . nl2br($comunicado_mensagem) . "</td>";
			echo "</tr>";
			echo "</table>";
		}

		if ($S3_online) {
			$s3->set_tipo_anexoS3($tipo);
			if ($s3->temAnexos($comunicado)) {
				$anexo = $s3->getS3Link($s3->attachList[0]);
				echo $s3->getAttachLink($anexo);
			}
		} else {
			$gif = "comunicados/$comunicado.gif";
			$jpg = "comunicados/$comunicado.jpg";
			$pdf = "comunicados/$comunicado.pdf";
			$doc = "comunicados/$comunicado.doc";
			$rtf = "comunicados/$comunicado.rtf";
			$xls = "comunicados/$comunicado.xls";

			if (file_exists($gif) == true) {
				echo "	<img src='comunicados/$comunicado.gif'>";
			}

			if (file_exists($jpg) == true) {
				echo "<img src='comunicados/$comunicado.jpg'>";
			}

			if (file_exists($doc) == true) {
				echo traduz("para.visualizar.o.arquivo",$con,$cook_idioma).", <a href='comunicados/$comunicado.doc' target='_blank'>".traduz("clique.aqui",$con,$cook_idioma)."</a>.";
			}

			if (file_exists($rtf) == true) {
				echo traduz("para.visualizar.o.arquivo",$con,$cook_idioma).", <a href='comunicados/$comunicado.rtf' target='_blank'>".traduz("clique.aqui",$con,$cook_idioma)."</a>.";
			}

			if (file_exists($xls) == true) {
				echo traduz("para.visualizar.o.arquivo",$con,$cook_idioma).", <a href='comunicados/$comunicado.xls' target='_blank'>".traduz("clique.aqui",$con,$cook_idioma)."</a>.";
			}

			if (file_exists($pdf) == true) {
				echo "<p align='center'>".traduz("para.visualizar.o.arquivo",$con,$cook_idioma).", <a href='comunicados/$comunicado.pdf' target='_blank'>".traduz("clique.aqui",$con,$cook_idioma)."</a>.<br>";
				echo "<font size='1' color='#A02828'>".traduz("se.voce.nao.possui.o.acrobat.reader",$con,$cook_idioma)."&reg;, <a href='http://www.adobe.com/products/acrobat/readstep2.html'>".traduz("instale.agora",$con,$cook_idioma)."</a>.</font></p>";
			}
		}
		echo "&nbsp;";
		echo "</td>";
		echo "</tr>";
	}

	echo "<tr bgcolor='#D9E2EF' height='15'>";
	echo "<td colspan='4' align='center'><a href='comunicado_mostra.php'><img src='";
		echo ($sistema_lingua == 'ES') ? "imagens/btn_nova_busca_es.gif" : "imagens/btn_nova_busca.gif";
		echo "'></a></td>";
	echo "</tr>";
	echo "</table>";
}

include "rodape.php";
?>
