<?php
class chamaBPMRV
{
    function __construct()
    {

    }
    
    function show ()
    {
        $dados = array();
        $dados['opm']   = 27;
        $dados['nome']  = 'BPMRV';
        TSession::setValue('unidade_FREP',$dados);
        TApplication::loadPage('contrato_WebForm');
    }
}
