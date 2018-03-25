<?php
/**
 * configura Active Record
 * @author  <your-name-here>
 */
class configura extends TRecord
{
    const TABLENAME = 'g_geral.configura';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('dominio');
        parent::addAttribute('pagina');
        parent::addAttribute('name');
        parent::addAttribute('label');
        parent::addAttribute('tip');
        parent::addAttribute('type');
        parent::addAttribute('value');
        parent::addAttribute('value_combo');
        parent::addAttribute('tamanho');
        parent::addAttribute('ativo');
        parent::addAttribute('visivel');
    }


}
