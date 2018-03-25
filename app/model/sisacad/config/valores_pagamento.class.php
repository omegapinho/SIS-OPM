<?php
/**
 * valores_pagamento Active Record
 * @author  <your-name-here>
 */
class valores_pagamento extends TRecord
{
    const TABLENAME = 'sisacad.valores_pagamento';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $titularidade;
    private $nivel_pagamento;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('valor');
        parent::addAttribute('data_inicio');
        parent::addAttribute('data_fim');
        parent::addAttribute('natureza');
        parent::addAttribute('titularidade_id');
        parent::addAttribute('nivel_pagamento_id');
    }

    
    /**
     * Method set_titularidade
     * Sample of usage: $valores_pagamento->titularidade = $object;
     * @param $object Instance of titularidade
     */
    public function set_titularidade(titularidade $object)
    {
        $this->titularidade = $object;
        $this->titularidade_id = $object->id;
    }
    
    /**
     * Method get_titularidade
     * Sample of usage: $valores_pagamento->titularidade->attribute;
     * @returns titularidade instance
     */
    public function get_titularidade()
    {
        // loads the associated object
        if (empty($this->titularidade))
            $this->titularidade = new titularidade($this->titularidade_id);
    
        // returns the associated object
        return $this->titularidade;
    }
    
    /**
     * Method get_titularidade
     * Sample of usage: $valores_pagamento->titularidade->attribute;
     * @returns titularidade instance
     */
    public function get_nivel_pagamento()
    {
        // loads the associated object
        if (empty($this->nivel_pagamento))
            $this->nivel_pagamento = new nivel_pagamento($this->nivel_pagamento_id);
    
        // returns the associated object
        return $this->nivel_pagamento;
    }
    


}
