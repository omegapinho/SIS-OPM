<?php
/**
 * CategoriasOpm Active Record
 * @author  <your-name-here>
 */
class CategoriasOpm extends TRecord
{
    const TABLENAME = 'gdocs.categorias_opm';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('categoria_id');
        parent::addAttribute('opm_id');
    }


}
