<?php
/**
 * feriadoopm Active Record - Gerencia serviços de OPMs
 * @author  <your-name-here>
 */
class feriadoopm extends TRecord
{
    const TABLENAME = 'bdhoras.feriadoopm';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('feriado_id');
        parent::addAttribute('opm_id');
    }


}
