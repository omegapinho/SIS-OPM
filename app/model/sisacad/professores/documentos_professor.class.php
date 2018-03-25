<?php
/**
 * documentos_professor Active Record
 * @author  <your-name-here>
 */
class documentos_professor extends TRecord
{
    const TABLENAME = 'sisacad.documentos_professor';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    private $professor;       //Associação
    private $escolaridade;     //Associação
    
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
        parent::addAttribute('arquivos_professor_id');
        parent::addAttribute('professor_id');
        parent::addAttribute('escolaridade_id');
        parent::addAttribute('assinatura');
    }

    /**
     * Method get_professor
     * Sample of usage: $model->campo->attribute;
     * @returns campo instance
     */
    public function get_professor()
    {
        // loads the associated object
        if (empty($this->professor))
            $this->professor = new professor($this->professor_id);
    
        // returns the associated object
        return $this->professor;
    }
    /**
     * Method get_escolaridade
     * Sample of usage: $model->campo->attribute;
     * @returns campo instance
     */
    public function get_escolaridade()
    {
        // loads the associated object
        if (empty($this->escolaridade))
            $this->escolaridade = new escolaridade($this->escolaridade_id);
    
        // returns the associated object
        return $this->escolaridade;
    }
    /**
     * Delete the object and its aggregates
     * @param $id object ID
     */
    public function delete($id = NULL)
    {
        $id = isset($id) ? $id : $this->id;
        $doc = new documentos_professor($id);
        // delete the related aluno objects
        $repository = new TRepository('arquivos_professor');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id', '=', $doc->arquivos_professor_id));
        $repository->delete($criteria);
        // delete the object itself
        parent::delete($id);
    }

}
