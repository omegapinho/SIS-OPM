<?php
/**
 * servidor_tag Active Record
 * @author  Fernando de Pinho Araújo
 */
class servidor_novo_escolaridade extends TRecord
{
    const TABLENAME = 'efetivo.servidor_novo_escolaridade';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    private $escolaridade;
    private $servidor_novo;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('servidor_novo_id');
        parent::addAttribute('escolaridade_id');
        parent::addAttribute('graduacao');
    }//Fim Módulo
}
