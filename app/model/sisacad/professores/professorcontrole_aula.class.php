<?php
/**
 * professorcontrole_aula Active Record
 * @author  <your-name-here>
 */
class professorcontrole_aula extends TRecord
{
    const TABLENAME = 'sisacad.professorcontrole_aula';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    private $professor;
    private $controleaula;
    private $nivel_pagamento;
    private $titularidade;
    private $controle_geracao;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('professor_id');
        parent::addAttribute('controle_aula_id');
        parent::addAttribute('aulas_saldo');
        parent::addAttribute('aulas_pagas');
        parent::addAttribute('data_aula');
        parent::addAttribute('valor_aula');
        parent::addAttribute('titularidade_id');
        parent::addAttribute('nivel_pagamento_id');
        parent::addAttribute('data_pagamento');
        parent::addAttribute('data_quitacao');
        parent::addAttribute('validado');
        parent::addAttribute('validador');
        parent::addAttribute('dt_validado');//2018-02-08
        parent::addAttribute('controle_geracao_id');
        parent::addAttribute('historico_pagamento');

    }
    public function get_professor()
    {
        //$criteria = new TCriteria;
        //$criteria->add(new TFilter('controle_aula_id', '=', $this->id));
        //return materia::getObjects( $criteria );
        // loads the associated object
        if (empty($this->professor))
            $this->professor = new professor($this->professor_id);
    
        // returns the associated object
        return $this->professor;
    }
    public function get_controleaula()
    {
        //$criteria = new TCriteria;
        //$criteria->add(new TFilter('controle_aula_id', '=', $this->id));
        //return materia::getObjects( $criteria );
        // loads the associated object
        if (empty($this->controleaula))
            $this->controleaula = new controle_aula($this->controle_aula_id);
    
        // returns the associated object
        return $this->controleaula;
    }
    public function get_nivel_pagamento()
    {
        //$criteria = new TCriteria;
        //$criteria->add(new TFilter('controle_aula_id', '=', $this->id));
        //return materia::getObjects( $criteria );
        // loads the associated object
        if (empty($this->nivel_pagamento))
            $this->nivel_pagamento = new nivel_pagamento($this->nivel_pagamento_id);
    
        // returns the associated object
        return $this->nivel_pagamento;
    }
    public function get_titularidade()
    {
        //$criteria = new TCriteria;
        //$criteria->add(new TFilter('controle_aula_id', '=', $this->id));
        //return materia::getObjects( $criteria );
        // loads the associated object
        if (empty($this->titularidade))
            $this->titularidade = new titularidade($this->titularidade_id);
    
        // returns the associated object
        return $this->titularidade;
    }
    public function get_controle_geracao()
    {
        //$criteria = new TCriteria;
        //$criteria->add(new TFilter('controle_aula_id', '=', $this->id));
        //return materia::getObjects( $criteria );
        // loads the associated object
        if (empty($this->controle_geracao))
            $this->controle_geracao = new controle_geracao($this->controle_geracao_id);
    
        // returns the associated object
        return $this->controle_geracao;
    }

}
