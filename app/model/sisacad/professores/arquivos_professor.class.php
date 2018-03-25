<?php
/**
 * arquivos_professor Active Record
 * @author  <your-name-here>
 */
class arquivos_professor extends TRecord
{
    const TABLENAME = 'sisacad.arquivos_professor';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('title');
        parent::addAttribute('diskfile');
        parent::addAttribute('filename');
        parent::addAttribute('folder');
        parent::addAttribute('filesize');
        parent::addAttribute('file_type');
        parent::addAttribute('date_add');
        parent::addAttribute('cadastrador');
        parent::addAttribute('contend');
        parent::addAttribute('oculto');
        parent::addAttribute('tipo_doc');
        parent::addAttribute('documento_antigo_id');
    }


}
