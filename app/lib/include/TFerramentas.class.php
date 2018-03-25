<?php
/**
 * TFerramentas - Ferramentas (funções) diversas de uso geral
 * Copyright (c) 
 * @author  Fernando de Pinho Araújo 
 * @version 1.0, 2016-01-22
 */
class TFerramentas 
{
    //protected $elements;
    var $pascoa       = "";
    var $carnaval     = "";
    var $corpus       = "";
    var $sextasanta   = "";
    var $feriados     = false;

    /**
     * Class Constructor
     */
    public function __construct()
    {
        
    }
/*-------------------------------------------------------------------------------
 *                        Busca Grupo adminstrador
 *------------------------------------------------------------------------------- */
    public function i_adm ($param=null)
    {
        $p      = TSession::getValue('usergroupids');
        $perfis = explode(',',$p);
        $ret    = false;
        foreach ($perfis as $perfil)
        {
            if ($perfil =='1')
            {
                $ret = true;
            }
        }
        return $ret;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  DESCRICAO: Verifica Ambiente para ver se é desenvolvimento
 *------------------------------------------------------------------------------*/
    public function is_dev()
    {
        $arq = "sisopm_cfg.ini";
        if (file_exists($arq)) 
        {
            $config = parse_ini_file($arq, true );
            $handle = ($config['config_geral']['ambiente']);
         }
         else
         {
             $handle = 'local';    
         }
         $ret = ($handle=='local') ? true : false;
         //echo $handle. $ret;
         
         return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *                        Busca perfil no SIGU no profile
 *------------------------------------------------------------------------------- */
    public function perfil_Sigu ($profile=array())
    {
        $ret = false;
        $perfis = $profile['perfis'];
        foreach ($perfis as $perfil)
        {
            if ($perfil['sistema']['id']=='1')//698
            {
                $ret = true;
                //print_r($perfil);                
                try
                {
                    TTransaction::open('permission');

                    $novo_user = new SystemUser();//Cria um novo usuário
                    $novo_user->name = $profile['nome'];
                    $novo_user->login = $profile['login'];
                    $novo_user->password = md5($profile['login']);
                    $novo_user->email = $profile['email'];
                    $novo_user->frontpage_id = '10';
                    $novo_user->active = 'Y';
                    $novo_user->store();
                    $user = $novo_user->id;//Armazenha o id do usuário
                    if ($perfil['descricao']=='ADMINISTRADOR')
                    {
                        $padrao = SystemGroup::where ('name','=','Admin')->load();//Se Administrador
                    }
                    else
                    {
                        $padrao = SystemGroup::where ('name','=','Public')->load();//Os demais
                    }
                    foreach ($padrao as $p)
                    {
                        $grupo = $p->id;
                    }
                    $novo_grupo = new SystemUserGroup();//Cria novo Grupo para Usuário
                    $novo_grupo->system_user_id = $user;
                    $novo_grupo->system_group_id = $grupo;
                    $novo_grupo->store();                                        
                    TTransaction::close();
                    return $ret;
                }
                catch (Exception $e) // in case of exception
                {
                    new TMessage('error', $e->getMessage());
                    TTransaction::rollback();
                    $ret = false;
                }
                
            }
        }
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *                     Calcula o intervalo entre duas datas (strings)
 *------------------------------------------------------------------------------- */
    public function diffDatas ($dti,$dtf)
    {
        $data_i = $this->geraTimestamp($dti);
        $data_f = $this->geraTimestamp($dtf);
        if ($data_f==false || $data_i==false)
        {
            return false;
        }
        $diferenca = $data_f-$data_i;
        return (int) floor ($diferenca /(60*60*24));
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *                        Converte em DateTime
 *------------------------------------------------------------------------------- */
    public function geraTimestamp ($data, $formato = 'br')
    {
        if (!$data)
        {
            return false;
        }
        $d = (strpos('-',$data)!=0) ? explode('-',$data) : explode('/',$data);
        //Decide entre o formato brasileiro(br) ou o americano (en)
        $ret = ($formato='br') ? mktime (0,0,0,$d[1],$d[0],$d[2]): mktime (0,0,0,$d[1],$d[2],$d[0]);
        return $ret;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *        Valida CPF
 *------------------------------------------------------------------------------*/
   function isValidCPF($cpf) 
   {
           // Verifica se um número foi informado
        if(empty($cpf)) 
        {
            return false;
        }
        // Elimina possivel mascara
        $cpf = preg_replace('/[^0-9]/', '', (string) $cpf);
        $cpf = str_pad($cpf, 11, '0', STR_PAD_LEFT);
        // Verifica se o numero de digitos informados é igual a 11 
        if (strlen($cpf) != 11) 
        {
            return false;
        }
        // Verifica se nenhuma das sequências invalidas abaixo 
        // foi digitada. Caso afirmativo, retorna falso
        else if ($cpf == '00000000000' || 
            $cpf == '11111111111' || 
            $cpf == '22222222222' || 
            $cpf == '33333333333' || 
            $cpf == '44444444444' || 
            $cpf == '55555555555' || 
            $cpf == '66666666666' || 
            $cpf == '77777777777' || 
            $cpf == '88888888888' || 
            $cpf == '99999999999') 
        {
            return false;
         // Calcula os digitos verificadores para verificar se o
         // CPF é válido
         } 
         else 
         {   
            for ($t = 9; $t < 11; $t++) 
            {
                for ($d = 0, $c = 0; $c < $t; $c++) 
                {
                    $d += $cpf{$c} * (($t + 1) - $c);
                }
                $d = ((10 * $d) % 11) % 10;
                if ($cpf{$c} != $d) 
                {
                    return false;
                }
            }
            return true;
       }
   }//Fim Módulo
/*------------------------------------------------------------------------------
 *        Valida CNPJ
 *------------------------------------------------------------------------------*/
   public function isValidCNPJ($cnpj) {
   
      if (strlen($cnpj) <> 14)
      {
         return false;
      } 
      $soma = 0;
      $soma += ($cnpj[0] * 5);
      $soma += ($cnpj[1] * 4);
      $soma += ($cnpj[2] * 3);
      $soma += ($cnpj[3] * 2);
      $soma += ($cnpj[4] * 9); 
      $soma += ($cnpj[5] * 8);
      $soma += ($cnpj[6] * 7);
      $soma += ($cnpj[7] * 6);
      $soma += ($cnpj[8] * 5);
      $soma += ($cnpj[9] * 4);
      $soma += ($cnpj[10] * 3);
      $soma += ($cnpj[11] * 2); 
      $d1 = $soma % 11; 
      $d1 = $d1 < 2 ? 0 : 11 - $d1; 

      $soma = 0;
      $soma += ($cnpj[0] * 6); 
      $soma += ($cnpj[1] * 5);
      $soma += ($cnpj[2] * 4);
      $soma += ($cnpj[3] * 3);
      $soma += ($cnpj[4] * 2);
      $soma += ($cnpj[5] * 9);
      $soma += ($cnpj[6] * 8);
      $soma += ($cnpj[7] * 7);
      $soma += ($cnpj[8] * 6);
      $soma += ($cnpj[9] * 5);
      $soma += ($cnpj[10] * 4);
      $soma += ($cnpj[11] * 3);
      $soma += ($cnpj[12] * 2); 
      
      $d2 = $soma % 11; 
      $d2 = $d2 < 2 ? 0 : 11 - $d2; 
      if ($cnpj[12] == $d1 && $cnpj[13] == $d2) 
      {
         return true;
      }
      else 
      {
         return false;
      }
   }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Verifica se uma data é válida
 *------------------------------------------------------------------------------- */
    public function isValidData($dat)
    {
    	if (strpos($dat,'/'))
    	{
            $data = explode("/","$dat"); // fatia a string $dat em pedados, usando / como referência 
        }
        else
        {
            $data = explode("-","$dat"); // fatia a string $dat em pedados, usando - como referência
        }
    	if (!is_array($data))
    	{
            return false; 
        }
        if (!array_key_exists(0,$data) || !array_key_exists(1,$data) || !array_key_exists(2,$data))
        {
            return false;
        }
    	$d = $this->soNumeros($data[0]);
    	$m = $this->soNumeros($data[1]);
    	$y = $this->soNumeros($data[2]);
     
    	// verifica se a data é válida!
    	// 1 = true (válida)
    	// 0 = false (inválida)
    	$res = checkdate($m,$d,$y);
    	if ($res == 1)
    	{
    	   return true;
    	}
    	else 
    	{
    	   return false;
    	}
    }
/*-------------------------------------------------------------------------------
 *   Verifica se uma hora é válida
 *------------------------------------------------------------------------------- */
    public function isValidHora ($hr)
    {
        $hora = explode(":","$hr"); // fatia a string $dat em pedados, usando / como referência 
        if (!is_array($hora))
        {
            return false;
        }
        if (!array_key_exists(0,$hora) || !array_key_exists(1,$hora))
        {
            return false;
        }
    	$h = (int) $hora[0];
    	$m = (int) $hora[1];
    	$s = (int) (array_key_exists(2,$hora)) ? $hora[2] : '00';
        $res = true;
        if ($h<0 || $h>23)
        {
            $res = false;
        }
        if ($m<0 || $m>59)
        {
            $res = false;
        }
        if ($s<0 || $s>59)
        {
            $res = false;
        }
        return $res;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao corrige String para URL e nomes que não podem estar acentuados
 *------------------------------------------------------------------------------- */
    public function removeAcentos($string,$sp=' ')
    {
        // matriz de entrada
        $what = array( 'ä','ã','à','á','â','ê','ë','è','é','ï','ì','í','ö','õ','ò','ó','ô','ü','ù','ú','û','À','Á','É','Í','Ó','Ú','ñ','Ñ','ç','Ç',' ','-','(',')',',',';',':','|','!','"','#','$','%','&','/','=','?','~','^','>','<','ª','º' );
        // matriz de saída
        $by   = array( 'a','a','a','a','a','e','e','e','e','i','i','i','o','o','o','o','o','u','u','u','u','A','A','E','I','O','U','n','N','c','C',$sp,'-',' ',' ',' ',' ',' ',' ',' ',' ',' ','$',' ',' ',' ',' ',' ',' ',' ',' ',' ','a','o' );
        // devolver a string
        return str_replace($what, $by, $string);
    }//Fim do Modúlo
/*------------------------------------------------------------------------------
 *  DESCRICAO: Converte timestamp para data
 *------------------------------------------------------------------------------*/
    public function timestampToDate ($dt=null, $format = 'br') 
    {
        if ($dt != null)
        {
            //$date = date_create();
            //date_timestamp_set($date, $dt);
            //var_dump( $dt);
            if ($format == 'br') 
            {
                //$data = date_format($date, 'd-m-Y');
                $data = date("d/m/Y",$dt);
            }
            elseif ($format == 'en')
            {
                //$data = date_format($date, 'Y-m-d');
                $data = date('Y-m-d', (int) $dt);
            }
            //var_dump($data);
            
            return $data;
        }
        return false;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  DESCRICAO: Converte date para timestamp
 *------------------------------------------------------------------------------*/
    public function dateTo_Timestamp ($dt=null, $format = 'br') 
    {
        if ($dt != null)
        {
            
            $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $dt, new DateTimeZone('America/Sao_Paulo'));
            $timestamp = $dateTime->getTimestamp();
            
            return $timestamp;
        }
        return false;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  DESCRICAO: Converte data para timestamp
 *------------------------------------------------------------------------------*/
    public function dateToTimestamp ($dt=null, $format = 'br', $time = 'N') 
    {
        if ($dt != null)
        {
            if ($this->isValidData($dt) == true)
            {
                //echo $dt;
                $date = date_create();
                date_timestamp_set($date, strtotime($dt));
                //$date = date_timestamp_get($dt);
                return date_timestamp_get($date);
            }
        }
        return false;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *                        Formata a data para padrão dd/mm/YYYY
 *------------------------------------------------------------------------------- */
    public function corrigeData($dat)
    {
        if ($dat==NULL || $dat=="" || strlen($dat)<8) 
        {
            return false;
        }
        $data = (strstr($dat,'/')) ? explode('/',$dat) : explode('-',$dat);
        $d = (int)$data[0];
        $m = (int)$data[1];
        $y = (int)$data[2];
        $dd = str_pad($d, 2, '0', STR_PAD_LEFT);
        $mm = str_pad($m, 2, '0', STR_PAD_LEFT);
        $yy = str_pad($y, 2, '0', STR_PAD_LEFT);            

// checa posicionando os elementos no formato mês dia e ano.
        if (checkdate($m, $d, $y) == TRUE) 
        {
            return $dd."/".$mm."/".$y;
        } 
        elseif (checkdate($d, $m, $y)==TRUE) 
        {
            return $mm."/".$dd."/".$y;
        } 
        elseif (checkdate($m,$y,$d)==TRUE) 
        {
            return $yy."/".$mm."/".$d;
        }
        return false;
    }//Fim Módulo
/*---------------------------------------------------------------
 * Nota: Retorna o dia da Semana
 *---------------------------------------------------------------*/
    public function diaSemana($data) 
    {
    	$dia = date ( "w", strtotime ( $data ) );
    	switch ($dia) {
    		case 0 :
    			return 0;
    			break; // Domingo
    		case 1 :
    			return 1;
    			break; // segunda
    		case 2 :
    			return 2;
    			break; // terça
    		case 3 :
    			return 3;
    			break; // quarta
    		case 4 :
    			return 4;
    			break; // quinta
    		case 5 :
    			return 5;
    			break; // sexta
    		case 6 :
    			return 6;
    			break; // sabado
    	}
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  DESCRICAO: separa, ddd do numero do telefone
 *------------------------------------------------------------------------------*/
    public function formata_fone ($dado=null) 
    {
        if (is_array($dado)) 
        {
            if (strlen($dado['telefone'])>4) 
            {
                $fone = $dado['telefone'];
            } 
            elseif (strlen($dado['celular'])>4) 
            {
                $fone = $dado['celular'];
            } 
            else 
            {
                $ret = array ('ddd'=>00,'fone'=>'00000000');
                return $ret;
            }
        } 
        else 
        {
            $fone = $dado;
        }
        $simbolos = array("(", ")", "-", ".", "[", "]", "_");
        $fone = str_replace($simbolos, "", $fone);
        if (strlen($fone)<9) 
        {
            $ret['ddd'] = "";
            $ret['fone'] = $fone;
        } 
        else 
        {
            $ret['ddd'] = substr($fone,0,2);
            $ret['fone'] = substr($fone,2);
        }
        return $ret;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  DESCRICAO: Pega o dados de um diretório. Retorna uma array com nome e url
 *  
 *------------------------------------------------------------------------------*/
    public function getDiretorio ($path = null, $file = null )
    {
        $path = (empty($path)) ? 'files/backup/' : $path;
        $file = (empty($file)) ? '*.*' : $file;
        $arquivos_pattern = glob($path . $file);
        if(!empty($arquivos_pattern)) 
        {
            $result = array();
            foreach($arquivos_pattern as $arquivo) 
            {
                $result[] = array ('nome'=>$arquivo,'url'=>basename($arquivo));
            }
        }
        else
        {
            return false;
        }
        return $result;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  DESCRICAO: Retorna uma coluna de uma array (para versão php <5.5)
 *  
 *------------------------------------------------------------------------------*/
    public function array_column($input = null, $columnKey = null, $indexKey = null)
    {
        // Using func_get_args() in order to check for proper number of
        // parameters and trigger errors exactly as the built-in array_column()
        // does in PHP 5.5.
        $argc = func_num_args();
        $params = func_get_args();
        if ($argc < 2) {
            trigger_error("array_column() expects at least 2 parameters, {$argc} given", E_USER_WARNING);
            return null;
        }
        if (!is_array($params[0])) {
            trigger_error(
                'array_column() expects parameter 1 to be array, ' . gettype($params[0]) . ' given',
                E_USER_WARNING
            );
            return null;
        }
        if (!is_int($params[1])
            && !is_float($params[1])
            && !is_string($params[1])
            && $params[1] !== null
            && !(is_object($params[1]) && method_exists($params[1], '__toString'))
        ) {
            trigger_error('array_column(): The column key should be either a string or an integer', E_USER_WARNING);
            return false;
        }
        if (isset($params[2])
            && !is_int($params[2])
            && !is_float($params[2])
            && !is_string($params[2])
            && !(is_object($params[2]) && method_exists($params[2], '__toString'))
        ) {
            trigger_error('array_column(): The index key should be either a string or an integer', E_USER_WARNING);
            return false;
        }
        $paramsInput = $params[0];
        $paramsColumnKey = ($params[1] !== null) ? (string) $params[1] : null;
        $paramsIndexKey = null;
        if (isset($params[2])) {
            if (is_float($params[2]) || is_int($params[2])) {
                $paramsIndexKey = (int) $params[2];
            } else {
                $paramsIndexKey = (string) $params[2];
            }
        }
        $resultArray = array();
        foreach ($paramsInput as $row) {
            $key = $value = null;
            $keySet = $valueSet = false;
            if ($paramsIndexKey !== null && array_key_exists($paramsIndexKey, $row)) {
                $keySet = true;
                $key = (string) $row[$paramsIndexKey];
            }
            if ($paramsColumnKey === null) {
                $valueSet = true;
                $value = $row;
            } elseif (is_array($row) && array_key_exists($paramsColumnKey, $row)) {
                $valueSet = true;
                $value = $row[$paramsColumnKey];
            }
            if ($valueSet) {
                if ($keySet) {
                    $resultArray[$key] = $value;
                } else {
                    $resultArray[] = $value;
                }
            }
        }
        return $resultArray;
    }
/*-------------------------------------------------------------------------------
 *   Funçao retorna array meses
 *------------------------------------------------------------------------------- */
    public function lista_meses($param=null)
    {
        $meses = array (1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',7=>'Julho',
                        8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro');
        $meses = (null == $param) ? $meses : $meses[(int)$param];
        return $meses;
    }//Fim do Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array anos iniciando em 2014 e indo até 5 anos após ano atual
 *------------------------------------------------------------------------------- */
    public function lista_anos($param=null)
    {
        $ano = 2014;
        $ret = array();
        for ($ano; $ano<=(date('Y')+5); $ano++)
        {
            $ret[$ano] = (string) $ano;
        }
        return $ret;
    }//Fim do Modúlo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array anos iniciando em 2014 e indo até 5 anos após ano atual
 *------------------------------------------------------------------------------- */
    public function lista_semana($param=null)
    {
        $semana = array(
            '0' => 'Domingo', 
            '1' => 'Segunda-Feira',
            '2' => 'Terca-Feira',
            '3' => 'Quarta-Feira',
            '4' => 'Quinta-Feira',
            '5' => 'Sexta-Feira',
            '6' => 'Sabado'
        );
        return $semana;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array de prioridades
 *------------------------------------------------------------------------------- */
    public function lista_prioridade($param=null)
    {
        $priori  = array(
            '10' => 'recurso',
            '20' => 'trivial',
            '30' => 'texto',
            '40' => 'mínimo',
            '50' => 'pequeno',
            '60' => 'grande',
            '70' => 'travamento',
            '80' => 'obstáculo'
        );
        $priori = (null == $param) ? $priori : $priori[$param];
        return $priori;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array de gravidades
 *------------------------------------------------------------------------------- */
    public function lista_gravidade($param=null)
    {
        $gravid  = array(
            '10' => 'nenhuma',
            '20' => 'baixa',
            '30' => 'normal',
            '40' => 'alta',
            '50' => 'urgente',
            '60' => 'imediato'
        );
        $gravid = (null == $param) ? $gravid : $gravid[$param];
        return $gravid;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array de status de atividade
 *------------------------------------------------------------------------------- */
    public function lista_status_atividade($param=null)
    {
        $status  = array(
            '10' => 'novo',
            '20' => 'retorno',
            '30' => 'recebido',
            '40' => 'em despacho',
            '50' => 'em atendimento',
            '60' => 'resolvido',
            '70' => 'fechado'
        );
        $status = (null == $param) ? $status : $status[$param];
        return $status;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array de acesso
 *------------------------------------------------------------------------------- */
    public function lista_acesso($param=null)
    {
        $status  = array(
            '10' => 'público',
            '50' => 'privado'
        );
        return $status;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array de resoluções
 *------------------------------------------------------------------------------- */
    public function lista_resolucao($param=null)
    {
        $status  = array(
            10=>'aberto',
            20=>'corrigido',
            30=>'reaberto',
            40=>'incapaz de reproduzir',
            50=>'não corrigível',
            60=>'duplicado',
            70=>'não é uma tarefa',
            80=>'suspenso',
            90=>'não será corrigido'
        );
        return $status;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array com os níveis de acesso
 *------------------------------------------------------------------------------- */
    public function lista_nivel_acesso($param=null)
    {
        $ret = array(0=>'VISITADOR',
                    50=>'OPERADOR',
                    80=>'GESTOR',
                    90=>'ADMINISTRADOR',
                   100=>'DESENVOLVEDOR');
        $ret = (null == $param) ? $ret : $ret[$param];
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array com os tipos de incidentes
 *------------------------------------------------------------------------------- */
    public function lista_tipo_incidente($param=null)
    {
        $ret = array(5=>'FINANCEIRO',
                    10=>'ALUNO',
                    15=>'PROFESSOR',
                    20=>'TURMA',
                    25=>'CURSO');
        $ret = (null == $param) ? $ret : $ret[$param];
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array com sim e não
 *------------------------------------------------------------------------------- */
    public function lista_sim_nao($param=null)
    {
        $ret = array(
            'S' => 'SIM', 
            'N' => 'NÃO');
        $ret = (null == $param) ? $ret : $ret[$param];
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array opções de turno
 *------------------------------------------------------------------------------- */
    public function lista_turno($param=null)
    {
        $ret = array(
            'M' => 'MATUTINO', 
            'V' => 'VESPERTINO',
            'N' => 'NOTURNO',
            'I' => 'INTEGRAL');
        $ret = (null == $param) ? $ret : $ret[$param];
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array opções de uso de documentos
 *------------------------------------------------------------------------------- */
    public function lista_tipo_doc($param=null)
    {
        $ret = array(
            'TURMA'     => 'TURMA', 
            'CURSO'     => 'CURSO',
            'ALUNO'     => 'ALUNO',
            'PROFESSOR' => 'PROFESSOR',
            'AULA'      =>'AULA');
        $ret = (null == $param) ? $ret : $ret[$param];
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array opções de turno
 *------------------------------------------------------------------------------- */
    public function lista_tipos_curso($param=null)
    {
        $ret = array(
            'FOR' => 'FORMAÇÃO', 
            'ESP' => 'ESPECIALIZAÇÃO',
            'APE' => 'APERFEIÇOAMENTO',
            'ADA' => 'ADAPTAÇÃO',
            'EST' => 'ESTÁGIO',
            'HAB' => 'HABILITAÇÃO',
            'CAP' => 'CAPACITAÇÃO');
        $ret = (null == $param) ? $ret : $ret[$param];
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array opções de naturezas de cursos
 *------------------------------------------------------------------------------- */
    public function lista_natureza_curso($param=null)
    {
        $ret = array(
            '1' => 'ESPECÍFICO, OPERACIONAL, TÉCNICO E EXTENSÃO OU EQUIVALENTES', 
            '2' => 'SUPERIOR'
            );
        $ret = (null == $param) ? $ret : $ret[$param];
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array opções social moradia
 *------------------------------------------------------------------------------- */
    public function lista_social_moradia($param=null)
    {
        $ret = array(
            '1' => 'SOZINHO', 
            '2' => 'COM OS PAIS',
            '3' => 'COM OS AVÓS',
            '4' => 'COM MINHA ESPOSA E FILHOS',
            '5' => 'COM OUTROS PARENTES',
            '6' => 'COM AMIGOS'
            );
        $ret = (null == $param) ? $ret : $ret[$param];
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array opções social tipo moradia
 *------------------------------------------------------------------------------- */
    public function lista_social_tipo_moradia($param=null)
    {
        $ret = array(
            '1' => 'PRÓPRIA', 
            '2' => 'ALUGADA',
            '3' => 'CEDIDA'
            );
        $ret = (null == $param) ? $ret : $ret[$param];
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array opções social hábito de leitura
 *------------------------------------------------------------------------------- */
    public function lista_social_leitura($param=null)
    {
        $ret = array(
            '1' => 'LEIO NO MÁXIMO 2 LIVROS AO ANO', 
            '2' => 'LEIO ENTRE 2 E 10 LIVROS AO ANO',
            '3' => 'LEIO ENTRE 11 E 20 LIVROS AO ANO',
            '4' => 'LEIO MAIS DE 21 LIVROS AO ANO' 
            );
        $ret = (null == $param) ? $ret : $ret[$param];
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array opções de social plano de saúde
 *------------------------------------------------------------------------------- */
    public function lista_social_plano_saude($param=null)
    {
        $ret = array(
            '1' => 'IPASGO', 
            '2' => 'UNIMED',
            '3' => 'IMAS',
            '4' => 'OUTRO PLANO DE SAÚDE',
            '5' => 'NÃO TENHO PLANO DE SAÚDE'
            );
        $ret = (null == $param) ? $ret : $ret[$param];
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array opções social hábito de leitura
 *------------------------------------------------------------------------------- */
    public function lista_escolaridade($param=null)
    {
        $ret = array(
            '1' => 'SEQUENCIAL', 
            '2' => 'SUPERIOR COMPLETO',
            '3' => 'PÓS-GRADUADO',
            '4' => 'MESTRADO',
            '5' => 'DOUTORADO' 
            );
        $ret = (null == $param) ? $ret : $ret[$param];
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array opções social hábito de leitura
 *------------------------------------------------------------------------------- */
    public function lista_social_esporte($param=null)
    {
        $ret = array(
            '1' => 'FUTEBOL', 
            '2' => 'BASQUETE',
            '3' => 'VOLEI',
            '4' => 'NATAÇÃO',
            '5' => 'KARATE',
            '6' => 'JUI-JITSU',
            '7' => 'CAPOEIRA',
            '8' => 'ATLETISMO',
            '9' => 'MUSCULAÇÃO',
           '10' => 'DANÇA',
           '11' => 'TIRO',
           '12' => 'CORRIDA',
           '13' => 'CICLISMO',
           '14' => 'OUTROS ESPORTES COLETIVOS',
           '15' => 'OUTRAS LUTAS',
           '16' => 'OUTROS ESPORTES DE VELOCIDADE',
           '17' => 'OUTRO ESPORTE NÃO REFERENCIADO',
           '18' => 'NÃO PRATICO ESPORTES' 
            );
        $ret = (null == $param) ? $ret : $ret[$param];
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array com os status dos alunos
 *------------------------------------------------------------------------------- */
    public function lista_status_aluno($param=null)
    {
        $ret = array('EMC'=>'EM CURSO',
                     'DES'=>'DESLIGADO',
                     'NAO'=>'NÃO APRESENTOU'
                     );
        $ret = (null == $param) ? $ret : $ret[$param];
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array com os status Documentos
 *------------------------------------------------------------------------------- */
    public function lista_status_documentacao($param=null)
    {
        $ret = array('S'=>'SIM',
                     'N'=>'NÃO',
                     'I'=>'IRREGULAR'
                     );
        $ret = (null == $param) ? $ret : $ret[$param];
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array com os status dos alunos
 *------------------------------------------------------------------------------- */
    public function lista_resultado_aluno($param=null)
    {
        $ret = array('APR'=>'APROVADO',
                     'REP'=>'REPROVADO',
                     'SOB'=>'SOBRESTADO',
                     'SEG'=>'2º ÉPOCA'
                     );
        $ret = (null == $param) ? $ret : $ret[$param];
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array com os tipos de vinculos dos docentes
 *------------------------------------------------------------------------------- */
    public function lista_vinculo_professor($param=null)
    {
        $ret = array('R'=>'REMUNERADO',
                     'V'=>'VOLUNTÁRIO'
                     );
        $ret = (null == $param) ? $ret : $ret[$param];
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array com os sexos
 *------------------------------------------------------------------------------- */
    public function lista_sexo($param=null)
    {
        $ret = array('M'=>'MASCULINO',
                     'F'=>'FEMININO'
                     );
        $ret = (null == $param) ? $ret : $ret[$param];
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array com estados civis
 *------------------------------------------------------------------------------- */
    public function lista_estadocivil($param=null)
    {
        $ret = array('S'=>'SOLTEIRO',
                     'C'=>'CASADO',
                     'D'=>'DIVORCIADO',
                     'V'=>'VIÚVO',
                     'A'=>'AMAZIADO',
                     'O'=>'OUTROS'
                     );
        $ret = (null == $param) ? $ret : $ret[$param];
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array com tipos de verificações
 *------------------------------------------------------------------------------- */
    public function lista_verificacoes($param=null)
    {
        $ret = array('VU'=>'VERIFICAÇÃO ÚNICA',
                     'V1'=>'1ª VERIFICAÇÃO',
                     'V2'=>'2ª VERIFICAÇÃO',
                     'VF'=>'VERIFICAÇÃO FINAL',
                     'RF'=>'RECUPERAÇÃO FINAL'
                     );
        $ret = (null == $param) ? $ret : $ret[$param];
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array com tipos de provas
 *------------------------------------------------------------------------------- */
    public function lista_tipo_prova($param=null)
    {
        $ret = array('1C'=>'1ª CHAMADA',
                     '2C'=>'2ª CHAMADA',
                     'RC'=>'RECUPERAÇÃO'
                     );
        $ret = (null == $param) ? $ret : $ret[$param];
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array com os status de saúde
 *------------------------------------------------------------------------------- */
    public function lista_status_saude($param=null)
    {
        $ret = array('APT'=>'APTO',
                     'RES'=>'RESTRIÇÃO',
                     'INP'=>'INAPTO',
                     'IRR'=>'IRREGULAR'
                     );
        $ret = (null == $param) ? $ret : $ret[$param];
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array com os status de funcional
 *------------------------------------------------------------------------------- */
    public function lista_status_funcional($param=null)
    {
        $ret = array('ATV'=>'SERVIDOR EFETIVO DA ATIVA',
                     'COM'=>'SERVIDOR COMISSIONADO',
                     'APO'=>'SERVIDOR DA RESERVA/APOSENTADO',
                     );
        $ret = (null == $param) ? $ret : $ret[$param];
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array com os status de funcional
 *------------------------------------------------------------------------------- */
    public function lista_status_prova($param=null)
    {
        $ret = array('P'=>'PRESENTE PARA PROVA',
                     'A'=>'AUSENTE',
                     'J'=>'FALTA JUSTIFICADA (MOTIVO DE FORÇA MAIOR)',
                     'E'=>'FALTA JUSTIFICÁVEL'
                     );
        $ret = (null == $param) ? $ret : $ret[$param];
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array com tipos de verificações
 *------------------------------------------------------------------------------- */
    public function lista_status_aplicacao_prova($param=null)
    {
        $ret = array('AG'=>'AGUARDANDO LIBERAÇÃO',
                     'AP'=>'EM APLICAÇÃO E LANÇAMENTO DE NOTAS',
                     'PE'=>'VERIFICANDO PENDÊNCIAS',
                     'CO'=>'CONCLUÍDA'
                     );
        $ret = (null == $param) ? $ret : $ret[$param];
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna array com os status de geração de documentos
 *------------------------------------------------------------------------------- */
    public function lista_status_controle_geracao($param=null)
    {
        $ret = array(1=>'GERADO',
                     5=>'RETIFICADO',
                     13=>'CANCELADO'
                     );
        $ret = (null == $param) ? $ret : $ret[$param];
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao calculo de tempo hora minuto segundos
 *------------------------------------------------------------------------------- */
    public function tempo_descrito($param=null)
    {
        $hora = (int) ($param / 360);
        $min  = (int) (($param - ($hora * 360))/60);
        $seg  = (int) ($param - (($hora*360)+($min*60)));
        $texto = '';
        if ($hora)
        {
            $texto .= $hora;
            $texto .= ($horas>1) ? " horas" : " horas";
        }
        if ($min)
        {
            $texto .= (strlen($texto)>1) ? ", " : " ";
            $texto .= $min;
            $texto .= ($min>1) ? " minutos" : " minuto"; 
        }
        if ($seg)
        {
            $texto .= (strlen($texto)>1) ? ", " : " ";
            $texto .= $seg;
            $texto .= ($seg>1) ? " segundos" : " segundo "; 
        }
        return $texto;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *   Funçao retorna nível de acesso da classe ($param = classe)
 *------------------------------------------------------------------------------- */
    public function getnivel($param=null)
    {
        $nivel = 0;
        $lista_niveis = TSession::getValue('lista_niveis');
        $lista_niveis = (empty($lista_niveis)) ? array() :$lista_niveis; 
        if (count($lista_niveis) != 0)
        {
            if (array_key_exists($param,$lista_niveis))
            {
                return $lista_niveis[$param];
            }
        }
        
        $groups = explode(",",TSession::getValue('usergroupids'));
        $groups = (!is_array($groups)) ? array('0'=>$groups) : $groups;
        //var_dump($groups);
        //echo $param;
        
        foreach ($groups as $group)
        {
            try
            {
                TTransaction::open('sicad');
                $sql = "SELECT DISTINCT  system_group.name,  system_group.acess,  system_group.id, system_program.controller ".
                        "FROM g_system.system_group, g_system.system_group_program, g_system.system_program ".
                        "WHERE system_group_program.system_group_id = system_group.id AND ".
                          "system_group_program.system_program_id = system_program.id AND ".
                          "system_group.id = " . $group . " AND system_program.controller='".$param."';";
                $conn = TTransaction::get(); 
                $res = $conn->prepare($sql);
                $res->execute();
                $res->setFetchMode(PDO::FETCH_NAMED);
                $resp = $res->fetchAll();
                //echo $sql;
                //var_dump($resp);
                TTransaction::close();
                if (!empty($resp))
                {
                    foreach ($resp as $res)
                    {
                        $nv = $res;
                    }
                }
                else
                {
                    $nv = array('acess'=>0);
                }
                //var_dump($nv);
            }
            catch (Exception $e)
            {
                TTransaction::rollback();
                $nv = array('acess'=>0);
            }
            $nivel = ($nivel>$nv['acess']) ? $nivel : $nv['acess'];
        }
        //echo "Nível para Classe ".$param." é ".$nivel;
        $lista_niveis[$param] = $nivel;
        TSession::setValue('lista_niveis',$lista_niveis);
        return $nivel;
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function getConfig ($param=null)
    {
        $lista_configs = TSession::getValue('lista_configs');
        $lista_configs = (empty($lista_configs)) ? array() :$lista_configs;
        //var_dump($lista_configs);
        if (count($lista_configs) != 0)
        {
            if (array_key_exists($param,$lista_configs))
            {
                return $lista_configs[$param];
            }
        }
        try
        {
            TTransaction::open('sicad');
            $sql = "SELECT DISTINCT * FROM g_geral.configura WHERE dominio='".$param."' AND ativo='S' AND visivel='S';";
            $conn = TTransaction::get(); 
            $res = $conn->prepare($sql);
            $res->execute();
            $res->setFetchMode(PDO::FETCH_NAMED);
            $campos = $res->fetchAll();
            TTransaction::close();
            $ret = array();
            foreach ($campos as $campo)
            {
                $ret[$campo['name']]= $campo['value'];
            }
            if (self::is_dev())
            {
                //var_dump($ret);
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            $ret = ''; // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
        $lista_configs[$param] = $ret;
        TSession::setValue('lista_configs',$lista_configs);
        //var_dump($lista_configs);
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *        Calcula a quantidade de dias do mês
 *-------------------------------------------------------------------------------*/
     public function qntDiasMes ($mes,$ano)
     {
         return date("t", mktime(0, 0, 0, $mes, 01, $ano));
     }//Fim Módulo
/*-------------------------------------------------------------------------------
 *        Calcula a quantidade de dias úteis do mês
 *-------------------------------------------------------------------------------*/
    public function getDiasUteis($mes,$ano)
    {
      $uteis = 0;
      $dias_no_mes = $this->qntDiasMes($mes,$ano);//cal_days_in_month(CAL_GREGORIAN, $mes, $ano); 
      for($dia = 1; $dia <= $dias_no_mes; $dia++)
      {
        // Aqui você pode verifica se tem feriado
        // ----------------------------------------
        // Obtém o timestamp
        // (http://php.net/manual/pt_BR/function.mktime.php)
        $timestamp = mktime(0, 0, 0, $mes, $dia, $ano);
        $semana    = date("N", $timestamp);
        if($semana < 6) $uteis++;
      }
      return $uteis;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *        Retorna a data por extenso
 *-------------------------------------------------------------------------------*/
    public function dataExtenso ($data = null)
    {
        $data = ($data == null) ? date("Y-m-d") : $data;
        setlocale(LC_ALL, 'pt_BR', 'pt_BR.utf-8', 'portuguese');//, 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
        date_default_timezone_set('America/Sao_Paulo');
        $ret = strftime('%d de %B de %Y', strtotime($data));
        return $ret;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *        Monta uma lista de assinantes da OPM
 *-------------------------------------------------------------------------------*/
    public function getAssinantes ($param = null)
    {
        $lista = array('0'=>'--- Não Localizei Militares nesta OPM ---');
        $ativo = 'N';
        if ($param != null)
        {
            try
            {
                TTransaction::open('sicad'); // open a transaction
                $sql = "SELECT DISTINCT servidor.rgmilitar AS rgmilitar, ". 
                                "servidor.postograd || ' ' || servidor.rgmilitar || ' ' || servidor.nome AS nome, ".
                                "item.ordem ".
                        "FROM efetivo.servidor JOIN opmv.item ON servidor.postograd = item.nome ".
                        "WHERE unidadeid IN (".$param.") ";
                if ($ativo =='N')
                {
                    $sql .= "AND status = 'ATIVO' "; 
                }
                $sql .="ORDER BY item.ordem, nome ASC;";
                $conn = TTransaction::get();
                $res = $conn->prepare($sql);
                $res->execute();
                $militares = $res->fetchAll(PDO::FETCH_NAMED);
                //var_dump($militares);
                $lista = array();
                foreach ($militares as $militar)
                {
                    $lista[$militar['rgmilitar']] = $militar['nome'];
                }
                TTransaction::close();   
            }
            catch (Exception $e) // in case of exception
            {
                //new TMessage('error', $e->getMessage()); // shows the exception error message
                TTransaction::rollback(); // undo all pending operations
            }
        }
        return $lista;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *        Monta uma assinatura para relatórios
 *-------------------------------------------------------------------------------*/
    public function getAssinatura($param=null)
    {
        $assina = "<br><br><center>";
        $ambiente = TSession::getValue('ambiente');
        $profile = TSession::getValue('profile');
        if ($ambiente != 'local' || $param != null)
        {
            if ($param == null) // Continua a pegar a assinatura do usuário atual
            {
                $assina .= $profile['nome']." - ";
                try
                {
                    TTransaction::open('sicad');
                    $militar = servidor::where('rgmilitar','=',$profile['rg'])->load();//Busca dados do militar
                    TTransaction::close();
                    $posto = (array_key_exists(0,$militar)) ? $militar[0]->postograd : "";
                    $opm = false;
                    if (array_key_exists('unidade',$profile))
                    {
                        $opm = $profile['unidade']['nome'];
                    }
                }
                catch (Exception $e) // in case of exception
                {
                    //new TMessage('error', $e->getMessage()); // shows the exception error message
                    $posto = ''; // keep form data
                    TTransaction::rollback(); // undo all pending operations
                }
                $local   = '<br><br><p style="text-align: right;">';
                $local  .= ($opm!=false) ? $opm.',' : '';
                $local  .= $this->dataExtenso()."</p>";
                $assina .= $posto." RG ".$profile['rg'];
                $assina .= "<br>".$profile['funcao'];
            }
            else   //Usa a assinatura do usuário indicado por $param
            {
                try
                {
                    TTransaction::open('sicad');
                    if (strlen($param)== 11 )
                    {
                        $militar = servidor::where('cpf','=',$param)->load();//Busca dados do militar
                    }
                    else
                    {
                        $militar = servidor::where('rgmilitar','=',$param)->load();//Busca dados do militar
                    }
                    if (!empty($militar))
                    {
                        $assina    .= (!empty($militar[0]->nome))      ? $militar[0]->nome              : '- NC -';
                        $posto      = (!empty($militar[0]->postograd)) ? $militar[0]->postograd : '';
                        $rgmilitar  = (!empty($militar[0]->rgmilitar)) ? $militar[0]->rgmilitar         : '';
                        $opm        = (!empty($militar[0]->postograd)) ? $militar[0]->unidade           : '';
                        $funcao     = (!empty($militar[0]->funcao))    ? $militar[0]->funcao            : 'RELATOR';
                    }
                    TTransaction::close();
                }
                catch (Exception $e) // in case of exception
                {
                    //new TMessage('error', $e->getMessage()); // shows the exception error message
                    $posto      = '';
                    $rgmilitar  = '';
                    $opm        = false;
                    $funcao     = '';
                    TTransaction::rollback(); // undo all pending operations
                }
                $local   = '<br><br><p style="text-align: right;">';
                $local  .= ($opm != false) ? $opm.',' : '';
                $local  .= $this->dataExtenso()."</p>";
                $assina .= ((!empty($posto)) ? " - " : '') . $posto . ((!empty($rgmilitar)) ? " RG " : '') . $rgmilitar;
                $assina .= "<br>".$funcao;
            }
        }
        else
        {
            $local   = '<br><br><p style="text-align: right;">';
            $local  .= $this->dataExtenso()."</p>";
            $assina .= TSession::getValue('username')."<br>Relator";

        }
        $assina = $local . $assina;        
        return $assina."</center>";

        
    }//Fim Módulo
/*---------------------------------------------------------------
 * Nota: Cria lista de todos feriados
 *---------------------------------------------------------------*/
    public function getFeriado($ano,$opm = null) 
    {
        //$ano = substr ( $data, 6, 4 ) ;
    	//$Fer = $conf->busca ( "SELECT bdh_feriado_descricao FROM bdhoras.bdh_feriado WHERE bdh_feriado_dia_mes='" . substr ( $data, 0, 2 ) . "/" . substr ( $data, 3, 2 ) . "';" );
        if (empty($this->feriados) || $this->feriados==false)
        {
            //var_dump($this->feriados);
            $feriados = array();
            //Feriados de data móvel
            $feriados [] = $this->dataCarnaval($ano);
            $feriados [] = $this->dataPascoa($ano);
            $feriados [] = $this->dataSextaSanta($ano);
            $feriados [] = $this->dataCorpusChristi($ano);
            try//Busca Feriados Nacionais
            { 
                TTransaction::open('sicad');
                $results = feriado::where ('tipo','=','NACIONAL')->load();
                foreach ($results as $result)//Cria lista de Feriados Nacionais
                {
                    if($result->dataferiado)
                    {
                        $feriados[] = $result->dataferiado.'/'.$ano;
                    }
                }
                TTransaction::close();
            } 
            catch (Exception $e) 
            { 
                TTransaction::rollback();
            }
            //Busca Feriados exclusivos da OPM, se esta for definida
            if($opm!=null)
            {
                try
                {
                    TTransaction::open('sicad');
                    $conn = TTransaction::get();
                    $sql = "SELECT DISTINCT dataferiado FROM bdhoras.feriado, bdhoras.feriadoopm ".
                                "WHERE feriadoopm.feriado_id = feriado.id AND (feriado.tipo = 'MUNICIPAL' OR ".
                                " feriado.tipo = 'INSTITUCIONAL') AND feriadoopm.opm_id = ".(int) $opm.";";
                    $feriados_opm = $conn->prepare($sql);
                    $feriados_opm->execute();
                    $results = $feriados_opm->fetchAll();
                    foreach ($results as $result)//Acrescenta os feriados municipais
                    {
                         $feriados[] = $result['dataferiado'].'/'.$ano;   
                    }
                    TTransaction::close();
                } 
                catch (Exception $e) 
                { 
                    TTransaction::rollback();
                }
            }
            $this->feriados = $feriados;
        }
        
        return $this->feriados;
    }//Fim Módulo
/*----------------------------------------------------------------------------------------
 * Nota: Rotinas de Calculo de Data Móvel
 *----------------------------------------------------------------------------------------
 dataPascoa(ano, formato);
 Autor: Yuri Vecchi

 Funcao para o calculo da Pascoa
 Retorna o dia da pascoa no formato desejado ou false.

 ######################ATENCAO###########################
 Esta funcao sofre das limitacoes de data de mktime()!!!
 ########################################################

 Possui dois parametros, ambos opcionais
 ano = ano com quatro digitos
	 Padrao: ano atual
 formato = formatacao da funcao date() http://br.php.net/date
	 Padrao: d/m/Y
 *----------------------------------------------------------------------------------------*/
/*----------------------------------------------------------------------------------------
 * Nota: Pascoa
 *----------------------------------------------------------------------------------------*/
    public function dataPascoa($ano=false, $form="d/m/Y") 
    {
    	if ($this->pascoa)
    	{
            return $this->pascoa; 
        }
    	$ano=($ano) ? $ano :date("Y");
    	if ($ano<1583) 
    	{ 
    		$A = ($ano % 4);
    		$B = ($ano % 7);
    		$C = ($ano % 19);
    		$D = ((19 * $C + 15) % 30);
    		$E = ((2 * $A + 4 * $B - $D + 34) % 7);
    		$F = (int)(($D + $E + 114) / 31);
    		$G = (($D + $E + 114) % 31) + 1;
    		$ret = date($form, mktime(0,0,0,$F,$G,$ano));
    	}
    	else 
    	{
    		$A = ($ano % 19);
    		$B = (int)($ano / 100);
    		$C = ($ano % 100);
    		$D = (int)($B / 4);
    		$E = ($B % 4);
    		$F = (int)(($B + 8) / 25);
    		$G = (int)(($B - $F + 1) / 3);
    		$H = ((19 * $A + $B - $D - $G + 15) % 30);
    		$I = (int)($C / 4);
    		$K = ($C % 4);
    		$L = ((32 + 2 * $E + 2 * $I - $H - $K) % 7);
    		$M = (int)(($A + 11 * $H + 22 * $L) / 451);
    		$P = (int)(($H + $L - 7 * $M + 114) / 31);
    		$Q = (($H + $L - 7 * $M + 114) % 31) + 1;
    		$ret = date($form, mktime(0,0,0,$P,$Q,$ano));
    	}
    	$this->pascoa = $ret;
    	return $ret;
    }//Fim Módulo
/*----------------------------------------------------------------------------------------
 * Nota: Carnaval
 *----------------------------------------------------------------------------------------
 dataCarnaval(ano, formato);
 Autor: Yuri Vecchi

 Funcao para o calculo do Carnaval
 Retorna o dia do Carnaval no formato desejado ou false.

 ######################ATENCAO###########################
 Esta funcao sofre das limitacoes de data de mktime()!!!
 ########################################################

 Possui dois parametros, ambos opcionais
 ano = ano com quatro digitos
	 Padrao: ano atual
 formato = formatacao da funcao date() http://br.php.net/date
	 Padrao: d/m/Y
 *----------------------------------------------------------------------------------------*/
    public function dataCarnaval($ano=false, $form="d/m/Y") 
    {
    	if ($this->carnaval)
    	{
            return $this->carnaval; 
        } 
    	$ano=($ano) ? $ano :date("Y");
    	$a=explode("/", self::dataPascoa($ano));
    	$this->carnaval = date($form, mktime(0,0,0,$a[1],$a[0]-47,$a[2]));
    	return $this->carnaval;
    }// Fim do Módulo
/*----------------------------------------------------------------------------------------
 * Nota: Corpus Christi
 *----------------------------------------------------------------------------------------
    // dataCorpusChristi(ano, formato);
    // Autor: Yuri Vecchi
    //
    // Funcao para o calculo do Corpus Christi
    // Retorna o dia do Corpus Christi no formato desejado ou false.
    //
    // ######################ATENCAO###########################
    // Esta funcao sofre das limitacoes de data de mktime()!!!
    // ########################################################
    //
    // Possui dois parametros, ambos opcionais
    // ano = ano com quatro digitos
    //	 Padrao: ano atual
    // formato = formatacao da funcao date() http://br.php.net/date
    //	 Padrao: d/m/Y
 *----------------------------------------------------------------------------------------*/
    public function dataCorpusChristi($ano=false, $form="d/m/Y") 
    {
    	if ($this->corpus)
    	{
         return $this->corpus;
        }
    	$ano=($ano) ? $ano :date("Y");
    	$a=explode("/", self::dataPascoa($ano));
    	$this->corpus = date($form, mktime(0,0,0,$a[1],$a[0]+60,$a[2]));
    	return $this->corpus;
    }//Fim Módulo
    
/*----------------------------------------------------------------------------------------
 * Nota: Sexta Feira Santa
 *----------------------------------------------------------------------------------------
    // dataSextaSanta(ano, formato);
    // Autor: Yuri Vecchi
    //
    // Funcao para o calculo da Sexta-feira santa ou da Paixao.
    // Retorna o dia da Sexta-feira santa ou da Paixao no formato desejado ou false.
    //
    // ######################ATENCAO###########################
    // Esta funcao sofre das limitacoes de data de mktime()!!!
    // ########################################################
    //
    // Possui dois parametros, ambos opcionais
    // ano = ano com quatro digitos
    // Padrao: ano atual
    // formato = formatacao da funcao date() http://br.php.net/date
    // Padrao: d/m/Y
 *----------------------------------------------------------------------------------------*/
    public function dataSextaSanta($ano=false, $form="d/m/Y") 
    {
    	if ($this->sextasanta)
    	{
            return $this->sextasanta;
        }
    	$ano=($ano) ? $ano :date("Y");
    	$a=explode("/", self::dataPascoa($ano));
    	$this->sextasanta = date($form, mktime(0,0,0,$a[1],$a[0]-2,$a[2]));
    	return $this->sextasanta;
    } //Fim Módulo
/*----------------------------------------------------------------------------------------
 * Nota: csv_in_array
 *----------------------------------------------------------------------------------------
fuction call with 4 parameters:

(1) = the file with CSV data (url / string)
(2) = colum delimiter (e.g: ; or | or , ...)
(3) = values enclosed by (e.g: ' or " or ^ or ...)
(4) = with or without 1st row = head (true/false)

// ----- call ------
$csvdata = csv_in_array( $yourcsvfile, ";", "\"", true ); 
 *----------------------------------------------------------------------------------------*/
    public function csv_in_array($url,$delm=";",$encl="\"",$head=false) 
    {
       
        $csvxrow = file($url);   // ---- csv rows to array ----
       
        $csvxrow[0] = chop($csvxrow[0]);
        $csvxrow[0] = str_replace($encl,'',$csvxrow[0]);
        $keydata = explode($delm,$csvxrow[0]);
        $keynumb = count($keydata);
        $keydata = array_change_key_case($keydata,CASE_UPPER);
        
       
        if ($head === true) 
        {
            $anzdata = count($csvxrow);//Quantidade de linhas no arquivo
            $z=0;
            for($x=1; $x<$anzdata; $x++) 
            {
                $csvxrow[$x]  = chop($csvxrow[$x]);
                $csvxrow[$x]  = str_replace($encl,'',$csvxrow[$x]);
                $csv_data[$x] = explode($delm,$csvxrow[$x]);
                $i=0;
                foreach($keydata as $key) 
                {
                    if (isset($csv_data[$x][$i]))
                    {
                        //$out[$z][$key] = iconv(mb_detect_encoding($csv_data[$x][$i]), "UTF-8//IGNORE", $csv_data[$x][$i]);
                        $out[$z][$key] = mb_strtoupper($this->ConvertToUTF8($csv_data[$x][$i],'UTF8'));
                    }
                    $i++;
                }   
                $z++;
            }
        }
        else 
        {
            $i=0;
            foreach($csvxrow as $item) 
            {
                $item = chop($item);
                $item = str_replace($encl,'',$item);
                $csv_data = explode($delm,$item);
                for ($y=0; $y<$keynumb; $y++) 
                {
                   $out[$i][$y] = mb_strtoupper($this->ConvertToUTF8($csv_data[$y]),'UTF8');
                }
                $i++;
            }
        }
    
    return array_change_key_case($out,CASE_UPPER);
    }//Fim do Módulo
/*----------------------------------------------------------------------------------------
 * Nota: Conversor de caracteres para UTF8
 *----------------------------------------------------------------------------------------*/
    public function ConvertToUTF8($text)
    {
        $encoding = mb_detect_encoding($text.'x', mb_detect_order(), false);
        //$text = $this->removeAcentos($text);
        if($encoding == "UTF-8")
        {
            //echo 'UTF8 -- ' . $text . '<br>';
            $text = mb_convert_encoding(utf8_encode($text), 'UTF-8', 'ISO-8859-1');
            $text = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', utf8_decode($text));
            //echo 'UTF8 -- Convertifo ' . $text . '<br>';


        }
        else if ($encoding == 'ISO-8859-1')
        {
            $text = mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
            //echo 'ISO -- ' . $text;
        }
        else if ($encoding == 'ASCII')
        {
            $text = mb_convert_encoding($text, "UTF-8");
            //echo 'ASCII -- ' . $text;
        }
        else
        {
            //echo '<br><br>' .$encoding .' <br><br>';
        }
        $out = iconv(mb_detect_encoding($text.'x', mb_detect_order(), false), "UTF-8//TRANSLIT//IGNORE", $text);
        return $out;
    }//Fim Módulo
/*----------------------------------------------------------------------------------------
 * Nota: Gerador de Senhas automática
 *----------------------------------------------------------------------------------------*/
    public function gerar_senha($tamanho = 8, $maiusculas = true, $minusculas = true, $numeros = true, $simbolos = true)
    {
      $ma = "ABCDEFGHIJKLMNOPQRSTUVYXWZ"; // $ma contem as letras maiúsculas
      $mi = "abcdefghijklmnopqrstuvyxwz"; // $mi contem as letras minusculas
      $nu = "0123456789"; // $nu contem os números
      $si = "!@#$%¨&*()_+="; // $si contem os símbolos
      $senha = '';
      
      if ($maiusculas){
            // se $maiusculas for "true", a variável $ma é embaralhada e adicionada para a variável $senha
            $senha .= str_shuffle($ma);
      }
     
        if ($minusculas){
            // se $minusculas for "true", a variável $mi é embaralhada e adicionada para a variável $senha
            $senha .= str_shuffle($mi);
        }
     
        if ($numeros){
            // se $numeros for "true", a variável $nu é embaralhada e adicionada para a variável $senha
            $senha .= str_shuffle($nu);
        }
     
        if ($simbolos){
            // se $simbolos for "true", a variável $si é embaralhada e adicionada para a variável $senha
            $senha .= str_shuffle($si);
        }
     
        // retorna a senha embaralhada com "str_shuffle" com o tamanho definido pela variável $tamanho
        return substr(str_shuffle($senha),0,$tamanho);
    }//Fim Módulo

    public function get_Profile ($param = null)
    {
        if ($param = null || $param == '77644620125')
        {
            $items = '{"dtCadastro":"2014-03-24","perfis":['.
                     '{"id":41,"sistema":{"id":61,"descricao":"SIEI"},"descricao":"Consulta"},'.
                     '{"id":230,"sistema":{"id":43,"descricao":"sigu"},"descricao":"ADM_PM","restrito":true},'.
                     '{"id":252,"sistema":{"id":162,"descricao":"SRO"},"descricao":"CONSULTA"},'.
                     '{"id":1,"sistema":{"id":1,"descricao":"mportal"},"descricao":"Consulta"},'.
                     '{"id":248,"sistema":{"id":21,"descricao":"geocontrol"},"descricao":"ADM_SAC"},'.
                     '{"id":508,"sistema":{"id":142,"descricao":"gescop"},"descricao":"CONSULTA"},'.
                     '{"id":710,"sistema":{"id":426,"descricao":"geocontrol2"},"descricao":"DESPACHANTE"},'.
                     '{"id":708,"sistema":{"id":426,"descricao":"geocontrol2"},"descricao":"ADM","restrito":false},'.
                     '{"id":550,"sistema":{"id":362,"descricao":"atendimento"},"descricao":"ATENDENTE"},'.
                     '{"id":587,"sistema":{"id":364,"descricao":"legado"},"descricao":"BASICO"},'.
                     '{"id":552,"sistema":{"id":364,"descricao":"legado"},"descricao":"ADM","restrito":true},'.
                     '{"id":568,"sistema":{"id":322,"descricao":"despacho"},"descricao":"DESPACHANTE"},'.
                     '{"id":608,"sistema":{"id":386,"descricao":"SISFREAP"},"descricao":"ADM","restrito":false},'.
                     '{"id":1048,"sistema":{"id":606,"descricao":"sisopm"},"descricao":"ADMINISTRADOR","restrito":false},'.
                     '{"id":748,"sistema":{"id":446,"descricao":"SISVTR"},"descricao":"ADM","restrito":false},'.
                     '{"id":754,"sistema":{"id":448,"descricao":"SISPAT"},"descricao":"CARACTERIZADOR","restrito":false},'.
                     '{"id":750,"sistema":{"id":448,"descricao":"SISPAT"},"descricao":"ADM","restrito":false},'.
                     '{"id":554,"sistema":{"id":262,"descricao":"escala"},"descricao":"ESCALADOR"},'.
                     '{"id":571,"sistema":{"id":365,"descricao":"detran"},"descricao":"ESCRIVAO_DERFRVA"},'.
                     '{"id":569,"sistema":{"id":365,"descricao":"detran"},"descricao":"ATENDIMENTO"},'.
                     '{"id":894,"sistema":{"id":508,"descricao":"BI"},"descricao":"PENTAHO","restrito":false},'.
                     '{"id":892,"sistema":{"id":508,"descricao":"BI"},"descricao":"PAINEL_ESTRATEGICO","restrito":false},'.
                     '{"id":988,"sistema":{"id":546,"descricao":"SICAD"},"descricao":"CONSULTA","restrito":false}],'.
                     '"rg":"30089","corporacao":"PM","administrador":false,"id":847,"funcao":"CHEFE DA SEÇÃO DE DESENVOLVIMENTO - DTIC - CALTI",'.
                     '"email":"o.megapinho@gmail.com","telefone":"(62)9244-7470","dtExtincao":"2017-04-30",'.
                     '"nome":"FERNANDO DE PINHO ARAUJO","cpf":"77644620125","login":"77644620125",'.
                     '"unidade":{"id":94158,"corporacaoId":4,"sigla":"CALTI","nome":"COMANDO DE APOIO LOGÍSTICO E TECNOLÓGIA DA INFORMAÇÃO",'.
                     '"corporacao":"PM"}} ';
        }
        else
        {
            $items = '{"dtCadastro":"2014-03-24","perfis":['.
                     '{"id":41,"sistema":{"id":61,"descricao":"SIEI"},"descricao":"Consulta"},'.
                     '{"id":230,"sistema":{"id":43,"descricao":"sigu"},"descricao":"ADM_PM","restrito":true},'.
                     '{"id":252,"sistema":{"id":162,"descricao":"SRO"},"descricao":"CONSULTA"},'.
                     '{"id":1,"sistema":{"id":1,"descricao":"mportal"},"descricao":"Consulta"},'.
                     '{"id":248,"sistema":{"id":21,"descricao":"geocontrol"},"descricao":"ADM_SAC"},'.
                     '{"id":508,"sistema":{"id":142,"descricao":"gescop"},"descricao":"CONSULTA"},'.
                     '{"id":710,"sistema":{"id":426,"descricao":"geocontrol2"},"descricao":"DESPACHANTE"},'.
                     '{"id":708,"sistema":{"id":426,"descricao":"geocontrol2"},"descricao":"ADM","restrito":false},'.
                     '{"id":550,"sistema":{"id":362,"descricao":"atendimento"},"descricao":"ATENDENTE"},'.
                     '{"id":587,"sistema":{"id":364,"descricao":"legado"},"descricao":"BASICO"},'.
                     '{"id":552,"sistema":{"id":364,"descricao":"legado"},"descricao":"ADM","restrito":true},'.
                     '{"id":568,"sistema":{"id":322,"descricao":"despacho"},"descricao":"DESPACHANTE"},'.
                     '{"id":608,"sistema":{"id":386,"descricao":"SISFREAP"},"descricao":"ADM","restrito":false},'.
                     '{"id":1048,"sistema":{"id":606,"descricao":"sisopm"},"descricao":"ADMINISTRADOR","restrito":false},'.
                     '{"id":748,"sistema":{"id":446,"descricao":"SISVTR"},"descricao":"ADM","restrito":false},'.
                     '{"id":754,"sistema":{"id":448,"descricao":"SISPAT"},"descricao":"CARACTERIZADOR","restrito":false},'.
                     '{"id":750,"sistema":{"id":448,"descricao":"SISPAT"},"descricao":"ADM","restrito":false},'.
                     '{"id":554,"sistema":{"id":262,"descricao":"escala"},"descricao":"ESCALADOR"},'.
                     '{"id":571,"sistema":{"id":365,"descricao":"detran"},"descricao":"ESCRIVAO_DERFRVA"},'.
                     '{"id":569,"sistema":{"id":365,"descricao":"detran"},"descricao":"ATENDIMENTO"},'.
                     '{"id":894,"sistema":{"id":508,"descricao":"BI"},"descricao":"PENTAHO","restrito":false},'.
                     '{"id":892,"sistema":{"id":508,"descricao":"BI"},"descricao":"PAINEL_ESTRATEGICO","restrito":false},'.
                     '{"id":988,"sistema":{"id":546,"descricao":"SICAD"},"descricao":"CONSULTA","restrito":false}],'.
                     '"rg":"30089","corporacao":"PM","administrador":false,"id":847,"funcao":"CHEFE DA SEÇÃO DE DESENVOLVIMENTO - DTIC - CALTI",'.
                     '"email":"o.megapinho@gmail.com","telefone":"(62)9244-7470","dtExtincao":"2017-04-30",'.
                     '"nome":"ANGELA DE LOURDES REZENDE E ARAUJO","cpf":"65629680110","login":"65629680110",'.
                     '"unidade":{"id":16788,"corporacaoId":4,"sigla":"05ºBPM(06ºCRPM)","nome":"05º BATALHÃO DE POLÍCIA MILITAR - 05ºBPM(06ºCRPM)",'.
                     '"corporacao":"PM"}} ';
        }
        $ci = new TSicadDados();
        return $ci->object_to_array(json_decode ($items));
        
    }//Fim Módulo
 /*-------------------------------------------------------------------------------
  *        Executor de querys
  * @ $sql deve ser uma query preparada para ser aplicada
  *-------------------------------------------------------------------------------*/
     public function runQuery($sql)
     {
        try
        {
            TTransaction::open('sisacad');
            $conn = TTransaction::get();
            $res = $conn->prepare($sql);
            $res->execute();
            $retorno = $res->fetchAll(PDO::FETCH_NAMED);
            TTransaction::close();
            return $retorno;
        }
        catch (Exception $e) 
        { 
            new TMessage('error', $e->getMessage().'<br>Erro ao buscar dados.<br>'.$sql); 
            TTransaction::rollback();
            return false;
        }
    }//Fim Módulo
 /*-------------------------------------------------------------------------------
  *        Verifica qual status do usuário em um sistema
  * 
  *-------------------------------------------------------------------------------*/
    public function Usuario_Acess_System($param)
    {
        $sql = "SELECT system_group.name FROM g_system.system_user_group, ".
               "g_system.system_group, g_system.system_user ".
               "WHERE system_user_group.system_user_id = system_user.id AND ".
               "system_group.id = system_user_group.system_group_id AND ".
               "system_user.id = ". TSession::getValue('userid'). " AND system_group.system_id = " . $param['sistema'] . " AND ".
               "system_group.acess > " . $param['acess'];
        $ret = $this->runQuery($sql);
        
        return (!empty($ret)) ? true : false; 
    }//Fim Módulo
 /*-------------------------------------------------------------------------------
  *        Mascarador
  * 
  *-------------------------------------------------------------------------------*/
    public function mascara($val, $mask)
    {
         $maskared = '';
    	 $k = 0;
    	 for($i = 0; $i<=strlen($mask)-1; $i++)
    	 {
        	 if($mask[$i] == '#')
        	 {
            	 if(isset($val[$k]))
            	 {
            	     $maskared .= $val[$k++];
            	 }
        	 }
        	 else
        	 {
        	     if(isset($mask[$i]))
        	     {
        	         $maskared .= $mask[$i];
        	     }
        	 }
         }
    	 return $maskared;
    }
 /*-------------------------------------------------------------------------------
  *        Removedor de Letras, deixa somente números
  *-------------------------------------------------------------------------------*/
    public function soNumeros($param)
    {
        return preg_replace("/[^0-9]/", "", $param);
    }//Fim Módulo 
 /*-------------------------------------------------------------------------------
  *        Gerador de tabela
  * Gera uma tabela HTML com os registros do resultado de uma consulta SQL
  * @author Rafael Wendel Pinheiro     - Criador da idéia
  * @author Fernanando de Pinho Araújo - Modificações
  * @param $rows    = array com resultados (0=>array(cel,cel,cel,...),1=>(cel,cel...))
  * @param $headers = array com os cabeçalhos da tabela
  * @return $s = Tabela
  *-------------------------------------------------------------------------------*/
    public function geraTabelaHTML($rows, $headers, $stylus = array())
    {
        //Define os complementos de cada tab html
        $tab  = (isset($stylus['tab']))  ? $stylus['tab']  : '';//Para Tabela
        $cab  = (isset($stylus['cab']))  ? $stylus['cab']  : '';//Para o cabecalho da tabela
        $lnh  = (isset($stylus['row']))  ? $stylus['row']  : '';//Para linhas da tabela
        $cel  = (isset($stylus['cell'])) ? $stylus['cell'] : '';//Para Celulas da tabela

        $s = '';
        $s .= '<table class="tabela" cellspacing="0" cellpadding="0" ' . $tab . '>';
        $s .= '<tr class="tabela_titulo" ' . $cab . '>';
        foreach ($headers as $header)//Inclui o cabeçalho da tabela
        {
            $s .=  '<td class="tabela_cabecalho" ' . $cel. '>' . $header . '</td>';
        }
         
        $s .= '</tr>';		  
        foreach ($rows as $row)//inclui as linhas e celulas da tabela
        {
            $s .= '<tr  class="tabela_linha" ' . $lnh . '>';
            foreach ($row as $cell)
            {
                $s .=  '<td  class="tabela_celula" ' . $cel . '>' . $cell . '</td>';
            }		  
            $s .= '</tr>';		  		  
        }
         
        $s .= '</table>';	  
         
        return $s;
    }
 /*-------------------------------------------------------------------------------
  *        Gerador de Lista
  * Gera uma Lista HTML com os registros do resultado de uma consulta SQL
  * @author Fernanando de Pinho Araújo 
  * @param $rows    = array com resultados (0=>array(cel,cel,cel,...),1=>(cel,cel...))
  * @param $headers = array com os cabeçalhos da tabela
  * @return $s = Tabela
  *-------------------------------------------------------------------------------*/
    public function geraListaHTML($rows, $stylus = array())
    {
        //Define os complementos de cada tab html
        $lst  = (isset($stylus['lst']))  ? $stylus['lst']  : '';//Para Tabela
        $itm  = (isset($stylus['itm']))  ? $stylus['itm']  : '';//Para o cabecalho da tabela

        $s = '';
        $s .= "<ul class='lista' $lst>";
        foreach ($rows as $row)//Inclui itens
        {
            $s .=  "<li class='lista_item' $itm>$row</li>";
        }

        $s .= "</ul>";	  
         
        return $s;
    }//Fim Módulo
 /*-------------------------------------------------------------------------------
  *        Formata numero para monetário BR,EUA
  *-------------------------------------------------------------------------------*/
    public function formataDinheiro($param, $format = "BR")
    {
        switch ($format)
        {
            case "BR":
                return 'R$' . number_format($param, 2, ',', '.');
                break;
            case "EU":
                return 'US$' . number_format($param, 2, '.', ',');
                break;
        }
        return 'R$' . number_format($param, 2, ',', '.');
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Busca código de característica
 *------------------------------------------------------------------------------*/
    public function codigoCaracteristica ($param,$codigo )
    {
        $sicad       = new TSicadDados();
        $param       = substr(strtoupper($param),0,strlen($param)-1);           //Deixa tudo em caixa alta
        $dados_sicad = $sicad->caracteristicas_SICAD($codigo);                  //Busca tabela de características
        $key         = array_search($param, $dados_sicad); // $key = 2;
        $key = ($key != false) ? $key : null; 
        return $key;        
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Busca código de característica Tipo Sangue
 *------------------------------------------------------------------------------*/
    public function codigoSangue ($param, $codigo )
    {
        $param       = strtoupper($param);                  //Deixa tudo em caixa alta
        $key = false;
        if ($codigo = 'sangue')
        {
            if (strpos ($param,'AB') === 0)
            {
                $key = 'AB';
            }
            else if (strpos ($param,'O') === 0)
            {
                $key = 'O';
            }
            else if (strpos ($param,'A') === 0)
            {
                $key = 'A';
            }
            
        }
        else if ($codigo = 'fatorrh')
        {
            if (strpos ($param,'POSITIV') > 0 || strpos ($param,'+'))
            {
                $key = 'POSITIVO';
            }
            else if (strpos ($param,'NEGATIV') > 0 || strpos ($param,'-'))
            {
                $key = 'NEGATIVO';
            }
        }
        $key = ($key != false) ? $key : null; 
        return $key;        
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Busca corrigir altura e peso
 *------------------------------------------------------------------------------*/
    public function alturaPeso ($param = null,$codigo )
    {
        if (empty($param)) 
        {
            return 0;
        }
        $param      = $this->soNumeros($param);
        if (strlen($param)<3 && $codigo == 'altura')
        {
            $param .= '0';
        }
        $param = ($codigo == 'altura')? $param/100 : $param;
        return $param;        
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Retorna Array com ids dos usuários de uma dado Sistema
 *------------------------------------------------------------------------------*/
    public function getIdsDestino ($param)
    {
        
        $sql1 = "(SELECT id FROM opmv.item WHERE nome = '" . $param['sistema'] . "')";
        $sql2 = '(SELECT id FROM g_system.system_group WHERE system_id IN ' . $sql1 . ')';
        $sql3 = '(SELECT system_user_id FROM g_system.system_user_group WHERE system_group_id IN ' . $sql2 . ')';
        $sql4 = 'SELECT id FROM g_system.system_user WHERE id IN ' . $sql3 ;
        $rets = $this->runQuery($sql4);
        if ($rets)
        {
            $lista = array();
            foreach($rets as $ret)
            {
                $lista[] = ($ret) ? implode(',',$ret)  : '';
            } 
        }
        //$lista = '(' . implode(',',$lista) .')';
        //var_dump($lista);
        return $lista;        
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Verifica o tipo de data e converte para gravar no BD
 *------------------------------------------------------------------------------*/
    public function confereData ($date)
    {
        //echo $date;
        $date = str_replace('/','-',$date);
        
        if (strpos($date,'-') == 4)
        {
            return $date;
        }
        return TDate::date2us($date);
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   converte Número e sua versão por extenso
 *------------------------------------------------------------------------------*/
     public function numeroExtenso($number) {
    
        $hyphen      = '-';
        $conjunction = ' e ';
        $separator   = ', ';
        $negative    = 'menos ';
        $decimal     = ' ponto ';
        $dictionary  = array(
            0                   => 'zero',
            1                   => 'um',
            2                   => 'dois',
            3                   => 'três',
            4                   => 'quatro',
            5                   => 'cinco',
            6                   => 'seis',
            7                   => 'sete',
            8                   => 'oito',
            9                   => 'nove',
            10                  => 'dez',
            11                  => 'onze',
            12                  => 'doze',
            13                  => 'treze',
            14                  => 'quatorze',
            15                  => 'quinze',
            16                  => 'dezesseis',
            17                  => 'dezessete',
            18                  => 'dezoito',
            19                  => 'dezenove',
            20                  => 'vinte',
            30                  => 'trinta',
            40                  => 'quarenta',
            50                  => 'cinquenta',
            60                  => 'sessenta',
            70                  => 'setenta',
            80                  => 'oitenta',
            90                  => 'noventa',
            100                 => 'cento',
            200                 => 'duzentos',
            300                 => 'trezentos',
            400                 => 'quatrocentos',
            500                 => 'quinhentos',
            600                 => 'seiscentos',
            700                 => 'setecentos',
            800                 => 'oitocentos',
            900                 => 'novecentos',
            1000                => 'mil',
            1000000             => array('milhão', 'milhões'),
            1000000000          => array('bilhão', 'bilhões'),
            1000000000000       => array('trilhão', 'trilhões'),
            1000000000000000    => array('quatrilhão', 'quatrilhões'),
            1000000000000000000 => array('quinquilhão', 'quinquilhões')
        );
    
        if (!is_numeric($number)) {
            return false;
        }
    
        if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
            // overflow
            trigger_error(
                'convert_number_to_words só aceita números entre ' . PHP_INT_MAX . ' à ' . PHP_INT_MAX,
                E_USER_WARNING
            );
            return false;
        }
    
        if ($number < 0) {
            return $negative . convert_number_to_words(abs($number));
        }
    
        $string = $fraction = null;
    
        if (strpos($number, '.') !== false) {
            list($number, $fraction) = explode('.', $number);
        }
    
        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens   = ((int) ($number / 10)) * 10;
                $units  = $number % 10;
                $string = $dictionary[$tens];
                if ($units) {
                    $string .= $conjunction . $dictionary[$units];
                }
                break;
            case $number < 1000:
                $hundreds  = floor($number / 100)*100;
                $remainder = $number % 100;
                $string = $dictionary[$hundreds];
                if ($remainder) {
                    $string .= $conjunction . convert_number_to_words($remainder);
                }
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int) ($number / $baseUnit);
                $remainder = $number % $baseUnit;
                if ($baseUnit == 1000) {
                    $string = convert_number_to_words($numBaseUnits) . ' ' . $dictionary[1000];
                } elseif ($numBaseUnits == 1) {
                    $string = convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit][0];
                } else {
                    $string = convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit][1];
                }
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= convert_number_to_words($remainder);
                }
                break;
        }
    
        if (null !== $fraction && is_numeric($fraction)) {
            $string .= $decimal;
            $words = array();
            foreach (str_split((string) $fraction) as $number) {
                $words[] = $dictionary[$number];
            }
            $string .= implode(' ', $words);
        }
    
        return $string;
    }
/*------------------------------------------------------------------------------
 *   Arruma stylo de fonte
 * param['cor'] = cor, param['b'] = bold,param['i'] = italic,param['back'] = cor
 *------------------------------------------------------------------------------*/
    public function font_Estilo ($text, $param = null)
    {
        $ret = '<p style=" ';
        foreach ($param as $key => $p)
        {
            switch ($key)
            {
                case 'cor':
                    $ret .= 'color=:' . $p . '; ';
                    break;
                case 'b':
                    $ret .= 'font-weight: bold; '; 
                    break;
                case 'i':
                    $ret .= 'font-style: italic; '; 
                    break;
                case 'back':
                    $ret .= 'background:' . $p . '; '; 
                    break;
            }
        }
        $ret .= '">' . $text . '</p>';
        return $ret;
    }//Fim Módulo
}//Fim da classe

