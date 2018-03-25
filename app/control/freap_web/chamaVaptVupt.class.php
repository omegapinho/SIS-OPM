<?php
class chamaVaptVupt
{
    function __construct()
    {

    }
    function show ()
    {
        $dados = array();
        $dados['opm']   = 28;
        $dados['nome']  = 'VAPT-VUPT';
        TSession::setValue('unidade_FREP',$dados);
        TApplication::loadPage('contrato_WebForm');
    }
}
