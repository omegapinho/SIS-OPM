<?php
/**
 * cidades Active Record
 * @author  <Fernando de Pinho Araújo>
 */
class cidades extends TRecord
{
    const TABLENAME = 'regioes.cidades';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('uf');
        parent::addAttribute('bairros');
    }
    /**
     * Method getcontratos
     */
    public function getcontratos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id_cidades', '=', $this->id));
        return contrato::getObjects( $criteria );
    }
    
    
    /**
     * Method getcontribuintes
     */
    public function getcontribuintes()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id_cidades', '=', $this->id));
        return contribuinte::getObjects( $criteria );
    }

}
