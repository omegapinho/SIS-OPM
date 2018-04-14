<?php
/**
 * ranking Active Record
 * @author  <your-name-here>
 */
class ranking extends TRecord
{
    const TABLENAME = 'sisacad.ranking';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $curso;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('data_fim');
        parent::addAttribute('usuario');
        parent::addAttribute('oculto');
        parent::addAttribute('usuario_atualizador');
        parent::addAttribute('data_atualizado');
        parent::addAttribute('curso_id');
    }

    
    /**
     * Method set_curso
     * Sample of usage: $ranking->curso = $object;
     * @param $object Instance of curso
     */
    public function set_curso(curso $object)
    {
        $this->curso = $object;
        $this->curso_id = $object->id;
    }
    
    /**
     * Method get_curso
     * Sample of usage: $ranking->curso->attribute;
     * @returns curso instance
     */
    public function get_curso()
    {
        // loads the associated object
        if (empty($this->curso))
            $this->curso = new curso($this->curso_id);
    
        // returns the associated object
        return $this->curso;
    }
    

    
    /**
     * Method getranking_alunos
     */
    public function getranking_alunos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('ranking_id', '=', $this->id));
        return ranking_aluno::getObjects( $criteria );
    }
    


}
