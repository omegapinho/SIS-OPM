<?php
/**
 * avaliacao_rankingaluno Active Record
 * @author  <your-name-here>
 */
class avaliacao_rankingaluno extends TRecord
{
    const TABLENAME = 'sisacad.avaliacao_rankingaluno';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $avaliacao_ranking;
    private $aluno;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nota');
        parent::addAttribute('posicao');
        parent::addAttribute('avaliacao_ranking_id');
        parent::addAttribute('aluno_id');
    }

    
    /**
     * Method set_avaliacao_ranking
     * Sample of usage: $avaliacao_rankingaluno->avaliacao_ranking = $object;
     * @param $object Instance of avaliacao_ranking
     */
    public function set_avaliacao_ranking(avaliacao_ranking $object)
    {
        $this->avaliacao_ranking = $object;
        $this->avaliacao_ranking_id = $object->id;
    }
    
    /**
     * Method get_avaliacao_ranking
     * Sample of usage: $avaliacao_rankingaluno->avaliacao_ranking->attribute;
     * @returns avaliacao_ranking instance
     */
    public function get_avaliacao_ranking()
    {
        // loads the associated object
        if (empty($this->avaliacao_ranking))
            $this->avaliacao_ranking = new avaliacao_ranking($this->avaliacao_ranking_id);
    
        // returns the associated object
        return $this->avaliacao_ranking;
    }
    
    
    /**
     * Method set_aluno
     * Sample of usage: $avaliacao_rankingaluno->aluno = $object;
     * @param $object Instance of aluno
     */
    public function set_aluno(aluno $object)
    {
        $this->aluno = $object;
        $this->aluno_id = $object->id;
    }
    
    /**
     * Method get_aluno
     * Sample of usage: $avaliacao_rankingaluno->aluno->attribute;
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
