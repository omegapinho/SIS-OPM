<?php
/**
 * avaliacao_resultadoaluno Active Record
 * @author  <your-name-here>
 */
class avaliacao_resultadoaluno extends TRecord
{
    const TABLENAME = 'sisacad.avaliacao_resultadoaluno';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $aluno;
    private $avaliacao_resultado;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nota');
        parent::addAttribute('tipo_avaliacao');
        parent::addAttribute('aluno_id');
        parent::addAttribute('avaliacao_resultado_id');
        parent::addAttribute('recuperado');//2018-02-18
    }

    
    /**
     * Method set_aluno
     * Sample of usage: $avaliacao_resultadoaluno->aluno = $object;
     * @param $object Instance of aluno
     */
    public function set_aluno(aluno $object)
    {
        $this->aluno = $object;
        $this->aluno_id = $object->id;
    }
    
    /**
     * Method get_aluno
     * Sample of usage: $avaliacao_resultadoaluno->aluno->attribute;
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
     * Method get_avaliacao_resultado
     * Sample of usage: $avaliacao_resultadoaluno->avaliacao_resultado->attribute;
     * @returns avaliacao_resultado instance
     */
    public function get_avaliacao_resultado()
    {
        // loads the associated object
        if (empty($this->avaliacao_resultado))
            $this->avaliacao_resultado = new avaliacao_resultado($this->avaliacao_resultado_id);
    
        // returns the associated object
        return $this->avaliacao_resultado;
    }
    


}
