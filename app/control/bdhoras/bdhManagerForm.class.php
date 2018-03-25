<?php
/**
 * bdhManagerForm
 * @author  <your name here>
 */
class bdhManagerForm extends TPage
{
    private $form;
    protected $quatro;
    protected $tres;
    protected $dois;
    protected $um_mes;
    
    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( )
    {

        parent::__construct();
        //parent::include_css('app/resources/myframe.css');
        
        $vbox = new TVBox;
        
        //Botões de Serviço
        $lancamento = new TButton('lancamento');
        $fechamento = new TButton('fechamento');
        $relatorio  = new TButton('relatorio');
        //Labels
        $lancamento->setLabel('Lançamento');
        $fechamento->setLabel('Fechamento');
        $relatorio->setLabel('Relatório');
        //Icones
        $lancamento->setImage('fa:exchange black');
        $fechamento->setImage('fa:floppy-o green');
        $relatorio->setImage('fa:file-text-o blue');
        //PopUps
        $lancamento->popover = 'true';
        $lancamento->popside = 'bottom';
        $lancamento->poptitle = 'Serviço de Lançamento';
        $lancamento->popcontent = 'Entre para: <br>Lançar escalas;<br>Criar/Remover Afastamentos'.
                                    '<br>Adicionar Escala Extra<br>Dispensar ou registrar faltas';
        $fechamento->popover = 'true';
        $fechamento->popside = 'bottom';
        $fechamento->poptitle = 'Fechamento de Escalas';
        $fechamento->popcontent = 'Entre para fechar escalas já lançadas para uma OPM.';
        $relatorio->popover = 'true';
        $relatorio->popside = 'bottom';
        $relatorio->poptitle = 'Relatórios';
        $relatorio->popcontent = 'Veja os Relatórios de trabalho.';
        //Classe dos botões
        $lancamento->class = 'btn btn-primary btn-lg';
        $relatorio->class = 'btn btn-info btn-lg';
        $fechamento->class = 'btn btn-warning btn-lg';
        //Scripts
        $lancamento->addFunction("__adianti_load_page('index.php?class=bdhLancamentoForm');");
        $fechamento->addFunction("__adianti_load_page('index.php?class=bdhFechamentoForm');");
        $relatorio->addFunction("__adianti_load_page('index.php?class=bdhRelatorioForm');");
        //Horizontal Box-01        
        $hbox1 = new THBox;
        $hbox1->addRowSet( $lancamento,$fechamento,$relatorio );
        $frame1 = new TFrame;
        $frame1->setLegend('Serviços de Banco de Horas');
        $frame1->add($hbox1);
        
        //Notificações
        // creates a table
        $table = new TTable;
        
        // creates a label with the title
        $title4 = new TLabel('4 Meses atrás');
        $title4->setFontSize(12);
        $title4->setFontFace('Arial');
        $title4->setFontColor('black');
        $title4->setFontStyle('b');
        
        $title3 = new TLabel('3 Meses atrás');
        $title3->setFontSize(12);
        $title3->setFontFace('Arial');
        $title3->setFontColor('black');
        $title3->setFontStyle('b');
        
        
        $title2 = new TLabel('2 Meses atrás');
        $title2->setFontSize(12);
        $title2->setFontFace('Arial');
        $title2->setFontColor('black');
        $title2->setFontStyle('b');
        
        $title1 = new TLabel('Mês passado');
        $title1->setFontSize(12);
        $title1->setFontFace('Arial');
        $title1->setFontColor('black');
        $title1->setFontStyle('b');
        
        $table-> border = '1';
        $table-> cellpadding = '4';
        $table-> style = 'border-collapse:collapse; text-align: center;';
        
        //4 meses atras
        $this->quatro = new TQuickGrid('quatro');
        $this->quatro->setHeight( 170 );
        $this->quatro->makeScrollable();
        $this->quatro->disableDefaultClick();
        $this->quatro->addQuickColumn('OPM', 'sigla', 'center');
        $this->quatro->createModel();
        
        $this->tres = new TQuickGrid('tres');
        $this->tres->setHeight( 170 );
        $this->tres->makeScrollable();
        $this->tres->disableDefaultClick();
        $this->tres->addQuickColumn('OPM', 'sigla', 'center');
        $this->tres->createModel();
        
        $this->dois = new TQuickGrid('dois');
        $this->dois->setHeight( 170 );
        $this->dois->makeScrollable();
        $this->dois->disableDefaultClick();
        $this->dois->addQuickColumn('OPM', 'sigla', 'center');
        $this->dois->createModel();
        
        $this->um_mes = new TQuickGrid('um_mes');
        $this->um_mes->setHeight( 170 );
        $this->um_mes->makeScrollable();
        $this->um_mes->disableDefaultClick();
        $this->um_mes->addQuickColumn('OPM', 'sigla', 'center');
        $this->um_mes->createModel();
        
        // adds a row to the table
        $row=$table->addRowSet($title4,$title3,$title2,$title1);
        $row=$table->addRowSet($this->quatro,$this->tres,$this->dois,$this->um_mes);
        
        $hbox2 = new THBox;
        $hbox2->addRowSet( $table );
        $frame2 = new TFrame;
        $frame2->setLegend('Escalas Não Fechadas');
        $frame2->add($hbox2);
        
        $vbox->style = 'width: 90%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', 'bdhManagerForm'));
        $vbox->add($frame1);
        $vbox->add($frame2);
        parent::add($vbox);
        
    }//Fim __construct
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function onReport ()
    {
        TApplication::loadPage('');
    }
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function onAppend ()
    {
        TApplication::loadPage('bdhLancamentoForm');
    }
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function onFechar ($param=null)
    {
        TApplication::loadPage('');
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function buscaEscalas ($param)
    {
         try
         {
            TTransaction::open('sicad');
            $datainicio = mktime(0, 0, 0, date('m')-$param , 1 , date('Y'));
            $datafim = mktime(23, 59, 59, date('m')-($param-1), 0, date('Y'));
            //echo 'início ' . date('Y-m-d',$datainicio);            echo ' fim ' . date('Y-m-d',$datafim);
            $sql = "SELECT DISTINCT opm.id,opm.nome,opm.sigla FROM bdhoras.historicotrabalho, g_geral.opm ".
                    "WHERE historicotrabalho.opm_id = opm.id AND historicotrabalho.status = 'P' AND ".
                    "historicotrabalho.datainicio BETWEEN '".date('Y-m-d',$datainicio)." 00:00:00' AND '".date('Y-m-d',$datafim)." 23:59:59';";
            $conn = TTransaction::get();
            $res = $conn->prepare($sql);
            $res->execute();
            $repository = $res->fetchAll();
            //var_dump($sql);

            TTransaction::close();
            return $repository;
         }
         catch (Exception $e)
         {
            new TMessage('error', $e->getMessage()."<br>Erro ao buscar dados de Unidades que não Escala.<br>".$sql);
            TTransaction::rollback();
            return false;
         }        
    }//Fim Módulo 
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function onReload($param)
    {
        for ($i = 1; $i <= 4; $i++) 
        {
            switch ($i)
            {
                case 1:
                    $this->um_mes->clear();
                    break;
                case 2:
                    $this->dois->clear();
                    break;
                case 3:
                    $this->tres->clear();
                    break;
                case 4:
                    $this->quatro->clear();
                    break;
            }
            
            $escalas = self::buscaEscalas($i); 
            if ($escalas)
            {
                foreach ($escalas as $escala)
                {
                    $item = new StdClass;
                    $item->sigla = $escala['sigla'];
                    switch ($i)
                    {
                        case 1:
                            $row = $this->um_mes->addItem( $item );
                            break;
                        case 2:
                            $row = $this->dois->addItem( $item );
                            break;
                        case 3:
                            $row = $this->tres->addItem( $item );
                            break;
                        case 4:
                            $row = $this->quatro->addItem( $item );
                            break;
                    }
                    $row->onmouseover='';
                    $row->onmouseout='';
                }
            }
            else
            {
                $item = new StdClass;
                $item->sigla = 'Nenhum Escala Aberta';
                switch ($i)
                {
                    case 1:
                        $row = $this->um_mes->addItem( $item );
                        break;
                    case 2:
                        $row = $this->dois->addItem( $item );
                        break;
                    case 3:
                        $row = $this->tres->addItem( $item );
                        break;
                    case 4:
                        $row = $this->quatro->addItem( $item );
                        break;
                }
                $row->onmouseover='';
                $row->onmouseout='';
            }
        }
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function show()
    {
        // check if the datagrid is already loaded
        if (!$this->loaded AND (!isset($_GET['method']) OR $_GET['method'] !== 'onReload') )
        {
            $this->onReload( func_get_arg(0) );
        }
        parent::show();
    }//Fim Módulo
        
}//Fim Classe