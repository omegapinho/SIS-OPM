<?php
/**
 * servicoOPM Active Record
 * @author  <your-name-here>
 */
class grupo_servico extends TRecord
{
    const TABLENAME = 'freap.grupo_servico';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('id_servico');
        parent::addAttribute('id_opm');
    }


}
