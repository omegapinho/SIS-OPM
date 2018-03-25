<?php
/**
 * OPM_filtro Active Record
 * @author  <your-name-here>
 */
class OPM_filtro extends TRecord
{
    const TABLENAME = 'g_geral.opm_filtro';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    private $servicos;
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('opm_id');
    }//Fim Módulo Construct
}//Fim Classe
