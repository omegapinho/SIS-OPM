<?php
/**
 * ctl_atualizar Active Record
 * @author  <your-name-here>
 */
class ctl_atualizar extends TRecord
{
    const TABLENAME = 'g_geral.ctl_atualizar';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('data_atual');
        parent::addAttribute('periodo');
    }


}
