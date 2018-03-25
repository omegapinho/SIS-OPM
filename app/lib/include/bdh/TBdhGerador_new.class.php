<?php
class TBdhGerador_new
{
    var $tabTurno_bkp = "";        //Dados do Turno
    var $idTurno_bkp  = "";        //Id do Turno
    var $pascoa       = false;
    var $carnaval     = false;
    var $corpus       = false;
    var $sextasanta   = false;
    var $feriados     = array();
    var $feriadosOPM  = array();
    var $OPM_feriado  = "";
    var $rg_afastado  = "";
    var $afastamento  = array();
    var $debug        = false;
    var $lista_afasta = array();
    var $interacoes   = array();

/*-------------------------------------------------------------------------------
 *        Função Construtora
 *-------------------------------------------------------------------------------*/
    public function __construct()
    {
        set_time_limit (3600);
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *        Função 
 *-------------------------------------------------------------------------------*/
/*====================================================================================================
 * Descrição do Módulo: Executa a manutenção da Lista de trabalho
 * 			A tabela temporária não  na verdade uma tabela transitória apenas, com isto
 * 		os dados permanecem nela até serem limpos via comando limpar. Abaixo o Drop da Tabela: 
 * 		
 * 
 *====================================================================================================*/
/*---------------------------------------------------------------
 * Nota: funções para trabalhar com datas
 *---------------------------------------------------------------*/
 /* ---------------------------------------------------------------
 * Nota: Calcula a data e hora do dia mais o intervalo e obtem o
 * dia apos o intervalo, necessário pois existem intervalos que
 * pode mudar o dia corrente.
 *----------------------------------------------------------------*/
    public function add_horas($dt, $qtdh) 
    {
    	if (strpos($dt,':'))
    	{
        	$dia = substr ( $dt, 0, 2 );
        	$mes = substr ( $dt, 3, 2 );
        	$ano = substr ( $dt, 6, 4 );
        	$h = substr ( $dt, 11, 2 );
        	$m = substr ( $dt, 14, 2 );
        	$s = substr ( $dt, 17, 2 );
    	    $result = date ( "d/m/Y H:i:s", mktime ( $h + $qtdh, $m, $s, $mes, $dia, $ano ) );
        }
        else
        {
        	$dia = substr ( $dt, 8, 2 );
        	$mes = substr ( $dt, 5, 2 );
        	$ano = substr ( $dt, 0, 4 );
        	$h = '00';
        	$m = '00';
        	$s = '00';
        	$result = date ( "Y-m-d", mktime ( $h + $qtdh, $m, $s, $mes, $dia, $ano ) );
        }

    	return $result;
    }//Fim Módulo
/*---------------------------------------------------------------
 * Nota: Verifica os Feriados
 *---------------------------------------------------------------*/
    public function is_feriado($data,$opm = null) {
    	$Feriado = false;
        $ano = substr ( $data, 6, 4 ) ;
        
        if (false !== strpos($data,"-"))//padroniza a data trocando o simbolo separador
        {
            $data = str_replace("-","/", $data);
            if ($this->debug) echo "Corrigida".$data;
        }
    	//$Fer = $conf->busca ( "SELECT bdh_feriado_descricao FROM bdhoras.bdh_feriado WHERE bdh_feriado_dia_mes='" . substr ( $data, 0, 2 ) . "/" . substr ( $data, 3, 2 ) . "';" );
        try 
        { 
            if (empty($this->feriados))
            {
                //var_dump($this->feriados);
                $feriados = array();
                //Feriados de data móvel
                $feriados [] = self::dataCarnaval($ano);
                $feriados [] = self::dataPascoa($ano);
                $feriados [] = self::dataSextaSanta($ano);
                $feriados [] = self::dataCorpusChristi($ano);
                TTransaction::open('sicad');
                $results = feriado::where ('tipo','=','NACIONAL')->load();
                foreach ($results as $result)//Cria lista de Feriados Nacionais
                {
                    if($result->dataferiado)
                    {
                        $feriados[] = $result->dataferiado.'/'.$ano;
                        if ($this->debug) echo $result->dataferiado.'/'.$ano."<br>".$data;
                    }
                }
                TTransaction::close();
                $this->feriados = $feriados;
            }
            else
            {
                //var_dump($this->feriadosOPM);
                $feriados = $this->feriados;//Feriados Nacionais
            } 
            if($this->OPM_feriado!= $opm && $opm!=null)
            {
                TTransaction::open('sicad');
                $conn = TTransaction::get();
                $sql = "SELECT DISTINCT dataferiado FROM bdhoras.feriado, bdhoras.feriadoopm ".
                            "WHERE feriadoopm.feriado_id = feriado.id AND (feriado.tipo = 'MUNICIPAL' OR ".
                            " feriado.tipo = 'INSTITUCIONAL') AND feriadoopm.opm_id = ".(int) $opm.";";
                $feriados_opm = $conn->prepare($sql);
                $feriados_opm->execute();
                $results = $feriados_opm->fetchAll();
                $municipais = array();
                foreach ($results as $result)
                {
                     $municipais[] = $result['dataferiado'].'/'.$ano;   
                }
                TTransaction::close();
                $this->feriadosOPM = $municipais;
                $this->OPM_feriado = $opm;
            }
            else
            {
                $municipais = $this->feriadosOPM;//Feriados Municiapais da OPM
            }

        } 
        catch (Exception $e) 
        { 
            new TMessage('error', $e->getMessage()."<br> Erro ao buscar feriados...");
            TTransaction::rollback();
            return false; 
        }
        foreach ($feriados as $feriado)
        {
            if ($feriado == $data)
            {
                $Feriado = true;
            }
        }
        foreach ($municipais as $municipal)
        {
            if ($municipal == $data)
            {
                $Feriado = true;
            }
        }
        
		$datas = substr ( $data, 6, 4 ) . "/" . substr ( $data, 3, 2 ) . "/" . substr ( $data, 0, 2 );
		$diaSemana = date ( "w", strtotime ( $datas ) ); // para retornar o dia da semana e necessário passar a data no formato americano ano/mes/dia
		if ($diaSemana == 0) 
		{
			$Feriado = true;//Domingo
		}
		if ($diaSemana == 6) 
		{
			$Feriado = true;//Sábado
		}
    	return $Feriado;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 * Nota: Verifica os afastamentos
 *------------------------------------------------------------------------------*/
    public function is_afasta($dt, $rgmilitar=null, $dtfim = null) 
    {
    	$afastado = false;
        if (in_array($dt,$this->afastamento))
        {
            $afastado = true;
        }
    	return $afastado;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 * Nota: Carrega os afastamentos para memória
 *------------------------------------------------------------------------------*/
    public function carga_afastamentos ($dt,$rgmilitar,$dtfim = null)
    {
        if ($rgmilitar!=$this->rg_afastado || empty($this->afastamento))
    	{
        	if ($dtfim!=null)
        	{
                $sql = "SELECT datainicio::date AS datainicio FROM bdhoras.historicotrabalho ".
            		   "WHERE datainicio >='$dt 00:00:00' AND datafim <='$dtfim 23:59:59'".
            		   " AND rgmilitar='$rgmilitar' AND (afastamentos_id IS NOT NULL) AND status='A'";
            }
            else
            {
            	$sql = "SELECT datainicio::date AS datainicio FROM bdhoras.historicotrabalho ".
            			"WHERE datainicio >='$dt 00:00:00' AND datafim <='$dt 23:59:59'".
            			" AND rgmilitar='$rgmilitar' AND (afastamentos_id IS NOT NULL) AND status='A'";
            }
            try 
            { 
                TTransaction::open('sicad'); 
                $conn = TTransaction::get();
                $res = $conn->prepare($sql);
                $res->execute();
                $repository = $res->fetchAll();
                TTransaction::close();
                $array = array();
                foreach ($repository as $repor)
                {
                    $array[] = $repor['datainicio'];
                }
                $this->afastamento = $array;
            } 
            catch (Exception $e) 
            { 
                new TMessage('error', $e->getMessage().'<br>Erro ao verificar afastamentos.');
                TTransaction::rollback();
                $this->afastamento = false;
            }
        }
        return;
    }//Fim Módulo

/*------------------------------------------------------------------------------
 * Nota: Grava um dia de serviço
 *------------------------------------------------------------------------------*/
     public function grava_dia ($turnos_id,$rgmilitar,$datainicio,$datafim,$status,$opm_id,$remunerada = 'N',$lota_info=null)
     {
        try 
        { 
            TTransaction::open('sicad'); 
            /*$servico = new historicotrabalho();
            $servico->turnos_id   = (int)$turnos_id;
            $servico->rgmilitar   = (string) $rgmilitar;
            $servico->datainicio  = $datainicio;
            $servico->datafim     = $datafim;
            $servico->status      = $status;
            $servico->opm_id      = $opm_id;
            $servico->remunerada  = $remunerada;
            $servico->opm_id_info = $lota_info;*/
            $sql = "INSERT INTO bdhoras.historicotrabalho(turnos_id,".
        				"rgmilitar, datainicio, datafim,".
        				"status, opm_id, remunerada,opm_id_info) ".
        				"VALUES ($turnos_id,'$rgmilitar','" . $datainicio . "','" .
        				$datafim . "','$status',$opm_id,'$remunerada',$lota_info);";
            $conn = TTransaction::get();
            $res = $conn->prepare($sql);
            $res->execute();
            $repository = $res->fetchAll();
            TTransaction::close();
            if ($this->debug) echo $sql;
            if ($repository)
            {
                return true;
            } 
        } 
        catch (Exception $e) 
        { 
            new TMessage('error', $e->getMessage().'<br>Erro ao gravar dia de serviço.'.$sql); 
            TTransaction::rollback();
        }
        return false;
     }//Fim Módulo
/*---------------------------------------------------------------
 * Nota: Gera a Escala de Serviço
 *---------------------------------------------------------------*/
/*-----------------------------------------------------------------
 * Nota: Função que efetua o cadastro no BD de um dia de serviço.
 * Será considerado a extensão do dia trabalhado para além das 24hs
 * criando mais um dia de serviço caso ultrapasse este limite.
 *-----------------------------------------------------------------*/
    public function lancamento($PM_RG, $data, $qtd_h_folga, $hora_inicio, $qtd_h_turn1, $intervalo, $status = "P", 
                                $qtd_hora_turn2, $idturno, $ID_OPM, $lota_info=null) 
    {
    	if ($qtd_h_turn1<25) 
    	{//Tempo do turno menor que 25 horas
    		$data_hora_termino = self::add_horas("$data $hora_inicio", $qtd_h_turn1 );
    		self::grava_dia($idturno,$PM_RG,self::data_banco("$data $hora_inicio"),self::data_banco($data_hora_termino),
    		                $status,$ID_OPM,'N',$lota_info);
    	} 
    	else 
    	{
    		$qthoras = $qtd_h_turn1;
    		$diaserv = $data;
    		$data_hora_termino = self::add_horas ( "$data $hora_inicio", $qtd_h_turn1 );
    		while ($qthoras>0) 
    		{
    			if ($qthoras>0)
    			{
    				if ($qthoras-24>=0) 
    				{
    					$dtfim = self::add_horas ( "$diaserv $hora_inicio", 24 );
    					$qthoras=$qthoras-24;
    				} 
    				else 
    				{
    					$dtfim = self::add_horas ( "$diaserv $hora_inicio", $qthoras );
    					$qthoras=0;
    				}
    			    self::grava_dia($idturno,$PM_RG,self::data_banco("$diaserv $hora_inicio"),self::data_banco ( $dtfim ),
    			                    $status,$ID_OPM,'N',$lota_info);
    				$diaserv= self::add_horas ( "$diaserv $hora_inicio", 24 );
    			}
    		}
    	}
    	if ($qtd_hora_turn2 > 0) 
    	{
    		if ($qtd_hora_turn2<25) 
    		{
    			$data_hora_reinicio = self::add_horas ( "$data_hora_termino", $intervalo );
    			$data_hora_fim = self::add_horas ( "$data_hora_reinicio", $qtd_hora_turn2 );
			    self::grava_dia($idturno,$PM_RG,self::data_banco($data_hora_reinicio),self::data_banco ( $data_hora_fim ),
			                    $status,$ID_OPM,'N',$lota_info);
    		} 
    		else 
    		{
    			$data_hora_reinicio = self::add_horas ( "$data_hora_termino", $intervalo );
    			$data_hora_fim = self::add_horas ( "$data_hora_reinicio", $qtd_hora_turn2 );
    				
    			$qthoras = $qtd_hora_turn2;
    			$diaserv = $data_hora_reinicio;
    			//$data_hora_termino = add_horas ( "$data $hora_inicio", $qtd_h_turn2 );
    			while ($qthoras>0) 
    			{
    				if ($qthoras>0)
    				{
    					if ($qthoras-24>=0) 
    					{
    						$dtfim = self::add_horas ( "$diaserv $hora_inicio", 24 );
    						$qthoras=$qthoras-24;
    					} 
    					else 
    					{
    						$dtfim = self::add_horas ( "$diaserv $hora_inicio", $qthoras );
    						$qthoras=0;
    					}
        			    self::grava_dia($idturno,$PM_RG,self::data_banco("$diaserv $hora_inicio"),self::data_banco ($dtfim),
        			                    $status,$ID_OPM,'N',$lota_info);
    					$diaserv= self::add_horas ( "$diaserv $hora_inicio", 24 );
    				}
    			}
    		}
    	} 
    	else 
    	{
    		$data_hora_fim = $data_hora_termino;
    	}
    	$data = self::add_horas ( "$data_hora_fim", $qtd_h_folga );
    	$data = substr ( $data, 0, 10 );
    	return $data;
    }// Fim Módulo
/*---------------------------------------------------------------
 * Nota: Retorna o dia da Semana
 *---------------------------------------------------------------*/
    public function DiaSemana($data) {
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
/*---------------------------------------------------------------
 * Nota: Retorna o Último dia de serviço
 *---------------------------------------------------------------*/
    public function ultimo_dia($ano, $mes) {
    	return date ( "t", mktime ( 0, 0, 0, $mes, 1, $ano ) );
    }//Fim 
/*---------------------------------------------------------------
 * Nota: Formadata a data para usar no BD
 *---------------------------------------------------------------*/
    public function data_banco($data_hora) {
    	$d = substr ( $data_hora, 0, 2 );
    	$m = substr ( $data_hora, 3, 2 );
    	$y = substr ( $data_hora, 6, 4 );
    	$h = substr ( $data_hora, 11, 2 );
    	$i = substr ( $data_hora, 14, 2 );
    	$s = substr ( $data_hora, 17, 2 );
    	if ($h != "") {
    		return "$y-$m-$d $h:$i:$s";
    	} else {
    		return "$y-$m-$d";
    	}
    }//Fim Módulo
/*------------------------------------------------------------------------------
 * Nota: Limpa a escala Futura do Policial
 *------------------------------------------------------------------------------*/
    public function limpa_escala ($dt_fim, $dt_ini, $rgmilitar,$tudo = "N")
    { 
    	$dt_fim_corrigido = substr ( $dt_fim, 8, 2 ) . "-" . substr ( $dt_fim, 5, 2 ) . "-" . substr ( $dt_fim, 0, 4 );
    	$dt_fim_corrigido = $dt_fim_corrigido . ' 00:00:01';
    	$dt_fim_corrigido = self::data_banco ( (self::add_horas ( "$dt_fim_corrigido", 24 )) );
    /*-------------------------------------------------------
     * Nota: A lógica parece incorreta em relação ao apagar as escalas
     * o certo seria deletar da seguinte forma
     * (data inicial >= Inicio AND Data Inicial <= Final) AND (Data Final <= Final + qnt de horas do turno)
     *-------------------------------------------------------*/
        try 
        { 
            TTransaction::open('sicad'); 
            $criteria = new TCriteria; 
            $criteria->add(new TFilter('rgmilitar',  '=', (string) $rgmilitar)); 
            $criteria->add(new TFilter('datainicio', '>=', $dt_ini.' 00:00:00'));
            $criteria->add(new TFilter('datafim',    '<', $dt_fim_corrigido));
            $criteria_status = new TCriteria;
            $criteria1 = new TCriteria;
            $criteria1->add(new TFilter('status',  '=', 'P'));
            $criteria2 = new TCriteria;
            $criteria2->add(new TFilter('status',  '=', 'T'));
            $criteria_status->add($criteria1, TExpression::OR_OPERATOR);
            $criteria_status->add($criteria2, TExpression::OR_OPERATOR);
            if ($tudo=="N")//Limpa somente as escalas Pendentes e Trabalhadas
            {
                $criteria->add($criteria_status);
            }
            $repository = new TRepository('historicotrabalho'); 
            $repository->delete($criteria); 
            //new TMessage('info', 'Records Deleted'); 
            TTransaction::close();
            return true; 
        } 
        catch (Exception $e) 
        { 
            //new TMessage('error', $e->getMessage().'<br>Erro ao limpar escala.'); 
            return false;
        }
    }//Fim Módulo

/*---------------------------------------------------------------
 * Nota: Monta a escala
 *---------------------------------------------------------------*/
    public function monta_escala($turno, $datai, $dataf, $hora, $RG, $lota,$horario,$lota_info=null) 
    {
    /*-------------------------------------------------------
     * Nota: As variáveis $data e $dt_fim são normatizadas 
     * pela função DataNorma, assim se elas não estiverem 
     * com mascara 'd/m/a' ou 'd-m-a' serão reparadas 
     * para uso na rotina.
     *-------------------------------------------------------*/
    	//$con = new Conect();
    	$idturno        = $turno;  				        //Vincula o histórico de trabalho a um turno cadastrado
    	$data           = $datai;	                	//Data de início (dd-mm-yyyy)
    	$dt_fim         = $dataf;                       //Data de término(dd-mm-yyyy)
    	$ID_OPM         = $lota;				        //Identificador da OPM
    	$PM_RG          = $RG;					        //Identificador do Policial
    	//$horario = $tabelaturno; 			            //Recebe a tabela com os dados do Turno, evitando assim a repesquisa uma vez que são um só.
    	$qtd_h_folga    = $horario ['qnt_h_folga'];     //Horas de folga
    	$hora_inicio    = $hora;                        //Hora inicial do turno	
    	$qtd_h_turn1    = $horario ['qnt_h_turno1'];	//Tempo de trabalho na primeira parte do turno
    	$intervalo      = $horario ['qnt_h_intervalo1'];//Tempo de intervalo
    	$status         = "P";						    //Status da escala [P = Pendente]
    	$qtd_hora_turn2 = $horario ['qnt_h_turno2'];	//Tempo de trabalho da segunda parte do turno
    	$tsabado        = strtoupper($horario ['sabado']); 		    //Se trabalha sábado
    	$tdomingo       = strtoupper($horario ['domingo']); 		//Se trabalha Domingo
    	$tferiado       = strtoupper($horario ['feriado']); 		//Consultar tabela feriado
    	$tquarta        = strtoupper($horario ['quarta']);		    //Se quarta é meio expediente
    	$tisegunda      = strtoupper($horario ['inicia_seg']); 		//Se a escala sempre inicia na segunda?
    	$teste          = "T";                                      //Verificador de Verdade (T = true,F = false)
    	
    	$ini         = substr ( $data, 0, 2 );                  //Pega o primeiro dia da escala
    	$dt_fim      = TDate::date2us( $dt_fim );            //Converte a data final para formando do BD 
    	$datas       = TDate::date2us( $data );               //Converte a inicial para o formato do BD
    	$qtddias     = self::ultimo_dia ( substr ( $data, 7, 4 ), substr ( $data, 3, 2 ) );
/*-------------------------------------------------------
 * Nota: Fim Limpa a escala Futura do Policial entre as 
 * datas inicial e final, excluido somente as escalas e
 * não os afastamentos.
 *-------------------------------------------------------*/
        self::limpa_escala($dt_fim,$datas,$PM_RG);
        self::carga_afastamentos($datas,$PM_RG,$dt_fim);
    	while ( $dt_fim >= $datas ) 
    	{
    		if ($this->debug) echo $datas . "<br>";
    		$diaSemana = (string) self::DiaSemana($datas);
    		if (!self::is_afasta(substr($data,6,4)."-".substr($data,3,2)."-".substr($data,0,2),$PM_RG)) 
    		{//Não consta afastado
    			switch ($diaSemana) 
    			{
    				case '0': // domingo
    					if ($tdomingo == $teste)//sim, trabalha aos domingos 
    					{
    						$data = self::lancamento ( $PM_RG, $data, $qtd_h_folga, $hora_inicio, $qtd_h_turn1, $intervalo, 
    						                            $status = "P", $qtd_hora_turn2, $idturno, $ID_OPM , $lota_info );
    					} 
    					else//não trabalha domingo 
    					{
    						//se inicia sempre na segunda feira soma apenas 24horas
    						if ($tisegunda == $teste) 
    						{
    							$data = self::add_horas ( "$data $hora_inicio", 24);
    						} 
    						else//Soma toda a folga em horas 
    						{
    							$data = self::add_horas ( "$data $hora_inicio", (24 + $qtd_h_folga) );
    						}
    						$data = substr ( $data, 0, 10 );
    					}
    					break;
    				case '6': // sabado
    					if ($tsabado == $teste)//Sim, Trabalha aos sábados 
    					{
    						$data = self::lancamento ( $PM_RG, $data, $qtd_h_folga, $hora_inicio, $qtd_h_turn1, $intervalo, 
    						                            $status = "P", $qtd_hora_turn2, $idturno, $ID_OPM, $lota_info );
    					} 
    					else//não trabalha sábado 
    					{
    						$data = self::add_horas ( "$data $hora_inicio", (24 + $qtd_h_folga) );
    						$data = substr ( $data, 0, 10 );
    					}
    					break;
    				case '3': // quarta-feira
    					if ($tquarta == $teste) //Trabalha Meio Expediente
    					{
        					if (self::is_feriado ( $data , $ID_OPM ) && $tferiado != $teste )//testa feriado 
        					{
    							//Folga no dia
    							$data = self::add_horas ( "$data $hora_inicio", (24 + $qtd_h_folga) );
    							$data = substr ( $data, 0, 10 );
    						}
    						else
    						{
        						//trabalha meio dia
        						$data = self::lancamento( $PM_RG, $data, 20, $hora_inicio, $qtd_h_turn1, 0, 
    						                            $status = "P", 0, $idturno, $ID_OPM, $lota_info );
                            }
    					} 
    					else//Trabalha quarta integral 
    					{
        					if (self::is_feriado ( $data , $ID_OPM ) && $tferiado != $teste )//testa feriado 
        					{
    							//Folga meio no dia
    							$data = self::add_horas ( "$data $hora_inicio", (24 + $qtd_h_folga) );
    							$data = substr ( $data, 0, 10 );
    						}
    						else
    						{
        						//trabalha quarta integral
        						$data = self::lancamento ( $PM_RG, $data, $qtd_h_folga, $hora_inicio, $qtd_h_turn1, $intervalo, 
    						                            $status = "P", $qtd_hora_turn2, $idturno, $ID_OPM, $lota_info );
                            }          
    					}
    					break;
    				default : //Dias úteis 
    					if (self::is_feriado ($data , $ID_OPM )) 
    					{
    						//Verifica se é feriado e se o turno trabalha
    						if ($tferiado == $teste) 
    						{//Trabalha
    							$data = self::lancamento ( $PM_RG, $data, $qtd_h_folga, $hora_inicio, $qtd_h_turn1, $intervalo,
    							                            $status = "P", $qtd_hora_turn2, $idturno, $ID_OPM, $lota_info );
    						} 
    						else 
    						{//Folga, pula para o próximo dia
                                if ($this->debug) echo $data . " ". $hora_inicio . " é feriado <br>";
    							$data = self::add_horas ( "$data $hora_inicio", ($qtd_h_turn1 + $intervalo + $qtd_hora_turn2 + $qtd_h_folga) );
                                if ($this->debug) echo "horas de folga". $qtd_h_folga.", nova data " . $data."<br>";
    							$data = substr ( $data, 0, 10 );
    						}
    					} 
    					else 
    					{
    						//Lança dia comum
    						$data = self::lancamento ( $PM_RG, $data, $qtd_h_folga, $hora_inicio, $qtd_h_turn1, $intervalo, 
    						                            $status = "P", $qtd_hora_turn2, $idturno, $ID_OPM, $lota_info );
    					}
    					break;
    			}
    		} 
    		else 
    		{//Afastado
    			$data = self::add_horas ( "$data $hora_inicio", (24 + $qtd_h_folga) );
    			$data = substr ( $data, 0, 10 );
    		}    
    		$datas = TDate::date2us( $data );
    		if ($this->debug) echo $data . "<br>";
    		if ($this->debug) echo ' atualizado ',$datas,$PM_RG, $data, $qtd_h_folga, $hora_inicio, $qtd_h_turn1, $intervalo, 
    		                    $qtd_hora_turn2, $idturno, $ID_OPM, $lota_info;
		}// Fim inclusão dos dias
        if ($dt_fim <= $datas) 
        {
            return true;
        } 
        else 
        {
            return false;
        }
    } // Fim do Módulo
/*---------------------------------------------------------------
 * Nota: Limpa as escala
 *---------------------------------------------------------------*/
    public function clear_escalas($datai, $dataf, $rgmilitar) 
    {
    /*-------------------------------------------------------
     * Nota: As variáveis $data e $dt_fim são normatizadas 
     * pela função DataNorma, assim se elas não estiverem 
     * com mascara 'd/m/a' ou 'd-m-a' serão reparadas 
     * para uso na rotina.
     *-------------------------------------------------------*/
    	$dt_fim      = TDate::date2us( $dataf );            //Converte a data final para formando do BD 
    	$dt_ini      = TDate::date2us( $datai );               //Converte a inicial para o formato do BD
        $res = self::limpa_escala($dt_fim,$dt_ini,$rgmilitar,"S");
        return $res;
    }//Fim Módulo
/*---------------------------------------------------------------
 * Nota: funções para trabalhar com datas
 *---------------------------------------------------------------*/
    public function desmembra_dias ($dias, $mes, $ano) 
    {
    /*---------------------------------------------
     * Nota: Desmembrador de dias
     *---------------------------------------------*/
    /*---------------------------------------------
     * Nota: limpa a variável $dias de simbolos e 
     * coisas que não deve.
     *---------------------------------------------*/
    	$work = (string) preg_replace("/[^0-9,-]/", "", $dias);
    	$work = (string) preg_replace("/[^0-9,-.]/", "", $dias);
    	$work.=".";
    	$numero = "";
    	$numini = "";
    	$numfim = "";
    	$contar=0;
    	$ret=array();
    	$tamanho = strlen($work)-1;
    	for ($n=0; $n<=$tamanho; $n++) 
    	{
    		$a = substr($work,$n,1);
    		if ($a=="," or $a==".") 
    		{
    			if (($numero!="" and $numini=="") or ($numero=="" and $numini!="")) 
    			{
    				if ($numero!="") 
    				{
    					$ret[]=$numero;
    				} 
    				else 
    				{
    					$ret[]=$numini;					
    				}
    				$numero="";
    				$numini="";
    				$numfim="";
    				$contar++;
    			} 
    			else if ($numero!="" and $numini!="") 
    			{
    				$numfim=$numero;
    				$ini = (int) $numini;
    				$fim = (int) $numfim;
    				if ($ini>$fim) 
    				{
    					$troca = $fim;
    					$fim = $ini;
    					$ini = $troca;
    				}
    				if ($fim>self::ultimo_dia($ano, $mes)) 
    				{
    					$fim=self::ultimo_dia($ano, $mes);
    					$n=$tamanho+1;
    				}
    				for ($i=$ini; $i<=$fim; $i++)
    				{
    					$ret[$contar]=(string) $i;
    					$contar++;
    				}
    				$numero="";
    				$numini="";
    			} 
    		} 
    		else if ($a=="-") 
    		{
    			if ($numero!="" and $numini=="") 
    			{
    				$numini=$numero;
    				$numero="";
    				$numfim="";
    			} 
    			else if ($numero!="" and $numini!="") 
    			{
    				$numfim=$numero;
    				//executa contagem
    				$ini = (int) $numini;
    				$fim = (int) $numfim;
    				if ($ini>$fim) 
    				{
    					$troca = $fim;
    					$fim = $ini;
    					$ini = $troca;
    				}
    				if ($fim>self::UltimoDia($ano, $mes)) 
    				{
    					$fim=self::UltimoDia($ano, $mes);
    					$n=$tamanho+1;
    				}
    				for ($i=$ini; $i<=$fim; $i++)
    				{
    					$ret[$contar]=(string) $i;
    					$contar++;
    				}
    				$numero="";
    				$numini="";
    			}
    
    		} 
    		else 
    		{
    			$numero.=$a;
    		}
    	}
    	return $ret;
    }//Fim Módulo
/*---------------------------------------------------------------
 * Nota: funções para trabalhar com datas
 *---------------------------------------------------------------*/
    public function monta_dia ($dia,$mes,$ano) 
    {
    /*---------------------------------------------
     * Nota: Função para montar dia/mês/Ano
     *---------------------------------------------*/
    	$h=0;
    	$m=0;
    	$s=0;
    	$ret =date ( "d/m/Y H:i:s", mktime ( $h, $m, $s, $mes, $dia, $ano ) );
    	$ret=substr ($ret,0,10);
    	return $ret;
    }//Fim Módulo
/*---------------------------------------------------------------
 * Nota: funções para trabalhar com datas
 *---------------------------------------------------------------*/
    public function monta_extra($idturno,$ID_OPM,$dia,$mes,$ano,$hora_inicio,$hrtrab,$tescala,$PM_RG, $lota_info=null) 
    {
    /*---------------------------------------------
     * Nota: Esta função gera escalas bom base na
     * array $dia:
     * Variável              Conteúdo
     * $idturno          identificador de turno
     * $ID_OPM           OPM
     * $dia              array com os dias
     * $mes/$ano         Mês e Ano
     * $hora_inicio		 hora inicial
     * $hrtrab           Quantidade de horas
     * $tescala          tipo de escala (S=SER,N=Adm)
     * $P_RGM            RG do Policial
     *---------------------------------------------*/
    	//$con = new Conect();
    	$status = "P";
    	$hrtrab= (double) $hrtrab;
    	foreach ( $dia as $umdia ) 
    	{
    		$diaserv = self::monta_dia ($umdia,$mes,$ano);
    		$dtfim= self::add_horas ( "$diaserv $hora_inicio", $hrtrab );
    		/*$sql = "INSERT INTO bdhoras.bdh_historico_trabalho(bdh_historico_trabalho_turno,".
    				"bdh_historico_trabalho_rg, bdh_historico_trabalho_dt_hora_inicio, bdh_historico_trabalho_dt_hora_termino,".
    				"bdh_historico_trabalho_status, bdh_historico_trabalho_opm, bdh_historico_trabalho_remunerada) ".
    				"VALUES ('$idturno','$PM_RG','" . data_banco ( "$diaserv $hora_inicio" ) . "','" .
    				data_banco ( $dtfim ) . "','$status','$ID_OPM','$tescala');";
    		$ret=$con->Executa ( $sql, 'bdhoras', 'bdh_historico_trabalho' );*/
		    $ret = self::grava_dia($idturno,$PM_RG,self::data_banco("$diaserv $hora_inicio"),self::data_banco ( $dtfim ),
                                $status,$ID_OPM,$tescala,$lota_info);
    	}
            if ($ret) 
            {
                return true;
            } 
            else 
            {
                return false;
            }
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  DESCRICAO: busca Dados do Turno de Serviço
 *------------------------------------------------------------------------------*/
     public function get_turno_servico ($idturno)
     {
         try
         {
            TTransaction::open('sicad');
            $result = new turnos($idturno);
            TTransaction::close();
            return $result->toArray();//Retorna em forma de Array
         }
         catch (Exception $e)
         {
            new TMessage('error', $e->getMessage()."<br> Erro ao buscar dados do turno de serviço.");
            TTransaction::rollback();
            return false;
         }
         
     }//Fim Módulo
/*------------------------------------------------------------------------------
 *  DESCRICAO: busca Dados do Afastamento
 *------------------------------------------------------------------------------*/
     public function get_afastamento($afasta_id)
     {
         try
         {
            TTransaction::open('sicad');
            $result = new afastamentos($afasta_id);
            TTransaction::close();
            return $result->toArray();//Retorna em forma de Array
         }
         catch (Exception $e)
         {
            new TMessage('error', $e->getMessage()."<br> Erro ao buscar dados de afastamento.");
            TTransaction::rollback();
            return false;
         }
     }//Fim Módulo
/*------------------------------------------------------------------------------
 *  DESCRICAO: Executa uma ação no BD
 *------------------------------------------------------------------------------*/
     public function pesquisa_afasta($sql , $tipo)
     {
         $result = true;
         try
         {
            TTransaction::open('sicad');
            if ($tipo == "INSERT" && is_array($sql))
            {
                $turnos_id  = $sql['turnos_id'];
                $rgmilitar  = $sql['rgmilitar'];
                $datainicio = $sql['datainicio'];
                $datafim    = $sql['datafim'];
                $afasta_id  = $sql['afastamentos_id'];
                $status     = $sql['status'];
                $remunerada = ($sql['remunerada']) ? $sql['remunerada'] : 'N' ;
                $opm_id     = $sql['opm_id'];
                $lota_info  = ($sql['opm_id_info']) ? $sql['opm_id_info'] :$sql['opm_id'];
                $bgaf       = $sql['bgafastamento'];
                $afasta     = $sql['afastamento'];
                $anobg      = $sql['anobg'];
                $sql = "INSERT INTO bdhoras.historicotrabalho(turnos_id,".
            				"rgmilitar, datainicio, datafim,".
            				"status, opm_id, remunerada,opm_id_info,afastamento,afastamentos_id,bgafastamento,anobg) ".
            				"VALUES ($turnos_id,'$rgmilitar','" . $datainicio . "','" .
            				$datafim . "','$status',$opm_id,'$remunerada',$lota_info,'$afasta',$afasta_id,'$bgaf','$anobg');";
                $conn = TTransaction::get();
                $res = $conn->prepare($sql);
                $res->execute();
                $repository = $res->fetchAll();
            }
            else
            {
                $conn = TTransaction::get();
                if (is_array($sql)) 
                {
                    //var_dump($sql);
                    if ($this->debug) echo $tipo;
                    $res = $conn->prepare($sql);
                    $res->execute();
                    $result = false;
                }
                else
                {
                    $res = $conn->prepare($sql);
                    $res->execute();
                    if ($tipo == "SELECT")
                    {
                        $result = ($res) ? $res->fetchAll() : true;
                    }
                    else
                    {
                        $result = true;
                        
                    }
                }
            }
            
            TTransaction::close();
            return $result;
         }
         catch (Exception $e)
         {
            $extra_info =  (!is_array($sql)) ? $sql : '';
            new TMessage('error', $e->getMessage()."<br>Erro ao criar/pesquisar Afastamento".$sql);
            TTransaction::rollback();
            return false;
         }
     }//Fim Módulo
/*------------------------------------------------------------------------------
 * DESCRICAO: Inicializa a variavel Interações
 *------------------------------------------------------------------------------*/     
    public function start_Interacao ($param = null)
    {
        $this->interacoes = array();
    }
/*------------------------------------------------------------------------------
 *  DESCRICAO: Acrescenta uma interação na lista
 *------------------------------------------------------------------------------*/     
    public function insert_Interacao ($param = null)
    {
        $this->interacoes[] = $param;
    }
/*------------------------------------------------------------------------------
 *  DESCRICAO: Converte uma entrada array num Insert
 *------------------------------------------------------------------------------*/     
    public function sql_Interacao ($sql = null)
    {
            $turnos_id  = $sql['turnos_id'];
            $rgmilitar  = $sql['rgmilitar'];
            $datainicio = $sql['datainicio'];
            $datafim    = $sql['datafim'];
            $afasta_id  = $sql['afastamentos_id'];
            $status     = $sql['status'];
            $remunerada = ($sql['remunerada']) ? $sql['remunerada'] : 'N' ;
            $opm_id     = $sql['opm_id'];
            $lota_info  = ($sql['opm_id_info']) ? $sql['opm_id_info'] :$sql['opm_id'];
            $bgaf       = $sql['bgafastamento'];
            $afasta     = $sql['afastamento'];
            $anobg      = $sql['anobg'];
            $ret = "INSERT INTO bdhoras.historicotrabalho(turnos_id,".
        				"rgmilitar, datainicio, datafim,".
        				"status, opm_id, remunerada,opm_id_info,afastamento,afastamentos_id,bgafastamento,anobg) ".
        				"VALUES ($turnos_id,'$rgmilitar','" . $datainicio . "','" .
        				$datafim . "','$status',$opm_id,'$remunerada',$lota_info,'$afasta',$afasta_id,'$bgaf','$anobg')";
        return $ret;
    }
/*------------------------------------------------------------------------------
 *  DESCRICAO: Executa as interações com o banco em lote
 *------------------------------------------------------------------------------*/
     public function save_Interacao($sql)
     {
         $result = true;
         try
         {
            TTransaction::open('sicad');
            $conn = TTransaction::get();
            if (is_array($sql))
            {
                foreach ($sql as $query)
                {
                    $res = $conn->query($query);
                }
            }
            else
            {
                $res = $conn->query($sql);                
            }

            TTransaction::close();
         }
         catch (Exception $e)
         {
            new TMessage('error', $e->getMessage()."<br>Erro ao criar/pesquisar Afastamento".$sql);
            TTransaction::rollback();
            $result = false;
         }
         return $result;
     }//Fim Módulo
/*================================================================================================
 * Nota: Módulo responsável por registrar os afastamentos, aqui é calculado as interferências
 * do afastamento vindo do 'hist_afast.php' recebe os dados abaixo pelo Metodo POST:
 * - rgmilitar       --> Identificador do policial
 * - dtinicio        --> Dia inicial do Afastamento
 * - dtfim           --> Dia final
 * - afasta_id       --> id do afastamento
 * ================================================================================================*/
     public function monta_afastamento ($rgmilitar,$dtinicio,$dtfim,$afasta_id,$bg=null,$anobg=null,$opm_id,$lota_info=null)
     {
        // Mantém as datas no formato do banco de dados (Ano-Mês-dia)
        $this->start_Interacao();
        $fer = new TFerramentas;
        $dt_hora_inicio  = self::data_banco ( $dtinicio);
        $dt_hora_termino = self::data_banco ( $dtfim );
        $afastamento     = self::get_afastamento($afasta_id);//Pega os dados do afastamento($afasta_id=id de afastamento na tabela)
/* =================================================================================================
 * Nota: Essa primeira verificação é para alterar os dados do serviço uma vez que o militar
 * continuo trabalhando mesmo afastado
 * ================================================================================================*/
        $falha = false;
		$sql = "SELECT datainicio, datafim, turnos_id, afastamentos_id, opm_id, status FROM bdhoras.historicotrabalho WHERE " .
		                    "datainicio BETWEEN '" . $dt_hora_inicio . " 00:00:00' AND '" . 
                             $dt_hora_termino . " 23:59:59' AND rgmilitar='" . $rgmilitar . "';";
		$dias_afasta = self::pesquisa_afasta ($sql,'SELECT');
		$this->lista_afasta = array();
		foreach ($dias_afasta as $dia_afasta)
		{
            $this->lista_afasta [] = array('dt_timeinicio'=>   $fer->dateTo_Timestamp($dia_afasta['datainicio']),
                                           'dt_timeafim'=>     $fer->dateTo_Timestamp($dia_afasta['datafim']),
                                           'datainicio'=>      $dia_afasta['datainicio'],
                                           'datafim'=>         $dia_afasta['datafim'],
                                           'status'=>          $dia_afasta['status'],
                                           'afastamentos_id'=> $dia_afasta['afastamentos_id'],
                                           'status'=>          $dia_afasta['status'],
                                           'turnos_id'=>       $dia_afasta['turnos_id']
                                           );
        }
		
        if (strtoupper($afastamento ['trabalha']) == "T")//Trabalha o turno mesmo afastado 
        {
/* ================================================================================================
 * Nota: Pega do primeiro dia de afastamento (dt_hora_inicio) até o ultimo (dt_hora_termino)
 * ===============================================================================================*/
        	while ( $dt_hora_inicio <= $dt_hora_termino ) 
        	{
/* ==================================================================================================
 * Nota: Faz a pesquisa no histórico do trabalho para saber se havia trabalho na data do afastamento
 * ==================================================================================================*/
        		$time_dtinicio = $fer->dateTo_Timestamp($dt_hora_inicio . " 00:00:00"); 
        		$time_dtfinal  = $fer->dateTo_Timestamp($dt_hora_inicio . " 23:59:59");
        		$trabalhou_afasta = false;
        		foreach ($this->lista_afasta as $dia_afasta)
        		{
                    if ($dia_afasta['dt_timeinicio'] >= $time_dtinicio && 
                        $dia_afasta['dt_timeinicio'] <= $time_dtfinal)
                    {
                        $trabalhou_afasta = true;
                    }
                }
/* ==================================================================================================
 * Nota: se há trabalho e como podia exercer serviço no expediente, troca dados do dia para constar
 * o Afastamento, mas marca o dia como trabalhado dando Status=T
 * ==================================================================================================*/
        		if ($trabalhou_afasta == true) 
        		{
        			$sql = "UPDATE bdhoras.historicotrabalho SET status='T', afastamentos_id='" . 
        			        $afasta_id . "', bgafastamento='" . $bg . "', anobg='" . $anobg."', afastamento='". 
        			        $afastamento['nome']. "' WHERE datainicio BETWEEN '" . $dt_hora_inicio . " 00:00:00' AND '" . 
        			        $dt_hora_inicio . " 23:59:59' AND rgmilitar='" . $rgmilitar. "'";
        			$this->insert_Interacao($sql);
        		} 
        		else 
        		{
/* ==================================================================================================
 * Nota: se não há trabalho previsto no dia, consta somente
 * o Afastamento, mas marca o dia como NÃO trabalhado dando Status=A
 * ==================================================================================================*/
                    $sql = array('afastamentos_id'=>$afasta_id,'rgmilitar'=>$rgmilitar,'datainicio'=>$dt_hora_inicio . ' 00:00:00',
                                    'datafim'=>$dt_hora_inicio . ' 00:00:00','opm_id'=>$opm_id,'status'=>'A','bgafastamento'=>$bg,'anobg'=>$anobg,
                                    'afastamento'=>$afastamento['nome'],'turnos_id'=>0,'opm_id_info'=>$lota_info,'remunerada'=>'N');

                    $this->insert_Interacao($this->sql_Interacao($sql));
        		}
        		$dt_hora_inicio = self::add_horas ( $dt_hora_inicio, 24 );
        	}//fim do laço while
    		$time_dtinicio = $fer->dateTo_Timestamp($dt_hora_inicio  . " 00:00:00"); 
    		$time_dtfinal  = $fer->dateTo_Timestamp($dt_hora_termino . " 23:59:59");
    		$dt_fim_ultimo_lancamento = array();
    		foreach ($this->lista_afasta as $dia_afasta)
    		{
                if ($dia_afasta['dt_timeinicio'] >= $time_dtinicio && $dia_afasta['dt_timeinicio'] <= $time_dtfinal)
                {
                    $dt_fim_ultimo_lancamento[] = $dia_afasta;
                }
            }
        	
        	if (is_array($dt_fim_ultimo_lancamento))
        	{
        	    $retira_vazio = array_pop ( $dt_fim_ultimo_lancamento );
                if (is_array($retira_vazio))
                {
            	    if ($retira_vazio)
            	    {
            	        $retira_vazio = (strlen( $retira_vazio)>9) ? substr ( $retira_vazio, 0, 10 ) : '';
            	    }
            	    else
            	    {
                         $retira_vazio = '';
                    }
            	}
        	    else
        	    {
                     $retira_vazio = '';
                }
        	}
        	else
        	{
                $retira_vazio = '';
            }
/* ===================================================================================================
 * $dt_fim_ultimo[0] ARMAZENOU HORARIO FINAL DO ULTIMO DIA DE SERVIÇO ENTRE A DATA DE AFASTAMENTO
 *
 * =================================================================================================*/
            $dt_fim_ultimo = $retira_vazio;
        	if ($dt_fim_ultimo != "") 
        	{
        		if ($dt_hora_inicio <= $dt_fim_ultimo) 
        		{
        			$sql = "UPDATE bdhoras.historicotrabalho SET status='T', afastamentos_id='" . $afasta_id . "', ". 
                                    "bgafastamento='" . $bg . "', anobg='".$anobg."', afastamento='".$afastamento['nome'].
                                    "' WHERE datainicio BETWEEN '" . $dt_hora_inicio . " 00:00:00' " . 
                                    "AND '" . $dt_fim_ultimo . " 23:59:59' AND rgmilitar='" . $rgmilitar . "'";
                    $this->insert_Interacao($sql);
        		}
        	} 
        	else 
        	{
        		while ( $dt_hora_inicio <= $dt_hora_termino ) 
        		{
                    $sql = array('afastamentos_id'=>$afasta_id,'rgmilitar'=>$rgmilitar,'datainicio'=>$dt_hora_inicio . ' 00:00:00',
                                    'datafim'=>$dt_hora_inicio . ' 05:42:00','opm_id'=>$opm_id,'status'=>'T','bgafastamento'=>$bg,'anobg'=>$anobg,
                                    'afastamento'=>$afastamento['nome'],'turnos_id'=>0,'opm_id_info'=>$lota_info,'remunerada'=>'N');
                    $this->insert_Interacao($this->sql_Interacao($sql));
        			// Acrescenta um dia (24horas)
        			$dt_hora_inicio = self::add_horas ( $dt_hora_inicio, 24 );
        		}
        	}
        } 
        else//Não trabalha (Afastamento total) 
        {
/* ================================================================================================
 * Nota: O militar afastou totalmente do serviço
 * ================================================================================================
 * Nota: Primeiro deve deletar escala de serviço entre data do afastamento
 * ================================================================================================*/
        	$sql = "DELETE FROM bdhoras.historicotrabalho WHERE datainicio BETWEEN '" . 
        	    $dt_hora_inicio . " 00:00:00' AND '" . 
                $dt_hora_termino . " 23:59:59' AND rgmilitar='" . $rgmilitar . "';";
            $this->insert_Interacao($sql);
// ================================================================================================
// Nota:grava afastamento com status 'T' e 5:42 hrs (por dia) correspondente a tempo que deve ser
// cumprido por dia durante todo periodo de afastamento
// ================================================================================================
        	while ( $dt_hora_termino >= $dt_hora_inicio ) 
        	{
                $sql = array('afastamentos_id'=>$afasta_id,'rgmilitar'=>$rgmilitar,'datainicio'=>$dt_hora_inicio . ' 00:00:00',
                                'datafim'=>$dt_hora_inicio . ' 05:42:00','opm_id'=>$opm_id,'status'=>'A','bgafastamento'=>$bg,'anobg'=>$anobg,
                                'afastamento'=>$afastamento['nome'],'turnos_id'=>0,'opm_id_info'=>$lota_info,'remunerada'=>'N');
                $this->insert_Interacao($this->sql_Interacao($sql));
        		$dt_hora_inicio = self::add_horas ( $dt_hora_inicio, 24 );
        	}
        }
        $result = $this->save_Interacao($this->interacoes);
    	if (!$result)
    	{
            return false;
        }
        else
        {
            return true;
        }
     }//Fim Módulo
/*================================================================================================
 * Nota: Módulo responsável por APAGAR os afastamentos, aqui é calculado as interferências
 * do afastamento vindo do 'hist_afast.php' recebe os dados abaixo pelo Metodo POST:
 * - rgmilitar       --> Identificador do policial
 * - dtinicio        --> Dia inicial do Afastamento
 * - dtfim           --> Dia final
 * - afasta_id       --> id do afastamento
 * ================================================================================================*/
     public function apaga_afastamento ($rgmilitar,$dtinicio,$dtfim,$afasta_id,$bg,$anobg,$opm_id)
     {
        // Mantém as datas no formato do banco de dados (Ano-Mês-dia)
        $dt_hora_inicio  = self::data_banco ( $dtinicio);
        $dt_hora_termino = self::data_banco ( $dtfim );
        $afastamento = self::get_afastamento($afasta_id);        //Pegas os dados do afastamento
    	
    	$sql = "DELETE FROM bdhoras.historicotrabalho WHERE datainicio BETWEEN '" . 
    	    $dt_hora_inicio . " 00:00:00' AND '" . 
            $dt_hora_termino . " 23:59:59' AND rgmilitar='" . $rgmilitar . "'";
        if ($afasta_id)
        {
            $sql .= " AND afastamentos_id=".$afasta_id;
        }
        /*if ($bg)
        {
            $sql .= " AND bgafastamento='".$bg."'";
        }
        if ($anobg)
        {
            $sql .= " AND anobg='".$anobg."'";
        }*/
        $sql.=";";
        $result = self::pesquisa_afasta ($sql,'DELETE');
        return $result;
        
     }//Fim Módulo
/*====================================================================================
 * 
 * 							>>>> LOOP PRINCIPAL  <<<<<<
 * @policiais     = array(rgmilitar)
 * @slc_opm       = OPM fixa ou array(opms[rgmilitar])
 * @action        = ação [ORDINARIA, EXTRA]
 * @dtini e dtfim = datas inicial e final Escala Ordinária
 * @hrini         = Hora do início da escala Ordinária
 * @idturno       = identificação do turno (escala) de serviço
 * @dias          = Campo com os dias de gerar escala extra
 * @mesx          = mes para gerar escala Extra
 * @anox          = ano para gerar escala Extra
 * @hrinix        = Hora do início da escala Extra
 * @hrtrab        = Quantidade de Horas trabalhadas
 * @tescala       = tipo de Escala Extra (1 = Administrativa e 2 = Renumerada)
 *
 *====================================================================================*/
    public function main_escala ($policiais,$slc_opm, $action = "ORDINARIA",
                                $dtini=null,$dtfim=null,$hrini=null,$idturno=null,
                                $dias=null,$mesx=null,$anox=null,$hrinix=null,$hrtrab=null,$tescala='N',
                                $dtinicioaf=null,$dtfimaf=null,$bgaf=null,$anobgaf=null,$afasta_id=null,$lota_info=null)
    {
/*--------------------------------------------------------------------
 * Nota: Rotina de Seleção de Ação(action). Podendo ser:
 * 		ORDINARIA			=   Cria uma escala Ordinária
 * 		EXTRA				=   Cria uma escala Extra
 * 		AFASTA				=   Cria afastamentos
 *--------------------------------------------------------------------*/
	    if (array_key_exists('0',$policiais))
		{
           unset($policiais['0']);
        }
        if (count($policiais)==0)
        {
            new TMessage ("info", "É necessário que se selecione ao menos um Militar!!!");
            return false;
        }
        $report = new TRelatorioOP();
        switch ($action)
        {
            case 'ORDINARIA':
                $report->mSucesso = 'Escala Ordinária incluída com SUCESSO.';
                $report->mFalha   = 'Escala não gerada.';
                break;
            case 'EXTRA':
                $report->mSucesso = 'Escala Extra incluída com SUCESSO.';
                $report->mFalha   = 'Escala não gerada.';
                break;
            case 'AFASTA':
                $report->mSucesso = 'Afastamento incluído com SUCESSO.';
                $report->mFalha   = 'Afastamento não gerado.';
                break;
            case 'CLEARAFASTA':
                $report->mSucesso = 'Afastamentos limpados com SUCESSO.';
                $report->mFalha   = 'Afastamentos não deletados.';
                break;
            case 'CLEARESCALA':
                $report->mSucesso = 'Escala limpada com SUCESSO.';
                $report->mFalha   = 'Escala não deletada.';
                break;
        }
        
        if ($action=="ORDINARIA" || $action=="EXTRA" || $action=="AFASTA" || $action=="CLEARAFASTA" || $action=="CLEARESCALA") 
        {
        		if ($action=="ORDINARIA") 
        		{
/*----------------------------------------------------------------------------------------
 * Nota: Gera uma array com os dados de um turno de trab.
 *----------------------------------------------------------------------------------------*/				
					if ($idturno == $this->idTurno_bkp)        //Já foi pesquisado
					{
                         $tabturno = $this->tabTurno_bkp;
                    }
                    else                                      //Primeira pesquisa
                    {
    					$tabturno = self::get_turno_servico($idturno);
    					$this->tabTurno_bkp = $tabturno;
    					$this->idTurno_bkp  = $idturno;
                    }
        		} 
        		else if ($action=="EXTRA") 
        		{
/*----------------------------------------------------------------------------------------
 * Nota: Rotina de inicialização das variáveis para escala extra.
 *----------------------------------------------------------------------------------------*/
    				$diax = self::desmembra_dias($dias,$mesx,$anox); 
        		}
/*----------------------------------------------------------------------------------------
 * Nota: Rotina de geração de Escala
 * 		A escala (ordinária ou extra) é lançada para cada policial presente em $turno
 *----------------------------------------------------------------------------------------*/		
        		if ($policiais)
        		{
        			$relatorio = array();
        			$tempoInicio = microtime(true);
        			$fer = new TFerramentas;

        			foreach ( $policiais as $rgmilitar => $policial ) 
        			{
        				if ($policial) 
        				{
        					//Seleciona lotação. Se, array pega a lotação do PM, caso contrário é OPM pra todos.
        					$lotacao   = 	(is_array($slc_opm)) ? $slc_opm[$rgmilitar] : $slc_opm;
        					$lota_exec =    (is_numeric($lota_info)) ? $lota_info : $lotacao;
        					if ($action=="ORDINARIA") 
        					{
        						$ret = self::monta_escala($idturno, $dtini, $dtfim, $hrini, $rgmilitar, $lotacao, $tabturno, $lota_exec);						
        					} 
        					else if ($action=="EXTRA") 
        					{
        						$ret = self::monta_extra(13,$lotacao,$diax,$mesx,$anox,$hrinix,$hrtrab,$tescala,$rgmilitar,$lota_exec);
        					}
        					else if ($action=="AFASTA") 
        					{
        						$ret = self::monta_afastamento($rgmilitar,$dtinicioaf,$dtfimaf,$afasta_id,$bgaf,$anobgaf,$lotacao,$lota_exec);
        					}
        					else if ($action=="CLEARAFASTA") 
        					{
        						$ret = self::apaga_afastamento($rgmilitar,$dtinicioaf,$dtfimaf,$afasta_id,$bgaf,$anobgaf,$lotacao);
        					}
        					else if ($action=="CLEARESCALA") 
        					{
        						$ret = self::clear_escalas($dtini, $dtfim, $rgmilitar);						
        					}
                            $report->addMensagem('- O PM RG '.$rgmilitar,$ret);
        				}
        			} 
        		}//Fim if ($policiais) 
        	}//Fim (actions validas)
            //Gera Relatório das atividades
            $tempoFinal = microtime(true);
            $tempoGasto = $tempoFinal - $tempoInicio;
            $report->addMensagem("Tempo Estimado Gasto na Operação de ".$fer->tempo_descrito($tempoGasto),null);
            $report->publicaRelatorio('info');
    }//Fim Módulo

/*----------------------------------------------------------------------------------------
 * Nota: Rotinas de Calculo de Data Móvel
 *----------------------------------------------------------------------------------------
 *
/*----------------------------------------------------------------------------------------
 * Nota: Pascoa
 *----------------------------------------------------------------------------------------*/
    public function dataPascoa($ano=false, $form="d/m/Y") 
    {
    	if (!$this->pascoa)
    	{
            $fer = new TFerramentas();
    	    $ano=($ano) ? $ano :date("Y");
    	    $this->pascoa = $fer->dataPascoa($ano);            
        }
    	return $this->pascoa;
    }//Fim Módulo
/*----------------------------------------------------------------------------------------
 * Nota: Carnaval
 *----------------------------------------------------------------------------------------*/
     public function dataCarnaval($ano=false, $form="d/m/Y") 
    {
    	if (!$this->carnaval)
    	{
            $fer = new TFerramentas();
    	    $ano=($ano) ? $ano :date("Y");
    	    $this->carnaval = $fer->dataCarnaval($ano);
        } 
    	return $this->carnaval;
    }// Fim do Módulo
/*----------------------------------------------------------------------------------------
 * Nota: Corpus Christi
 *----------------------------------------------------------------------------------------*/
    public function dataCorpusChristi($ano=false, $form="d/m/Y") 
    {
    	if (!$this->corpus)
    	{
            $fer = new TFerramentas();
    	    $ano=($ano) ? $ano :date("Y");
    	    $this->corpus = $fer->dataCorpusChristi($ano);
        }
    	return $this->corpus;
    }//Fim Módulo
    
/*----------------------------------------------------------------------------------------
 * Nota: Sexta Feira Santa
 *----------------------------------------------------------------------------------------*/
    public function dataSextaSanta($ano=false, $form="d/m/Y") 
    {
    	if (!$this->sextasanta)
    	{
            $fer = new TFerramentas();
    	    $ano=($ano) ? $ano :date("Y");
    	    $this->sextasanta = $fer->dataSextaSanta($ano);
        }

    	return $this->sextasanta;
    } //Fim Módulo
/*----------------------------------------------------------------------------------------
 * Nota: Executa o query para rotina de fechamento;
 *----------------------------------------------------------------------------------------*/
     public function grava_fechamento ($sql,$modo = "SELECT")
     {
         try
         {
             TTransaction::open('sicad');
                $conn = TTransaction::get();
                $res = $conn->prepare($sql);
                $res->execute();
             TTransaction::close();
             if ($this->debug) echo $sql;
             if ($modo=="SELECT")
             {
                 $repository = $res->fetchAll();
                 return $repository;
             }
             else
             {
                 return true;
             }
         }
         catch (Exception $e) 
         { 
             new TMessage('error', $e->getMessage().'<br>Erro ao gravar dia de serviço.'); 
             TTransaction::rollback();
             return false;
         }
         
     }//Fim Módulo
/*----------------------------------------------------------------------------------------
 * Nota: Executa o fechamento de uma OPM
 *----------------------------------------------------------------------------------------*/
    public function close_escala ($opm,$mes,$ano,$inativo = 'S',$toda_OPM = 'S')
    {
        $retorno = true;
        $status = ($inativo=='S') ? " AND servidor.status='ATIVO'" : '';//Fecha Somente Ativo
        $p_dia = '01';
        if ($mes == date("m")) 
        {
            $u_dia = date("d"); // se for mes atual fecha ate o dia atual
        } 
        else 
        {
            $u_dia = self::ultimo_dia($ano, $mes); // senao fecha o mes todo
        }
        $dt_inicio = $ano . "-" . $mes . "-" . $p_dia . " 00:00:00";
        $dt_fim = $ano . "-" . $mes . "-" . $u_dia . " 23:59:59";
        if ($dt_fim) 
        {
            if ($toda_OPM!='S')     // Fecha somente os militares ativos(ou não) que estão atualmente na OPM
            {
                //$Consmes = new Conect ();
                // busca pms da OPM selecionada
                $sql = "SELECT rgmilitar, unidadeid "
                        . "FROM efetivo.servidor"
                        . " WHERE unidadeid=$opm"
                        . $status
                        . " order by rgmilitar";
                $rg_opm = self::grava_fechamento($sql,"SELECT");
    
                $rg_pms = $rg_opm; // FECHAMENTO APENAS DA OPM SELECIONADA
                //print_r ($rg_pms);exit;
                // inicia fechamento de todos RGs obtidos
                foreach ($rg_pms as $key) 
                {
                    if ($key ['rgmilitar'] != "") 
                    {
                        // atualiza status de dias trabalhados do policial
                        $sql = "UPDATE bdhoras.historicotrabalho "
                                . "SET status='T' "
                                . "WHERE rgmilitar='" . $key ['rgmilitar'] 
                                . "' AND (status='P')"
                                . " AND datainicio"
                                . " BETWEEN '$dt_inicio' AND '$dt_fim';";
                        //print $key[0]."<br>";
                        //print $sql;exit;
                        $ret = self::grava_fechamento($sql,"UPDATE");
                        if (!$ret)
                        {
                            $retorno = false;
                        }
                        // horas QUEBRADAS trabalhadas no mes
                        /*$sql = "SELECT to_char(sum((datafim - "
                                . "datainicio)),'HH24:MI:SS') AS tempo "
                                . "FROM bdhoras.historicotrabalho "
                                . "WHERE (status='T' OR status='A') "
                                . "AND datainicio "
                                . "BETWEEN '$dt_inicio' AND '$dt_fim' AND rgmilitar='" . $key ['rgmilitar'] . "';";
                        $Htrab_q = self::grava_fechamento($sql,"SELECT");
                        // DIAS INTEIROS trabalhadas no mes
                        $sql = "SELECT sum((datafim - "
                                . "datainicio)) AS tempo "
                                . "FROM bdhoras.historicotrabalho "
                                . "WHERE (status='T' OR status='A') "
                                . "AND datainicio "
                                . "BETWEEN '$dt_inicio' AND '$dt_fim' AND rgmilitar='" . $key ['rgmilitar'] . "';";
                        $Htrab_i = self::grava_fechamento($sql,"SELECT");
                        // convertendo DIAS INTEIROS para caso de escalas de 24 hrs
                        /*if (strpos($Htrab_i [0] [0], 'day')) 
                        {
                            $Htrab_i [0] [0] = intval($Htrab_i [0] [0]) * 24;
                            $Htrab [0] [0] = ($Htrab_i [0] [0]) + ($Htrab_q [0] [0]);
                            $Htrab [0] [0] = $Htrab [0] [0] . ":00:00";
                        } 
                        else 
                        {
                            $Htrab [0] [0] = $Htrab_q [0] [0];
                        }
    
                        // verifica se já há saldo para aquele mes/ano para o PM
                        $saldo = $Consmes->busca("SELECT bdh_historico_saldo_id FROM bdhoras.bdh_historico_saldo WHERE bdh_historico_saldo_rg=" . $key [0] . " AND bdh_historico_saldo_ano='$ano' AND bdh_historico_saldo_mes='$mes';");
    
                        if ($saldo [0] [0]) 
                        {
                            // se já existir saldo naquele mes/ano para o PM atualiza este saldo
                            $Consmes->Executa("UPDATE bdhoras.bdh_historico_saldo SET bdh_historico_saldo_hora='" . $Htrab [0] [0] . "' WHERE bdh_historico_saldo_id=" . $saldo [0] [0] . ";", 'bdhoras', 'bdh_historico_saldo');
                        } 
                        else 
                        {
                            // se ainda não existe saldo naquele mes/ano para o PM insere novo saldo
                            $Consmes->Executa("INSERT INTO bdhoras.bdh_historico_saldo (bdh_historico_saldo_hora, bdh_historico_saldo_rg, bdh_historico_saldo_mes, bdh_historico_saldo_ano, bdh_historico_saldo_opm) VALUES('" . $Htrab [0] [0] . "', " . $key [0] . ", $mes, $ano, " . $key [1] . ");", 'bdhoras', 'bdh_historico_saldo');
                        }*/
                    }
                }
            }
            else    //Fecha toda OPM independente do militar estar mais nela
            {
                $sql = "UPDATE bdhoras.historicotrabalho "
                        . "SET status='T' "
                        . "WHERE opm_id='" . $opm 
                        . "' AND (status='P')"
                        . " AND datainicio"
                        . " BETWEEN '$dt_inicio' AND '$dt_fim';";
                $ret = self::grava_fechamento($sql,"UPDATE");
                if (!$ret)
                {
                    $retorno = false;
                }
            }
        }
        return $retorno;
    }//Fim Módulo
/*====================================================================================
 * 
 * >>>> LOOP PRINCIPAL FECHAMENTO <<<<<<
 * @unidades      = array(opm_id)
 * @lista_opm     = listagem com o nome das OPMs
 * @action        = ação [CLOSE]
 * @mes e ano     = mes/ano a fechar
 *
 *====================================================================================*/
    public function main_fechar ($unidades,$lista_opm, $action = "CLOSE",$mes=null,$ano=null,$fecha_inativo = 'S',$toda_OPM = 'S')
    {
/*--------------------------------------------------------------------
 * Nota: Rotina de Seleção de Ação(action). Podendo ser:
 * 		CLOSE   			=   Fecha uma escala de uma ou várias OPMs
 *--------------------------------------------------------------------*/
	    $retorno = true;
	    $report = new TRelatorioOP();
	    $report->mSucesso = "teve as escalas fechadas com SUCESSO.";
	    $report->mFalha   = "teve um PROBLEMA e suas escalas não foram fechadas.";
        if (count($unidades)==0)
        {
            new TMessage ("info", "É necessário que se selecione ao menos uma OPM!!!");
            return false;
        }
        if ($action=="CLOSE" || $action='outra acao') 
        {
    		if ($action=="CLOSE") 
    		{
/*----------------------------------------------------------------------------------------
 * Nota: Rotina de geração de Escala
 * 		A escala (ordinária ou extra) é lançada para cada policial presente em $turno
 *----------------------------------------------------------------------------------------*/		
        		if ($unidades)
        		{
        			foreach ( $unidades as $unidade ) 
        			{
        				if ($unidade) 
        				{
        					if ($action=="CLOSE") 
        					{
        						$ret = self::close_escala($unidade,$mes,$ano,$fecha_inativo,$toda_OPM);						
        					}
                             $report->addMensagem('- A UNIDADE '.$lista_opm[$unidade].' ',$ret);
                             if (!$ret)
                             {
                                 $retorno = false;
                             }
        				}
        			} 
        		}//Fim if ($policiais) 
        	}//Fim (actions validas)
            //Gera Relatório das atividades
        	$report->publicaRelatorio('info');
        }
        return $retorno;

    }//Fim Módulo
}//Fim Classe

