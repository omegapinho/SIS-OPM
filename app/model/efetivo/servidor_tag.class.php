<?php
/**
 * servidor_tag Active Record
 * @author  Fernando de Pinho Araújo
 */
class servidor_tag extends TRecord
{
    const TABLENAME = 'efetivo.servidor';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    private $dependente;
    private $endereco;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('unidadeid');
        parent::addAttribute('unidade');
        parent::addAttribute('nome');
        parent::addAttribute('nomeguerra');
        parent::addAttribute('postograd');
        parent::addAttribute('quadro');
        parent::addAttribute('lotacao');
        parent::addAttribute('funcao');
        parent::addAttribute('status');
        parent::addAttribute('situacao');
        parent::addAttribute('rgmilitar');
        parent::addAttribute('cpf');
        parent::addAttribute('email');
        parent::addAttribute('sexo');
        parent::addAttribute('siglaunidade');
        parent::addAttribute('dtnascimento');
    }//Fim Módulo
}
