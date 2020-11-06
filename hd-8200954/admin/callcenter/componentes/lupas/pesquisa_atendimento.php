<?php 

include_once '../../../../fn_traducao.php';

$tipo       = $_REQUEST["tipo"];
$filtro     = $_REQUEST["campo"];
$callcenter = $_REQUEST["callcenter"];
$tiposPesquisas = [
                    "n_protocolo"       => traduz("Nº Atendimento"),
                    "cpf_cnpj"          => traduz("CPF/CNPJ"),
                    "nome"              => traduz("Nome"),
                    "telefone"          => traduz("Telefone"),
                    "n_ordem_servico"   => traduz("Nº O.S."),
                    "n_serie"           => traduz("Nº Série"),
                    "cep"               => traduz("CEP"),
                ];

?>
<link rel='stylesheet' type='text/css' href='../../../plugins/bootstrap3/css/bootstrap.min.css' />
<link rel='stylesheet' type='text/css' href='../../../plugins/bootstrap3/css/bootstrap-theme.min.css' />
<link rel='stylesheet' type='text/css' href='../../../plugins/font_awesome/css/font-awesome.css' />
<style>
    .titulo_coluna{
        background: #596d9b;
        color: #fff;
    }
    .titulo_coluna th{
        font-size: 12px !important;
    }
    tbody td{
        font-size: 12px !important;
    }
    .text_callcenter{
        font-weight: bold;
        text-decoration:underline;
        color: #596d9b;
    }
    .tac{text-align: center;}
    .tal{text-align: left;}
    .btn-ajuste-pesquisa{margin-top: 5px;}
</style>

<h3 style="text-align: center;background: #596d9b;margin: 0px;padding: 15px;color: #fff;font-size: 18px;"><?php echo traduz("Pesquisar Atendimento");?></h3>
<div class="well" style="margin: 5px;border-radius: 0px;">
    <div class="row">
        <div class="col-xs-4 col-sm-4 col-md-4">
            <label><?php echo traduz("Tipo");?></label>
            <select name="tipo" id="tipo" class="form-control input-sm">
                <option value=""><?php echo traduz("Selecione...");?></option>
                <?php foreach ($tiposPesquisas as $key => $tipos) {?>
                    <option value="<?php echo $key;?>" <?php echo ($key == $tipo) ? "selected" : "";?>><?php echo $tipos;?></option>
                <?php }?>
            </select>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <label><?php echo traduz("Descrição da Pesquisa");?></label>
            <input type="text" name="campo" value="<?php echo $filtro;?>" class="form-control input-sm">
        </div>
        <div class="col-xs-2 col-sm-2 col-md-2">
            <br>
            <button class="btn btn-sm btn-ajuste-pesquisa btn-default"><?php echo traduz("Pesquisar");?></button>
        </div>
    </div>
</div>

<div class="well" style="margin: 5px;background: #fbfbfb;border-radius: 0px;text-align: center;">
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 tac">
            <p><?php echo traduz("Pesquisando pelo");?> <b><?php echo $tiposPesquisas[$tipo];?></b>: <?php echo $filtro;?></p>
        </div>
    </div>
</div>

<!-- <div class="well" style="margin: 5px;background: #fbfbfb;border-radius: 0px;">
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <p style="color: #d90000;">ATENÇÃO</p>
            -Clicando sobre o número do atendimento, irá continuar o atendimento selecionado.<br>
            -Clicando sobre o número da ordem de serviço:<br>
            ... Caso exista atendimento:<br><br>

            Se o atendimento não estiver resolvido, o atendimento continuará.<br>
            Se o atendimento já estiver resolvido,abrirá um novo atendimento.<br>
            Caso não tenha um atendimento, o admin poderá cadastrar um atendimento para a ordem de serviço.<br>
            -Clicando sobre o nome do consumidor, abrirá um novo chamado para o consumidor.<br>
            -Clicando sobre o produto, abrirá um novo chamado para o produto e o consumidor da linha selecionada.<br><br>

            Pare o cursor do mouse sobre os itens para instruções / informações adicionais
        </div>
    </div>
</div>
 -->

 <div class="table-responsive" style="margin: 5px;">
     <table class="table table-bordered table-striped">
         <thead>
             <tr class="titulo_coluna">
                 <th nowrap class="tac"><?php echo traduz("Atendimento");?></th>
                 <th nowrap class="tac"><?php echo traduz("Data");?></th>
                 <th nowrap><?php echo traduz("Cliente");?></th>
                 <th nowrap><?php echo traduz("Endereço");?></th>
                 <th nowrap class="tac"><?php echo traduz("Ordem de Serviço");?></th>
                 <th nowrap><?php echo traduz("Produto");?></th>
                 <th nowrap class="tac"><?php echo traduz("Status");?></th>
             </tr>
         </thead>
         <tbody>
            <?php for ($i=0; $i < 5; $i++) { ?>
             <tr>
                 <td nowrap class="tac"><span class="text_callcenter">6797050</span></td>
                 <td nowrap class="tac">27/02/2020 </td>
                 <td nowrap>TESTE JOAO</td>
                 <td nowrap>    ALEXANDRE CHAIA, 16515 - JARDIM ESPLANADA - MARILIA/SP - 17521182</td>
                 <td nowrap class="tac">
                    <span class="label label-danger">SEM ORDEM SERVIÇO</span>
                 </td>
                 <td nowrap>45SDS55 - PRODUTO TESTE</td>
                 <td nowrap class="tac">Aberto</td>
             </tr>
             <tr>
                 <td nowrap class="tac">
                    <span class="label label-danger">SEM ATENDIMENTO</span>
                 </td>
                 <td nowrap class="tac">27/02/2020 </td>
                 <td nowrap>TESTE JOAO</td>
                 <td nowrap>    ALEXANDRE CHAIA, 16515 - JARDIM ESPLANADA - MARILIA/SP - 17521182</td>
                 <td nowrap class="tac">
                    <span class="label label-danger">SEM ORDEM SERVIÇO</span>
                 </td>
                 <td nowrap>45SDS55 - PRODUTO TESTE</td>
                 <td nowrap class="tac">Aberto</td>
             </tr>
            <?php }?>
         </tbody>
     </table>
 </div>
</div>