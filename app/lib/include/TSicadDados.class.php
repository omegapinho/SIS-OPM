<?php
/**
 * TSicadDados - Funções de extração de dados via WS SICAD
 * Copyright (c) 
 * @author  Fernando de Pinho Araújo 
 * @version 1.0, 2016-12-27
 */
class TSicadDados 
{
    //protected $elements;
    const sicad_cidades = 'https://legadows-h.ssp.go.gov.br/cidadesPorEstado/';
    const sicad_estados = 'https://legadows-h.ssp.go.gov.br/estados/';
    //const sicad_dadopm  = 'https://sicadws-h.ssp.go.gov.br/dadosServidorPorCpfOuRgMilitar/';
    const sicad_dadopm  = 'https://sicadws-h.ssp.go.gov.br/dadosServidorPmPorCpfOuRgMilitar/';
    const sicad_dadoopm = 'https://legadows-h.ssp.go.gov.br/servidorPorUnidade/';
    const sicad_funcoes = 'https://legadows-h.ssp.go.gov.br/funcoesAtivasPorCpfRg/';
    const sicad_email   = 'https://legadows-h.ssp.go.gov.br/ha_implementar/';
    const site_sso      = "https://ssows-h.ssp.go.gov.br/auth?response_type=token_only";
    const logoff_sso    = "https://ssows-h.ssp.go.gov.br/logout/?response_type=token";
    const validade      = "https://ssows-h.ssp.go.gov.br/validate?token=";

    /**
     * Class Constructor
     */
    public function __construct()
    {
        //nada
        set_time_limit (360);
    }
/*-------------------------------------------------------------------------------
 *                        Busca as Cidades  de uma UF
 *------------------------------------------------------------------------------- */
    public function cidades ($param)
    {
        $uf = ($param) ? $param : "GO";   
        $uf = ($uf==null || $uf=="" || (strlen($uf)<2)) ? "GO" : $uf;
        try
        {
            $url = $this->get_Prd_Hom(self::sicad_cidades,TSession::getValue('ambiente'));
            $items = $this->object_to_array(json_decode (self::my_file_get_contents($url.$uf)));
            if (!$items)
            {
                throw new Exception ('Envio de Municipios Falhou.');
            }
            $lista = $this->object_to_array($items);
            return $lista;
        }
        catch (Exception $e)
        {
            //new TMessage('error', $e->getMessage()); // shows the exception error message
            return false;
        }
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *                        Busca UFs
 *------------------------------------------------------------------------------- */
    public function estados ()
    {
        try
        {
            $url = $this->get_Prd_Hom(self::sicad_estados,TSession::getValue('ambiente'));
            $items = $this->object_to_array(json_decode (self::my_file_get_contents($url)));
            //$lista = $this->make_list_states($items);
            if (!$items)
            {
                throw new Exception ('Envio de Estados Falhou.');
            }            
            $lista = $this->object_to_array($items);
            return $lista;
        }
        catch (Exception $e)
        {
            //new TMessage('error', $e->getMessage()); // shows the exception error message
            return false;
        }
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *                        Busca Dados do Militar por RG ou CPF
 *------------------------------------------------------------------------------- */
    public function dados_servidor ($param , $log = false)
    {
        set_time_limit (360);
        $token = TSession::getValue('token');
        if (empty($token) || empty($param))
        {
            return false; 
        }
        $url = $this->get_Prd_Hom(self::sicad_dadopm,TSession::getValue('ambiente')).$param.'?token='.$token;
        if ($log == true)
        {
            TTransaction::log($url);
        }
        try
        {
            $dados = json_decode (file_get_contents($url));
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage().'<br>Problemas ao buscar dados do Servidor.<br>'.$dados); // shows the exception error message
            return false;
        }
        if (empty($dados))
        {
            $lista = false;
        }
        else
        {
            $lista = $this->object_to_array($dados);
        }
        return $lista;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *                        Busca servidores de uma OPM
 *------------------------------------------------------------------------------- */
    function lista_servidores_opm ($param)
    {
        set_time_limit (360);
        try
        {
            $token = TSession::getValue('token');
            $query = $this->get_Prd_Hom(self::sicad_dadoopm,TSession::getValue('ambiente')).$param.'?token='.$token;
            $items = file_get_contents($query);//Executa a busca
            $itesm = (empty($items)) ? false : $items;
            if (!$items)
            {
                throw new Exception ('Envio de Lista de Servidores da OPM Falhou.<br>O Retorno foi Nulo ou vazio.');
            }
            $lista = $this->object_to_array(json_decode ($items));//Converte Json para array
            $lista = (empty($lista)) ? false : $lista;
            //print_r ($lista);
            if ($lista)
            {
                if (is_array($lista))
                {
                    if (array_key_exists('error',$lista))
                    {
                        throw new Exception ('Envio de Lista de Servidores da OPM Falhou.<br>Houve notificação de Erro no Retorno.'.$lista['error']);
                    }   
                }
                else
                {
                    throw new Exception ('Envio de Lista de Servidores da OPM Falhou.<br>Não houve conversão de Array.');
                }
            }
            else
            {
                throw new Exception ('Envio de Lista de Servidores da OPM Falhou.<br>Listagem retornou falso.');
            }
            return $lista;
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage()."<br>Erro ao buscar lista de PMs de OPM.<br>".$query); // shows the exception error message
            return false;
        }
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *                        Busca OPMs da PM
 *------------------------------------------------------------------------------- */
    function lista_opms_pm ()
    {
        $fer = new TFerramentas();
        try
        {
            $token = TSession::getValue('token');
            if ($token)
            {
                $items = self::my_file_get_contents($this->get_Prd_Hom('https://legadows-h.ssp.go.gov.br/estruturaOrganizacionalPorCorporacao/4?token=',TSession::getValue('ambiente')).$token);
            }
            else if ($fer->is_dev())
            {
                $items = $this->opm_sicad_lista();
            }
            if (!$items)
            {
                throw new Exception ('Envio de Lista de OPMs Falhou.');
            }
            $lista = $this->object_to_array(json_decode ($items));
            return $lista;
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage()."<br>Erro ao buscar lista de OPMs."); // shows the exception error message
            return false;
        }
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *                        Busca funções ativas de um PM
 *------------------------------------------------------------------------------- */
    function lista_funcoes_pm ($param)
    {
        try
        {
            $token = TSession::getValue('token');
            if ($token)
            {
                $items = self::my_file_get_contents($this->get_Prd_Hom(self::sicad_funcoes,TSession::getValue('ambiente')).$param.'?token='.$token);
            }
            if (!$items)
            {
                throw new Exception ('Envio de funções do PM Falhou.');
            }
            $lista = $this->object_to_array(json_decode ($items));
            return $lista;
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage()."<br>Falha ao Buscar Funções Ativas de PM."); // shows the exception error message
            return false;
        }
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *                        Transforma objeto em array
 *------------------------------------------------------------------------------- */    
    function object_to_array($data) {
        if (is_array($data) || is_object($data)) 
        {
            $result = array();
            foreach ($data as $key => $value) 
            {
                $result[$key] = $this->object_to_array($value);
            }
            return $result;
        }
        return $data;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *                        Cria lista com Id e Nome de cidades
 *------------------------------------------------------------------------------- */    
    static function make_list_city($data) 
    {
        if (is_array($data) || is_object($data)) {
            $result = array();
            foreach ($data as $key => $value) 
            {
                $result[$value['nome']]= $value['nome'];
            }
            return $result;
        }
        return false;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *                        Cria lista com Id e Nome de estados
 *------------------------------------------------------------------------------- */    
    static function make_list_states($data) 
    {
        if (is_array($data) || is_object($data)) {
            $result = array();
            foreach ($data as $key => $value) 
            {
                $result[$value['sigla']]= $value['sigla'];
            }
            return $result;
        }
        return false;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *                        Retorna dados para teste pessoa
 *------------------------------------------------------------------------------- */    
    static function get_dados($param) 
    {
        $dado = false;
        switch ($param)
        {
            case '30089':
                $dado = '[{"id":"77644620125","nome":"FERNANDO DE PINHO ARAÚJO","identificacao":"30089"}]';
                break;
            case '27169':
                $dado = '[{"id":"00000000000","nome":"EDMILSON LOPES DA SILVA","identificacao":"27169"}]';
                break;
        }
        
        return $dado;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *                        Retorna dados para teste corporação
 *------------------------------------------------------------------------------- */  
     static function opm_sicad_lista()
     {
         /*$data =  '[{"id":65,"nome":"POLÍCIA MILITAR DO ESTADO DE GOIÁS","sigla":"PMGO","idSuperior":null,"superior":null,"corporacao":null,"corporacaoId":null,"level":1,"telefone":null},{"id":17827,"nome":"COMANDO GERAL - CG","sigla":"CG","idSuperior":65,"superior":null,"corporacao":null,"corporacaoId":null,"level":2,"telefone":null},{"id":140,"nome":"01º COMANDO REGIONAL (01º CRPM)*","sigla":"01º CRPM","idSuperior":17827,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":217,"nome":"01º BATALHÃO DE POLÍCIA MILITAR - 01º BPM (01º CRPM)","sigla":"01º BPM (01º CRPM)","idSuperior":140,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":218,"nome":"07º BATALHÃO DE POLÍCIA MILITAR - 07º BPM (01º CRPM)","sigla":"07º BPM (01º CRPM)","idSuperior":140,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":219,"nome":"09º BATALHÃO DE POLÍCIA MILITAR - 09º BPM (01º CRPM)","sigla":"09º BPM (01º CRPM)","idSuperior":140,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":220,"nome":"13º BATALHÃO DE POLÍCIA MILITAR - 13º BPM (01º CRPM)","sigla":"13º BPM (01º CRPM)","idSuperior":140,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":222,"nome":"BATALHÃO DE TRÂNSITO - BPMTRAN (01º CRPM)","sigla":"BPMTRAN (01º CRPM)","idSuperior":140,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":223,"nome":"BATALHÃO ESCOLAR - BPMESC (01º CRPM)","sigla":"BPMESC (01º CRPM)","idSuperior":140,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":227,"nome":"01ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 01ª CIPM (01º CRPM)","sigla":"01ª CIPM (01º CRPM)","idSuperior":140,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":229,"nome":"CENTRO DE OPERAÇÕES - COPOM (01º CRPM)","sigla":"COPOM (01º CRPM)","idSuperior":140,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":25424,"nome":"29ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 29ª CIPM (01º CRPM) ","sigla":"29ª CIPM (01º CRPM) ","idSuperior":140,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":25425,"nome":"28ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 28ª CIPM (01º CRPM)","sigla":"28ª CIPM (01º CRPM)","idSuperior":140,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":32805,"nome":"BATALHÃO DE ROTAM - BPMROTAM (01º CRPM)","sigla":"BPMROTAM (01º CRPM)","idSuperior":140,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":42104,"nome":"27ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 27ª CIPM (01º CRPM)","sigla":"27ª CIPM (01º CRPM)","idSuperior":140,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":43764,"nome":"09ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 09ª CIPM (01º CRPM)","sigla":"09ª CIPM (01º CRPM)","idSuperior":140,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":43765,"nome":"37ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 37ª CIPM (01º CRPM) ","sigla":"37ª CIPM (01º CRPM)","idSuperior":140,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":49846,"nome":"30º BATALHÃO DE POLÍCIA MILITAR - 30º BPM (01º CRPM)","sigla":"30º BPM (01º CRPM)","idSuperior":140,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":50324,"nome":"31º BATALHÃO DE POLÍCIA MILITAR - 31º BPM (01º CRPM) ","sigla":"31º BPM (01º CRPM) ","idSuperior":140,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":86807,"nome":"15ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 15ª CIPM (01º CRPM)","sigla":"15ª CIPM (01º CRPM)","idSuperior":140,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":92992,"nome":"BATALHÃO DE POLÍCIA MILITAR DE EVENTOS - BPMEVE (01º CRPM)","sigla":"BPMEVE (01º CRPM)","idSuperior":140,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":102458,"nome":"08ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - COPOM (01º CRPM) - desativar","sigla":"08ª CIPM COPOM (01º CRPM)","idSuperior":140,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null}';
         $data .= ',{"id":142,"nome":"02º COMANDO REGIONAL (02º CRPM)*","sigla":"02º CRPM","idSuperior":17827,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":2180,"nome":"08º BATALHÃO DE POLÍCIA MILITAR - 08º BPM (02º CRPM) ","sigla":"08º BPM (02º CRPM) ","idSuperior":142,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":2181,"nome":"22º BATALHÃO DE POLÍCIA MILITAR - 22º BPM (02º CRPM)  ","sigla":"22º BPM (02º CRPM)  ","idSuperior":142,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":2183,"nome":"16ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 16ª CIPM (02º CRPM) ","sigla":"16ª CIPM (02º CRPM) ","idSuperior":142,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":44027,"nome":"26ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 26ª CIPM (02º CRPM) ","sigla":"26ª CIPM (02º CRPM)","idSuperior":142,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":47604,"nome":"27º  BATALHÃO DE POLÍCIA MILITAR - 27º BPM (02º CRPM)","sigla":"27º BPM (02º CRPM)","idSuperior":142,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":85132,"nome":"41ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 41ª CIPM (02º CRPM)","sigla":"41ª CIPM (02º CRPM)","idSuperior":142,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":85257,"nome":"43ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 43ª CIPM - CPE (02º CRPM)","sigla":"43ª CIPM (02º CRPM)","idSuperior":142,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":85820,"nome":"46ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 46ª CIPM/COPOM (02º CRPM)","sigla":"46ª CIPM COPOM (02º CRPM)","idSuperior":142,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":86921,"nome":"36º BATALHÃO DA POLÍCIA MILITAR - 36º BPM (02º CRPM)","sigla":"36º BPM (02º CRPM)","idSuperior":142,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":93208,"nome":"39º BATALHÃO DE POLÍCIA MILITAR - 39º BPM (02º CRPM)","sigla":"39º BPM (02º CRPM) ","idSuperior":142,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":145,"nome":"03º COMANDO REGIONAL (03º CRPM)*","sigla":"03º CRPM","idSuperior":17827,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":2628,"nome":"04º BATALHÃO DE POLÍCIA MILITAR - 04º BPM (03º CRPM)","sigla":"04º BPM (03º CRPM)","idSuperior":145,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":2632,"nome":"23ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 23ª CIPM (03º CRPM) ","sigla":"23ª CIPM (03º CRPM)","idSuperior":145,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":47024,"nome":"31ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 31ª CIPM (03º CRPM)","sigla":"31ª CIPM (03º CRPM)","idSuperior":145,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":47644,"nome":"24ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 24ª CIPM (03º CRPM) ","sigla":"24ª CIPM (03º CRPM)","idSuperior":145,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":47664,"nome":"28º BATALHÃO DE POLÍCIA MILITAR - 28º BPM (03º CRPM)","sigla":"28º BPM (03º CRPM)","idSuperior":145,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":86667,"nome":"37º BATALHÃO DE POLÍCIA MILITAR - 37º BPM (03º CRPM)","sigla":"37º BPM (03º CRPM)","idSuperior":145,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":87064,"nome":"48ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 48ª CIPM  (03º CRPM)","sigla":"48ª CIPM (03º CRPM)","idSuperior":145,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":88968,"nome":"47ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 47ª CIPM  (03º CRPM)","sigla":"47ª CIPM (03º CRPM)","idSuperior":145,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":93301,"nome":"38º BATALHÃO DE POLÍCIA MILITAR - 38º BPM (03º CRPM)","sigla":"38º BPM (03º CRPM)","idSuperior":145,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":146,"nome":"04º COMANDO REGIONAL (04º CRPM)*","sigla":"04º CRPM","idSuperior":17827,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":12380,"nome":"06º BATALHÃO DE POLÍCIA MILITAR - 06º BPM (04º CRPM)","sigla":"06º BPM (04º CRPM)","idSuperior":146,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":65519,"nome":"MUSEU DA POLÍCIA MILITAR DO ESTADO DE GOIÁS","sigla":"MUSEU PMGO","idSuperior":146,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":86047,"nome":"44ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 44ª CIPM  (04º CRPM)","sigla":"44ª CIPM (04º CRPM)","idSuperior":146,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":86048,"nome":"45ª CIPM - COMPANHIA DE POLICIAMENTO ESPECIALIZADO - CPE (04º CRPM)","sigla":"45ª CIPM-CPE (04º CRPM)","idSuperior":146,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":87713,"nome":"32º BATALHÃO DE POLÍCIA MILITAR - 32º BPM (04º CRPM)","sigla":"32º BPM (04º CRPM)","idSuperior":146,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":89748,"nome":"34º BATALHÃO DE POLÍCIA MILITAR - 34º BPM (04º CRPM)","sigla":"34º BPM (04º CRPM)","idSuperior":146,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":93099,"nome":"17ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 17ª CIPM (04º CRPM)","sigla":"17ª CIPM (04º CRPM)","idSuperior":146,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":147,"nome":"05º COMANDO REGIONAL (05º CRPM)*","sigla":"05º CRPM","idSuperior":17827,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null}';
         $data .= ',{"id":15821,"nome":"10º  BATALHÃO DE POLÍCIA MILITAR - 10º BPM (05º CRPM)","sigla":"10º BPM (05º CRPM)","idSuperior":147,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":15823,"nome":"19º BATALHÃO DE POLÍCIA MILITAR - 19º BPM (05º CRPM) ","sigla":"19º BPM (05º CRPM) ","idSuperior":147,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":15824,"nome":"20º BATALHÃO DE POLÍCIA MILITAR - 20º BPM (05º CRPM) ","sigla":"20º BPM (05º CRPM) ","idSuperior":147,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":23983,"nome":"02ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 02ª CIPM (05º CRPM) ","sigla":"02ª CIPM (05º CRPM) ","idSuperior":147,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":31887,"nome":"33ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR DE CHOQUE - 33ª CIPM CHOQUE (05º CRPM)","sigla":"33ª CIPM CHOQUE (05º CRPM)","idSuperior":147,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":32104,"nome":"32ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 32ª CIPM (05º CRPM) ","sigla":"32ª CIPM (05º CRPM) ","idSuperior":147,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":89425,"nome":"33º BATALHÃO DE POLÍCIA MILITAR - 33º BPM (05º CRPM)","sigla":"33º BPM (05º CRPM)","idSuperior":147,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":148,"nome":"06º COMANDO REGIONAL (06º CRPM)*","sigla":"06º CRPM","idSuperior":17827,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":16788,"nome":"05º BATALHÃO DE POLÍCIA MILITAR - 05º BPM (06º CRPM) ","sigla":"05º BPM (06º CRPM) ","idSuperior":148,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":16789,"nome":"10ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 10ª CIPM (06º CRPM) ","sigla":"10ª CIPM (06º CRPM) ","idSuperior":148,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":46764,"nome":"26º BATALHÃO DE POLÍCIA MILITAR - 26º BPM (06º CRPM) ","sigla":"26º BPM (06º CRPM) ","idSuperior":148,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":49045,"nome":"29º BATALHÃO DE POLÍCIA MILITAR - 29º BPM (06º CRPM) ","sigla":"29º BPM (06º CRPM) ","idSuperior":148,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":149,"nome":"07º COMANDO REGIONAL (07º CRPM)*","sigla":"07º CRPM","idSuperior":17827,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":17393,"nome":"12º BATALHÃO DE POLÍCIA MILITAR - 12º BPM (07º CRPM) ","sigla":"12º BPM (07º CRPM) ","idSuperior":149,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":22543,"nome":"25º BATALHÃO DE POLÍCIA MILITAR - 25º BPM (07º CRPM) ","sigla":"25º BPM (07º CRPM) ","idSuperior":149,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":33663,"nome":"04ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 04ª CIPM (07º CRPM) ","sigla":"04ª CIPM (07º CRPM)","idSuperior":149,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":48304,"nome":"20ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 20ª CIPM (07º CRPM) ","sigla":"20ª CIPM (07º CRPM) ","idSuperior":149,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":150,"nome":"08º COMANDO REGIONAL (08º CRPM)*","sigla":"08º CRPM","idSuperior":17827,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":17934,"nome":"02º BATALHÃO DE POLÍCIA MILITAR - 02º BPM (08º CRPM) ","sigla":"02º BPM (08º CRPM) ","idSuperior":150,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":17935,"nome":"05ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 05ª CIPM (08º CRPM)","sigla":"05ª CIPM (08º CRPM) ","idSuperior":150,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":17936,"nome":"12ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 12ª CIPM (08º CRPM) ","sigla":"12ª CIPM (08º CRPM)","idSuperior":150,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":17937,"nome":"21ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 21ª CIPM (08º CRPM) ","sigla":"21ª CIPM (08º CRPM) ","idSuperior":150,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":89223,"nome":"19ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 19ª CIPM (08º CRPM)","sigla":"19ª CIPM (08º CRPM) ","idSuperior":150,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":152,"nome":"09º COMANDO REGIONAL (09º CRPM)*","sigla":"09º CRPM","idSuperior":17827,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":18647,"nome":"11º BATALHÃO DE POLÍCIA MILITAR - 11º BPM (09º CRPM) ","sigla":"11º BPM (09º CRPM) ","idSuperior":152,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":18648,"nome":"18º BATALHÃO DE POLÍCIA MILITAR - 18º BPM (09º CRPM) ","sigla":"18º BPM (09º CRPM) ","idSuperior":152,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":84809,"nome":"40ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 40ª CIPM (09º CRPM)","sigla":"40ª CIPM (09º CRPM)","idSuperior":152,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":3493,"nome":"QUARTEL DA AJUDANCIA GERAL - QAG","sigla":"QAG","idSuperior":17827,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":3494,"nome":"ASSISTÊNCIA POLICIAL MILITAR - ASPM","sigla":"ASPM","idSuperior":17827,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":45424,"nome":"ASSISTÊNCIA POLICIAL MILITAR DO DETRAN - ASPM DETRAN","sigla":"ASPM DETRAN","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":46004,"nome":"ASSISTÊNCIA POLICIAL MILITAR DA CÂMARA MUNICIPAL DE GOIÂNIA - ASPM CMG","sigla":"ASPM CMG","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":46011,"nome":"ASSISTÊNCIA POLICIAL MILITAR DA ASSEMBLÉIA LEGISLATIVA - ASPM AL","sigla":"ASPM AL","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":46016,"nome":"ASSISTÊNCIA POLICIAL MILITAR DO MINISTÉRIO PÚBLICO - ASPM MP","sigla":"ASPM MP","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":46020,"nome":"ASSISTÊNCIA POLICIAL MILITAR DA OUVIDORIA GERAL DO ESTADO - ASPM OGE","sigla":"ASPM OGE","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":46027,"nome":"ASSISTÊNCIA POLICIAL MILITAR DA AGÊNCIA GOIANA DO SIST.DE EXEC.PENAL-ASPM AGSEP","sigla":"ASPM AGSEP","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":46032,"nome":"ASSISTÊNCIA POLICIAL MILITAR DA DELEGACIA GERAL DE POLÍCIA CIVIL - ASPM DGPC","sigla":"ASPM DGPC","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":46044,"nome":"ASSISTÊNCIA POLICIAL MILITAR DA PREFEITURA DE GOIÂNIA - ASPM PREF GOIANIA","sigla":"ASPM PREF GOIANIA","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":46047,"nome":"ASSISTÊNCIA POLICIAL MILITAR DA PROCURADORIA GERAL DE JUSTIÇA - ASPM PGJ","sigla":"ASPM PGJ","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":46050,"nome":"ASSISTÊNCIA POLICIAL MILITAR DA SECRETARIA DE SEGURANÇA PÚBLICA - ASPM SSP","sigla":"ASPM SSP","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":46052,"nome":"ASSISTÊNCIA POLICIAL MILITAR DO TRIBUNAL DE CONTAS DO ESTADO - ASPM TCE","sigla":"ASPM TCE","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":46054,"nome":"ASSISTÊNCIA POLICIAL MILITAR DO TRIBUNAL DE JUSTIÇA - ASPM TJ","sigla":"ASPM TJ","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":63018,"nome":"ASSISTÊNCIA POLICIAL MILITAR DO TRIBUNAL REGIONAL ELEITORAL DE GOIÁS-ASPM TRE-GO","sigla":"ASPM TRE-GO","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":65485,"nome":"ASSISTÊNCIA POLICIAL MILITAR DA PROCURADORIA GERAL DO ESTADO - ASPM PGE","sigla":"ASPM PGE","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null}';
         $data .= ',{"id":15821,"nome":"10º  BATALHÃO DE POLÍCIA MILITAR - 10º BPM (05º CRPM)","sigla":"10º BPM (05º CRPM)","idSuperior":147,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":15823,"nome":"19º BATALHÃO DE POLÍCIA MILITAR - 19º BPM (05º CRPM) ","sigla":"19º BPM (05º CRPM) ","idSuperior":147,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":15824,"nome":"20º BATALHÃO DE POLÍCIA MILITAR - 20º BPM (05º CRPM) ","sigla":"20º BPM (05º CRPM) ","idSuperior":147,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":23983,"nome":"02ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 02ª CIPM (05º CRPM) ","sigla":"02ª CIPM (05º CRPM) ","idSuperior":147,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":31887,"nome":"33ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR DE CHOQUE - 33ª CIPM CHOQUE (05º CRPM)","sigla":"33ª CIPM CHOQUE (05º CRPM)","idSuperior":147,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":32104,"nome":"32ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 32ª CIPM (05º CRPM) ","sigla":"32ª CIPM (05º CRPM) ","idSuperior":147,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":89425,"nome":"33º BATALHÃO DE POLÍCIA MILITAR - 33º BPM (05º CRPM)","sigla":"33º BPM (05º CRPM)","idSuperior":147,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":148,"nome":"06º COMANDO REGIONAL (06º CRPM)*","sigla":"06º CRPM","idSuperior":17827,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":16788,"nome":"05º BATALHÃO DE POLÍCIA MILITAR - 05º BPM (06º CRPM) ","sigla":"05º BPM (06º CRPM) ","idSuperior":148,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":16789,"nome":"10ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 10ª CIPM (06º CRPM) ","sigla":"10ª CIPM (06º CRPM) ","idSuperior":148,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":46764,"nome":"26º BATALHÃO DE POLÍCIA MILITAR - 26º BPM (06º CRPM) ","sigla":"26º BPM (06º CRPM) ","idSuperior":148,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":49045,"nome":"29º BATALHÃO DE POLÍCIA MILITAR - 29º BPM (06º CRPM) ","sigla":"29º BPM (06º CRPM) ","idSuperior":148,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":149,"nome":"07º COMANDO REGIONAL (07º CRPM)*","sigla":"07º CRPM","idSuperior":17827,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":17393,"nome":"12º BATALHÃO DE POLÍCIA MILITAR - 12º BPM (07º CRPM) ","sigla":"12º BPM (07º CRPM) ","idSuperior":149,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":22543,"nome":"25º BATALHÃO DE POLÍCIA MILITAR - 25º BPM (07º CRPM) ","sigla":"25º BPM (07º CRPM) ","idSuperior":149,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":33663,"nome":"04ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 04ª CIPM (07º CRPM) ","sigla":"04ª CIPM (07º CRPM)","idSuperior":149,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":48304,"nome":"20ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 20ª CIPM (07º CRPM) ","sigla":"20ª CIPM (07º CRPM) ","idSuperior":149,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":150,"nome":"08º COMANDO REGIONAL (08º CRPM)*","sigla":"08º CRPM","idSuperior":17827,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":17934,"nome":"02º BATALHÃO DE POLÍCIA MILITAR - 02º BPM (08º CRPM) ","sigla":"02º BPM (08º CRPM) ","idSuperior":150,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":17935,"nome":"05ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 05ª CIPM (08º CRPM)","sigla":"05ª CIPM (08º CRPM) ","idSuperior":150,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":17936,"nome":"12ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 12ª CIPM (08º CRPM) ","sigla":"12ª CIPM (08º CRPM)","idSuperior":150,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":17937,"nome":"21ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 21ª CIPM (08º CRPM) ","sigla":"21ª CIPM (08º CRPM) ","idSuperior":150,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":89223,"nome":"19ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 19ª CIPM (08º CRPM)","sigla":"19ª CIPM (08º CRPM) ","idSuperior":150,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":152,"nome":"09º COMANDO REGIONAL (09º CRPM)*","sigla":"09º CRPM","idSuperior":17827,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":18647,"nome":"11º BATALHÃO DE POLÍCIA MILITAR - 11º BPM (09º CRPM) ","sigla":"11º BPM (09º CRPM) ","idSuperior":152,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":18648,"nome":"18º BATALHÃO DE POLÍCIA MILITAR - 18º BPM (09º CRPM) ","sigla":"18º BPM (09º CRPM) ","idSuperior":152,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":84809,"nome":"40ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 40ª CIPM (09º CRPM)","sigla":"40ª CIPM (09º CRPM)","idSuperior":152,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":3493,"nome":"QUARTEL DA AJUDANCIA GERAL - QAG","sigla":"QAG","idSuperior":17827,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":3494,"nome":"ASSISTÊNCIA POLICIAL MILITAR - ASPM","sigla":"ASPM","idSuperior":17827,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":45424,"nome":"ASSISTÊNCIA POLICIAL MILITAR DO DETRAN - ASPM DETRAN","sigla":"ASPM DETRAN","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":46004,"nome":"ASSISTÊNCIA POLICIAL MILITAR DA CÂMARA MUNICIPAL DE GOIÂNIA - ASPM CMG","sigla":"ASPM CMG","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":46011,"nome":"ASSISTÊNCIA POLICIAL MILITAR DA ASSEMBLÉIA LEGISLATIVA - ASPM AL","sigla":"ASPM AL","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":46016,"nome":"ASSISTÊNCIA POLICIAL MILITAR DO MINISTÉRIO PÚBLICO - ASPM MP","sigla":"ASPM MP","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":46020,"nome":"ASSISTÊNCIA POLICIAL MILITAR DA OUVIDORIA GERAL DO ESTADO - ASPM OGE","sigla":"ASPM OGE","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":46027,"nome":"ASSISTÊNCIA POLICIAL MILITAR DA AGÊNCIA GOIANA DO SIST.DE EXEC.PENAL-ASPM AGSEP","sigla":"ASPM AGSEP","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":46032,"nome":"ASSISTÊNCIA POLICIAL MILITAR DA DELEGACIA GERAL DE POLÍCIA CIVIL - ASPM DGPC","sigla":"ASPM DGPC","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":46044,"nome":"ASSISTÊNCIA POLICIAL MILITAR DA PREFEITURA DE GOIÂNIA - ASPM PREF GOIANIA","sigla":"ASPM PREF GOIANIA","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":46047,"nome":"ASSISTÊNCIA POLICIAL MILITAR DA PROCURADORIA GERAL DE JUSTIÇA - ASPM PGJ","sigla":"ASPM PGJ","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":46050,"nome":"ASSISTÊNCIA POLICIAL MILITAR DA SECRETARIA DE SEGURANÇA PÚBLICA - ASPM SSP","sigla":"ASPM SSP","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":46052,"nome":"ASSISTÊNCIA POLICIAL MILITAR DO TRIBUNAL DE CONTAS DO ESTADO - ASPM TCE","sigla":"ASPM TCE","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":46054,"nome":"ASSISTÊNCIA POLICIAL MILITAR DO TRIBUNAL DE JUSTIÇA - ASPM TJ","sigla":"ASPM TJ","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":63018,"nome":"ASSISTÊNCIA POLICIAL MILITAR DO TRIBUNAL REGIONAL ELEITORAL DE GOIÁS-ASPM TRE-GO","sigla":"ASPM TRE-GO","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":65485,"nome":"ASSISTÊNCIA POLICIAL MILITAR DA PROCURADORIA GERAL DO ESTADO - ASPM PGE","sigla":"ASPM PGE","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null}';
         $data .= ',{"id":78734,"nome":"ASSISTÊNCIA PM DA SUPERINTENDÊNCIA EXECUTIVA DE ADMINISTRACÃO PENITENCIÁRIA-SEAP","sigla":"ASPM SEAP","idSuperior":3494,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":25509,"nome":"01ª BASE ADMINISTRATIVA DA POLICIA MILITAR (01ª BAPM)","sigla":"01ª BAPM ","idSuperior":17827,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":29023,"nome":"OUTROS ÓRGÃOS ","sigla":null,"idSuperior":65,"superior":null,"corporacao":null,"corporacaoId":null,"level":2,"telefone":null},{"id":33603,"nome":"CORREGEDORIA GERAL - COR (SSPJ)","sigla":"COR (SSPJ)","idSuperior":29023,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":34824,"nome":"GERÊNCIA INTELIGÊNCIA DA SECRETARIA DE SEG. PUBLICA ADM. PENIT. /SSPAP-INATIVAR","sigla":"GERÊNCIA DE INTELIGÊNCIA/SSPAP","idSuperior":29023,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":41506,"nome":"SUPERINTENDÊNCIA DA ACADEMIA ESTADUAL DE SEGURANÇA PÚBLICA - SAESP (SSPJ)","sigla":"SAESP (SSPJ)","idSuperior":29023,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":42584,"nome":"ASSESSORIA DE INFORMÁTICA DA POLÍCIA MILITAR - GSOP (SSP)","sigla":"GSOP (SSP)","idSuperior":29023,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":65716,"nome":"DEPARTAMENTO DE TRÂNSITO","sigla":"DETRAN","idSuperior":29023,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":104838,"nome":"SECRETARIA DE ESTADO DA CASA MILITAR","sigla":"SECAMI","idSuperior":29023,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":34624,"nome":"GABINETE DO COMANDO GERAL","sigla":"GCG","idSuperior":65,"superior":null,"corporacao":null,"corporacaoId":null,"level":2,"telefone":null},{"id":48584,"nome":"ÓRGÃOS DE APOIO","sigla":"APOIO","idSuperior":65,"superior":null,"corporacao":null,"corporacaoId":null,"level":2,"telefone":null},{"id":48586,"nome":"COMANDO DA ACADEMIA DE POLÍCIA MILITAR","sigla":"CAPM","idSuperior":48584,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":48587,"nome":"COMANDO DE SAÚDE - CS","sigla":"CS","idSuperior":48584,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":48591,"nome":"COMANDO DE CORREIÇÕES E DISCIPLINA DA POLÍCIA MILITAR","sigla":"CCDPM","idSuperior":48584,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":48778,"nome":"PRESÍDIO MILITAR - PRESMIL (CCDPM) ","sigla":"PRESMIL (CCDPM) ","idSuperior":48591,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":48806,"nome":"01ª DIVISÃO DE POLÍCIA JUDICIÁRIA MILITAR - 01ª DPJM (CCDPM) ","sigla":"01ª DPJM (CCDPM) ","idSuperior":48591,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":48824,"nome":"02ª DIVISÃO DE POLÍCIA JUDICIÁRIA MILITAR - 02ª DPJM (CCDPM)","sigla":"02ª DPJM (CCDPM)","idSuperior":48591,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":48842,"nome":"03ª DIVISÃO DE POLÍCIA JUDICIÁRIA MILITAR - 03ª DPJM (CCDPM)","sigla":"03ª DPJM (CCDPM)","idSuperior":48591,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":85807,"nome":"DIVISÃO DE PREVENÇÃO E QUALIDADE - DPQ","sigla":"DQP","idSuperior":48591,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":89163,"nome":"DIVISÃO DE INVESTIGAÇÃO ESPECIAL - DIE","sigla":"DIE","idSuperior":48591,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":48605,"nome":"COMANDO DE ENSINO POLICIAL MILITAR","sigla":"CEPM","idSuperior":48584,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":51658,"nome":"CENTRO DE INSTRUÇÃO  E TIRO DA POLÍCIA MILITAR - CITPM (CEPM)","sigla":"CITPM (CEPM)","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":51709,"nome":"COLÉGIO MILITAR HUGO DE CARVALHO RAMOS - CPMG HCR (CEPM)","sigla":"CPMG HCR (CEPM)","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":51747,"nome":"COLÉGIO MILITAR POLIVALENTE MODELO VASCOS DOS REIS - CPMG PMVR (CEPM)","sigla":"CPMG PMVR (CEPM)","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":51785,"nome":"COLÉGIO MILITAR DOUTOR CÉSAR TOLEDO - CPMG DCT - ANÁPOLIS (CEPM) ","sigla":"CPMG DCT - ANÁPOLIS (CEPM) ","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":51847,"nome":"COLÉGIO MILITAR AYRTON SENNA - CPMG AS (CEPM)","sigla":"CPMG AS (CEPM)","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":51885,"nome":"COLÉGIO MILITAR DE RIO VERDE - CPMG RV (CEPM)","sigla":"CPMG RV (CEPM)","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":51923,"nome":"COLÉGIO MILITAR DIONARIA ROCHA - CPMG DR (CEPM)","sigla":"CPMG DR (CEPM)","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":85407,"nome":"COLÉGIO MILITAR PROF. JOÃO AUGUSTO PERILLO - CPMG JAP - GOIÁS (CEPM)","sigla":"CPMG JAP - GOIÁS (CEPM)","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":85821,"nome":"COLÉGIO MILITAR CLEMENTINA RANGEL DE MOURA - CPMG CRM - FORMOSA (CEPM)*","sigla":"CPMG CRM - FORMOSA (CEPM)","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":85822,"nome":"COLÉGIO MILITAR DOUTOR PEDRO LUDOVICO - CPMG DPL - QUIRINOPOLIS (CEPM)","sigla":"CPMG DPL - QUIRINOPOLIS (CEPM)","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":85867,"nome":"COLÉGIO MILITAR TOMÁZ MARTINS DA CUNHA - CPMG TMC - PORANGATU (CEPM)","sigla":"CPMG TMC - PORANGATU (CEPM)","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":86169,"nome":"COLÉGIO MILITAR NADER ALVES DOS SANTOS - CPMG NAS (CEPM)","sigla":"CPMG NAS (CEPM)","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":86170,"nome":"COLÉGIO MILITAR POLIVALENTE GABRIEL ISSA - CPMG PGI - ANAPÓLIS (CEPM)","sigla":"CPMG PGI - ANAPÓLIS (CEPM)","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":86171,"nome":"COLÉGIO MILITAR FERNANDO PESSOA - CPMG FP - VALPARAÍSO (CEPM)","sigla":"CPMG FP - VALPARAÍSO (CEPM)","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":86172,"nome":"COLÉGIO MILITAR JOSÉ DE ALENCAR - CPMG JA - NOVO GAMA(CEPM)","sigla":"CPMG JA - NOVO GAMA (CEPM)","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null}';
         $data .= ',{"id":86173,"nome":"COLÉGIO MILITAR JOSÉ CARRILHO - CPMG JC - GOIANÉSIA (CEPM)","sigla":"CPMG JC - GOIANÉSIA (CEPM)","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":86174,"nome":"COLÉGIO MILITAR NESTÓRIO RIBEIRO - CPMG NR - JATAÍ (CEPM)","sigla":"CPMG NP - JATAÍ (CEPM)","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":86175,"nome":"COLÉGIO MILITAR MANOEL VILA VERDE - CPMG MVV - INHUMAS (CEPM)","sigla":"CPMG INHUMAS","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":87535,"nome":"COLÉGIO MILITAR MARIA TEREZA GARCIA NETA BENTO - CPMG MTGNB - JUSSARA (CEPM)","sigla":"CPMG JUSSARA","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":89548,"nome":"COLÉGIO MILITAR CABO EDMILSON DE SOUSA LEMOS - CPMG ESL - PALMEIRAS (CEPM)","sigla":"CPMG PALMEIRAS (CEPM)","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":89549,"nome":"COLÉGIO MILITAR Dr. THARSIS CAMPOS - CPMG DTC - CATALÃO (CEPM)","sigla":"CPMG CATALÃO (CEPM)","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":94966,"nome":"CPMG MIRIAM BENCHIMOL - GOIÂNIA (CEPM)","sigla":"CPMG MB","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":94971,"nome":"CPMG WALDEMAR MUNDIM - GOIÂNIA (CEPM)","sigla":"CPMG WM","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":94972,"nome":"CPMG JARDIM GUANABARA - GOIÂNIA (CEPM)","sigla":"CPMG JG","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":94973,"nome":"CPMG JUVENAL JOSÉ PEDROSO - GOIÂNIA (CEPM)","sigla":"CPMG JJP","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":94974,"nome":"CPMG COLINA AZUL - APARECIDA DE GOIÂNIA (CEPM)","sigla":"CPMG CA","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":94975,"nome":"CPMG MANSÕES PARAÍSO - APARECIDA DE GOIÂNIA (CEPM)","sigla":"CPMG MP","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":94977,"nome":"CPMG MADRE GERMANA - APARECIDA DE GOIÂNIA (CEPM)","sigla":"CPMG MG","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":94978,"nome":"CPMG MARIA ROSILDA RODRIGUES - APARECIDA DE GOIÂNIA (CEPM)","sigla":"CPMG MRR","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":94979,"nome":"CPMG PEDRO XAVIER TEIXEIRA - SENADOR CANEDO (CEPM)","sigla":"CPMG PXT","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":94980,"nome":"CPMG POLIVALENTE DR. SEBASTIÃO ALMEIDA - URUAÇU (CEPM)","sigla":"CPMG SA","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":94981,"nome":"CPMG SILVIO DE CASTRO RIBEIRO - JARAGUÁ (CEPM)","sigla":"CPMG SCR","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":94982,"nome":"CPMG HÉLIO VELOSO - CERES (CEPM)","sigla":"CPMG HV","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":94983,"nome":"CPMG DOMINGOS DE OLIVEIRA - FORMOSA (CEPM)","sigla":"CPMG DO","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":102638,"nome":"COLÉGIO MILITAR MARIA HELNY PERILLO - CPMG BPA - ITABERAI (CEPM)","sigla":"CPMG BPA","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":105158,"nome":"COLÉGIO MILITAR NIVO DAS NEVES - CPMG NN - CALDAS NOVAS (CEPM)","sigla":"CEPM","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":105578,"nome":"COLÉGIO MILITAR DOM PRUDÊNCIO - CPMG DP - POSSE (CEPM)","sigla":"CEPM","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":105659,"nome":"COLÉGIO MILITAR DE ITAUÇU - CPMG ITAUÇU (CEPM)","sigla":"CPMG ITAUÇU (CEPM)","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":105660,"nome":"COLÉGIO MILITAR DE GOIATUBA - CPMG GOIATUBA (CEPM)","sigla":"CPMG GOIATUBA (CEPM)","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":105789,"nome":"CPMG SEBASTIÃO JOSÉ DE ALMEIDA PRIMO","sigla":"CPMG SJAP","idSuperior":48605,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":52164,"nome":"COMANDO DE GESTÃO E FINANÇAS","sigla":"CGF","idSuperior":48584,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":52184,"nome":"CHEFIA DE EXECUÇÃO ORÇAMENTÁRIA E FINANCEIRA - CEOF (CAF)","sigla":"CEOF","idSuperior":52164,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":52217,"nome":"CHEFIA DE RECURSOS HUMANOS - CRH (CAF)","sigla":"CRH","idSuperior":52164,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":64296,"nome":"COMISSÃO DE PROMOÇÃO DE OFICIAIS","sigla":"CPO","idSuperior":48584,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":64297,"nome":"COMISSÃO DE PROMOÇÃO DE PRAÇAS","sigla":"CPP","idSuperior":48584,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":64298,"nome":"COMISSÃO DE PERMANENTE DE MEDALHAS","sigla":"CPM","idSuperior":48584,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":94158,"nome":"COMANDO DE APOIO LOGÍSTICO E TECNOLÓGIA DA INFORMAÇÃO","sigla":"CALTI","idSuperior":48584,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":106398,"nome":"CENTRO INTEGRADO DE COMANDO E CONTROLE","sigla":"CICC","idSuperior":48584,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":48585,"nome":"ÓRGÃOS DE DIREÇÃO","sigla":"DIREÇÃO","idSuperior":65,"superior":null,"corporacao":null,"corporacaoId":null,"level":2,"telefone":null},{"id":53190,"nome":"ESTADO MAIOR - EM","sigla":"EM","idSuperior":48585,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":53172,"nome":"1ª SEÇÃO DO ESTADO MAIOR - PM/1","sigla":"PM/1","idSuperior":53190,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":53174,"nome":"6ª SEÇÃO DO ESTADO MAIOR - PM/6","sigla":"PM/6","idSuperior":53190,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":53175,"nome":"7ª SEÇÃO DO ESTADO MAIOR - PM/7","sigla":"PM/7","idSuperior":53190,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":53191,"nome":"2ª SEÇÃO DO ESTADO MAIOR - PM/2","sigla":"PM/2","idSuperior":53190,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":53192,"nome":"3ª SEÇÃO DO ESTADO MAIOR - PM/3","sigla":"PM/3","idSuperior":53190,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":53193,"nome":"4ª SEÇÃO DO ESTADO MAIOR - PM/4","sigla":"PM/4","idSuperior":53190,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":57805,"nome":"5ª SEÇÃO DO ESTADO MAIOR - PM/5","sigla":"PM/5","idSuperior":53190,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":63648,"nome":"8ª SEÇÃO DO ESTADO MAIOR - PM/8","sigla":"PM/8","idSuperior":53190,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":78658,"nome":"CENTRO DE POLÍCIA COMUNITÁRIA - CPCOM (EM)","sigla":"CPCOM (EM)","idSuperior":53190,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":64908,"nome":"CHEFIA DO ESTADO MAIOR ESTRATÉGICO","sigla":"CH EME","idSuperior":48585,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":48604,"nome":"ÓRGÃOS DE EXECUÇÃO","sigla":"EXECUÇÃO","idSuperior":65,"superior":null,"corporacao":null,"corporacaoId":null,"level":2,"telefone":null},{"id":48588,"nome":"COMANDO DE POLICIAMENTO RODOVIÁRIO - CPR","sigla":"CPR","idSuperior":48604,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":49185,"nome":"01ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR RODOVIÁRIA (CPR)","sigla":"01ª CIPMR (CPR)","idSuperior":48588,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":49204,"nome":"02º BATALHÃO DE POLÍCIA MILITAR RODOVIÁRIO (CPR)","sigla":"02º BPMR (CPR)","idSuperior":48588,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":67018,"nome":"01° BATALHÃO DE POLÍCIA MILITAR OPERAÇÕES DE DIVISA (CPR)","sigla":"01º BPMOD (CPR)","idSuperior":48588,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":88614,"nome":"01º BATALHÃO DE POLÍCIA MILITAR RODOVIÁRIO (CPR)","sigla":"01º BPMR (CPR)","idSuperior":48588,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":93638,"nome":"03º BATALHÃO DE POLÍCIA MILITAR RODOVIÁRIO (CPR)","sigla":"03º BPMR (CPR)","idSuperior":48588,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":94250,"nome":"BATALHÃO DE POLÍCIA MILITAR FAZENDÁRIA (CPR)","sigla":"BPMFAZ (CPR)","idSuperior":48588,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":48589,"nome":"COMANDO DE POLICIAMENTO AMBIENTAL - CPA","sigla":"CPA","idSuperior":48604,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":50760,"nome":"01ª CIA. INDEP. DE POLÍCIA MILITAR AMBIENTAL (CPA)","sigla":"01ª CIPMA (CPA)","idSuperior":48589,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":85493,"nome":"01º BATALHÃO DE POLÍCIA MILITAR AMBIENTAL (CPA)","sigla":"01º BPMA (CPA)","idSuperior":48589,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":93401,"nome":"NÚCLEO DE ENSINO AMBIENTAL (CPA)","sigla":"NEA(CPA)","idSuperior":48589,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":64373,"nome":"COMANDO DE MISSÕES ESPECIAIS - CME","sigla":"CME","idSuperior":48604,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":64586,"nome":"GRUPAMENTO DE RÁDIO PATRULHA AEREA (CME)","sigla":"GRAER(CME)","idSuperior":64373,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":90114,"nome":"BATALHÃO DE CHOQUE - BPMCHOQUE (CME)","sigla":"BPMCHOQUE(CME)","idSuperior":64373,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":90115,"nome":"REGIMENTO DE POLÍCIA MONTADA (CME)","sigla":"RPMON(CME)","idSuperior":64373,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":91719,"nome":"01º GRUPAMENTO DE INTERVENÇÃO RÁPIDA OSTENSIVA (CME)","sigla":"01º GIRO(CME)","idSuperior":64373,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":92179,"nome":"35º BPM - BATALHÃO DE OPERAÇÕES ESPECIAIS (CME)","sigla":"BOPE(CME)","idSuperior":64373,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":97678,"nome":"15º COMANDO REGIONAL DA POLÍCIA MILITAR - GOIANÉSIA","sigla":"15º CRPM","idSuperior":48604,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":99512,"nome":"23º BATALHÃO DE POLÍCIA MILITAR - 23º BPM (15º CRPM)","sigla":"23º BPM (15º CRPM)","idSuperior":97678,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":99513,"nome":"03ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 03ª CIPM (15º CRPM)","sigla":"03ª CIPM (15º CRPM)","idSuperior":97678,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":98319,"nome":"10º COMANDO REGIONAL DA POLÍCIA MILITAR - URUAÇÚ","sigla":"10º CRPM","idSuperior":48604,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":99346,"nome":"14º BATALHÃO DE POLÍCIA MILITAR - 14º BPM (10º CRPM) ","sigla":"14º BPM (10º CRPM)","idSuperior":98319,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":98320,"nome":"11º COMANDO REGIONAL DA POLÍCIA MILITAR - FORMOSA","sigla":"11º CRPM","idSuperior":48604,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":99348,"nome":"16º BATALHÃO DE POLÍCIA MILITAR - 16º BPM (11º CRPM)","sigla":"16º BPM (11º CRPM)","idSuperior":98320,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":99472,"nome":"21º BATALHÃO DE POLÍCIA MILITAR - 21º BPM (11º CRPM)","sigla":"21º BPM (11º CRPM)","idSuperior":98320,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":99504,"nome":"14ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 14ª CIPM (11º CRPM)","sigla":"14ª CIPM (11º CRPM)","idSuperior":98320,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":98321,"nome":"12º COMANDO REGIONAL DA POLÍCIA MILITAR - PORANGATU","sigla":"12º CRPM","idSuperior":48604,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":99505,"nome":"03º BATALHÃO DE POLÍCIA MILITAR - 03º BPM (12º CRPM)","sigla":"03º BPM (12º CRPM)","idSuperior":98321,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":99506,"nome":"13ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 13ª CIPM (12º CRPM)","sigla":"13ª CIPM (12º CRPM)","idSuperior":98321,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":98322,"nome":"13º COMANDO REGIONAL DA POLÍCIA MILITAR - POSSE","sigla":"13º CRPM","idSuperior":48604,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":99507,"nome":"24º BATALHÃO DE POLÍCIA MILITAR - 24º BPM (13º CRPM)","sigla":"24º BPM (13º CRPM)","idSuperior":98322,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":99508,"nome":"42ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 42ª CIPM (13º CRPM)","sigla":"42ª CIPM (13º CRPM)","idSuperior":98322,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":98323,"nome":"14º COMANDO REGIONAL DA POLÍCIA MILITAR - JATAÍ","sigla":"14º CRPM","idSuperior":48604,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":99509,"nome":"15º BATALHÃO DE POLÍCIA MILITAR - 15º BPM (14º CRPM)","sigla":"15º BPM (14º CRPM)","idSuperior":98323,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":99510,"nome":"07ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 07ª CIPM (14º CRPM)","sigla":"07ª CIPM(14º CRPM)","idSuperior":98323,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":99511,"nome":"18ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 18ª CIPM (14º CRPM)","sigla":"18ª CIPM (14º CRPM)","idSuperior":98323,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":98324,"nome":"16º COMANDO REGIONAL DA POLÍCIA MILITAR - CERES","sigla":"16º CRPM","idSuperior":48604,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":99514,"nome":"22ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 22ª CIPM (16º CRPM)","sigla":"22ª CIPM (16º CRPM)","idSuperior":98324,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":99063,"nome":"17º COMANDO REGIONAL DA POLÍCIA MILITAR - ÁGUAS LINDAS","sigla":"17º CRPM","idSuperior":48604,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":99515,"nome":"17º BATALHÃO DE POLÍCIA MILITAR - 17º BPM (17º CRPM)","sigla":"17º BPM (17º CRPM)","idSuperior":99063,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":99516,"nome":"11ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 11ª CIPM (17º CRPM)","sigla":"11ª CIPM (17º CRPM)","idSuperior":99063,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":99517,"nome":"34ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 34ª CIPM (17º CRPM)","sigla":"34ª CIPM (17º CRPM)","idSuperior":99063,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":99518,"nome":"35ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 35ª CIPM (17º CRPM)","sigla":"35ª CIPM (17º CRPM)","idSuperior":99063,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":99519,"nome":"36ª COMPANHIA INDEPENDENTE DE POLÍCIA MILITAR - 36ª CIPM (17º CRPM)","sigla":"36ª CIPM (17º CRPM)","idSuperior":99063,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":103218,"nome":"02º COMANDO REGIONAL DA POLÍCIA MILITAR - APARECIDA DE GOIÂNIA","sigla":"02º CRPM","idSuperior":48604,"superior":null,"corporacao":null,"corporacaoId":null,"level":3,"telefone":null},{"id":103342,"nome":"40º BATALHÃO DE POLÍCIA MILITAR - 40º BPM (02º CRPM)","sigla":"40º BPM (02º CRPM)","idSuperior":103218,"superior":null,"corporacao":null,"corporacaoId":null,"level":4,"telefone":null},{"id":87711,"nome":"MODELO DE BPM","sigla":"PMGO","idSuperior":65,"superior":null,"corporacao":null,"corporacaoId":null,"level":2,"telefone":null},{"id":97745,"nome":"MODELO CRPM","sigla":"PMGO","idSuperior":65,"superior":null,"corporacao":null,"corporacaoId":null,"level":2,"telefone":null},{"id":99347,"nome":"MODELO CIPM","sigla":"MO CIPM","idSuperior":65,"superior":null,"corporacao":null,"corporacaoId":null,"level":2,"telefone":null}]';*/
         $data = false;
         
         return $data;
     }//Fim Módulo
/*-------------------------------------------------------------------------------
 *                        Verifica período (retorna true se o prazo vencer)
 *------------------------------------------------------------------------------- */
     public function get_periodo_vence ($param)
     {
        try
        {
            TTransaction::open('sicad');
            $result = ctl_atualizar::where('nome','=',$param)->load();//Busca dados de atualização
            TTransaction::close();
            //var_dump($result);
        }
        catch (Exception $e)
        {
            new TMessage('info',$e->getMessage()."Erro ao Buscar data de Vencimento.");
            TTransaction::rollback();
            return true;
        }
        
        if (!empty($result))
        {
            foreach ($result as $parte)
            {
                $data_atual = ($parte->data_atual) ? $parte->data_atual : null;
                $periodo = ($parte->periodo) ? $parte->periodo : null;
            }
            if ($periodo==null || $data_atual==null)
            {
                return true;//não existe a chamada, logo prazo venceu
            }
            //echo $data_atual.' '.$periodo;
            $data_ini = strtotime($data_atual);//Data da Atualização
            $data_hj = strtotime(date('Y-m-d'));//Data de Hoje
            $dif = $data_hj-$data_ini;
            $diferenca = floor($dif/(60*60*24));
            if ($diferenca>$periodo)
            {
                return true;//Sim, venceu o período
            }
        }
        else
        {
            return true;
        }
        return false;


     }// Fim Módulo
/*---------------------------------------------------------------------------
 *               Atualiza OPMs 
 *---------------------------------------------------------------------------*/
    public function atualiza_opms()
    {
        try
        {
            TTransaction::open('permission');
            $opms = $this->lista_opms_pm();
            $keep = array();
            if ($opms)
            {
                foreach ($opms as $opm)
                {
                    if (is_array($opm))
                    {
                        $unidade = new OPM();
                        $unidade->fromArray( (array) $opm);
                        $unidade->idSuperior = (!$opm['idSuperior']) ? '0' : $opm['idSuperior'];
                        $unidade->store();                        //Armazena a OPM
                        $keep[] = $unidade->id;
                    }
                }
                if (is_array($keep) && count($keep)>0)
                {
                    $sql = "UPDATE g_geral.opm SET oculto = 'S' WHERE id NOT IN (" . implode(',',$keep) . ")";
                    $fer = new TFerramentas();
                    $fer->runQuery($sql);
                }
                else
                {
                    throw new Exception ('Falha na atualização das OPMs');
                }
            }
            else
            {
                throw new Exception ('Falha na atualização das OPMs. O SICAD não retornou nenhuma OPM para atualizar.');
            }
            TTransaction::close();            
        }
        catch (Exception $e)
        {
            new TMessage('info',$e->getMessage()."<br>Erro ao atualizar OPMs.");
            TTransaction::rollback();
            return false;            
        }
        $this->put_atualizou('opms',3);//Armazena atualização
        return true;
        
    }//Fim Módulo
/*---------------------------------------------------------------------------
 *               Atualiza Estados 
 *---------------------------------------------------------------------------*/
    public function atualiza_estados()
    {
        try
        {
            TTransaction::open('permission');
            $ufs = $this->estados();
            if (is_array($ufs))
            {
                $ret = estados::where('id','>','0')->delete();     //Apaga BD de estados
            }
            else
            {
                throw new Exception ('Falha na atualização dos Estados');
            }
            if ($ufs)
            {
                foreach ($ufs as $uf)
                {
                    if (is_array($uf))
                    {
                        $unidade = new estados();
                        $unidade->fromArray( (array) $uf);
                        $unidade->store();                        //Armazena a UF
                    }
                }
            }
            else
            {
                throw new Exception ('Falha na atualização dos Estados');
            }
            TTransaction::close();            
        }
        catch (Exception $e)
        {
            new TMessage('error',$e->getMessage()."<br>Erro ao atualizar Estados.");
            TTransaction::rollback();
            return false;            
        }
        $this->put_atualizou('estados',365);//Armazena atualização
        return true;
    }//Fim Módulo
/*---------------------------------------------------------------------------
 *               Atualiza municípios 
 *---------------------------------------------------------------------------*/
    public function atualiza_cidades()
    {
        try
        {
            TTransaction::open('permission');
            $ufs = $this->estados();
            if ($ufs)
            {
                foreach ($ufs as $uf)
                {
                    if (is_array($uf))
                    {
                        $cidades = $this->cidades($uf['sigla']);
                        if ($cidades)
                        {
                             $ret = cidades::where('uf','=',$uf['sigla'])->delete();     //Apaga BD de cidades
                             foreach ($cidades as $cidade)
                            {
                                if (is_array($cidade))
                                {
                                    $unidade = new cidades();
                                    $unidade->fromArray( (array) $cidade);
                                    $unidade->store();                        //Armazena a cidade
                                }
                            }
                        }
                    }
                    else
                    {
                        throw new Exception ('Falha na atualização das Cidades. Não houve retorno das Cidades');
                    }
                }
            }
            else
            {
                throw new Exception ('Falha na atualização das Cidades. Não houve retorno dos Estados.');
            }
            TTransaction::close();            
        }
        catch (Exception $e)
        {
            new TMessage('error',$e->getMessage()."<br>Erro ao Atualizar Cidades.");
            TTransaction::rollback();
            return false;            
        }
        $this->put_atualizou('municipios',365);//Armazena atualização        
        return true;
    }//Fim Módulo
/*---------------------------------------------------------------------------
 *               Registra atualização 
 *---------------------------------------------------------------------------*/
    public function put_atualizou ($sistema,$periodo)
    {
        try
        {
            TTransaction::open('permission');
            date_default_timezone_set('America/Sao_Paulo');
            $date = date('Y-m-d');
            $deletar = ctl_atualizar::where('nome','=',$sistema)->delete();
            $atualiza = new ctl_atualizar();
            $atualiza->data_atual = $date;
            $atualiza->nome       = $sistema;
            $atualiza->periodo    = $periodo;
            $atualiza->store();
            TTransaction::close();

        }
        catch (Exception $e)
        {
            new TMessage('error',$e->getMessage()."<br>Erro ao Marcar atualização.");
            TTransaction::rollback();
            return false;
        }     
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  DESCRICAO: Forma uma lista de OPMs de um regional
 *------------------------------------------------------------------------------*/
    function get_OpmsRegiao($opm_find) //$opm_find = dados da OPM
    {
        if (TSession::getValue('dados_regional'))
        {
            if (TSession::getValue('dados_regional')==$opm_find)
            {
                $saida = TSession::getValue('dados_saida');
                $lista = TSession::getValue('dados_lista');
                $ret = array ('valores'=>$saida,'lista'=>$lista);
                return $ret;
            }
        }
        try
        {
            TTransaction::open('sicad');
            $SQL = "SELECT b.id, b.nome, a.id as idSuperior, a.nome as superior ".
                    "FROM g_geral.opm a LEFT JOIN g_geral.opm b on b.idSuperior=a.id ".
                    "WHERE a.id=".$opm_find." ORDER BY b.id, a.id;";
            $conn = TTransaction::get();
            $result = $conn->prepare($SQL);
            $result->execute();
            $opms = $result->fetchAll(PDO::FETCH_NAMED);
            /*$lista_opms = OPM::where('id','>','0')->load();
            if (empty($lista_opms))
            {
                throw new Exception ('Erro na formação do Grupo de OPMs');
            }
            $opms = $lista_opms;*/
            TTransaction::close();

        }
        catch (Exception $e)
        {
            new TMessage('error',$e->getMessage()."<br>Erro ao buscar estrutura Pai e Filhas.<br>" .
                                                  "Provavelmente ficou muito tempo sem usar o sistema e sua sessão foi interrompida.<b>".
                                                  "Saia do Sistema e logue novamente para sanar a pane.<br><br>".
                                                  "Código SQL =>".$SQL);
            TTransaction::rollback();
            return false;
        }

        if ($opms)//Há OPMs filhas
        {
            //var_dump($opms);
            $lista   = array();
            $lista[] = $opm_find;
            foreach ($opms as $opm)
            {
                $lista[] = $opm['id'];
            }
        }
        else//Não existe OPMs filhas, incluir só a unidade que foi procurada.
        {
            $lista   = array();
            $lista[] = $opm_find;
        }
        $saida   = trim(implode(',',$lista));
        if (substr($saida,-1) == ',')
        {
            $saida = substr($saida,0,strlen($saida)-1);
        }
        TSession::setValue('dados_regional',$opm_find);//Armazena a pesquisa nas variáveis de sessão
        TSession::setValue('dados_saida',$saida);
        TSession::setValue('dados_lista',$lista);
        $ret = array ('valores'=>$saida,'lista'=>$lista);
        return $ret;
        
        
        //Código velho
        
        $lista = array();//Uma lista em array
        $lista[]=$opm_find;
        $valores = "-(".(string)$opm_find.")";
        $saida = (string)$opm_find;//Uma lista separadas por vírgulas
        ini_set("max_execution_time", 120);//Aumenta o prazo pra receber dados        
        do 
        {
            $achei = false;
            foreach($opms as $opm)
            {
                $opm = $opm->toArray();
                $id = (string)$opm['id'];
                if (strpos($valores,$id)===FALSE) 
                {
                    if (array_key_exists('idSuperior',$opm)==true)
                    {
                        $idsuperior = $opm['idSuperior'];
                    }
                    else
                    {
                        $idsuperior = $opm['idsuperior'];  
                    }
                    if ($idsuperior!='') 
                    {
                        if(strpos($valores,"(".$idsuperior.")")>0)
                        {
                            $lista[]     =$opm['id'];
                            $saida      .= ",".$opm['id'];
                            $valores    .= "(".$opm['id'].")";
                            $achei=true;
                        } 
                    } 
                }
            }
            if ($achei==false) 
            {
                break;
            }
        } while ($achei);
        
        $ret = array ('valores'=>$saida,'lista'=>$lista);
        TSession::setValue('dados_regional',$opm_find);//Armazena a pesquisa nas variáveis de sessão
        TSession::setValue('dados_saida',$saida);
        TSession::setValue('dados_lista',$lista);
        return $ret;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  DESCRICAO: busca lista de serviços com base na listagem de opms
 *------------------------------------------------------------------------------*/
     public function get_ServicosOPMs ($param)
     {
         try
         {
            TTransaction::open('freap');
            $query = "(SELECT DISTINCT id_servico FROM freap.grupo_servico_opm WHERE id_opm IN (".$param."))";
            $criteria = new TCriteria;
            $criteria->add (new TFilter ('id','IN',$query));
            $criteria->add (new TFilter ('oculto','=','false'));
            $repository = new TRepository('servico');
            $servicos = $repository->load($criteria);
            TTransaction::close();
            return $servicos;
            
         }
         catch (Exception $e)
         {
            new TMessage('error',$e->getMessage()."<br>Erro ao buscar serviços de uma OPM-FREAP.");
            TTransaction::rollback();
            return false;
         }
         
     }//Fim Módulo
/*------------------------------------------------------------------------------
 *  DESCRICAO: Faz a listagem dos serviços com base na OPM e região (FREAP)
 *------------------------------------------------------------------------------*/
     public function get_ListaServicoOPM ($opm='65')
     {
         $opms = $this->get_OpmsRegiao($opm);
         if (empty($opms))
         {
             return false;
         }         
         $servicos = $this->get_ServicosOPMs($opms['valores']);
         if (empty($servicos))
         {
             return false;
         }
         $lista = array();
         foreach ($servicos as $servico)
         {
             $lista[$servico->id] = $servico->nome;
         }
         return $lista;

     }//Fim Módulo
/*------------------------------------------------------------------------------
 *  DESCRICAO: verifica onde se logou e remove tab de homologação
 *------------------------------------------------------------------------------*/
     static function get_Prd_Hom ($url,$local)
     {
        $arq = "sisopm_cfg.ini";
        if ($local==null)
        {
            if (file_exists($arq)) 
            {
                $config = parse_ini_file($arq, true );
                $local = $config['config_geral']['ambiente'];
            }
        }
        $url = ($local=='producao') ? str_replace('-h','',$url) : $url;
        return $url;
     }//Fim Módulo
/*------------------------------------------------------------------------------
 *  DESCRICAO: converte timestamp para datas em especial se oriundas do BD Oracle
 *------------------------------------------------------------------------------*/
     public function time_To_Date_SICAD ($timestamp,$tam='9',$formato='br')
     {
        $timestamp = substr($timestamp,0,strlen($timestamp)-3);
        $ret = ($formato=='br') ? date('Y-m-d H:i:s',$timestamp) : date("Y/m/d",$timestamp);
        return $ret;  
     }//Fim Módulo
/*------------------------------------------------------------------------------
 *  DESCRICAO: Array de caracteristicas físicas
 *------------------------------------------------------------------------------*/
     public function caracteristicas_SICAD ($item=null,$key=null)
     {
        $cara = array (
                        'codigocorpele'=>array('87'=>'AMARELA','25'=>'BRANCA','236'=>'CLARA','237'=>'ESCURA',
                        '88'=>'INDÍGENA','26'=>'MORENA','30'=>'NEGRA','28'=>'PARDA','268'=>'PARDA CLARA',
                        '269'=>'PARDA ESCURA','238'=>'PRETA'),
                        'codigocorcabelo'=>array('270'=>'ALOURADOS','289'=>'AVERMELHADOS','308'=>'CASTANHO','33'=>'CASTANHOS CLAROS',
                        '35'=>'CASTANHOS ESCUROS','34'=>'CASTANHOS MÉDIOS','271'=>'ENCANECIDOS','89'=>'GRISALHOS',
                        '31'=>'LOUROS','36'=>'PRETOS','90'=>'RUIVOS','288'=>'TINGIDOS'),
                        'codigocorolho'=>array('52'=>'AZUIS','235'=>'AZUL CLARO','309'=>'CASTANHO','60'=>'CASTANHOS CLAROS',
                        '92'=>'CASTANHOS ESCUROS','59'=>'CASTANHOS MÉDIOS','234'=>'ESVERDEADOS','93'=>'PRETOS','54'=>'VERDES'),
                        'codigomaoqueescreve'=>array('107'=>'AMBAS','106'=>'DIREITA','105'=>'ESQUERDA'),
                        'codigotipocabelo'=>array('272'=>'CARAPINA','85'=>'CRESPOS','40'=>'LISOS','41'=>'ONDULADOS'),
                        'grauparentesco'=>array('105'=>'AVÓ','106'=>'AVÔ','15'=>'Avós inválidos ou interditos','14'=>'Companheiro(a)',
                        '10'=>'Cônjuge','85'=>'ENTEADO(A)','45'=>'FILHO(A)','145'=>'IRMÃ','46'=>'MÃE','47'=>'PAI','165'=>'SOGRO(A)',
                        '16'=>'cunhado menor ou interdito','11'=>'ex-esposa','12'=>'filha solteira','13'=>'filho adotivo',
                        '25'=>'filho estudante','26'=>'filho interdito','27'=>'filho inválido','28'=>'filho menor',
                        '29'=>'filho tutelado','30'=>'irmã, cunhada e sobrinha - solteiras, viuvas, separadas',
                        '31'=>'irmãos - menores ou interditos','33'=>'menor - sob sua guarda',
                        '32'=>'mãe viuva','34'=>'neto - órfão, menor, inválido ou interdito','35'=>'pais - com mais de 60 anos',
                        '36'=>'pais - inválidos ou interditos','37'=>'pessoa com mais de 5 anos sob dependência econômica',
                        '38'=>'sobrinhos - menores ou interditos'),
                        'quadro'=>array('52'=>'QOA','51'=>'QOS','53'=>'QOEM','50'=>'QOPM','286'=>'QPS','145'=>'QPEPM',
                        '55'=>'QPE','285'=>'QPM','54'=>'QPPM','165'=>'QPT','405'=>'QPMV'),
                        'quadro_alfa'=>array('QOA'=>'QOA','QOS'=>'QOS','QOEM'=>'QOEM','QOPM'=>'QOPM','QPS'=>'QPS','QPEPM'=>'QPEPM',
                        'QPE'=>'QPE','QPM'=>'QPM','QPPM'=>'QPPM','QPT'=>'QPT','QPMV'=>'QPMV'),
                        'postograd'=>array(	'170'=>'1º SARGENTO','166'=>'1º Tenente','171'=>'2º Sargento',
                        '167'=>'2º Tenente','172'=>'3º Sargento','306'=>'Aluno Cabo','305'=>'Aluno Sargento',
                        '307'=>'Aluno Soldado','168'=>'Aspirante À Oficial','173'=>'Cabo','267'=>'Cadete 1º Ano',
                        '266'=>'Cadete 2º Ano','265'=>'Cadete 3º Ano','165'=>'Capitão','162'=>'Coronel',
                        '665'=>'Funcionário Civil','164'=>'Major','174'=>'Soldado','285'=>'Soldado Temporário',
                        '765'=>'Soldado de 2ª Classe','925'=>'Soldado de 3ª Classe','169'=>'Subtenente',
                        '163'=>'Tenente Coronel'),
                        'postograd_sigla'=>array(	'170'=>'1ºSGT','166'=>'1ºTEN','171'=>'2ºSGT',
                        '167'=>'2ºTEN','172'=>'3ºSGT','306'=>'AL CB','305'=>'AL SGT',
                        '307'=>'AL SD','168'=>'ASP OF','173'=>'CB','267'=>'CAD 1ºANO',
                        '266'=>'CAD 2º Ano','265'=>'CAD 3º Ano','165'=>'CAP','162'=>'CEL',
                        '665'=>'FUNC CIV','164'=>'MAJ','174'=>'SD','285'=>'SD TEMP',
                        '765'=>'SD 2ª','925'=>'SD 3ª','169'=>'ST',
                        '163'=>'TC'),
                        'sexo'=>array('F'=>'Feminino','M'=>'Masculino'),
                        'categoriacnh'=>array('30'=>'A','45'=>'AB','46'=>'AC','47'=>'AD','68'=>'AE','31'=>'B','48'=>'BB',
                        '32'=>'C','33'=>'D','49'=>'E'),
                        'romaneio_roupa'=>array('32'=>'32','34'=>'34','36'=>'36','38'=>'38','40'=>'40','42'=>'42',
                        '44'=>'44','46'=>'46','48'=>'48','50'=>'50','52'=>'52','54'=>'54','56'=>'56','58'=>'58',
                        '60'=>'60'),
                        'romaneio_tamanho'=>array('PPP'=>'PPP','PP'=>'PP','P'=>'P','M'=>'M','G'=>'G','GG'=>'GG',
                        'GGG'=>'GGG'),

                        );

        
        if ($item==null)
        {
            return $cara;
        }
        elseif ($item!=null && $key==null)
        {
            return $cara[$item]; 
        }
        elseif ($item!=null && $key!=null)
        {
            return $cara[$item][$key];
        }
        return false;  
     }//Fim Módulo
/*------------------------------------------------------------------------------
 *  DESCRICAO: Função substituta do file_get_contents
 *------------------------------------------------------------------------------*/
    public function my_file_get_contents( $site_url )
    {
    	$ch = curl_init();
    	$timeout = 60; // set to zero for no timeout
    	curl_setopt ($ch, CURLOPT_URL, $site_url);
    	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    	ob_start();
    	curl_exec($ch);
    	curl_close($ch);
    	$file_contents = ob_get_contents();
    	ob_end_clean();
    	return $file_contents;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  DESCRICAO: Atualiza lotação dos Militares de Uma OPM
 *------------------------------------------------------------------------------*/
    public function update_pm_opm( $idopm )
    {
        $ci = new TFerramentas;
        if ($ci->is_dev())
        {
            return;
        }
        if (self::get_periodo_vence('OPM='.$idopm)==false)
        {
            return;//Retorna se o prazo não estiver vencido (true)
        }
        else
        {
            self::put_atualizou('OPM='.$idopm,'3');//Já define uma nova data para atualizar
        }
        //Busca no SICAD a listagem atual da OPM
        $lista_opm = self::lista_servidores_opm($idopm);
        if ($lista_opm==false)
        {
            return false;//Não atualiza em virtude da falta da lista para atualizar
        }
        try
        {
            TTransaction::open('sicad');
            //Pega a listagem no BD da unidade atual e marca todos como transferido
            $repos = new TRepository('servidor');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('unidadeid', '=', $idopm));
            //$criteria->add(new TFilter('status', '=', 'ATIVO'));
            
            $values = array('unidadeid' => 0,'unidade'=>'TRANSFERIDO','siglaunidade'=>'TRANSF');
            $repos->update($values, $criteria);
            //Busca informaçõe da OPM (objeto)
            if ($lista_opm)
            {
                $opm = new OPM ($idopm);
                if ($opm)
                {
                    $fer = new TFerramentas();
                    $militares = "'".implode("','",$fer->array_column ($lista_opm,'identificacao'))."'";
                    //var_dump( $militares);
                    //echo "<br><br>";
                    $repos = new TRepository('servidor');
                    $criteria = new TCriteria;
                    $query = "(SELECT DISTINCT id FROM efetivo.servidor WHERE rgmilitar IN (".$militares."))";
                    $criteria->add(new TFilter('id', 'IN', $query));
                    //$criteria->add(new TFilter('status', '=', 'ATIVO'));
                    
                    $values = array('unidadeid' => $opm->id,'unidade'=>$opm->nome,'siglaunidade'=>$opm->sigla);
                    $repos->update($values, $criteria);
                }
            }
            TTransaction::close();
            new TMessage('info', 'Militares atualizados'); 
        }
         catch (Exception $e)
         {
            self::put_atualizou('OPM='.$idopm,'1');//Troca o tempo de atualização em caso de erro
            new TMessage('error',$e->getMessage().'<br>Erro ao atualizar os dados de um PM da sequência de uma OPM<br>');
            TTransaction::rollback();
            return false;
         }
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  DESCRICAO: Atualiza policiais transferidos
 *------------------------------------------------------------------------------*/
    public function update_transferidos( $militar = null )
    {
        $ci = new TFerramentas;
        if ($ci->is_dev())
        {
            return;
        }
        //Procura os PM no BD do SISOPM
        $sql = "SELECT rgmilitar, nome FROM efetivo.servidor WHERE siglaunidade = 'TRANSF' ORDER BY rgmilitar;";
        try 
        { 
            TTransaction::open('sicad'); 
            $conn = TTransaction::get();
            $res = $conn->prepare($sql);
            $res->execute();
            $res->setFetchMode(PDO::FETCH_NAMED);
            $repository = $res->fetchAll();
            TTransaction::close();
        }
        catch (Exception $e)
        {
            return false;
        }
        if (is_array($repository))
        {
            foreach ($repository as $militar)
            {
                self::update_militar($militar['rgmilitar']);
            }
        }
    }
/*------------------------------------------------------------------------------
 *  DESCRICAO: Atualiza um policial
 *------------------------------------------------------------------------------*/
    public function update_militar( $militar )
    {
        $ci = new TFerramentas;
        if ($ci->is_dev())
        {
            return;
        }
        //Procura o PM no BD do SISOPM
        try
        {
            TTransaction::open('sicad');
            if (strlen($militar)==11)//verificar pelo CPF
            {
                $result = servidor::where('cpf','=',$militar)->load();//busca os dados do Militar pelo CPF
            }
            else //Verifica pelo RG
            {
                $result = servidor::where('rgmilitar','=',$militar)->load();//busca os dados do Militar pelo RG
            }
            TTransaction::close();
        }
        catch (Exception $e)
        {
            return false;
        }
        //Se teve resultado, separa o id e o rgmilitar para conferir
        if ($result)
        {
            foreach ($result as $parte)
            {
                $id = $parte->id;
                $rg_chk = $parte->rgmilitar;
            }
        }
        else
        {
            return false;
        }
        //Verifica se o Militar já foi atualizado recentemente
        if (self::get_periodo_vence('RGPM='.$rg_chk)==false)
        {
            return;//Retorna se o prazo não estiver vencido (true)
        }
        else
        {
            //echo "está fora do prazo";
            self::put_atualizou('RGPM='.$rg_chk,'30');//Já define uma nova data para atualizar
        }
        
        //Executa a busca dos dados no SICAD para atualização
        $cadastro = self::dados_servidor($rg_chk);
        if ($cadastro==false || empty($cadastro))
        {
            return false;
        }
        $cadastro['dtNascimento']   = $this->time_To_Date_SICAD($cadastro['dtNascimento']);
        $cadastro['dtExpedicaoCnh'] = $this->time_To_Date_SICAD($cadastro['dtExpedicaoCnh']);
        $cadastro['dtValidadeCnh']  = $this->time_To_Date_SICAD($cadastro['dtValidadeCnh']);
        $cadastro['dtPromocao']     = $this->time_To_Date_SICAD($cadastro['dtPromocao']);
        //Campos distoados
        $cadastro['orgaoExpedicaoRg'] = $cadastro['orgaoExpediçãoRg'];
        $cadastro['ufExpedicaoRg']    = $cadastro['ufExpediçãoRg'];
        if (is_array($cadastro))
        {
            $cadastro = array_change_key_case ($cadastro, CASE_LOWER);            
        }
        //echo "<br>Variável Cadastro<br>";print_r($cadastro);
        try
        {
            TTransaction::open('sicad');
            $object = new servidor($id);  // carrega um objeto com base no id do militar
            //echo "<br>Original<br>";var_dump($object);
            $object->dtpromocao     = $cadastro['dtpromocao'];
            $object->postograd      = $cadastro['postograd'];
            $object->siglaunidade   = $cadastro['siglaunidade'];
            $object->unidade        = $cadastro['unidade'];
            $object->unidadeid      = $cadastro['unidadeid'];
            $object->dtexpedicaocnh = $cadastro['dtexpedicaocnh'];
            $object->quadro         = $cadastro['quadro'];
            $object->status         = $cadastro['status'];
            $object->situacao       = $cadastro['situacao'];
            $object->email          = $cadastro['email'];
            $object->dtvalidadecnh  = $cadastro['dtvalidadecnh'];
            $object->dtexpedicaocnh = $cadastro['dtexpedicaocnh'];
            $object->dtnascimento   = $cadastro['dtnascimento'];
            $object->ufdotituloeleitoral = $cadastro['ufdotituloeleitoral'];
            //Verifica se o município eleitoral uma vez que o campo foi digitado
            $muntitulo = $cadastro['municipiotituloeleitoral'];
            if (strpos( $muntitulo,'-'))
            {
                $t = explode("-",$muntitulo);
                $muntitulo = $t[0];
            }
            if (!empty ($muntitulo))
            {
                $m = self::getCidadeEleitoral($muntitulo,$cadastro['ufdotituloeleitoral']);
                $muntitulo = (empty($m) || is_array($m)) ? $muntitulo : $m;
            }
            $object->municipiotituloeleitoral   = $muntitulo;
            if ($cadastro['endereco'])
            {
                $endereco = $cadastro['endereco'];//Carregas os dados de endereço
                $object->logradouro   = $endereco['logradouro'];
                $object->numero       = $endereco['numero'];
                $object->quadra       = $endereco['quadra'];
                $object->lote         = $endereco['lote'];
                $object->complemento  = $endereco['complemento'];
                $object->bairro       = $endereco['bairro'];
                $object->codbairro    = $endereco['codBairro'];
                $object->municipio    = $endereco['municipio'];
                $object->codmunicipio = $endereco['codMunicipio'];
                $object->uf           = $endereco['estado'];
                $object->cep          = $endereco['cep'];
            }
            $object->store();
            TTransaction::close();
        }
         catch (Exception $e)
         {
            new TMessage('error',$e->getMessage()."<br>Erro ao atualizar um PM.");
            TTransaction::rollback();
            return;
         }
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *        Acha cidade para Título eleitoral com problema de acentuação
 *------------------------------------------------------------------------------- */
    function getCidadeEleitoral ($cidade,$uf)
    {
        $sql = "SELECT DISTINCT nome FROM regioes.cidades WHERE translate(nome,  
             'áàâãäåaaaÁÂÃÄÅAAAÀéèêëeeeeeEEEÉEEÈìíîïìiiiÌÍÎÏÌIIIóôõöoooòÒÓÔÕÖOOOùúûüuuuuÙÚÛÜUUUUçÇñÑýÝ',  
             'aaaaaaaaaAAAAAAAAAeeeeeeeeeEEEEEEEiiiiiiiiIIIIIIIIooooooooOOOOOOOOuuuuuuuuUUUUUUUUcCnNyY'   
              ) ILIKE '%".strtoupper($cidade)."%' and uf = '".$uf."';";
        try 
        { 
            TTransaction::open('sicad'); 
            $conn = TTransaction::get();
            $res = $conn->prepare($sql);
            $res->execute();
            $res->setFetchMode(PDO::FETCH_NAMED);
            $repository = $res->fetchAll();
            TTransaction::close();
            //print_r($repository);
            $repository = $repository[0]['nome'];
        } 
        catch (Exception $e) 
        { 
            new TMessage('error', $e->getMessage().'<br>Erro ao buscar uma cidade.');
            TTransaction::rollback();
            $repository = false;
        }
        return $repository;
  
    }
/*-------------------------------------------------------------------------------
 *        Busca validação e profile
 *------------------------------------------------------------------------------- */
    function validateLogin ($token,$ambiente=null)
    {
        $url = "https://ssows-h.ssp.go.gov.br/validate?token=";
        try
        {
            //$token = TSession::getValue('token');
            if ($token)
            {
                $query = $this->get_Prd_Hom($url,$ambiente).$token;
                $items = file_get_contents($query);
                //echo($items);exit;
            }
            if (!$items)
            {
                throw new Exception ('Falha na Validação.'.$query);
            }
            $lista = $this->object_to_array(json_decode ($items));
            return (array) $lista;
        }
        catch (Exception $e)
        {
            new TMessage('error', "Erro na Validação de Usuário<br>".$e->getMessage()); // shows the exception error message
            return false;
        }
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *    Busca OPM
 *    @ $opm = id da OPM
 *    @ 
 *------------------------------------------------------------------------------*/
     public function getOPM ($opm)
     {
            $ret = false;
            if ($opm != null)
            {
                try 
                {
                    TTransaction::open('sicad'); // open a transaction with database
                    $opm = '217';
                    $dados = new OPM($opm);
                    TTransaction::close();
                    if (!empty($dados))
                    {
                        $ret = $dados;
                    }
                }
                catch (Exception $e)
                {
                    $ret = false;
                }
            }
            return $ret;
     }//Fim Módulo
/*------------------------------------------------------------------------------
 *    Busca OPMs do usuário
 *    @ $opm = id da OPM
 *    @ 
 *------------------------------------------------------------------------------*/
     public function get_OPMsUsuario ($opm = null)
     {
         $fer = new TFerramentas();
         $lista = array();
         $lista_opms = TSession::getValue('lista_opms');
         $lista_opms = (empty($lista_opms)) ? array() :$lista_opms;
         
         if (count($lista_opms) != 0)
         {
             if (array_key_exists($opm,$lista_opms))
             {
                 return $lista_opms[$opm];
             }
         }
         if ($opm == null)
         {
             $profile = TSession::getValue('profile');           //Profile da Conta do usuário
             $lista['opm'] = $this->get_OPM(); 
             if (array_key_exists('unidade',$profile))
             {
                 $lista['opm'] = ($fer->is_dev()==true) ? 140 : $profile['unidade']['id'];
             }
             else
             {
                 $lista['opm'] =  140;
             }
         }
         else
         {
             $lista['opm'] = $opm;
         }
         $ret = self::get_OpmsRegiao($lista['opm']);//Carregas as OPMs que o usuário administra
         if (is_array($ret) && count($ret)>0)
         {
             $lista['valores'] = $ret['valores'];
             $lista['lista']   = $ret['lista'];
         }
         
         $lista_opms[$lista['opm']] = array('valores'=>$lista['valores'],'lista'=>$lista['lista']);
         TSession::setValue('lista_opms',$lista_opms);
         return array('opm'=>$lista['opm'], 'valores'=>$lista['valores'],'lista'=>$lista['lista']);

     }
/*------------------------------------------------------------------------------
 *    Busca OPM do usuário entre as possíbilidades que podem existir
 *    @ 
 *    @ Sistema automatizado, armazena a OPM em $profile['unidade']['id']
 *    @ Monta tb um $profile básico tentando várias informações coerentes.
 *    @ Mantém os dados armazenados em Profile
 *------------------------------------------------------------------------------*/
     public function get_OPM ()
     {
        $fer   = new TFerramentas;
        $sicad = new TSicadDados;
        $opm      = 0;
        $opm_nome = '';
        $opm_sigl = '';
        $nome     = '';
        $cpf      = '';
        $corp     = '';
        $email    = '';
        $fone     = '';
        $profile_criado = false;
        $profile = TSession::getValue('profile');           //Profile da Conta do usuário
        if (is_array($profile) && array_key_exists('unidade',$profile))
        {
            $opm = $profile['unidade']['id'];
        }
        else
        {
            $profile = $this->set_Profile();
            $profile['unidade']['id'] = 0;
            $profile_criado = true;
        }
        $area = TSession::getValue('area');
        try
        {
            if ($opm == 0 || $profile_criado == true)
            {
                if ($area == 'SERVIDOR')
                {
                    TTransaction::open('sicad');
                    $object = aluno::newFromLogin(TSession::getValue('login'));//servidor
                    if ($object)
                    {
                        $nome  = $object->nome;
                        $cpf   = $object->cpf;
                        $corp  = $object->corporacao;
                        $email = $object->email;
                        $fone  = $object->telefonecelular;
                    }
                    if ($object->unidadeid)
                    {
                        $opm = $object->unidadeid;
                    }
                    else
                    {
                        TTransaction::close();
                        TTransaction::open('permission');
                        $object = SystemUser::newFromLogin(TSession::getValue('login'));
                        if ($object->system_unit_id)
                        {
                            $opm = $object->system_unit_id;
                        }
                    }
                }
                else if ($area == 'ALUNO')
                {
                    TTransaction::open('sisacad');
                    $object = aluno::newFromLogin(TSession::getValue('login'));//servidor
                    if ($object)
                    {
                        $nome  = $object->nome;
                        $cpf   = $object->cpf;
                        $corp  = $object->corporacao;
                        $email = $object->email;
                        $fone  = $object->telefonecelular;
                        if ($object->unidadeid)
                        {
                            $opm = $object->unidadeid;
                        }
                    }
                }
                else if ($area == 'PROFESSOR')
                {
                    TTransaction::open('sisacad');
                    $object = professor::newFromLogin(TSession::getValue('login'));//servidor
                    if ($object)
                    {
                        $nome  = $object->nome;
                        $cpf   = $object->cpf;
                        $corp  = $object->orgao_origem;
                        $email = $object->email;
                        $fone  = $object->telefone;
                    }
                }
                else if ($area == 'SISTEMA')
                {
                    TTransaction::open('permission');
                    $object = SystemUser::newFromLogin(TSession::getValue('login'));
                    if ($object)
                    {
                        $nome  = $object->name;
                        $cpf   = $object->login;
                        $corp  = '';
                        $email = $object->email;
                        $fone  = '';
                    }
                    if ($object->system_unit_id)
                    {
                        $opm = $object->system_unit_id;
                    }
                    else
                    {
                        TTransaction::close();
                        TTransaction::open('sicad');
                        $object = aluno::newFromLogin(TSession::getValue('login'));//servidor
                        if ($object->unidadeid)
                        {
                            $opm = $object->unidadeid;
                        }
                    }
                }
            }
            $verifcar = TTransaction::get();
            if (!empty($verifcar))
            {
                TTransaction::close();
            }                
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            $msg = 'Erro ao buscar a OPM do usuário';
            if (isset($area))
            {
                $msg .= ' no modo ' . $area;
            }
            $msg .= '.<br>';
            new TMessage('error', $msg . $e->getMessage()); // shows the exception error message
        }
        if ($fer->is_dev()==true && $opm == 0)//se o ambiente é de desenvolvimento
        {
            $opm = 140; 
        }
        if ($opm != 0)
        {
            try
            {
                TTransaction::open('sisacad');
                $unidade = new OPM($opm);
                if ($unidade)
                {
                    $opm_nome = $unidade->nome;
                    $opm_sigl = $unidade->sigla;
                }
                TTransaction::close();
            }
            catch (Exception $e)
            {
                TTransaction::rollback();
            }
        }
        if ($profile['unidade']['id'] == 0 || $opm = 0 || $profile_criado == true)
        {
            $profile['unidade']['id']    = $opm;
            $profile['unidade']['nome']  = $opm_nome;
            $profile['unidade']['sigla'] = $opm_sigl;
            $profile['nome']             = $nome;
            $profile['cpf']              = $cpf;
            $profile['email']            = $email;
            $profile['telefone']         = $fone;
            $profile['corporacao']       = $corp;
            TSession::setValue('profile',$profile);            
        }
        return $opm;
     }//Fim Módulo
/*------------------------------------------------------------------------------
 *    Formata o $profile para um básico funcional
 *------------------------------------------------------------------------------*/
    public function set_Profile ()
    {
        $profile = array('dtCadastro'=>'','perfis'=>array(),'corporacao'=>'','administrador'=>'','id'=>0,'funcao'=>'',
                         'email'=>'','telefone'=>'','nome'=>'','login'=>TSession::getValue('login'),'cpf'=>TSession::getValue('login'),
                         'unidade'=>array('id'=>0,'corporacaoid'=>0,'sigla'=>'','nome'=>'','corporacao'=>''));
    }//Fim Módulo     
}//Fim da classe
