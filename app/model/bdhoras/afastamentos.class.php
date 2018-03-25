<?php
/**
 * afastamentos Active Record
 * @author  <your-name-here>
 */
class afastamentos extends TRecord
{
    const TABLENAME = 'bdhoras.afastamentos';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('oculto');
        parent::addAttribute('trabalha');
        parent::addAttribute('integral');
    }

    
    /**
     * Method gethistorico_trabalhos
     */
    public function gethistorico_trabalhos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('afastamentos_id', '=', $this->id));
        return historico_trabalho::getObjects( $criteria );
    }
    


}
