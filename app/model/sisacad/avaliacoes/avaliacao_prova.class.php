<?php
/**
 * avaliacao_prova Active Record
 * @author  <your-name-here>
 */
class avaliacao_prova extends TRecord
{
    const TABLENAME = 'sisacad.avaliacao_prova';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $avaliacao_alunos;
    private $avaliacao_turma;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('avaliacao_turma_id');
        parent::addAttribute('dt_aplicacao');
        parent::addAttribute('tipo_prova');
        parent::addAttribute('usuario_liberador');
        parent::addAttribute('data_liberacao');
        parent::addAttribute('oculto');
        parent::addAttribute('status'); //2018-01-04 (AG = aguardando, AP = Aplicando (tempo do professor lançar notas),
                                        //PE = Pendências (ausências a justificar), CO = Conclusa 
    }

    
    /**
     * Method addavaliacao_aluno
     * Add a avaliacao_aluno to the avaliacao_prova
     * @param $object Instance of avaliacao_aluno
     */
    public function addavaliacao_aluno(avaliacao_aluno $object)
    {
        $this->avaliacao_alunos[] = $object;
    }
    
    /**
     * Method getavaliacao_alunos
     * Return the avaliacao_prova' avaliacao_aluno's
     * @return Collection of avaliacao_aluno
     */
    public function getavaliacao_alunos()
    {
        if (empty($this->avaliacao_alunos))
        {
            $criteria = new TCriteria;
            $criteria->add(new TFilter('avaliacao_prova_id', '=', $this->id));
            // default order
            $param = array('order'=>'id','direction'=>'asc');
            $criteria->setProperties($param); // order, offset
            $this->avaliacao_alunos = avaliacao_aluno::getObjects( $criteria );
        }
        return $this->avaliacao_alunos;
    }
    /**
     * Method get_avaliacao_turma
     * Sample of usage: $avaliacao_curso->avaliacao_turma->attribute;
     * @returns materias_previstas instance
     */
    public function get_avaliacao_turma()
    {
        // loads the associated object
        if (empty($this->avaliacao_turma))
            $this->avaliacao_turma = new avaliacao_turma($this->avaliacao_turma_id);
    
        // returns the associated object
        return $this->avaliacao_turma;
    }
}
