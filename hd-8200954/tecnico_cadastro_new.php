<?php
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include 'autentica_usuario.php';
  include "funcoes.php";
  
  $title = "Cadastro de Técnico";

  function validDate($data = null){
    if($data == null)
      return false;
    
    $data   = array_reverse(explode("/", $data));     

    if(!checkdate($data[1], $data[2], $data[0]))
      return false;
    
    return implode("-",$data);
  }

  function validaEmail($email = null){
        if($email == null)
            return false;
      
        if (!preg_match("/^[a-z0-9_\.\-]+@[a-z0-9_\.\-]*[a-z0-9_\-]+\.[a-z]{2,4}$/", $email)) {
            return false;
        }

        return true;
  }

  $formacoes = array(
      "ENSINO FUNDAMENTAL" => array(
        'ENSINO FUNDAMENTAL',
        'AUX. TÉCNICO EM ELETRICIDADE',
        'AUX. TÉCNICO EM ELETROELETRÔNICA',
        'AUX. TÉCNICO EM ELETROMECÂNICA',
        'AUX. TÉCNICO EM ELETRÔNICA',
        'AUX. TÉCNICO EM ELETROTÉCNICA',
        'AUX. TÉCNICO EM INFORMÁTICA - MANUTENÇÃO DE COMPUTADORES',
        'AUX. TÉCNICO EM INFORMÁTICA - MANUTENÇÃO DE REDES',
        'AUX. TÉCNICO EM INFORMÁTICA - SISTEMAS DE INFORMAÇÃO',
        'AUX. TÉCNICO EM INSTALAÇÕES ELÉTRICAS',
        'AUX. TÉCNICO EM MECÂNICA',
        'AUX. TÉCNICO EM SISTEMAS DE AUTOMAÇÃO INDUSTRIAL',
        'AUX. TÉCNICO EM TELECOMUNICAÇÕES',
        'AUX. TÉCNICO EM OUTRAS ÁREAS'
      ),
      "ENSINO MÉDIO" => array(
        'ENSINO MÉDIO',
        'TÉCNICO EM ACIONAMENTOS ELETRÔNICOS',
        'TÉCNICO EM AUTOMAÇÃO INDUSTRIAL',
        'TÉCNICO EM AUTOMOBILISTICA',
        'TÉCNICO EM ELÉTRICA',
        'TÉCNICO EM ELETRICISTA DE MANUTENÇÃO',
        'TÉCNICO EM ELETROELETRÔNICA',
        'TÉCNICO EM ELETROMECÂNICA',
        'TÉCNICO EM ELETRÔNICA',
        'TÉCNICO EM ELETROTÉCNICA',
        'TÉCNICO EM INFORMÁTICA - DESENVOLVIMENTO DE SISTEMA',
        'TÉCNICO EM INFORMÁTICA - HABILITAÇÃO EM MANUTENÇÃO DE MICRO',
        'TÉCNICO EM INFORMÁTICA COM HABILITAÇÃO EM REDES',
        'TÉCNICO EM MANUTENÇÃO DE COMPUTADORES',
        'TÉCNICO EM MANUTENÇÃO ELETROELETRÔNICA',
        'TÉCNICO EM MANUTENÇÃO INDUSTRIAL',
        'TÉCNICO EM MANUTENÇÃO MECÂNICA',
        'TÉCNICO EM MECANICA',
        'TÉCNICO EM MECATRÔNICA',
        'TÉCNICO EM REFRIGERAÇÃO',
        'TÉCNICO EM TELECOMUNICAÇÕES',
        'TÉCNICO EM DEMAIS ÁREAS'
      ),
      "ENSINO SUPERIOR" => array(
        'BACH. EM ANALISE DE SISTEMAS',
        'BACH. EM CIÊNCIAS DA COMPUTAÇÃO',
        'BACH. EM ENGENHARIA DE INFORMÁTICA',
        'BACH. EM INFORMÁTICA',
        'BACH. EM PROCESSAMENTO DE DADOS',
        'BACH. EM SISTEMAS DE INFORMAÇÃO',
        'ENG. DE SISTEMAS ELÉTRICOS INDUSTRIAIS',
        'ENGENHARIA DE AUTOMAÇÃO',
        'ENGENHARIA DE COMPUTAÇÃO',
        'ENGENHARIA DE CONTROLE E AUTOMAÇÃO INDUSTRIAL - MECATRÔNICA',
        'ENGENHARIA DE PRODUÇÃO E SISTEMAS',
        'ENGENHARIA DE PRODUÇÃO ELÉTRICA',
        'ENGENHARIA DE PRODUÇÃO MECÂNICA',
        'ENGENHARIA DE TELECOMUNICAÇÕES',
        'ENGENHARIA ELÉTRICA',
        'ENGENHARIA ELÉTRICA COM ENFASE EM ELETRÔNICA',
        'ENGENHARIA ELETRICA-HABILITAÇÃO EM TELECOMUNICACOES',
        'ENGENHARIA ELETRÔNICA',
        'ENGENHARIA ELETROTÉCNICA',
        'ENGENHARIA INDUSTRIAL ELÉTRICA',
        'ENGENHARIA MECÂNICA COM ÊNFASE EM MECATRÔNICA',
        'ENGENHARIA MECÂNICA COM HABILITAÇÃO EM AUTOMAÇÃO E CONTROLE',
        'ENGENHARIA MECATRÔNICA',
        'TECNOLOGIA DA INFORMAÇÃO',
        'TECNOLOGIA EM ANÁLISE DE SISTEMAS INFORMATIZADOS',
        'TECNOLOGIA EM AUTOMAÇÃO E CONTROLE INDUSTRIAL',
        'TECNOLOGIA EM AUTOMAÇÃO E ROBÓTICA',
        'TECNOLOGIA EM BANCO DE DADOS',
        'TECNOLOGIA EM ELÉTRICA',
        'TECNOLOGIA EM ELETROELETRÔNICA',
        'TECNOLOGIA EM ELETROMECÂNICA',
        'TECNOLOGIA EM ELETRÔNICA',
        'TECNOLOGIA EM GESTÃO DA TECNOLOGIA DA INFORMAÇÃO',
        'TECNOLOGIA EM INFORMÁTICA',
        'TECNOLOGIA EM MANUTENÇÃO INDUSTRIAL',
        'TECNOLOGIA EM MECÂNICA',
        'TECNOLOGIA EM MECATRÔNICA',
        'TECNOLOGIA EM OPERAÇÃO E MANUTENÇÃO MECATRÔNICA INDUSTRIAL',
        'TECNOLOGIA EM PROCESSAMENTO DE DADOS',
        'TECNOLOGIA EM REDES DE COMPUTADORES',
        'TECNOLOGIA EM SISTEMAS DE INFORMAÇÃO',
        'TECNOLOGIA EM SISTEMAS DIGITAIS',
        'SUPERIOR EM DEMAIS ÁREAS'
      ),
    );

  if(@$_POST['ajax'] == 'ajax'){
    $tecnico   = @$_POST['tecnico'];
    $categoria = utf8_decode(@$_POST['categoria']);
    $formacao  = utf8_decode(@$_POST['formacao']);

    if(!empty($tecnico)){
      //verifica se já tem em alguma OS o técnico

      $sql = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND posto = {$login_posto} AND tecnico = {$tecnico} LIMIT 1";
      $res = pg_query($con, $sql);
      if(pg_num_rows($res) > 0){
        echo "1";
      }else{
        $sql = "DELETE FROM tbl_tecnico WHERE tecnico = {$tecnico}";
        if(pg_query($con, $sql))
          echo "0";
        else
          echo "2";
      }
    }

    if(!empty($categoria)){
      $formacoes = $formacoes[$categoria];

      echo "<option value=''>Selecione</option>";
      foreach ($formacoes as $formacao) {
        echo "<option value='{$formacao}' title='{$formacao}'>{$formacao}</option>";
      }
    }

    if(!empty($formacao)){
      foreach (array_keys($formacoes) AS $value) {
        foreach ($formacoes[$value] AS $key) {
          if($key == $formacao){
            echo $value."|".$formacao;
          }
        }
      }
    }

    exit;
  }

  include "cabecalho.php";

  if(!empty($_POST['gravar'])){
    $tecnico        = $_POST['tecnico'];
    $nome           = trim($_POST['nome']);
    $cpf            = preg_replace("/[-.]/", "", trim($_POST['cpf']));
    $telefone       = trim($_POST['telefone']);
    $ramal          = trim($_POST['ramal']);
    $email          = trim($_POST['email']);
    $data_admissao  = trim($_POST['data_admissao']);
    $formacao       = trim($_POST['formacao']);
    $data_conclusao = trim($_POST['data_conclusao']);
    $status         = trim($_POST['status']);
    $linhas         = (Array) $_POST['linhas'];
    $linha_atende   = "null";

    if(empty($nome)){
        $msg_erro = "Nome inválido!";
    }

    if(empty($cpf) AND empty($msg_erro))
        $msg_erro = "CPF inválido!";
    elseif(empty($msg_erro)){
		$valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",$cpf));

		if(empty($valida_cpf_cnpj)){
			$res = pg_query($con,"SELECT fn_valida_cnpj_cpf('$cpf')");
			if ($res === false) {
				$msg_erro = "CPF inválido!";
			}
		}else{
			$msg_erro = $valida_cpf_cnpj;
		}
    }

    if(empty($formacao) AND empty($msg_erro)){
        $msg_erro = "Formação inválida!";
    }

    if(empty($msg_erro) AND !validaEmail($email))
        $msg_erro = "Email inválido!";

    if(empty($data_admissao) AND empty($msg_erro))
        $msg_erro = "Data admissão inválida!";
    elseif(empty($msg_erro)){
        $x_data_admissao = validDate($data_admissao);
        if (!$x_data_admissao) {
            $msg_erro = "Data admissão inválida!";
        }
    }

    if(empty($telefone) AND empty($msg_erro)){
        $msg_erro = "Telefone inválido!";
    }

    if(empty($data_conclusao) AND empty($msg_erro))
        $msg_erro = "Data conclusão inválida!";
    elseif(empty($msg_erro)){
        $x_data_conclusao = validDate($data_conclusao);
        if (!$x_data_conclusao) {
            $msg_erro = "Data conclusão inválida!";
        }
    } 

    if(empty($msg_erro) AND count($linhas) == 0){
        $msg_erro = "Selecione as linhas que o técnico '$nome' atende!";
    }elseif(empty($msg_erro)){
        $linha_atende = Array();

        foreach ($linhas as $key => $value) {
            $sql = "SELECT 
                      tbl_linha.linha
                    FROM tbl_posto_linha 
                        JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = {$login_fabrica} 
                    WHERE posto = {$login_posto} 
                      AND tbl_linha.ativo 
                      /*AND tbl_posto_linha.ativo */
                      AND tbl_linha.linha = {$value}
                    ORDER BY tbl_linha.nome;";
            $res = pg_query($con, $sql);

            if(pg_num_rows($res)){
                $linha_atende[] = pg_fetch_result($res, 0, 'linha'); 
            }
        }

        $linha_atende = "{".implode(",", $linha_atende)."}";
    }


    if(empty($msg_erro)){

        //verifica se o tecnico realmente existe
        $sql = "SELECT tecnico FROM tbl_tecnico WHERE fabrica = {$login_fabrica} AND posto = {$login_posto} AND tecnico = {$tecnico};";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res) == 0)
          $tecnico = null;

        if(empty($tecnico)){
            $sql = "INSERT INTO tbl_tecnico
                        (
                            posto,
                            fabrica,
                            nome,
                            ativo,
                            cpf,
                            data_admissao,
                            formacao,
                            data_conclusao,
                            email,
                            telefone,
                            ramal,
                            linhas
                        )
                    VALUES
                        (
                            $login_posto,
                            $login_fabrica,
                            '$nome',
                            '$status',
                            $cpf,
                            '$x_data_admissao',
                            '$formacao',
                            '$x_data_conclusao',
                            '$email',
                            '$telefone',
                            '$ramal',
                            '$linha_atende'
                        );";
        }else{
            echo $sql = "UPDATE tbl_tecnico SET 
                        nome            = '{$nome}',
                        ativo           = '{$status}',
                        cpf             = '{$cpf}',
                        data_admissao   = '{$x_data_admissao}',
                        formacao        = '{$formacao}',
                        data_conclusao  = '{$x_data_conclusao}',
                        email           = '{$email}',
                        telefone        = '{$telefone}',
                        ramal           = '{$ramal}',
                        linhas          = '$linha_atende'
                    WHERE tecnico = {$tecnico}";
        }
        $res = pg_query($con, $sql);
        $msg_erro = pg_last_error();

        if(empty($msg_erro))
            header ("Location: {$_SERVER['PHP_SELF']}");
        else
            $msg_erro = "Erro ao gravar dados! <erro>".pg_last_error($con)."</erro>";
    }

}


?>
<style> 
    a {
        text-decoration: none;
        color: #000000;
    }

    a:hover {
        text-decoration: underline;
    }

    .titulo_coluna{
           background-color:#596d9b;
           font: bold 11px "Arial";
           color:#FFFFFF;
           text-align:center;
    }

	 .titulo_tabela{
           background-color:#596d9b;
           font: bold 14px "Arial";
           color:#FFFFFF;
           text-align:center;
    }

    table.tabela tr td{
           font-family: verdana;
           font-size: 11px;
           border-collapse: collapse;
           border:1px solid #596d9b;
    }
    .formulario{
           background-color:#D9E2EF;
           font:11px Arial;
           text-align:left;
    }

    .formulario td{
      font-weight: bold;
    }

    .msg_erro {
        background: #FF0000;
        color: #FFFFFF;
        font: bold 16px "Arial";
        text-align:center;
    }

    .texto_avulso{
       font: 14px Arial; color: rgb(89, 109, 155);
       background-color: #d9e2ef;
       text-align: center;
       width:700px;
       margin: 0 auto;
       border-collapse: collapse;
       border:1px solid #596d9b;
    }

    .nao_disponivel {
       font: 14px Arial; color: rgb(200, 109, 89);
       background-color: #ffddff;
       border:1px solid #DD4466;
    }

    .espaco{
	     padding:0 0 0 150px;
    }

    input[type="text"] {
      font-weight: normal !important;
    }

    #linhas{
      width: 660px;
      margin: 0 auto;
    }

    #linhas ul, #linhas ul li{
      list-style: none;
      padding: 0;
      margin: 0;
    }

     #linhas ul li{
       float: left;
       width: 219px;
     }

     erro{
       display: none;
     }

</style>
<?php 


    if(!empty($_GET["tecnico"])){
        $tecnico = $_GET["tecnico"];

        $sql = "SELECT * FROM tbl_tecnico WHERE tecnico = {$tecnico} AND posto = {$login_posto} AND fabrica = {$login_fabrica}";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res)){
            extract(pg_fetch_array($res));

            $linhas     = str_replace('{', '', $linhas);
            $linhas     = str_replace('}', '', $linhas);

            $linhas = explode(",", $linhas);

            $data_admissao   = implode("/",array_reverse(explode("-", $data_admissao)));
            $data_conclusao  = implode("/",array_reverse(explode("-", $data_conclusao)));

            $status = ($ativo == 't') ? "true" : "false";
        }
    }?>
    <link type="text/css" href="plugins/jqueryUI/css/redmond/jquery-ui-1.8.17.custom.css" rel="stylesheet" />  
    <script type="text/javascript" src="plugins/jqueryUI/js/jquery-1.7.1.min.js"></script>
    <script type="text/javascript" src="plugins/jqueryUI/js/jquery-ui-1.8.17.custom.min.js"></script>
    <script src="js/jquery.maskedinput-1.3.min.js" type="text/javascript"></script>
  	<script type="text/javascript">
          $(function() {
              $(".date").datepicker({ 
                  dateFormat: 'dd/mm/yy',
                  maxDate: '0d',
                  autoSize: false
              });

              $(".date").mask("99/99/9999");
              $(".cpf").mask("999.999.999-99");
              $(".fone").mask("(99) 9999-9999");
          });

          $(document).ready(function(){
            $('.apagarTecnico').click(function() {
              var id = $(this).attr('rel');
              $(this).attr("disabled", true);

              if(id.length > 0){
                var pergunta = confirm("Deseja realmente apagar este registro?")
                if (pergunta){
                  $.ajax({
                    type: "POST",
                    url: "<?php echo $_SERVER['PHP_SELF'];?>",
                    data: "ajax=ajax&tecnico="+id,
                    success: function(retorno){
                      if(retorno == 1){
                        alert("Erro ao apagar registro!\n\nO técnico possui cadastro em OS!");
                      }  

                      if(retorno == 2){
                        alert("Erro ao apagar registro!\n\nTente novamente mais tarde!");
                      }  

                      if(retorno == 0){
                        $("#"+id).fadeOut(1000);
                      }
                    }
                  }); 
                }
              }

              $(this).attr("disabled", false);
            });

          });
  	</script>

  <br />				
	<form name="frm_peca" method="post" action="<?=$_SERVER['PHP_SELF']?>" >
    <?php
      if(!empty($msg_erro))
        echo "<div class='msg_erro' style='width:700px'>{$msg_erro}</div>";
    ?>
    <input type='hidden' name='tecnico' value='<?php echo $tecnico?>' /> 
		<table cellpadding="3" cellspacing="1" width="700px" border="0" class="formulario" align="center">
				<tr class="titulo_tabela">
					<th colspan="8">Cadastro de Técnico</th>
				</tr>
			<tr>
				<td width='*'>&nbsp;</td>
        <td width='100px'>&nbsp;</td>
				<td width='100px'>&nbsp;</td>
				<td width='100px'>&nbsp;</td>
				<td width='100px'>&nbsp;</td>
				<td width='100px'>&nbsp;</td>
				<td width='100px'>&nbsp;</td>
			</tr>
			<tr>
        <td>&nbsp;</td>
				<td colspan='2'>
					Nome Completo<br/>
					<input type="text" name="nome" value="<?php echo $nome?>" maxlength="100" style="width: 209px"/>
				</td>
        <td colspan='2'>
          Email<br/>
          <input type="text" name="email" maxlength="50" value="<?php echo $email?>" style="width: 200px"/>
        </td>
        <td colspan='2'>
          CPF<br/>
          <input type="text" name="cpf" class='cpf' value="<?php echo $cpf?>" maxlength="14" style="width: 180px" />
        </td>
        
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>
          Telefone<br/>
          <input type="text" name="telefone" class='fone' maxlength="20" value="<?php echo $telefone?>" style="width: 95px" />
        </td>
        <td>
          Ramal<br/>
          <input type="text" name="ramal" maxlength="10" value="<?php echo $ramal?>" style="width: 95px"  />
        </td>
        <td>
          Data Admissão<br/>
          <input type="text" name="data_admissao" maxlength="10" value="<?php echo $data_admissao?>" class='date' style="width: 95px" />
        </td>
        <td colspan='3'>
          Categoria Formação<br/>
          <select name='categoria_formacao' id='categoria_formacao' class='frm' style="width: 290px">
            <option value="" selected = 'selected'> - selecione -</option>
            <?php
                foreach (array_keys($formacoes) as $cat_formacao) {
                  echo "<option value='{$cat_formacao}'>{$cat_formacao}</option>";
                }
            ?>
          </select>
        </td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td colspan='3'>
          Formação<br/>
          <input type='hidden' name='formacao_anterior' id='formacao_anterior' value='<?php echo $formacao;?>' />
          <select name='formacao' id='formacao' class='frm' style="width: 318px">
            <option value="" selected = 'selected'> - selecione uma categoria - </option>
          </select>
        </td>
        <td>
          Data Conclusão<br/>
          <input type="text" name="data_conclusao" maxlength="10" value="<?php echo $data_conclusao?>" class='date'style="width: 95px"/>
        </td>
        <td colspan='2'>
          Status<br/>
          <select name='status' class='frm' style="width: 178px">
            <?php
                if(empty($status))
                    $status = "true";
            ?>
            <option value="true" <?php if($status == 'true') echo " selected = 'selected' "?>>Ativo</option>
            <option value="false" <?php if($status == 'false') echo " selected = 'selected' "?>>Inativo</option>
          </select>
        </td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td colspan='6'>
          Linhas que o técnico atende:<br/>
          <?php 
            $sql = "SELECT 
                      tbl_linha.linha, 
                      tbl_linha.nome 
                    FROM tbl_posto_linha 
                        JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = {$login_fabrica} 
                    WHERE posto = {$login_posto} 
                      AND tbl_linha.ativo 
                      /*AND tbl_posto_linha.ativo*/ 
                    ORDER BY tbl_linha.nome;";
            /*
            $sql = "
                    SELECT DISTINCT
                      tbl_linha.linha, 
                      tbl_linha.nome 
                    FROM tbl_linha 
                    WHERE tbl_linha.ativo 
                      AND fabrica = {$login_fabrica}
                    ORDER BY tbl_linha.nome;";
            */
            $res = pg_query($con, $sql);

            echo "<div id='linhas'>";
              if(pg_num_rows($res)){
                echo "<ul>";
                  for ($i=0; $i < pg_num_rows($res); $i++) { 
                    $linha = pg_fetch_result($res, $i, 'linha');
                    $nome = pg_fetch_result($res, $i, 'nome');
                    
                    $selected =  in_array($linha, $linhas) ? " checked = 'checked' " : null;

                    echo "<li>
                            <input type='checkbox' name='linhas[]' value='{$linha}' {$selected} id='{$linha}' /> <label for='{$linha}'>{$nome}</label>
                          </li>";
                  }

                echo "</ul>";
              }
              echo "<div style='clear: both;'>&nbsp;</div>";
            echo "</div>";
           ?>
        </td>
      </tr>
      <tr>
        <td colspan='10' style='padding: 15px; text-align: center'>
          <input type="submit" name="gravar" value=' Gravar '  />
          <?php if(!empty($tecnico)){?>
            <input type="button" name="novo" value=' Novo Registro ' onclick='javascript: window.location="<?php echo $_SERVER['PHP_SELF']; ?>"'  />
          <?php }?>
        </td>
      </tr>
	</table>
	</form>


    <?php 
        $sql = "SELECT tecnico, nome, email, telefone, ramal, ativo 
                FROM tbl_tecnico 
                WHERE posto = {$login_posto} 
                    AND fabrica = {$login_fabrica}
                ORDER BY tecnico DESC;";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res)){
            echo "<br><table align='center' width='700px' border='0' cellpadding='0' cellspacing='1' class='tabela' >";

            echo "<tr class='titulo_coluna'>";
                echo "<td colspan='6'>Técnico cadastrado</td>";
            echo "</tr>"; 

            echo "<tr class='titulo_coluna'>";
                echo "<td>Nome</td>";
                echo "<td>Email</td>";
                echo "<td>Telefone</td>";
                echo "<td>Ramal</td>";
                echo "<td>Status</td>";
                echo "<td>Ação</td>";
            echo "</tr>"; 

            for ($i=0; $i < pg_num_rows($res); $i++) { 
                extract(pg_fetch_array($res));
                $ativo = ($ativo == 't') ? "Ativo" : "Inativo";

                $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
                echo "<tr bgcolor='$cor' id='{$tecnico}'>";
                    echo "<td><a href='{$_SERVER['PHP_SELF']}?tecnico={$tecnico}'>&nbsp;{$nome}</a></td>";
                    echo "<td>&nbsp;{$email}</td>";
                    echo "<td align='center'>&nbsp;{$telefone}</td>";
                    echo "<td>&nbsp;{$ramal}</td>";
                    echo "<td>&nbsp;{$ativo}</td>";
                    echo "<td style='text-align: center'>&nbsp;<input type='button' value=' Apagar ' rel='{$tecnico}' class='apagarTecnico' />&nbsp;</td>";
                echo "</tr>";
            }

            echo "</table>";
        } 
?>
<script type="text/javascript">
  $(document).ready(function(){
    $("#categoria_formacao").change(function(){
      atualizaFormacao();
    });

    $("#formacao").change(function(){
      $("#formacao_anterior").val($(this).val());
    });

    function atualizaFormacao(){
      var categoria = $("#categoria_formacao").val();

      if(categoria.length){
        $.ajax({
          type: "POST",
          url: "<?php echo $_SERVER['PHP_SELF'];?>",
          data: "ajax=ajax&categoria="+categoria,
          success: function(retorno){
            $("#formacao").html(retorno);

            var formacao = $("#formacao_anterior").val();
            if(formacao.length > 0){
              $("#formacao").val(formacao);
            }
          }
        });  
      }
    }

    function buscaCategoriaFormacao(formacao){
      $.ajax({
        type: "POST",
        url: "<?php echo $_SERVER['PHP_SELF'];?>",
        data: "ajax=ajax&formacao="+formacao,
        success: function(retorno){
          retorno = retorno.split("|");
          $("#categoria_formacao").val(retorno[0]);
          atualizaFormacao();
        }
      });
    }

    <?php if(strlen($formacao) > 0){?>
      buscaCategoriaFormacao("<?php echo $formacao; ?>");
    <?php }?>
  });
</script>
<?php include "rodape.php";?>
