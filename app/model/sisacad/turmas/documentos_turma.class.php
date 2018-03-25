<?php
/**
 * documentos_turma Active Record
 * @author  <your-name-here>
 */
class documentos_turma extends TRecord
{
    const TABLENAME = 'sisacad.documentos_turma';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $arquivos_externos;
    private $turma;
    //private $tipo_doc;    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('data_doc');
        parent::addAttribute('tipo_doc');
        parent::addAttribute('cadastrador');
        parent::addAttribute('descricao');
        parent::addAttribute('oculto');
        parent::addAttribute('comprovante');
        parent::addAttribute('data_aula');
        parent::addAttribute('arquivos_externos_id');
        parent::addAttribute('turma_id');
    }

    
    /**
     * Method set_arquivos_externos
     * Sample of usage: $documentos_turma->arquivos_externos = $object;
     * @param $object Instance of arquivos_externos
     */
    public function set_arquivos_externos(arquivos_externos $object)
    {
        $this->arquivos_externos = $object;
        $this->arquivos_externos_id = $object->id;
    }
    
    /**
     * Method get_arquivos_externos
     * Sample of usage: $documentos_turma->arquivos_externos->attribute;
     * @returns arquivos_externos instance
     */
    public function get_arquivos_externos()
    {
        // loads the associated object
        if (empty($this->arquivos_externos))
            $this->arquivos_externos = new arquivos_externos($this->arquivos_externos_id);
    
        // returns the associated object
        return $this->arquivos_externos;
    }
    /**
     * Method get_tipo_doc
     * Sample of usage: $documentos_turma->tipo_doc->attribute;
     * @returns tipo_doc instance
     */
    /*public function get_tipo_doc()
    {
        // loads the associated object
        if (empty($this->tipo_doc))
        {
            $criteria = new TCriteria;
            $criteria->add(new TFilter('tipo_doc', '=', $this->tipo_doc));
            $this->tipo_doc = tipo_doc::getObjects( $criteria );
        }
        return $this->tipo_doc;
    }*/
    /**
     * Method get_turma
     * Sample of usage: $documentos_turma->turma->attribute;
     * @returns turma instance
     */
    public function get_turma()
    {
        // loads the associated object
        if (empty($this->turma))
            $this->turma = new turma($this->turma_id);
    
        // returns the associated object
        return $this->turma;
    }
    
    


}
