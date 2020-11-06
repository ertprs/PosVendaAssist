<?php

namespace Posvenda\Fabricas\_156;

use Posvenda\Os as OsPosvenda;

class Os extends OsPosvenda
{
	public function __construct($fabrica, $os = null, $conn = null)
    {
        parent::__construct($fabrica, $os, $conn);
    }

    public function finalizaAtendimento($hd_chamado)
    {
        if (empty($hd_chamado)) {
            return false;
        }

        $pdo = $this->_model->getPDO();
        $comentario = 'A OS ' . $this->_os . ' aberta para este atendimento foi finalizada.';

        $sql = "INSERT INTO tbl_hd_chamado_item (
            hd_chamado,
            data,
            comentario,
            status_item
        ) VALUES (
            $hd_chamado,
            CURRENT_TIMESTAMP,
            '$comentario',
            'Resolvido'
        )";

        $query = $pdo->query($sql);

        if (empty($query)) {
            return false;
        }

        $sql = "UPDATE tbl_hd_chamado SET status = 'Resolvido' WHERE hd_chamado = $hd_chamado";
        $query = $pdo->query($sql);

        if (empty($query)) {
            return false;
        }

        return true;
    }
}
