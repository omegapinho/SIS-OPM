<?php
/**
 * avaliacao_ranking Active Record
 * @author  <your-name-here>
 */
class avaliacao_ranking extends TRecord
{
    const TABLENAME = 'sisacad.avaliacao_ranking';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $turma;

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
        parent::addAttribute('turma_id');
    }

    
    /**
     * Method set_turma
     * Sample of usage: $avaliacao_ranking->turma = $object;
     * @param $object Instance of turma
     */
    public function set_turma(turma $object)
    {
        $this->turma = $object;
        $this->turma_id = $object->id;
    }
    
    /**
     * Method get_turma
     * Sample of usage: $avaliacao_ranking->turma->attribute;
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
    

    
    /**
     * Method getavaliacao_rankingalunos
     */
    public function getavaliacao_rankingalunos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('avaliacao_ranking_id', '=', $this->id));
        return avaliacao_rankingaluno::getObjects( $criteria );
    }
    


}
