<?php
/**
 * historico_geracao Active Record
 * @author  <your-name-here>
 */
class historico_geracao extends TRecord
{
    const TABLENAME = 'g_geral.historico_geracao';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('dt_historico');
        parent::addAttribute('mudanca');
        parent::addAttribute('valor_anterior');
        parent::addAttribute('valor_atual');
        parent::addAttribute('usuario_atualizador');
        parent::addAttribute('controle_geracao_id');
		parent::addAttribute('motivo');//2017-12-07
    }


}
