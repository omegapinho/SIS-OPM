<?php
/**
 * incidentes_texto Active Record
 * @author  <your-name-here>
 */
class incidentes_texto extends TRecord
{
    const TABLENAME = 'g_geral.incidentes_texto';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('descricao');
        parent::addAttribute('texto_adicional');
        parent::addAttribute('oculto');
        parent::addAttribute('incidente_id');     //2017-09-29
    }


}
