<?php
/**
 * incidentes_categoria Active Record
 * @author  <your-name-here>
 */
class incidentes_categoria extends TRecord
{
    const TABLENAME = 'g_geral.incidentes_categoria';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('sistema_id');
        parent::addAttribute('grupo_id');           //Sem Uso
        parent::addAttribute('user_id');            //Sem Uso
        parent::addAttribute('nome');
        parent::addAttribute('status');
        parent::addAttribute('oculto');
    }


}
