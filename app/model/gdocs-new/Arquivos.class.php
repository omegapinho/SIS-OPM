<?php
/**
 * Arquivos Active Record
 * @author  <your-name-here>
 */
class Arquivos extends TRecord
{
    const TABLENAME = 'gdocs.arquivos';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('titulo');
        parent::addAttribute('descricao');
        parent::addAttribute('diskfile');
        parent::addAttribute('filename');
        parent::addAttribute('folder');
        parent::addAttribute('filesize');
        parent::addAttribute('file_type');
        parent::addAttribute('conteudo');
        parent::addAttribute('data_incluido');
        parent::addAttribute('relator_id');
        parent::addAttribute('relator');
        parent::addAttribute('visibilidade');
        parent::addAttribute('oculto');
        parent::addAttribute('problema_id');
    }


}
