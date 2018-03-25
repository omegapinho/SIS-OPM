<?php
/**
 * TurmasMatriculando Active Record
 * @author  <your-name-here>
 */
class turmasmatriculando extends TRecord
{
    const TABLENAME = 'sisacad.turmas_matriculando';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
        
    private $turma;       //Associação
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('turma_id');
        parent::addAttribute('oculto');
        parent::addAttribute('data_inicio');
    }
    /**
     * Method get_orgaosorigem
     * Sample of usage: $professor->orgaosorigem->attribute;
     * @returns orgaosorigem instance
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
