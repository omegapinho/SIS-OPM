<?php
/**
 * estados Active Record
 * @author  <your-name-here>
 */
class estados extends TRecord
{
    const TABLENAME = 'regioes.estados';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('hash');
        parent::addAttribute('sigla');
        parent::addAttribute('nome');
        parent::addAttribute('cidades');
    }

    /**
     * Method getcontratos
     */
    public function getcontratos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id_estados', '=', $this->id));
        return contrato::getObjects( $criteria );
    }
    
    
    /**
     * Method getcontribuintes
     */
    public function getcontribuintes()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id_estados', '=', $this->id));
        return contribuinte::getObjects( $criteria );
    }

}
