<?php
/**
 * avaliacao_curso Active Record
 * @author  <your-name-here>
 */
class avaliacao_curso extends TRecord
{
    const TABLENAME = 'sisacad.avaliacao_curso';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $materias_previstas;
    private $curso;
    private $avaliacao_turmas;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('dt_inicio');
        parent::addAttribute('oculto');
        parent::addAttribute('tipo_avaliacao');
        parent::addAttribute('motivo');
        parent::addAttribute('ch_minima');
        parent::addAttribute('usuario_liberador');
        parent::addAttribute('data_liberacao');
        parent::addAttribute('materias_previstas_id');
        parent::addAttribute('curso_id');
        parent::addAttribute('media_minima');//2018-02-14
    }

    
    /**
     * Method set_materias_previstas
     * Sample of usage: $avaliacao_curso->materias_previstas = $object;
     * @param $object Instance of materias_previstas
     */
    public function set_materias_previstas(materias_previstas $object)
    {
        $this->materias_previstas = $object;
        $this->materias_previstas_id = $object->id;
    }
    
    /**
     * Method get_materias_previstas
     * Sample of usage: $avaliacao_curso->materias_previstas->attribute;
     * @returns materias_previstas instance
     */
    public function get_materias_previstas()
    {
        // loads the associated object
        if (empty($this->materias_previstas))
            $this->materias_previstas = new materias_previstas($this->materias_previstas_id);
    
        // returns the associated object
        return $this->materias_previstas;
    }
    
    
    /**
     * Method addavaliacao_turma
     * Add a avaliacao_turma to the avaliacao_curso
     * @param $object Instance of avaliacao_turma
     */
    public function addavaliacao_turma(avaliacao_turma $object)
    {
        $this->avaliacao_turmas[] = $object;
    }
    
    /**
     * Method getavaliacao_turmas
     * Return the avaliacao_curso' avaliacao_turma's
     * @return Collection of avaliacao_turma
     */
    public function getavaliacao_turmas()
    {
        if (empty($this->avaliacao_turmas))
        {
            $this->avaliacao_turmas = avaliacao_turma::where('avaliacao_curso_id','=',$this->id)->load();
        }
        return $this->avaliacao_turmas;
    }
    /**
     * Method get_curso
     * Sample of usage: $avaliacao_curso->curso->attribute;
     * @returns materias_previstas instance
     */
    public function get_curso()
    {
        // loads the associated object
        if (empty($this->curso))
            $this->curso = new curso($this->curso_id);
    
        // returns the associated object
        return $this->curso;
    }
}
