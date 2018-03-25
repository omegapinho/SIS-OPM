<?php
/**
 * incidentes_historico Active Record
 * @author  <your-name-here>
 */
class incidentes_historico extends TRecord
{
    const TABLENAME = 'g_geral.incidentes_historico';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('usuario_id');
        parent::addAttribute('incidentes_id');
        parent::addAttribute('campo_alterado');
        parent::addAttribute('valor_velho');
        parent::addAttribute('valor_novo');
        parent::addAttribute('tipo_operacao');
        parent::addAttribute('data_alteracao');
        parent::addAttribute('oculto');
    }


}
