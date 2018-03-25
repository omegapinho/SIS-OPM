<?php
/**
 * escalaCalendarioView
 *
 * @version    1.0
 * @subpackage bdHoras
 * @author     Fernando de Pinho Araújo
  */
class escalaCalendarioView extends TWindow//TPage 
{
    private $fc;
    private $rgmilitar = null;
    
    /**
     * Page constructor
     */
    public function __construct($militares=null)
    {
        parent::__construct();
        //var_dump($militares[0]);
        if (array_key_exists(0,$militares))
        {
            TSession::setValue(__CLASS__.'_rgmilitar',$militares[0]);

        }
        parent::setTitle('Visualização da Escala mensal do Militar RG'.TSession::getValue(__CLASS__.'_rgmilitar'));
        $this->fc = new TFullCalendar(date('Y-m-d'), 'month');
        $this->fc->setReloadAction(new TAction(array($this, 'getEvents')));
        //$this->fc->setDayClickAction(new TAction(array('CalendarEventForm', 'onStartEdit')));
        $this->fc->setEventClickAction(new TAction(array('escalaCalendarioForm', 'onEdit')));
        //$this->fc->setEventUpdateAction(new TAction(array('CalendarEventForm', 'onUpdateEvent')));
        parent::add( $this->fc );
    }
    
    /**
     * Output events as an json
     */
    public static function getEvents($param=NULL)
    {
        $return = array();
        try
        {
            TTransaction::open('sicad');
            
            //$events = CalendarEvent::where('start_time', '>=', $param['start'])
            //                       ->where('end_time',   '<=', $param['end'])->load();

            //var_dump($param);
            $rgmilitar = TSession::getValue(__CLASS__.'_rgmilitar');
            $sql = "SELECT servidor.rgmilitar AS \"RG\", 
servidor.postograd || ' ' || servidor.nome AS \"NOME\",
sum(age(historicotrabalho.datafim,historicotrabalho.datainicio)) AS tot,
historicotrabalho.datainicio::timestamp AS dtinicio,
historicotrabalho.datafim::timestamp AS dtfim,turnos.\"descricao\",
historicotrabalho.status,turnos.\"nome\" AS nometurno,
(turnos.qnt_h_turno1::integer+turnos.qnt_h_turno2::integer) AS ttur,
historicotrabalho.afastamento, historicotrabalho.turnos_id, historicotrabalho.id as id_historico 
FROM efetivo.servidor
   JOIN g_geral.opm ON servidor.unidadeid = opm.id
   JOIN bdhoras.historicotrabalho ON historicotrabalho.rgmilitar= servidor.rgmilitar
   JOIN bdhoras.turnos ON historicotrabalho.turnos_id = turnos.id
WHERE
   servidor.rgmilitar in('$rgmilitar')".
   "AND datainicio BETWEEN '".$param['start']." 00:00:00' AND '".$param['end']." 23:59:59'".
"GROUP BY
   servidor.nome,servidor.postograd,
   dtinicio,dtfim,
   \"descricao\",turnos.\"nome\",
   historicotrabalho.status,
   historicotrabalho.afastamento,
   ttur,
   servidor.rgmilitar,
   opm.sigla,
   opm.id,
   historicotrabalho.turnos_id,
   historicotrabalho.id
ORDER BY dtinicio, \"NOME\",ttur,historicotrabalho.turnos_id ASC;";

            $conn = TTransaction::get();
            $res = $conn->prepare($sql);
            $res->execute();
            $results = $res->fetchAll();

            //var_dump ($events);$events=false;
            if ($results)
            {
                foreach ($results as $result)
                {
                    //$result = $escala->toArray();
                    $event_array=array();//Array que comporá o retorno com as escalas
                    
                    
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
                    
                    
                    $event_array['id']    = $result['id_historico'];
                    $event_array['title'] = $nomeShow;
                    $event_array['color'] = $color;
                    $event_array['description']='';
                    $event_array['start'] = str_replace( ' ', 'T', $result['dtinicio']);
                    $event_array['end'] = str_replace( ' ', 'T', $result['dtfim']);
                    $return[] = $event_array;
                }
            }
            TTransaction::close();
            echo json_encode($return);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }
    
    /**
     * Reconfigure the callendar
     */
    public function onReload($param = null)
    {
        if (isset($param['view']))
        {
            $this->fc->setCurrentView($param['view']);
        }
        
        if (isset($param['date']))
        {
            $this->fc->setCurrentDate($param['date']);
        }
    }
}
