<?php
/**
 * aluno_presenca Active Record
 * @author  <your-name-here>
 */
class aluno_presenca extends TRecord
{
    const TABLENAME = 'sisacad.aluno_presenca';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    private $controle_aula;                 //Assossiação
    private $aluno;                         //Assossiação    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('faltas');
        parent::addAttribute('abonadas');
        parent::addAttribute('justificativa');
        parent::addAttribute('controle_aula_id');
        parent::addAttribute('aluno_id');
    }
    /**
     * Method get_controle_aula
     * Sample of usage: $aluno_presenca->controle_aula->attribute;
     * @returns controle_aula instance
     */
    public function get_controle_aula()
    {
        // loads the associated object
        if (empty($this->controle_aula))
            $this->controle_aula = new controle_aula($this->controle_aula_id);
    
        // returns the associated object
        return $this->controle_aula;
    }
    /**
     * Method get_aluno
     * Sample of usage: $aluno_presenca->aluno->attribute;
     * @returns aluno instance
     */
    public function get_aluno()
    {
        // loads the associated object
        if (empty($this->aluno))
            $this->aluno = new aluno($this->aluno_id);
    
        // returns the associated object
        return $this->aluno;
    }


}
