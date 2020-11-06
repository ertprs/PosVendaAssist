<input type="hidden" name="ri_acoes_corretivas[id]" value="<?= $dadosRequisicao["ri_acoes_corretivas"]["id"] ?>" />
<div align='center' class='form-horizontal tc_formulario'>
    <div class="row row-relatorio">
        <div class='titulo_tabela' style="margin-bottom: 30px;">Identificação e Verificação das Ações Corretivas</div>
        <div class="col-sm-10 col-sm-offset-1">
            <div class="alert alert-info">
                <h5>Listar as ações de combate as causas raíz, os poka Yokes criados (se houverem) e as evidências destas ações. <br /><br />As ações corretivas listadas abaixo devem eliminar a causa-raíz encontrada.</h5>
            </div>
        </div>
    </div>
    <div class="row row-relatorio">
        <table class="col-sm-10 table table-bordered table-fixed table-condensed tabela-identificacao-acoes">
            <thead>
                <tr>
                    <th colspan="2">O quê ?</th>
                    <th>Onde ?</th>
                    <th>Quem ?</th>
                    <th>Quando ?</th>
                </tr>
            </thead>
            <tbody>
                <?php

                $countAcoes = count($dadosRequisicao["ri_acoes_corretivas"]["o_que"]);

                if ($countAcoes == 0) {

                    $countAcoes = 1;

                }
                
                for ($x = 0;$x < $countAcoes;$x++) { ?>

                    <tr>
                        <td style="background-color: lightgray;">
                            <?php
                            $hiddenExcluir = $x == 0 ? "hidden" : "";
                            ?>
                            <div <?= $hiddenExcluir ?> class="form-group div-btn-excluir-corretiva" style="text-align: center;">
                                <br />
                                <button class="btn btn-danger btn-sm btn-remover-corretiva" type="button">
                                    X
                                </button>
                            </div>
                        </td>
                        <td>
                            <div class="form-group">
                                <textarea placeholder="O quê" class="form-control textarea-corretivas obrigatorio" name="ri_acoes_corretivas[o_que][]" rows="3"><?= $dadosRequisicao["ri_acoes_corretivas"]["o_que"][$x] ?></textarea>
                            </div>
                        </td>
                        <td>
                            <div class="form-group">
                                <textarea placeholder="Onde" class="form-control textarea-corretivas obrigatorio" name="ri_acoes_corretivas[onde][]" rows="3"><?= $dadosRequisicao["ri_acoes_corretivas"]["onde"][$x] ?></textarea>
                            </div>
                        </td>
                        <td>
                            <div class="form-group">
                                <textarea placeholder="Quem" class="form-control textarea-corretivas obrigatorio" name="ri_acoes_corretivas[quem][]" rows="3"><?= $dadosRequisicao["ri_acoes_corretivas"]["quem"][$x] ?></textarea>
                            </div>
                        </td>
                        <td>
                            <div class="form-group">
                                <textarea placeholder="Quanto" class="form-control textarea-corretivas obrigatorio" name="ri_acoes_corretivas[quando][]" rows="3"><?= $dadosRequisicao["ri_acoes_corretivas"]["quando"][$x] ?></textarea>
                            </div>
                        </td>
                    </tr>

                <?php
                } ?>
            </tbody>
            <tfoot>
                <td colspan="100%">
                    <br />
                    <input type="button" id="btn_nova_linha_corretiva" class="btn btn-info" value="+ Acrescentar Nova Linha">
                    <br /><br />
                </td>
            </tfoot>
        </table>
    </div>
    <div class="row row-relatorio">
        <div class='titulo_tabela' style="margin-bottom: 30px;">Poka Yoke</div>
        <div class="col-sm-8 col-sm-offset-4">
            <div class="form-group">
                <label style="font-size: 14px;color: darkblue;">Deseja implementar a Poka Yoke?</label> 
                <label>
                    <input style="margin-left: 25px;" type="radio" id="pokaSim" name="ri_acoes_corretivas[poka_yoke]" value= "t" <?= $dadosRequisicao["ri_acoes_corretivas"]["poka_yoke"] == true ? "checked" : "" ?> /> Sim
                </label>
                <label>
                    <input style="margin-left: 25px;" type="radio" id="pokaNao" name="ri_acoes_corretivas[poka_yoke]" value= "f" <?= $dadosRequisicao["ri_acoes_corretivas"]["poka_yoke"] != true || !isset($dadosRequisicao["ri_acoes_corretivas"]["poka_yoke"])  ? "checked" : "" ?> /> Não
                </label>
            </div>
        </div>
        <div class="col-sm-7">
            <label>Justique:</label>
            <textarea name="ri_acoes_corretivas[poka_yoke_justificativa]" rows="6" cols="110" style='font-size:10px;'><?= $dadosRequisicao["ri_acoes_corretivas"]["poka_yoke_justificativa"] ?></textarea>
        </div>
        <div class="col-sm-5">
            <?php
            include "../box_uploader.php";
            ?>
        </div>
    </div>
    <div class="row row-relatorio">
        <div class='titulo_tabela' style="margin-bottom: 30px;">Revisão de Documentos</div>
        <div class="col-sm-10 col-sm-offset-1">
            <div class="form-group">
                <label style="font-size: 14px;color: darkblue;">É necessário revisar documentos?</label> 
                <label>
                    <input style="margin-left: 25px;" type="radio" class="revisar_docs" name="revisao_docs" value= "t" <?= count($dadosRequisicao["ri_acoes_corretivas"]["documentos"]) > 0 ? "checked" : "" ?> /> Sim
                </label>
                <label>
                    <input style="margin-left: 25px;" type="radio" class="revisar_docs" name="revisao_docs" value= "f" <?= count($dadosRequisicao["ri_acoes_corretivas"]["documentos"]) == 0 ? "checked" : "" ?> /> Não
                </label>
            </div>
        </div>
        <div class="col-sm-10 col-sm-offset-1 documentos-revisar" <?= count($dadosRequisicao["documentos"]) > 0 ? "" : "hidden" ?>>
            <div class="form-group">
                <label style="font-size: 14px;color: darkblue;">Quais?</label> <br />
                <label>
                    <input style="margin-left: 25px;" type="checkbox" name="ri_acoes_corretivas[documentos][desenhos]" value= "t" <?= $dadosRequisicao["ri_acoes_corretivas"]["documentos"]["desenhos"] == "t" ? "checked" : "" ?> /> Desenhos / Especificações
                </label>
                <label>
                    <input style="margin-left: 25px;" type="checkbox" name="ri_acoes_corretivas[documentos][dvp]" value= "t" <?= $dadosRequisicao["ri_acoes_corretivas"]["documentos"]["dvp"] == "t" ? "checked" : "" ?> /> DVP FMEA
                </label>
                <label>
                    <input style="margin-left: 25px;" type="checkbox" name="ri_acoes_corretivas[documentos][ppap]" value= "t" <?= $dadosRequisicao["ri_acoes_corretivas"]["documentos"]["ppap"] == "t" ? "checked" : "" ?> /> PPAP
                </label>
                <label>
                    <input style="margin-left: 25px;" type="checkbox" name="ri_acoes_corretivas[documentos][cep]" value= "t" <?= $dadosRequisicao["ri_acoes_corretivas"]["documentos"]["cep"] == "t" ? "checked" : "" ?> /> CEP
                </label>
                <label>
                    <input style="margin-left: 25px;" type="checkbox" name="ri_acoes_corretivas[documentos][msa]" value= "t" <?= $dadosRequisicao["ri_acoes_corretivas"]["documentos"]["msa"] == "t" ? "checked" : "" ?> /> MSA
                </label>
                <label>
                    <input style="margin-left: 25px;" type="checkbox" name="ri_acoes_corretivas[documentos][plano_controle]" value= "t" <?= $dadosRequisicao["ri_acoes_corretivas"]["documentos"]["plano_controle"] == "t" ? "checked" : "" ?> /> Plano de Controle
                </label>
                <label>
                    <input style="margin-left: 25px;" type="checkbox" name="ri_acoes_corretivas[documentos][instrucao]" value= "t" <?= $dadosRequisicao["ri_acoes_corretivas"]["documentos"]["instrucao"] == "t" ? "checked" : "" ?> /> Instrução Operacional
                </label>
                <label>
                    <input style="margin-left: 25px;" type="checkbox" name="ri_acoes_corretivas[documentos][procedimentos]" value= "t" <?= $dadosRequisicao["ri_acoes_corretivas"]["documentos"]["procedimentos"] == "t" ? "checked" : "" ?> /> Procedimentos
                </label><br />
                <label>Outros:</label>
                <input style="width: 150px;" type="text" class="form-control" name="ri_acoes_corretivas[documentos][outros]" value="<?= $dadosRequisicao["ri_acoes_corretivas"]["documentos"]["outros"] ?>" />
            </div>
            
        </div>
    </div>
    <br />
    <div class="row row-relatorio">
        <div class="col-sm-12" style="text-align: center;">
            <hr />
            <br />
            <input data-aba="<?= $codigoAba ?>" type="button" class="btn-submit btn btn-default btn-lg" value="<?= !empty($dadosRequisicao['ri_acoes_corretivas']['id']) ? 'Alterar' : 'Gravar' ?>" style="width: 300px;" />
        </div>
    </div>
	<br /><br />
</div>