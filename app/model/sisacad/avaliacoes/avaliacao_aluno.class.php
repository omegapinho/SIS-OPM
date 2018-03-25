<?php
/**
 * avaliacao_aluno Active Record
 * @author  <your-name-here>
 */
class avaliacao_aluno extends TRecord
{
    const TABLENAME = 'sisacad.avaliacao_aluno';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $aluno;
    private $avaliacao_prova;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nota');
        parent::addAttribute('status');
        parent::addAttribute('usuario_lancador');
        parent::addAttribute('data_lancamento');
        parent::addAttribute('aluno_id');
        parent::addAttribute('avaliacao_prova_id');
        parent::addAttribute('fator_moderador');//2018-02-14
    }

    
    /**
     * Method set_aluno
     * Sample of usage: $avaliacao_aluno->aluno = $object;
     * @param $object Instance of aluno
     */
    public function set_aluno(aluno $object)
    {
        $this->aluno = $object;
        $this->aluno_id = $object->id;
    }
    
    /**
     * Method get_aluno
     * Sample of usage: $avaliacao_aluno->aluno->attribute;
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
    /**
     * Method get_avaliacao_prova
     * Sample of usage: $avaliacao_aluno->avaliacao_prova->attribute;
     * @returns avaliacao_prova instance
     */
    public function get_avaliacao_prova()
    {
        // loads the associated object
        if (empty($this->avaliacao_prova))
            $this->avaliacao_prova = new avaliacao_prova($this->avaliacao_prova_id);
    
        // returns the associated object
        return $this->avaliacao_prova;
    }


}
