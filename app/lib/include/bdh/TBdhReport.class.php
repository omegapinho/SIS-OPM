<?php
class TBdhReport
{
//Declaração de Variáveis
    //protected $opm;
    protected $militares;
    protected $cabecalho;
    protected $corpo;
    protected $fds;
    protected $sem;
    
    
    public $mes;
    public $ano;
    public $opm;             //OPM que será relatada
    public $assinatura;      //Nome de quem abriu o relatório
    public $opm_relatorio;   //Unidade de quem assina
    public $tipo = 1;        //Tipo de Relatório que será feito
    public $unidade;         //Nome da OPM ou cabeçalho
    public $so_ativo;        //Não lista os Inativos
    public $so_OPM;          //Relaciona só que está atualmente na OPM mesmo que tenha escala na unidade
    public $escala;          //Array temporária de registro da escala do PM
    public $dados_pm;        //Array com dados do PM
    public $afastamento;     //Array com dados de afastamentos
    public $id_afasta;       //Id do afastamento para se evitar multiplas pesquisas no BD
    public $qntDias;         //Quantidade de Dias que tem o mês escolhido
    public $diasFeriado;     //Relação de dias de feriado a se comparar
    public $horas_semanais;  //Quantidade de horas semanais máximo.
    
    

/*-------------------------------------------------------------------------------
 *        Função Construtora
 *-------------------------------------------------------------------------------*/
    public function __construct()
    {
        $this->mes = date('m');
        $this->ano = date('Y');
        $this->militares = array();
        $this->cabecalho = '';
        $this->corpo     = '';
        $this->fds = "red";
        $this->sem = "lightblue";
        $this->so_ativo = 'S';
        $this->so_OPM   = 'S';
        $this->diasFeriado = false;
        $this->horas_semanais = 40;
        
        set_time_limit (600);
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *        Calcula a quantidade de dias úteis do mês
 *-------------------------------------------------------------------------------*/
    public function getDiasUteis()
    {
      
      $fer = new TFerramentas();
      $uteis = $fer->getDiasUteis ($this->mes,$this->ano);
      //subtrair feriados
      $this->diasFeriado = ($this->diasFeriado != false) ? $this->diasFeriado : $fer->getFeriado($this->ano);
      $desconto = 0;
      foreach ($this->diasFeriado as $feriado)
      {
          $data = explode('/',$feriado);
          if ($data[1]==$this->mes) $desconto ++;
      }
      $uteis = $uteis - $desconto;
      return $uteis;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *        Busca PMs da OPM que tenham escala
 *-------------------------------------------------------------------------------*/
     public function getMilitares()
     {
         $dtinicio = $this->ano.'-'.$this->mes.'-01 00:00:00';
         $dtfim    = $this->ano.'-'.$this->mes.'-'.$this->qntDias.' 23:59:59';
         if ($this->so_ativo=="I")
         {
             $txt_ativo = "servidor.status::text != 'ATIVO'::text AND";
         }
         elseif ($this->so_ativo=="A")
         {
             $txt_ativo = "servidor.status::text = 'ATIVO'::text AND";
         }
         else
         {
             $txt_ativo = '';
         }
         $txt_OPM = ($this->so_OPM == 'S') ? "servidor.unidadeid=".$this->opm : "historicotrabalho.opm_id=".$this->opm;
         
         $sql = "SELECT DISTINCT servidor.rgmilitar AS rgmilitar, ". 
                        "servidor.postograd || ' ' || servidor.rgmilitar || ' ' || servidor.nome AS nome, ".
                        "item.ordem ".
                "FROM efetivo.servidor JOIN opmv.item ON servidor.postograd = item.nome ".
                   "JOIN g_geral.opm ON servidor.unidadeid = opm.id ".
                   "JOIN bdhoras.historicotrabalho ON historicotrabalho.rgmilitar = servidor.rgmilitar ".
                   "JOIN bdhoras.turnos ON historicotrabalho.turnos_id = turnos.id ".
                "WHERE ".$txt_ativo." ".$txt_OPM." ".
                   "AND historicotrabalho.datainicio BETWEEN '".$dtinicio."' AND '".$dtfim."' ORDER BY item.ordem, nome ASC;";
         if ($this->so_OPM == 'N')    // ? "servidor.unidadeid=".$this->opm : "historicotrabalho.opm_id=".$this->opm;
         {
             $sql = "SELECT DISTINCT servidor.rgmilitar AS rgmilitar, ". 
                            "servidor.postograd || ' ' || servidor.rgmilitar || ' ' || servidor.nome AS nome, ".
                            "item.ordem ".
                    "FROM efetivo.servidor JOIN opmv.item ON servidor.postograd = item.nome ".
                       //"JOIN g_geral.opm ON servidor.unidadeid = opm.id ".
                       //"JOIN bdhoras.historicotrabalho ON historicotrabalho.rgmilitar = servidor.rgmilitar ".
                       //"JOIN bdhoras.turnos ON historicotrabalho.turnos_id = turnos.id ".
                    "WHERE ".$txt_ativo." servidor.unidadeid=".$this->opm." ".
                       //"AND historicotrabalho.datainicio BETWEEN '".$dtinicio."' AND '".$dtfim."' ORDER BY item.ordem, nome ASC;";
                       "ORDER BY item.ordem, nome ASC;";
          }
          else
          {
         $sql = "SELECT DISTINCT servidor.rgmilitar AS rgmilitar, ". 
                        "servidor.postograd || ' ' || servidor.rgmilitar || ' ' || servidor.nome AS nome, ".
                        "item.ordem ".
                "FROM efetivo.servidor JOIN opmv.item ON servidor.postograd = item.nome ".
                   "JOIN g_geral.opm ON servidor.unidadeid = opm.id ".
                   "JOIN bdhoras.historicotrabalho ON historicotrabalho.rgmilitar = servidor.rgmilitar ".
                   "JOIN bdhoras.turnos ON historicotrabalho.turnos_id = turnos.id ".
                "WHERE ".$txt_ativo." historicotrabalho.opm_id=".$this->opm." ".
                   "AND historicotrabalho.datainicio BETWEEN '".$dtinicio."' AND '".$dtfim."' ORDER BY item.ordem, nome ASC;";
          }
        $this->militares = $this->runQuery($sql);
        return $this->militares;
     }//Fim Módulo
 /*-------------------------------------------------------------------------------
  *        Busca a escala de um PM no intervalo definido
  *-------------------------------------------------------------------------------*/
     public function getEscalaPM($rgmilitar)
     {
         $dtinicio = $this->ano.'-'.$this->mes.'-01 00:00:00';
         $dtfim    = $this->ano.'-'.$this->mes.'-'.$this->qntDias.' 23:59:59';
         $sql = "SELECT DISTINCT servidor.rgmilitar AS rgmilitar , historicotrabalho.datainicio::date AS tdtrab, ".
             "historicotrabalho.status AS status, historicotrabalho.afastamento AS afastamento, ".
             "historicotrabalho.remunerada AS remunerada, historicotrabalho.nometurno AS nomeescala, ".
             "turnos.id AS turnos_id,turnos.nome AS nometurno,turnos.quarta AS quarta ".
             "FROM efetivo.servidor, bdhoras.historicotrabalho,bdhoras.turnos ".
             "WHERE servidor.rgmilitar = '".$rgmilitar."' AND".
             " historicotrabalho.turnos_id = turnos.id AND".
             " historicotrabalho.rgmilitar = servidor.rgmilitar AND".
             " historicotrabalho.datainicio BETWEEN '".$dtinicio."' AND '".$dtfim."' ORDER BY tdtrab, servidor.rgmilitar ASC;";
             //echo $sql;
         return $this->runQuery($sql);
     }//Fim Módulo
  /*-------------------------------------------------------------------------------
  *        Verifica se teve qualquer movimentação no periodo e armazena a escala
  *-------------------------------------------------------------------------------*/
     public function is_Afastado($afastamento=null)
     {
         if ($afastamento == $this->id_afasta)
         {
             $dados = $this->afastamento;
         }
         else
         {
             $sql = "SELECT * FROM bdhoras.afastamentos WHERE id=".$afastamento;
             $r = $this->runQuery($sql);
             $dados = (!empty($r)) ? $r[0] : false;
             $this->id_afasta = $afastamento;
             $this->afastamento = $dados;
         }
         if (is_array($dados))
         {
             if (strtoupper($dados['trabalha'])=='T')
             {
                 return false;//Retorna falso se ele trabalha no período
             }
         }
         return true;
     }//Fim Módulo
 /*-------------------------------------------------------------------------------
  *        Verifica se teve qualquer movimentação no periodo e armazena a escala
  *-------------------------------------------------------------------------------*/
     public function is_Escala($rgmilitar)
     {
         $dtinicio = $this->ano.'-'.$this->mes.'-01 00:00:00';
         $dtfim    = $this->ano.'-'.$this->mes.'-'.$this->qntDias.' 23:59:59';
         /*$sql = "SELECT * FROM bdhoras.historicotrabalho WHERE historicotrabalho.datainicio BETWEEN '" .
                 $dtinicio . "' AND '" . $dtfim . "' AND historicotrabalho.rgmilitar='" . $rgmilitar. "' ORDER BY datainicio;";*/
         $sql = "SELECT servidor.rgmilitar AS rgmilitar , historicotrabalho.datainicio as datainicio, ".
             " historicotrabalho.datafim as datafim, historicotrabalho.afastamentos_id AS afastamentos_id, ".
             "historicotrabalho.status AS status, historicotrabalho.afastamento AS afastamento, ".
             "historicotrabalho.remunerada AS remunerada, historicotrabalho.nometurno AS nometurno, ".
             "turnos.id AS turnos_id,turnos.nome AS nomeescala,turnos.quarta AS quarta ".
             "FROM efetivo.servidor, bdhoras.historicotrabalho,bdhoras.turnos ".
             "WHERE servidor.rgmilitar = '".$rgmilitar."' AND".
             " historicotrabalho.turnos_id = turnos.id AND".
             " historicotrabalho.rgmilitar = servidor.rgmilitar AND".
             " historicotrabalho.datainicio BETWEEN '".$dtinicio."' AND '".$dtfim."' ORDER BY datainicio, servidor.rgmilitar ASC;";
         $dados = $this->runQuery($sql);
         //print_r($dados); echo "<br>";
         $ret = (empty($dados)) ? false : true;
         $this->escala = ($ret==true) ? $dados : false;
         $sql = "SELECT DISTINCT * FROM efetivo.servidor where rgmilitar='".$rgmilitar."'";
         $pm = $this->runQuery($sql);
         $this->dados_pm = (!empty($pm)) ? $pm[0] : false;
         //var_dump($this->dados_pm);
         return $ret;
     }//Fim Módulo
 /*-------------------------------------------------------------------------------
  *        Verifica se a data é um feriado
  *-------------------------------------------------------------------------------*/
     public function is_feriado($data=null)
     {
         $this->diasFeriado = ($this->diasFeriado != false) ? $this->diasFeriado : $fer->getFeriado($this->ano);
         $data = (string) TDate::date2br ($data);
         //echo $data." = ";
         $ret = array_search ($data , $this->diasFeriado);
         //if ($ret!=false) echo $this->diasFeriado[$ret]."<br>";
         return ($ret != false ) ? true : false;
     }//Fim Módulo
 /*-------------------------------------------------------------------------------
  *        Busca Saldos de um PM no intervalo definido
  *-------------------------------------------------------------------------------*/
     public function getSaldosPM($rgmilitar=null)
     {
        $horas_dia = $this->horas_semanais/5; //Horas por dia trabalhado
        $dias_uteis = $this->getDiasUteis();
        $dtinicio = $this->ano.'-'.$this->mes.'-01 00:00:00';
        $dtfim    = $this->ano.'-'.$this->mes.'-'.$this->qntDias.' 23:59:59';
        $saldos = array();
        $filtro = '';
        $escalas = $this->escala;
        $dt_trab = 0;
        $dt_trab_desconto = 0;
        //$dt_trab = Dias trabalhados : $DT = $PM->busca("SELECT min(bdh_historico_trabalho_dt_hora_inicio) FROM bdhoras.bdh_historico_trabalho WHERE bdh_historico_trabalho_dt_hora_inicio BETWEEN '$ano-$mes-01 00:00:00' AND '$ano-$mes-$qtddias 23:59:59' AND bdh_historico_trabalho_rg=" . $key [0] . " AND (bdh_historico_trabalho_status='T' OR bdh_historico_trabalho_status='A') AND ((bdh_historico_trabalho_dt_hora_termino - bdh_historico_trabalho_dt_hora_inicio) <> '05:42:00') AND ((bdh_historico_trabalho_dt_hora_termino - bdh_historico_trabalho_dt_hora_inicio) <> '00:00:00') GROUP BY EXTRACT(DAY FROM bdh_historico_trabalho_dt_hora_inicio);");
        //--------------------------------------------------------------------------------------------------------------------
        //Calcula a quantidade de dias que iria trabalhar (mês fechado)
        //--------------------------------------------------------------------------------------------------------------------
        foreach ($escalas as $escala)
        {
            $dt_i = new DateTime ($escala['datainicio']);
            $dt_f = new DateTime ($escala['datafim']);
            $dif  =  $dt_i->diff($dt_f);
            $h_dif = $dif->format ("%H:%I:%S");
            if (($escala['status'] == 'T' || $escala['status'] == 'A') && ($h_dif!="05:42:00") && ($h_dif!="00:00:00" || $dt_i!=$dt_f))
            {
                $dia = substr($escala['datainicio'],0,10);
                if (false == strpos($filtro,$dia))
                {
                    $filtro .= '['.$dia.']';
                    $dt_trab ++;
                    $timestamp = date(substr($escala['datainicio'],0,10));
                    $semana    = date("w", strtotime(date(substr($escala['datainicio'],0,10))));
                    //Se é quarta feira e a escala prevê meio expediente, soma diferença
                    if($semana == 3 && strtoupper($escala['quarta']) == 'T')
                    {
                        $dt_trab_desconto = $dt_trab_desconto + 0.5;
                    } 
                } 
            } 
        }
        $saldos['dt_trab'] = $dt_trab;
        //--------------------------------------------------------------------------------------------------------------------
        //Calcula as faltas no período
        //--------------------------------------------------------------------------------------------------------------------
        $faltas = 0;
        $filtro = '';
        foreach ($escalas as $escala)
        {
            if ($escala['status'] == 'F')
            {
                $dia = substr($escala['datainicio'],0,10);
                if (false == strpos($filtro,$dia))
                {
                    $filtro .= '['.$dia.']';
                    $faltas ++;
                }
            } 
        }
        $saldos['faltas'] = $faltas;
        //--------------------------------------------------------------------------------------------------------------------
        //Calcula os dias afastados
        //--------------------------------------------------------------------------------------------------------------------
        $afast = 0;
        $afast_desconto = 0; //Dias uteis que estava no perido de afastamento
        $filtro = '';
        foreach ($escalas as $escala)
        {
            $id_afast = $escala['afastamentos_id'];
            if ($escala['status'] == 'A' && $this->is_afastado($id_afast)==true)
            {
                $dia = substr($escala['datainicio'],0,10);
                if (false == strpos($filtro,$dia))
                {
                    $filtro .= '['.$dia.']';
                    $afast ++;
                    $timestamp = date(substr($escala['datainicio'],0,10));
                    $semana    = date("w", strtotime(date(substr($escala['datainicio'],0,10))));
                    //Se for FDS ou Quarta meio expediente, compensa as horas
                    if($semana != 6 && $semana!=0 && $this->is_feriado($timestamp)!=true)//Não é fds nem feriado
                    {
                        $afast_desconto++;
                    } 
                    //if($semana == 3 && strtoupper($escala['quarta']) == 'F') $afast_desconto = $afast_desconto + 0.5;  
                }
            } 
        }
        $saldos['afastamento'] = $afast;
        //--------------------------------------------------------------------------------------------------------------------
        //Calcula os dias Dispensados
        //--------------------------------------------------------------------------------------------------------------------
        $disp = 0;
        $filtro = '';
        foreach ($escalas as $escala)
        {
            if ($escala['status'] == 'D')
            {
                $dia = substr($escala['datainicio'],0,10);
                if (false == strpos($filtro,$dia))
                {
                    $filtro .= '['.$dia.']';
                    $disp ++;
                }
            } 
        }
        $saldos['dispensa'] = $disp;
        $saldos['folga']    = $this->qntDias - ($dt_trab + $afast + $disp + $faltas);
        //--------------------------------------------------------------------------------------------------------------------
        //Calcula a quantidade de horas trabalhadas
        //--------------------------------------------------------------------------------------------------------------------
        $hr_trab = 0;
        $filtro = '';
        foreach ($escalas as $escala)
        {
            $dt_i = new DateTime ($escala['datainicio']);
            $dt_f = new DateTime ($escala['datafim']);
            $dif  =  $dt_i->diff($dt_f);
            $dias = $dif->format("%D");        //Verifica que houve escala de muitos dias
            $h_dif = $dif->format ("%H:%I:%S");//Pega horas quebradas de um dia
            
            if (($escala['status'] == 'T' || $escala['status'] == 'A' || $escala['status'] == 'D' ) && ($h_dif!="05:42:00") && ($h_dif!="00:00:00" || $dt_i!=$dt_f || $dias!=0))
            {
                $dia = $escala['datainicio'];
                if (false == strpos($filtro,$dia))
                {
                    $filtro .= '['.$dia.']';
                    $hr_trab = $hr_trab + $h_dif +(24*$dias);//Soma os 24 horas vezes os dias trabalhados mais as horas de diferença
                }
            } 
        }
        //Horas Trabalhadas
        $saldos['hr_trab']  = $hr_trab;
        //Horas que teria que trabalhar
        $saldos['hr_mes']   = (($dias_uteis - ($dt_trab_desconto + $afast_desconto)));
        //echo $dias_uteis . " -- " . $dt_trab_desconto . " --- " . $afast_desconto." horas dia".$horas_dia."<br>";
        $saldos['hr_mes'] = $this->round_horas(($saldos['hr_mes'] * $horas_dia));
        //Saldo de horas
        $saldos['hr_saldo'] = $this->round_horas((($saldos['hr_trab'] - $saldos['hr_mes'])));
        //echo "dias Uteis ".$dias_uteis. " ,desconto de afastamento ". $afast_desconto . " ,desconto de quarta " . $dt_trab_desconto."<br>";
        
        return $saldos;
     }//Fim Módulo
 /*-------------------------------------------------------------------------------
  *        Arredonda Horas para cima
  *-------------------------------------------------------------------------------*/
     public function round_horas($param = 0)
     {
         $inteira = (int) $param;
         $parte   = ($param - $inteira) * 60;
         //echo $param . "   -   ". $inteira." diferença ".$parte."<br>";
         if ($parte >=30)
         {
             $inteira++;
         }
         //echo " correção " . $inteira."<br>";
         return $inteira;
     }
 /*-------------------------------------------------------------------------------
  *        Executor de querys
  *-------------------------------------------------------------------------------*/
     public function runQuery($sql)
     {
        try
        {
            TTransaction::open('sicad');
            $conn = TTransaction::get();
            $res = $conn->prepare($sql);
            $res->execute();
            $retorno = $res->fetchAll(PDO::FETCH_NAMED);
            TTransaction::close();
            //echo $sql;
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
  *        Cabeçalho da tabela mensal
  *-------------------------------------------------------------------------------*/
     public function cabecalhoMensal()
     {
        $ano = $this->ano;
        $mes = $this->mes;
        $cmd = $this->scriptPrint();
        $r = $cmd['codigo']. "<div id='relatorio'>
                                <center>ESTADO DE GOIÁS<br>POLÍCIA MILITAR DO ESTADO DE GOIÁS<br><br>".
                                "<strong>ESCALA MENSAL</strong><br>".
                                "<br><strong>".strtoupper($this->unidade)."</strong></center><br>".
                                "<center><table border=1 cellspacing=0 cellpadding=1 style='font-size:10px; color:#000;'>";
        $r.=  "<tr>"
            . "<th rowspan=2 style='width: 25%;'> "
            . "<center>Nome do Policial</center>"
            . "</th>";
        $fer = new TFerramentas();
        $semana = $fer->lista_semana();
        for ($i = 1; $i <= $this->qntDias; $i ++) 
        {
            $dsem = date("w", strtotime("$ano/$mes/$i"));
            $cor = ($dsem==6 || $dsem==0) ? $this->fds : $this->sem;
            $r.= "<th style='background-color:$cor;'>";
            $r.= substr($semana[$dsem],0,3);
            $r.="</th>";//Mostra o Dia da Semana
        }
        $r.= "</tr>";
        for ($i = 1; $i <= $this->qntDias; $i ++) 
        {
            $dsem = date("w", strtotime("$ano/$mes/$i"));
            $cor = ($dsem==6 || $dsem==0) ? $this->fds : $this->sem;
            $r.= "<th style='background-color:$cor;'><center>" . str_pad($i, 2, '0', STR_PAD_LEFT) . "</center></th>";//Mostras os dias
        }
        $r.= "</tr>";
        $this->cabecalho = $r;
        return $r;
    }//Fim Módulo
 /*-------------------------------------------------------------------------------
  *        Cabeçalho da tabela Saldos
  *-------------------------------------------------------------------------------*/
     public function cabecalhoSaldo()
     {
        $ano = $this->ano;
        $mes = $this->mes;
        $cmd = $this->scriptPrint();
        $r = $cmd['codigo']. "<div id='relatorio'>
                                <center>ESTADO DE GOIÁS<br>POLÍCIA MILITAR DO ESTADO DE GOIÁS<br><br>".
                                "RELATÓRIO DE SALDO MENSAL<br>".
                                "<br><strong>".strtoupper($this->unidade)."</strong></center><br>".
                                "<center><table border=1 cellspacing=0 cellpadding=1 style='font-size:10px; color:#000;'>";
        $r.=  '<thead>
                    <tr>
                        <th width="350"><center>&nbsp;Policial&nbsp;</center></th>
                        <!--<th>&nbsp;Escala&nbsp;</th>-->
                        <th>&nbsp;Dias Trab. Mês&nbsp;</th>
                        <th>&nbsp;Faltas&nbsp;</th>
                        <th>&nbsp;Afast.&nbsp;</th>
                        <th>&nbsp;Disp.&nbsp;</th>
                        <th>&nbsp;Folga&nbsp;</th>
                        <th>&nbsp;Hrs. Trab.&nbsp;</th>
                        <th>&nbsp;Hrs. a Trab.&nbsp;</th>
                        <th>&nbsp;Saldo no Mes&nbsp;</th>
                    </tr>
               </thead>
               <tbody>';
        $this->cabecalho = $r;
        return $r;
    }//Fim Módulo
 /*-------------------------------------------------------------------------------
  *        Corpo da tabela mensal
  *-------------------------------------------------------------------------------*/
     public function corpoMensal($militar)
     {
        $ano = $this->ano;
        $mes = $this->mes;
        $r =  "";
        $fer = new TFerramentas();
        $semana = $fer->lista_semana();
        $results = $this->getEscalaPM($militar['rgmilitar']);//Carrega o trabalho mensal 
        $r .= "<tr>";//início
        $r .= "     <td>".$militar['nome'].
                   "</td>";

        $calendars = array();
        for ($i = 1; $i <= $this->qntDias; $i ++) 
        {
            $dados = array();
            foreach ($results as $key=>$result)
            {
                if ($i == (int) substr ($result['tdtrab'],8,2))
                {
                    $dados[] = $result;
                }
            }
            $calendars[$i] = (empty($dados)) ? array('0'=>"FOLGA") : $dados;
        }
        foreach ($calendars as $dia=>$calendar)
        {
            if (!array_key_exists('1',$calendar))
            {
                if (is_array($calendar['0']))
                {
                    $def = $this->geraTitle($calendar['0']);
                }
                else
                {
                    $def = array('cor'=>'darkblue','title'=>'Sem Escala','sigla'=>'S E');
                }
                $cor2 = ($def['cor']=='black') ? "#FFFFFF" : "#000";
                $r .= "<td style='background-color:".$def['cor']."; color:".$cor2."; text-align: center;' title='".$def['title']."'>";
                $r .= $def['sigla'];
            }
            else
            {
                $r .= "<td style='background-color: purple; color:#000; text-align: center;' title='";
                $title = '';
                foreach ($calendar as $parte)
                {
                    //print_r($parte); echo "<br>";
                    if (is_array($parte))
                    {
                        $def = $this->geraTitle($parte);
                        //print_r ($def);
                    }
                    else
                    {
                        $def = array('cor'=>'darkblue','title'=>'Sem Escala','sigla'=>'S E');
                    }
                    if ($title!='')
                    {
                        $title .='<br>';
                    }
                    $title.=$def['title'];
                }
                $r.=$title."'>";
                $r .= "V/E";
            }
            
            $r .= "</td>";
        }
        $r .= "</tr>";
        $this->corpo = $r;
        return $r;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
  *        Corpo da tabela Saldo Mensal
  *-------------------------------------------------------------------------------*/
     public function corpoSaldo($militar,$cor = 'ddd')
     {
        $ano = $this->ano;
        $mes = $this->mes;
        $r =  "";
        $fer = new TFerramentas();
        $r .= " <tr bgcolor='#".$cor."'>";
        $confirma = $this->is_Escala($militar['rgmilitar']);
        $r .= "<td>&nbsp;" . $this->dados_pm['postograd']. " RG " . $this->dados_pm['rgmilitar'] . " <strong>" .
            $this->dados_pm['nome'] . "</strong> " . "&nbsp;</td>";
        if (!$confirma)//Verifica se há lançamentos no período
        {
            $r .= "<td colspan=\"8\"><center><font color=\"#CC3333\"><strong>SEM LANÇAMENTOS NO PERÍODO</strong></font></center></td>";

        }
        else
        { 
            $saldos = $this->getSaldosPM();
            //$r .= "<td style=\"text-align: center;\">".'--'."</td>";
            $r .= "<td style=\"text-align: right;\">".$saldos['dt_trab']."</td>";
            $r .= "<td style=\"text-align: right;\">".$saldos['faltas']."</td>";
            $r .= "<td style=\"text-align: right;\">".$saldos['afastamento']."</td>";
            $r .= "<td style=\"text-align: right;\">".$saldos['dispensa']."</td>";
            $r .= "<td style=\"text-align: right;\">".$saldos['folga']."</td>";
            $r .= "<td style=\"text-align: right;\">".$saldos['hr_trab']."</td>";
            $r .= "<td style=\"text-align: right;\">".$saldos['hr_mes']."</td>";
            if ($saldos['hr_saldo']>=0)
            {
                $cor_saldo = 'green';
            }
            else
            {
                $cor_saldo = 'red';
            }
            $r .= "<td style=\"text-align: right; background-color:". $cor_saldo . ";\">".$saldos['hr_saldo']."</td>";
        }
        $r .= "</tr>";
        
        
        
        $this->corpo = $r;
        return $r;
    }//Fim Módulo
 /*-------------------------------------------------------------------------------
  *        Cria definições
  *-------------------------------------------------------------------------------*/
    public function geraTitle ($result=null)
    {
        if (!is_array($result))
        {
            return array ('cor'=>'blue','title'=>'ERRO','sigla'=>"ERR");
        }
        //var_dump($result);
        $nomeShow = $result['nometurno'];
        if ($result['afastamento']!=null)
        {
            if ($result['turnos_id'])
            {
                $color = ($result['status']=='T') ? 'gray' : 'lightgray';//Red
            }
            else
            {
                $color = ($result['status']=='P') ? 'lightred' : 'red';//Red
            }
            $nomeShow = $result['afastamento'];
        }
        else if ($result['turnos_id']==13)
        {
            $color = 'lightblue';
            switch ($result['status'])
            {
                case 'F':
                    $nomeShow.=' (FALTOU!)';
                    $color = 'black';
                    break;
                case 'D':
                    $nomeShow.=' (DISPENSADO!)';
                    $color = 'pink';
                    break;
                case 'T':
                    $nomeShow.=' (TRABALHADO!)';
                    $color = 'blue';
                    break;
                default:
                    $nomeShow.=' (PENDENTE!)';
                    break;
            }
        }
        else
        {
            $color = 'lightgreen';
            switch ($result['status'])
            {
                case 'F':
                    $nomeShow.=' (FALTOU!)';
                    $color = 'black';
                    break;
                case 'D':
                    $nomeShow.=' (DISPENSADO!)';
                    $color = 'pink';
                    break;
                case 'T':
                    $nomeShow.=' (TRABALHADO!)';
                    $color = 'green';
                    break;
                default:
                    $nomeShow.=' (PENDENTE!)';
                    break;
            }
        }
        $sigla = ($result['turnos_id']==13) ? "EXT" : substr($nomeShow,0,3);
        return array ('cor'=>$color,'title'=>$nomeShow,'sigla'=>strtoupper($sigla));
    }//Fim Módulo
 /*-------------------------------------------------------------------------------
  *        Principal do Relatório Mensal da OPM
  *-------------------------------------------------------------------------------*/
    public function mainMensal ($param=null)
    {

        $this->getMilitares();
        $militares = $this->militares;
        switch ($this->tipo)
        {
            case 1://Cria relatório do Tipo: Mensal da OPM
                $relatorio = $this->cabecalhoMensal();
                foreach ($militares as $key=>$militar)
                {
                    $relatorio .= $this->corpoMensal($militar);
                }
                $cmd = $this->scriptPrint();
                $relatorio.= "</center></table>".$this->assinatura."</div>".$cmd['botao'];
                break;
            case 2://Cria relatório do Tipo: Saldo Mensal da OPM
                $relatorio = $this->cabecalhoSaldo();
                $cor = 'ddd';
                foreach ($militares as $key=>$militar)
                {
                    $relatorio .= $this->corpoSaldo($militar,$cor);
                    $cor = ($cor == 'ddd') ? 'fff' : 'ddd';
                }
                $cmd = $this->scriptPrint();
                $relatorio.= "</center></tbody></table>".$this->assinatura."</div>".$cmd['botao'];
                break;
            case 3:
                
                break;
        }

        
        
        return $relatorio;
    }//Fim Módulo
 /*-------------------------------------------------------------------------------
  *        Script de impressão
  *-------------------------------------------------------------------------------*/
    public function scriptPrint ($param=null)
    {
        
        $script_print = '<script>
                            function impressao(elementodiv)
                            {
                                var ficha = document.getElementById(elementodiv);
                                var ventimp = window.open(" ", "popimpr");
                                ventimp.document.write( "<html><head><STYLE TYPE=\"text/css\">table{font-size:12px;} .folha { page-break-after: always; } </STYLE><title>IMPRESSORA</title></head><body>",ficha.innerHTML ,"</body></html>" );
                                ventimp.document.close();
                                ventimp.print();
                                ventimp.close();
                            }
                            function visualiza_impressao(elementodiv)
                            {
                                var ficha = document.getElementById(elementodiv);
                                var ventimp = window.open(" ","SIS-OPM");
                                ventimp.document.write( "<html><head><STYLE TYPE=\"text/css\">table{font-size:12px;} .folha { page-break-after: always; } </STYLE><title>IMPRESSORA</title></head><body>",ficha.innerHTML,"<center><br><input type=button name=voltar value=FECHAR onclick=window.close();></center></body></html>" );
                                ventimp.document.close();
                            }
                            </script>';
        $button_print = "<input type=\"button\" value = \"Imprimir\" id=\"impressora\" onclick=\"impressao('relatorio')\" title=\"IMPRIMIR\">".
                        "<input type=\"button\" value = \"Visualizar\" id=\"visualizar\" onclick=\"visualiza_impressao('relatorio')\" title=\"VIZUALIZA\">";
        return array('codigo'=>$script_print,'botao'=>$button_print);
    }//Fim Módulo
 }//Fim Classe
