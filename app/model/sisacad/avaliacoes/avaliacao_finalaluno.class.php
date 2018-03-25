<?php
/**
 * avaliacao_finalaluno Active Record
 * @author  <your-name-here>
 */
class avaliacao_finalaluno extends TRecord
{
    const TABLENAME = 'sisacad.avaliacao_finalaluno';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $avaliacao_final;
    private $aluno;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nota');
        parent::addAttribute('aprovado');
        parent::addAttribute('recuperado');
        parent::addAttribute('avaliacao_final_id');
        parent::addAttribute('aluno_id');
    }

    
    /**
     * Method set_avaliacao_final
     * Sample of usage: $avaliacao_finalaluno->avaliacao_final = $object;
     * @param $object Instance of avaliacao_final
     */
    public function set_avaliacao_final(avaliacao_final $object)
    {
        $this->avaliacao_final = $object;
        $this->avaliacao_final_id = $object->id;
    }
    
    /**
     * Method get_avaliacao_final
     * Sample of usage: $avaliacao_finalaluno->avaliacao_final->attribute;
     * @returns avaliacao_final instance
     */
    public function get_avaliacao_final()
    {
        // loads the associated object
        if (empty($this->avaliacao_final))
            $this->avaliacao_final = new avaliacao_final($this->avaliacao_final_id);
    
        // returns the associated object
        return $this->avaliacao_final;
    }
    
    
    /**
     * Method set_aluno
     * Sample of usage: $avaliacao_finalaluno->aluno = $object;
     * @param $object Instance of aluno
     */
    public function set_aluno(aluno $object)
    {
        $this->aluno = $object;
        $this->aluno_id = $object->id;
    }
    
    /**
     * Method get_aluno
     * Sample of usage: $avaliacao_finalaluno->aluno->attribute;
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
