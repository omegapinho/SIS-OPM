<?php
/**
 * arquivos_externos Active Record
 * @author  <your-name-here>
 */
class arquivos_externos extends TRecord
{
    const TABLENAME = 'sisacad.arquivos_externos';
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
        parent::addAttribute('documento_turma_antigo_id');
    }

    
    /**
     * Method getdocumentos_turmas
     */
    public function getdocumentos_turmas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('arquivos_externos_id', '=', $this->id));
        return documentos_turma::getObjects( $criteria );
    }
    


}
