<form id="form_callcenter" action="" method="post">
<div class="container_bc">
    <div class="top_painel">
        <div class="row">
            <div class="col-md-12 tac">
                <b><?php echo traduz("Informações do Consumidor");?></b> 
            </div>
        </div>
    </div>
    <div id="box_consumidor" class="box_campos">
        <div class="box_add_init_callcenter">
            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="control-label"><?php echo traduz("Tipo Consumidor");?>:</label>
                        <select name="consumidor[tipo_consumidor]" class="form-control input-sm tipo_consumidor">
                            <option value=""><?php echo traduz("Selecione");?> ...</option>
                            <option value="F" <?php echo (isset($status) && (trim($status) == "F")) ? "selected" : "";?>>CPF</option>
                            <option value="J" <?php echo (isset($status) && (trim($status) == "J")) ? "selected" : "";?>>CNPJ</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="control-label"><?php echo traduz("Nome");?>:</label>
                        <div class="input-group">
                            <input type="text" name="consumidor[nome]" class="form-control input-sm">
                            <span class="input-group-btn">
                                <a  href="<?php ?>" class="btn btn-sm btn-default" data-toggle="modal" data-target=".modal_projeto" title="Adicionar Novo Projeto"><i class="fa fa-search"></i></a>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="control-label label_cpf"><?php echo traduz("CPF");?>:</label>
                        <div class="input-group">
                            <input type="text" name="consumidor[cpf_cnpj]" id="cpf_cnpj" class="form-control input-sm cpf_cnpj">
                            <span class="input-group-btn">
                                <a  href="<?php ?>" data-toggle="modal" data-target=".modal_contato" title="Adicionar Novo Contato" class="btn btn-sm btn-default"><i class="fa fa-search"></i></a>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="control-label label_rg"><?php echo traduz("RG");?>:</label>
                        <input type="text" name="consumidor[rg_ie]" class="form-control input-sm">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="control-label label_email"><?php echo traduz("E-mail");?>:</label>
                        <input type="text" name="consumidor[email]" class="form-control input-sm email">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="control-label label_fone"><?php echo traduz("Fone");?>:</label>
                        <div class="input-group">
                            <input type="text" name="consumidor[fone]" class="form-control input-sm campoFone">
                            <span class="input-group-addon"><i class="fa fa-phone"></i>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="control-label label_celular"><?php echo traduz("Celular");?>:</label>
                        <div class="input-group">
                            <input type="text" name="consumidor[celular]" class="form-control input-sm campoCelular">
                            <span class="input-group-addon"><i class="fa fa-mobile"></i>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="control-label label_fax"><?php echo traduz("Fone Comercial");?>:</label>
                        <div class="input-group">
                            <input type="text" name="consumidor[fax]" class="form-control input-sm campoFone">
                            <span class="input-group-addon"><i class="fa fa-phone"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div><hr>
            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="control-label label_cep"><?php echo traduz("CEP");?>:</label>
                        <div class="input-group">
                            <input type="text" name="consumidor[cep]" class="form-control input-sm campoCEP">
                            <span class="input-group-addon"><i class="fa fa-map-marker"></i>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <label class="control-label label_endereco"><?php echo traduz("Endereço");?>:</label>
                    <input type="text" name="consumidor[endereco]" class="form-control input-sm endereco">
                </div>
                <div class="col-md-1">
                    <label class="control-label label_numero"><?php echo traduz("Número");?>:</label>
                    <input type="text" name="consumidor[numero]" class="form-control input-sm numero">
                </div>
                <div class="col-md-2">
                    <label class="control-label label_complemento"><?php echo traduz("Complemento");?>:</label>
                    <input type="text" name="consumidor[complemento]" class="form-control input-sm complemento">
                </div>
                <div class="col-md-2">
                    <label class="control-label label_bairro"><?php echo traduz("Bairro");?>:</label>
                    <input type="text" name="consumidor[bairro]" class="form-control input-sm bairro">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label class="control-label label_cidade"><?php echo traduz("Cidade");?>:</label>
                    <select name="consumidor[cidade]" class="form-control input-sm cidade">
                        <option value=""><?php echo traduz("Selecione");?> ...</option>
                        <?php
                            if (strlen($consumidor_estado) > 0) {
                                $sql = "SELECT DISTINCT * FROM
                                            (
                                                SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".$consumidor_estado."')
                                            UNION (
                                                SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".$consumidor_estado."')
                                            )
                                            ) AS cidade
                                            ORDER BY cidade ASC";
                                $res = pg_query($con, $sql);

                                if (pg_num_rows($res) > 0) {
                                    while ($result = pg_fetch_object($res)) {
                                        $selected  = (trim($result->cidade) == strtoupper(retira_acentos(retira_especiais($consumidor_cidade)))) ? "SELECTED" : "";
                                        echo "<option value='{$result->cidade}' {$selected} >{$result->cidade} </option>";
                                    }
                                }
                            }
                            ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="control-label label_uf"><?php echo traduz("UF");?>:</label>
                    <select name="consumidor[uf]" class="form-control input-sm uf">
                        <option value=""><?php echo traduz("Selecione");?> ...</option>
                        <?php
                            foreach ($array_estados()as $sigla => $nome) {
                                echo"<option value='".$sigla."'>".$nome."</option>\n";
                            }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="control-label label_obs"><?php echo traduz("Obs Contato");?>:</label>
                    <input type="text" name="consumidor[obs]" class="form-control input-sm obs">
                </div>
            </div>
        </div>
    </div>
</div>
