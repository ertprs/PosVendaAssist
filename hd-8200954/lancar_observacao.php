<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "funcoes.php";
include "class/communicator.class.php";

	if(($_GET["ajax"]=="true") && (strlen($_GET["os"]) > 0)){
		if(strlen($_POST["observacao"]) > 0){
			$os = $_GET["os"];
			$observacao = $_POST["observacao"];
			$observacao = str_replace("\\","\/",$observacao);
			#$observacao = preg_replace("/\n(Gravado)\s(em)\s[0-9]{2}\/[0-9]{2}\/[0-9]{4}\s\-\s[0-9]{2}\:[0-9]{2}\:[0-9]{2}$/", "", $observacao);
			$observacao .= "\nGravado em ".date("d/m/Y - H:i:s");
			$observacao = utf8_decode($observacao);

			$sqlObs = " SELECT  obs
                        FROM    tbl_os
                        WHERE   os =$os";
            $res = pg_query($con, $sqlObs);
            $obs = pg_result($res, 0, "obs");

			$obs .= "<br />==============================<br />".$observacao;
			$update = " UPDATE tbl_os
						SET obs = '$obs'
						WHERE os = $os";

			pg_query($con, $update);
			$erro = pg_last_error($con);
			if(strlen($erro) == 0){
				echo "Observação Inserida";
			}else{

				echo "Erro ao Inserir.";
			}
		}else{
			echo "Preencha a Observação";
		}

		if ($login_fabrica == 30 && $login_codigo_posto == 2211) {
       
             $mailTc = new TcComm($externalId);
             $res = $mailTc->sendMail(
                'lucas.souza@telecontrol.com.br',
                "Interação na O.S $os",
                "Houve uma interação na O.S $os",
                'noreply@telecontrol.com.br' 
                );
        }

		
		exit;
	}


?>
	<script type="text/javascript" src="js/jquery-1.6.2.js"></script>
	<script>

	$(function(){
		$("button.btn_lancar").click(function(){
			var os = $("#os").val();
			var observacao = $("#obs").val();
			$("#loading").show();
			$(".btn_lancar").hide();
			$.ajax({
				url: "lancar_observacao.php?os="+os+"&ajax=true",
				type:"POST",
				dataType:"JSON",
				data: {
					observacao: observacao
				},
				complete:function(data){
					alert(data.responseText);
					window.parent.Shadowbox.close();
				}

			});
		});
	});
	
	</script>

	<div style="text-align:center; width:100%; height:100%; background-color:#D9E2EF">
	<br/>
		<input id="os" type="hidden" value="<?=$os?>" />

		<span>Observa&ccedil;&atilde;o	</span><br/>
		<textarea id='obs' name="obs" cols="50" rows='3' class="frm"></textarea> <br/>
		<button class="btn_lancar" type="button">Gravar</button>
		<img src="admin/imagens/loading_img.gif" style="display: none; height: 20px; width: 20px;" id="loading" />
	</div>
