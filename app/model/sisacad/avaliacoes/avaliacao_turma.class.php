<?php
/**
 * avaliacao_turma Active Record
 * @author  <your-name-here>
 */
class avaliacao_turma extends TRecord
{
    const TABLENAME = 'sisacad.avaliacao_turma';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $materia;
    private $avaliacao_curso;
    private $turma;
    private $avaliacao_provas;
    private $avaliacao_resultado;
    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('oculto');
        parent::addAttribute('materia_id');
        parent::addAttribute('avaliacao_curso_id');
        parent::addAttribute('turma_id');
    }

    
    /**
     * Method set_materia
     * Sample of usage: $avaliacao_turma->materia = $object;
     * @param $object Instance of materia
     */
    public function set_materia(materia $object)
    {
        $this->materia = $object;
        $this->materia_id = $object->id;
    }
    
    /**
     * Method get_materia
     * Sample of usage: $avaliacao_turma->materia->attribute;
     * @returns materia instance
     */
    public function get_materia()
    {
        // loads the associated object
        if (empty($this->materia))
            $this->materia = new materia($this->materia_id);
    
        // returns the associated object
        return $this->materia;
    }
    
    
    /**
     * Method addavaliacao_prova
     * Add a avaliacao_prova to the avaliacao_turma
     * @param $object Instance of avaliacao_prova
     */
    public function addavaliacao_prova(avaliacao_prova $object)
    {
        $this->avaliacao_provas[] = $object;
    }
    
    /**
     * Method getavaliacao_provas
     * Return the avaliacao_turma' avaliacao_prova's
     * @return Collection of avaliacao_prova
     */
    public function getavaliacao_provas()
    {
        if (empty($this->avaliacao_provas))
        {
            $this->avaliacao_provas = avaliacao_prova::where('avaliacao_turma_id','=',$this->id)->load();
        }
        return $this->avaliacao_provas; 
    }
    /**
     * Method get_avaliacao_resultado
     * Return avaliacao_resultado 
     * @return Collection of avaliacao_resultado
     */
    public function get_avaliacao_resultado()
    {
        if (empty($this->avaliacao_resultado))
        {
            $criteria = new TCriteria;
            $criteria->add(new TFilter('avaliacao_turma_id', '=', $this->id));
            $this->avaliacao_resultado = avaliacao_resultado::getObjects( $criteria );
            if (!empty($this->avaliacao_resultado))
            {
                $this->avaliacao_resultado = $this->avaliacao_resultado[0];//Só existe um avaliacao resultado
            }
        }
        return $this->avaliacao_resultado;
    }
    /**
     * Method get_curso
     * Sample of usage: $avaliacao_curso->curso->attribute;
     * @returns materias_previstas instance
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
     * Method get_avaliacao_curso
     * Sample of usage: $avaliacao_turma->avaliacao_curso->attribute;
     * @returns avaliacao_curso instance
     */
    public function get_avaliacao_curso()
    {
        // loads the associated object
        if (empty($this->avaliacao_curso))
            $this->avaliacao_curso = new avaliacao_curso($this->avaliacao_curso_id);
    
        // returns the associated object
        return $this->avaliacao_curso;
    }
}
