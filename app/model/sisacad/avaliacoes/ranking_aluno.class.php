<?php
/**
 * ranking_aluno Active Record
 * @author  <your-name-here>
 */
class ranking_aluno extends TRecord
{
    const TABLENAME = 'sisacad.ranking_aluno';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $ranking;
    private $aluno;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nota');
        parent::addAttribute('posicao');
        parent::addAttribute('ranking_id');
        //parent::addAttribute('aluno_id');//Removido em 2018-04-12
        //Adicionados em 2018-04-12
        parent::addAttribute('cpf');
        parent::addAttribute('recuperado');
        parent::addAttribute('reprovado');
        parent::addAttribute('turma_id');
    }

    
    /**
     * Method set_ranking
     * Sample of usage: $ranking_aluno->ranking = $object;
     * @param $object Instance of ranking
     */
    public function set_ranking(ranking $object)
    {
        $this->ranking = $object;
        $this->ranking_id = $object->id;
    }
    
    /**
     * Method get_ranking
     * Sample of usage: $ranking_aluno->ranking->attribute;
     * @returns ranking instance
     */
    public function get_ranking()
    {
        // loads the associated object
        if (empty($this->ranking))
            $this->ranking = new ranking($this->ranking_id);
    
        // returns the associated object
        return $this->ranking;
    }
    
    
    /**
     * Method set_aluno
     * Sample of usage: $ranking_aluno->aluno = $object;
     * @param $object Instance of aluno
     */
    public function set_aluno(aluno $object)
    {
        $this->aluno = $object;
        $this->aluno_id = $object->id;
    }
    
    /**
     * Method get_aluno
     * Sample of usage: $ranking_aluno->aluno->attribute;
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
