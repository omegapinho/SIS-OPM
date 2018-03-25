<?php
/**
 * Unidades Active Record - Gerencia unidades externas ou virtuais
 * @author  Fernando de Pinho Araújo
 */
class unidades_ext extends TRecord
{
    const TABLENAME = 'opmv.unidades';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    private $servicos;
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('idSuperior');
        parent::addAttribute('sigla');
        parent::addAttribute('tipo_estrutura');
        parent::addAttribute('logradouro');
        parent::addAttribute('complemento');
        parent::addAttribute('bairro');
        parent::addAttribute('cidade');
        parent::addAttribute('uf');
        parent::addAttribute('ativo');
    }
}
