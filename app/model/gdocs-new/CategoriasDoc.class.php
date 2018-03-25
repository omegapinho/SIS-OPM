<?php
/**
 * CategoriasDoc Active Record
 * @author  <your-name-here>
 */
class CategoriasDoc extends TRecord
{
    const TABLENAME = 'gdocs.categorias_doc';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('descricao');
        parent::addAttribute('opm_id');
        parent::addAttribute('opm');
        parent::addAttribute('visivel');
    }


}
