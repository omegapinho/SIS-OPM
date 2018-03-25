<?php
/**
 * ProntuarioOpm Active Record
 * @author  <your-name-here>
 */
class prontuario_opm extends TRecord
{
    const TABLENAME = 'opmv.prontuario_opm';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('status');
        parent::addAttribute('oculto');
        parent::addAttribute('dataativacao');
        parent::addAttribute('datainativacao');
        parent::addAttribute('endereco');
        parent::addAttribute('bairro');
        parent::addAttribute('cidade');
        parent::addAttribute('telefone');
        parent::addAttribute('email');
        parent::addAttribute('doc_ativacao');
    }


}
