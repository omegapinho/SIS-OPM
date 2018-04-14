<?php
/**
 * bdhLancamentoForm Registration
 * @author  <your name here>
 */
class bdhLancamentoForm extends TPage
{
    private $form;
    private $datagrid_opm;
    private $datagrid_slc;
    private $pageNavigation_opm;
    private $pageNavigation_slc;
    private $loaded_opm;
    private $loaded_slc;
    private $lista_opm;
    private $lisca_scl;
    
    var $popAtivo = true;
    var $sistema  = 'Banco de Horas';  //Nome do sistema que irá configurar(filtro)
    var $servico  = 'Lançamento';      //Nome da página de serviço.
    
    protected $nivel_sistema = false;  //Registra o nível de acesso do usuário
    protected $config        = array();//Array com configuração
    protected $config_load   = false;  //Informa que a configuração está carregada
    
    var $up_date_pm    = true;  //Ativa desativa atualização pessoal
    static $up_date_opm   = true;  //Ativa desativa atualização de OPM    
    
    //Nomes registrados em banco de configuração e armazenados na array config
    private $cfg_ord     = 'criar_ordinaria';
    private $cfg_ext     = 'criar_extra';
    private $cfg_afa     = 'criar_afastamento';
    private $cfg_cls_afa = 'limpar_afastamento';
    private $cfg_cls_esc = 'limpar_escala';
    private $cfg_chg_opm = 'troca_opm';
    private $cfg_nv_adm  = 'nivel_administrador';
    private $cfg_nv_ges  = 'nivel_operador';
    private $cfg_nv_vis  = 'nivel_visitante';
    private $cfg_pm_opm  = 'pm_de_outra_opm';
    
    private $opm_operador = false;     // Unidade do Usuário
    private $listas = false;           // Lista de valores e array de OPM
 
    /**
     * Class constructor
     * Creates the page and the registration form
     */
    function __construct()
    {
        parent::__construct();
        
        // creates the form
        $this->form = new TQuickForm('form_historicotrabalho');
        $this->form->class = 'tform';                      // change CSS class
        
        $this->form->style = 'display: table;width:100%';  // change style
        
        // Define Título da página
        $this->form->setFormTitle('Banco de Horas - Lançamento de Escalas');
        // Inicía ferramentas auxiliares
        $fer   = new TFerramentas();                        // Ferramentas diversas
        $sicad = new TSicadDados();                         // Ferramentas de acesso ao SICAD
        //Realiza definições iniciais de acesso
        $profile = TSession::getValue('profile');           //Profile da Conta do usuário
        if ($this->opm_operador==false)                     //Carrega OPM do usuário
        {
            //Confere se já foi carregado a OPM, senão carrega...ou se o ambiente for de desenvolvimento usa a OPM = 140
            $this->opm_operador = ($fer->is_dev()==true) ? 140 : $profile['unidade']['id'];
        }
        if (!$this->nivel_sistema || $this->config_load == false)    //Carrega OPMs que tem acesso
        {
            $this->nivel_sistema = $fer->getnivel (get_class($this));//Verifica qual nível de acesso do usuário
            $this->listas        = $sicad->get_OpmsRegiao($this->opm_operador);//Carregas as OPMs que o usuário administra
            $this->config = $fer->getConfig($this->sistema);         //Busca o Nível de acesso que o usuário tem para a Classe
            $this->config_load = true;                               //Informa que configuração foi carregada
        }
       
        // Cria os Itens do Formulário
        $rgmilitar           = new TEntry('rgmilitar');
        
        //Monta ComboBox com OPMs que o Operador pode ver
        //echo $this->nivel_sistema.'---'.$this->opm_operador;
        if ($this->nivel_sistema>=80)           //Adm e Gestor
        {
            $criteria = null;
        }
        else if ($this->nivel_sistema>=50 )     //Nível Operador (carrega OPM e subOPMs)
        {
            $criteria = new TCriteria;
            //Se não há lista de OPM, carrega só a OPM do usuário
            $lista = ($this->listas['valores']!='') ? $this->listas['valores'] : $profile['unidade']['id'];
            $query = "(SELECT DISTINCT id FROM g_geral.opm WHERE id IN (".$lista."))";
            $criteria->add (new TFilter ('id','IN',$query));
        }
        else if ($this->nivel_sistema<50)       //nível de visitante (só a própria OPM)
        {
            $criteria = new TCriteria;
            $query = "(SELECT DISTINCT id FROM g_geral.opm WHERE id IN (".$this->opm_operador."))";
            $criteria->add (new TFilter ('id','IN',$query));
        }
        $opm                 = new TDBCombo('opm','sicad','OPM','id','nome','nome',$criteria);
        
        $ativo               = new TCombo('ativo');
        $lista_opm           = new TSelect('lista_opm');
        $lista_slc           = new TSelect('lista_slc');
        //Critério para os Turnos de Serviço (Deve-se excluir os ocultos e o item id=13)
        $criteria = new TCriteria; 
        $criteria->add(new TFilter('oculto', '=', 'f'));
        $criteria->add(new TFilter('id', '!=', 13));
        $turno               = new TDBCombo('turno','sicad','turnos','id','nome','nome',$criteria);
        
        $datainicial         = new TDate('dataInicial');
        $datafinal           = new TDate('dataFinal');
        $horaIncialOrdinario = new TEntry('horaInicialOrdinario');
        $opm_id_info         = new TDBCombo('opm_id_info','sicad','OPM','id','sigla','sigla');
        $opm_info_atual      = new TCombo('OPM_info_Atual');
        $diasExtra           = new TEntry('diasExtra');
        $mesExtra            = new TCombo('mesExtra');
        $anoExtra            = new TCombo('anoExtra');
        $horaInicioExtra     = new TEntry('horaInicioExtra');
        $horasTrabalhadas    = new TEntry('horasTrabalhadas');
        $tipoExtra           = new TCombo('tipoExtra');
        $afasta_id           = new TDBCombo('afasta_id','sicad','afastamentos','id','nome','nome');
        $dtinicioaf          = new TDate('dtinicioaf');
        $dtfimaf             = new TDate('dtfimaf');
        $bgaf                = new TEntry('bgaf');
        $anobgaf             = new TEntry('anobgaf');
        
        //Formatar Itens
        $rgmilitar->setSize(80);
        $opm->setSize(300);
        $lista_opm->setSize(280,256);
        $lista_slc->setSize(280,256);
        $turno->setSize(200);
        $datainicial->setSize(80);
        $datafinal->setSize(80);
        $horaIncialOrdinario->setSize(50);
        $opm_id_info->setSize(150);
        $opm_info_atual->setSize(80);
        $diasExtra->setSize(200);
        $mesExtra->setSize(80);
        $anoExtra->setSize(80);
        $horaInicioExtra->setSize(50);
        $horasTrabalhadas->setSize(50);
        $tipoExtra->setSize(120);
        $afasta_id->setSize(150);
        $dtinicioaf->setSize(80);
        $dtfimaf->setSize(80);
        $bgaf->setSize(80);
        $anobgaf->setSize(80);
        $ativo->setSize(80);
        //Style
        $lista_opm->style = "font-size: 12px;";
        $lista_slc->style = "font-size: 12px;";

        //Mascaras
        $datainicial->setMask('dd-mm-yyyy');
        $datafinal->setMask('dd-mm-yyyy');
        $dtinicioaf->setMask('dd-mm-yyyy');
        $dtfimaf->setMask('dd-mm-yyyy');
        $horaIncialOrdinario->setMask('99:99');
        $horaInicioExtra->setMask('99:99');
        $horasTrabalhadas->setMask('99');

        //Dados
        $opm_info_atual->addItems($fer->lista_sim_nao());//($item);
        $opm_info_atual->setValue('N');
        $ativo->addItems($fer->lista_sim_nao());//($item);
        $ativo->setValue('N');
        //
        $item = array ("S"=>"Remunerada","N"=>"Administrativa");
        $tipoExtra->addItems($item);
        $tipoExtra->setValue('N');
        //
        $fer = new TFerramentas;
        $mesExtra->addItems($fer->lista_meses());
        $anoExtra->addItems($fer->lista_anos());
        //Tips
        $rgmilitar->setTip('Preencha com o RG do Militar pretendido...');
        $opm->setTip('Selecione a OPM para que possa preencher o campo de Lista da OPM com os componentes da Unidade.');
        $lista_opm->setTip('Lista dos Militares pertencente à Unidade Selecionada acima.<br>'.
                            'Vale lembrar que esta lista é atualizada diáriamente, assim os que estão aqui reflete as listas do SICAD.<br>' .
                            'Outro ponto a se considerar é a possibilidade de selecionar vários PMs, para isso basta usar<br>'.
                            'as teclas Control (ctrl) ou shift (seta pra cima);');
        $lista_slc->setTip('Militares Selecionados. Todos que estão nesta lista serão afetados, quer por uma escala ou por afastamentos...');
        $turno->setTip('Selecione uma Escala conforme a necessidade.');
        $datainicial->setTip('Selecione a data inicial da Escala Ordinária.');
        $datafinal->setTip('Selecione a data final da Escala Ordinária');
        $horaIncialOrdinario->setTip('Informe a hora de inicio do primeiro turno da Escala Ordinária.');
        $opm_id_info->setTip('Selecione qual foi a OPM informante da Escala.<br>É útil quando o militar está prestando serviços em uma OPM diferente da sua.');
        $opm_info_atual->setTip('A unidade Informante é a Unidade Atual? Se SIM, irei substituir a unidade que por ventura está na ficha do militar pela que foi informada...');
        $diasExtra->setTip('Defina os dias que o militar trabalhou podendo ser:<br> - Um dia apenas (Ex: 1);<br>- Alguns dias separados por vírgula(Ex: 2,5,8);<br>- Um intervalo de dias ligados por traço (Ex: 2-10);<br>- Um misto de combinações (Ex: 1,3-5,8,15-25). ');
        $mesExtra->setTip('Mês que ocorreu o serviço extra.');
        $anoExtra->setTip('Ano que ocorreu o serviço extra.');
        $horaInicioExtra->setTip('Hora que o serviço extra iniciou.');
        $horasTrabalhadas->setTip('Quantas horas foram trabalhadas neste serviço extra.');
        $tipoExtra->setTip('Defina se a escala foi Administrativa (sem remuneração AC-4) ou Remunerada (com pagamento de AC-4).');
        $afasta_id->setTip('Qual tipo de afastamento o militar fez jus.');
        $dtinicioaf->setTip('Data inicial do afastamento.');
        $dtfimaf->setTip('Data final do afastamento.');
        $bgaf->setTip('Numero do BG onde foi publicado o afastamento(Opcional).');
        $anobgaf->setTip('Ano de publicação do BG de Afastamento (Opcional).');
        $ativo->setTip('Se desejar que os militares inativos façam parte da seleção, marque como SIM para seleciona-los.'.
                        '<br>Caso troque esta opção, não haverá a limpeza dos já selecionados.');
        //Ações
        //$change_action = new TAction(array($this, 'onSelectOpm_old'));//Ação de Popular lista de PMs
        //$opm->setChangeAction($change_action);
        //$ativo->setChangeAction($change_action);
        
        //Controle de Nível
        if ($this->nivel_sistema<$this->config[$this->cfg_chg_opm])
        {
            $opm_id_info->setEditable(FALSE);
            $opm_info_atual->setEditable(FALSE);
        }
        //Botões
        //Seleciona PMs
        $addPM = new TButton('addPM');
        $addPM->setImage('fa:arrow-down black');
        $addPM->class = 'btn btn-primary btn-sm';
        $Action = new TAction(array($this, 'onSelectMilitar'));
        $addPM->setAction($Action);
        $addPM->setLabel('Seleciona');
        
        //Botão Gera Escala Ordinária
        $runOrd = new TButton('runOrd');
        $runOrd->setImage('fa:floppy-o red');
        $runOrd->class = 'btn btn-primary btn-sm';
        $Action = ($this->nivel_sistema>=$this->config[$this->cfg_ord]) ? new TAction(array($this, 'onGeraOrdinaria')) : new TAction(array($this, 'NoAcess'));
        $runOrd->setAction($Action);
        $runOrd->setLabel('Gera Escala');
        
        //Botão Gera Escala Extra
        $runExt = new TButton('runExt');
        $runExt->setImage('fa:floppy-o red');
        $runExt->class = 'btn btn-primary btn-sm';
        $Action = ($this->nivel_sistema>=$this->config[$this->cfg_ext]) ? new TAction(array($this, 'onGeraExtra')) : new TAction(array($this, 'NoAcess'));
        $runExt->setAction($Action);
        $runExt->setLabel('Gera Escala');
        
        //Botão Gera Afastamento
        $runAfa = new TButton('runAfa');
        $runAfa->setImage('fa:floppy-o red');
        $runAfa->class = 'btn btn-primary btn-sm';
        $Action = ($this->nivel_sistema>=$this->config[$this->cfg_afa]) ? new TAction(array($this, 'onGeraAfastamento')) : new TAction(array($this, 'NoAcess'));
        $runAfa->setAction($Action);
        $runAfa->setLabel('Gera Afastamento');
        
        //Botão Limpa Afastamento
        $runCls = new TButton('runCls');
        $runCls->setImage('fa:trash black');
        $runCls->class = 'btn btn-danger btn-sm';
        $Action = ($this->nivel_sistema>=$this->config[$this->cfg_cls_afa]) ? new TAction(array($this, 'onLimpaAfastamento')) : new TAction(array($this, 'NoAcess'));
        $runCls->setAction($Action);
        $runCls->setLabel('Limpa Afastamento');
        
        //Botão Verifica Escala
        $runVer = new TButton('runVer');
        $runVer->setImage('fa:eye black');
        $runVer->class = 'btn btn-info btn-sm';
        $Action = new TAction(array($this, 'onListaEscala'));
        $runVer->setAction($Action);
        $runVer->setLabel('Ver Escala');
        
        //Botão limpa Escalas
        $runLmp = new TButton('runLmp');
        $runLmp->setImage('fa:trash black');
        $runLmp->class = 'btn btn-danger btn-sm';
        $Action = ($this->nivel_sistema>=$this->config[$this->cfg_cls_esc]) ? new TAction(array($this, 'onLimpaEscala')) : new TAction(array($this, 'NoAcess'));
        $runLmp->setAction($Action);
        $runLmp->setLabel('Limpa Escala');
        
        //Botão Carrega OPM na Lista da OPM
        $runOpm = new TButton('runOpm');
        $runOpm->setImage('fa:retweet');
        $runOpm->class = 'btn btn-success btn-sm';
        $Action = new TAction(array($this, 'onSelectOpm_old'));
        $runOpm->setAction($Action);
        $runOpm->setLabel('Carrega OPM');
        
        $table = new TTable();
        $table-> border = '0';
        $table-> cellpadding = '4';
        $table-> style = 'border-collapse:collapse; text-align: center;';

        //Monta selecionador
        $hbox1 = new THBox;
        $hbox1->addRowSet( new TLabel('RG:'),$rgmilitar,$addPM,new TLabel('Unidade:'),$opm,new TLabel('Seleciona Inativos?'),$ativo,$runOpm);
        $frame1 = new TFrame;
        $frame1->setLegend('Selecione os PMs ou a OPM');
        $frame1->add($hbox1);
        //Monta Labels das tabelas de distribuição
        $title4 = new TLabel('Listagem da OPM');
        $title4->setFontSize(12);
        $title4->setFontFace('Arial');
        $title4->setFontColor('black');
        $title4->setFontStyle('b');
        
        $title3 = new TLabel('Comandos');
        $title3->setFontSize(12);
        $title3->setFontFace('Arial');
        $title3->setFontColor('black');
        $title3->setFontStyle('b');
        
        $title2 = new TLabel('PMs Selecionados');
        $title2->setFontSize(12);
        $title2->setFontFace('Arial');
        $title2->setFontColor('black');
        $title2->setFontStyle('b');
        
        $title1 = new TLabel('Gestão da Escala');
        $title1->setFontSize(12);
        $title1->setFontFace('Arial');
        $title1->setFontColor('black');
        $title1->setFontStyle('b');
        
        //Botões de Serviço
        $add = new TButton('add_opm');
        $del = new TButton('del_opm');
        $cls = new TButton('clear');
        $ret = new TButton('return');
        
        //Tabelas Auxiliares de Cadastro
        $table_ord    = new TTable;//Escalas Ordinárias
        $table_ext    = new TTable;//Escalas Extras
        $table_afa    = new TTable;//Afastamentos

        //Cria no Notebook 
        $notebook = new TNotebook(200, 220);
        // Crias as Abas no notebook
        $notebook->appendPage('Ordinária'  , $table_ord);
        $notebook->appendPage('Extra'      , $table_ext);
        $notebook->appendPage('Afastamento', $table_afa);

        //Itens: Escala Ordinária
        $table_ord->addRowSet(array(new TLabel('Escala'),$turno));
        $table_ord->addRowSet(array(new TLabel('De'),$datainicial,new TLabel('A'),$datafinal));
        $table_ord->addRowSet(array(new TLabel('Hora Inicial'),$horaIncialOrdinario));
        $table_ord->addRowSet(array(new TLabel('OPM Informante'),$opm_id_info));
        $table_ord->addRowSet(array(new TLabel('Usar Informante com Atual'),$opm_info_atual));
        $table_ord->addRowSet(array($runLmp,$runOrd));
        //Itens: Escala Extra
        $table_ext->addRowSet(array(new TLabel('Dias'),$diasExtra));
        $table_ext->addRowSet(array(new TLabel('Mês'),$mesExtra,new TLabel('Ano'),$anoExtra));
        $table_ext->addRowSet(array(new TLabel('Hr Início'),$horaInicioExtra,new TLabel('Hrs. Trab.'),$horasTrabalhadas));
        $table_ext->addRowSet(array(new TLabel('Tipo Escala'),$tipoExtra));
        $table_ext->addRowSet($runExt);
        //Itens: Afastamento
        $table_afa->addRowSet(array(new TLabel('Afastamento'),$afasta_id));
        $table_afa->addRowSet(array(new TLabel('De'),$dtinicioaf,new TLabel('A'),$dtfimaf));
        $table_afa->addRowSet(array(new TLabel('BG'),$bgaf,new TLabel('/'),$anobgaf));
        $table_afa->addRowSet(array($runCls,$runAfa));

        //Ações
        $add->setAction(new TAction(array($this, 'onAddPMSelect')));
        $del->setAction(new TAction(array($this, 'onDelPMSelect')));
        $cls->setAction(new TAction(array($this, 'onClearSelect')));
        $ret->setAction(new TAction(array($this, 'onReturn')));
        //Labels
        $add->setLabel('Adiciona');
        $del->setLabel('Remove');
        $cls->setLabel('Limpa');
        $ret->setLabel('Volta ao Gerenciador');
        //Icones
        $add->setImage('fa:plus green');
        $del->setImage('fa:minus red');
        $cls->setImage('fa:file-o black');
        $ret->setImage('fa:backward black');
        //Classes
        $ret->class = 'btn btn-warning';
        //PopUps
        if ($this->popAtivo)
        {
            $addPM->popover = 'true';
            $addPM->popside = 'top';
            $addPM->poptitle = 'Seleciona Militar';
            $addPM->popcontent = 'Clique aqui ou tecle ENTER para selecionar o militar.';
            //
            $add->popover = 'true';
            $add->popside = 'top';
            $add->poptitle = 'Adiciona Seleção de Militares';
            $add->popcontent = 'Adiciona o(s) Militar(es) selecionado(s) da caixa Lista da OPM.';
            //
            $del->popover = 'true';
            $del->popside = 'top';
            $del->poptitle = 'Remove Seleção de Militares';
            $del->popcontent = 'Remove o(s) Militar(es) selecionado(s) da caixa Selecionados.';
            //
            $cls->popover = 'true';
            $cls->popside = 'top';
            $cls->poptitle = 'Limpa toda Seleção de Militares';
            $cls->popcontent = 'Remove TODOS os Militares selecionados da caixa Selecionados.';
            //
            $ret->popover = 'true';
            $ret->popside = 'top';
            $ret->poptitle = 'Retorno à Tela de Gerenciamento';
            $ret->popcontent = 'Retorna para a Tela de Gerenciamento do Banco de Horas.<br>A lista e Militares já selecionados permanecerá até o fechamento do sistema.';
            //
            $runOrd->popover = 'true';
            $runOrd->popside = 'top';
            $runOrd->poptitle = 'Gera Escala Ordinária.';
            $runOrd->popcontent = 'São campos necessários:<br>- Turno;<br>- Data inicial e final;<br>- Hora de Início da Escala.';//.
            //
            $runExt->popover = 'true';
            $runExt->popside = 'top';
            $runExt->poptitle = 'Gera Escala Extra';
            $runExt->popcontent = 'São Campos necessários:<br>- Dias (preencher com um ou mais);<br>- Mês e Ano;<br>- Hora Início;<br>'.
                                    '- Horas Trabalhadas;<br>- Tipo Escala.';
            //
            $runCls->popover = 'true';
            $runCls->popside = 'top';
            $runCls->poptitle = 'Limpa Afastamentos e Restrições (apenas)';
            $runCls->popcontent = 'Use os campos acima como filtro.';
            //
            $runAfa->popover = 'true';
            $runAfa->popside = 'top';
            $runAfa->poptitle = 'Gera Afastamentos e Restrições';
            $runAfa->popcontent = 'São Campos necessários:<br>- Afastamento;<br>- O intervalo de datas.';
            //
            $runVer->popover = 'true';
            $runVer->popside = 'top';
            $runVer->poptitle = 'Verifica a Escala';
            $runVer->popcontent = 'É necessário escolher um militar (um apenas) quer no Campo Lista da OPM quer no Campo Selecionados.';
            //
            $runLmp->popover = 'true';
            $runLmp->popside = 'top';
            $runLmp->poptitle = 'Limpa as Escalas e Afastamentos';
            $runLmp->popcontent = 'Limpa Escalas (ordinária e Extra) e Afastamentos dos militares Selecionados e no intervalo de datas';
            
            $runOpm->popover = 'true';
            $runOpm->popside = 'top';
            $runOpm->poptitle = 'Carrega os militares da Unidade';
            $runOpm->popcontent = 'Carrega os Militares da unidade escolhida filtrando os ativos e inativos conforme se escolhe Sim ou Não no campo Seleciona inativos:';
        }
        //Tabela com Comandos
        $frame_tempo = new TFrame();
        $hboxc   = new THBox;
        $hboxc->addRowSet($add);
        $hboxc->addRowSet($del);
        $hboxc->addRowSet($cls);
        $hboxc->addRowSet($runVer);
        $hboxc->addRowSet($ret);

        $frame1->add($hboxc);
        $frame1->style = "width: 100%; display: table-cell; vertical-align: top; text-align: center;";

        //Frame com Lista da OPM
        $vbox2 = new TVBox;
        $frame4 = new TFrame(260,330);
        $frame4->setLegend('Lista de Militares da OPM');
        $frame4->add($lista_opm);
        $frame4->style = "width: 280px; display: table-cell; vertical-align: top;";
        $vbox2->add($frame4);
        //Frame de Seleção
        $vbox4 = new TVBox;
        $frame6 = new TFrame(260,330);
        $frame6->setLegend('Militares Selecionados');
        $frame6->add($lista_slc);
        $frame6->style = "width: 280px; display: table-cell; vertical-align: top;";
        $vbox4->add($frame6);
        //Frame de Geração
        $vbox5 = new TVBox;
        $frame3 = new TFrame(280,330);
        $frame3->setLegend('Funções de Geração');
        $frame3->add($notebook);
        $frame3->style = "width: 280px; display: table-cell; vertical-align: top;";
        $vbox5->add($frame3);
        
        $frame2 = new TFrame;
        $frame2->style = "width: 100%; display: table-cell; vertical-align: top;";
        $frame2->add($vbox2);
        $frame2->add($vbox4);
        $frame2->add($vbox5);

        $this->form->setFields(array($rgmilitar,$opm,$addPM,$lista_opm,$lista_slc,$opm_info_atual,$opm_id_info,$turno,
                                $datafinal,$datainicial,$dtinicioaf,$dtfimaf,$horaIncialOrdinario,$horaInicioExtra,$horasTrabalhadas,
                                $diasExtra,$mesExtra,$anoExtra,$bgaf,$anobgaf,$tipoExtra,$afasta_id,$ativo,
                                $add,$del,$cls,$ret,$runOrd,$runExt,$runAfa,$runCls,$runVer,$runLmp,$runOpm));        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'bdhManagerForm'));
        $container->add($frame1);
        //$container->add($frame_tempo);
        $container->add($frame2);
        $this->form->add($container);

        //parent::add($container);
        parent::add($this->form);
        if ($opm->getValue())
        {
            self::onSelectOpm_old(array('opm'=>$opm->getValue(),'ativo'=>$ativo->getValue()));
        }
        $lista_slc = TSession::getValue(__CLASS__.'_lista_slc');
        self::onLoadPMSelect();
        self::popula_escalas();

    }//Fim Módulo __contruct
/*---------------------------------------------------------------------------------------
 *            Carrega Militares na Seleção
 *---------------------------------------------------------------------------------------*/
    public function onAddPMSelect($param)
    {
        $data = $this->form->getData();
        $lista_opm = TSession::getValue(__CLASS__.'_lista_opm');
        $lista_slc = TSession::getValue(__CLASS__.'_lista_slc');
        $slc_opm   = TSession::getValue(__CLASS__.'_slc_opm');
        if (!$data->opm || $data->opm=='')
        {
            new TMessage ('info','É necessário selecionar uma OPM primeiro para se ter a lista de PMs da mesma.');
            $key = "XX";
        }
        else
        {
            if (!is_array($lista_slc))//Se lista_slc não existe ou não é array...inicia
            {
                $lista_slc = array('0'=>'- Nenhum Militar Selecionado -');
                $slc_opm   = array('0'=>'- Nenhum Militar Selecionado -');
            }
            $militares = (empty($data->lista_opm)) ? false : $data->lista_opm;
            if ($militares)
            {
                foreach ($militares as $rgmilitar)
                {
                    $lista_slc [$rgmilitar] = $lista_opm[$rgmilitar];//add militar
                    $slc_opm [$rgmilitar]   = $data->opm;            //add OPM
                }
                if (array_key_exists('0',$lista_slc))//Remove elemento zero, se existir
                { 
                    unset($lista_slc['0']);
                    unset($slc_opm['0']);
                }
             }    
            $key = $data->opm;                
        }
        $this->form->setData( $data ); //Atualiza form
        self::onSelectOpm_old(array('opm'=>$key,'ativo'=>$data->ativo));//Atualiza combo
        asort($lista_slc);
        TSession::setValue(__CLASS__.'_lista_slc',$lista_slc);
        TSession::setValue(__CLASS__.'_slc_opm',$slc_opm);
        TDBSelect::reload('form_historicotrabalho', 'lista_slc', $lista_slc);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *            Remove Militares da seleção
 *---------------------------------------------------------------------------------------*/
    public function onDelPMSelect($param)
    {
        $data = $this->form->getData();
        $lista_opm = TSession::getValue(__CLASS__.'_lista_opm');
        $lista     = TSession::getValue(__CLASS__.'_lista_slc');
        $slc_opm   = TSession::getValue(__CLASS__.'_slc_opm');
        $militares = $data->lista_slc;
        if ($lista && $militares)
        {
            foreach ($militares as $rgmilitar)
            {
                if (array_key_exists($rgmilitar,$lista))
                {
                     unset($lista [$rgmilitar]);   
                }
                if (array_key_exists($rgmilitar,$slc_opm))
                {
                    unset($slc_opm[$rgmilitar]);  
                }
            }
        }
        $this->form->setData( $data );
        if ($data->opm)
        {
            self::onSelectOpm_old(array('opm'=>$data->opm,'ativo'=>$data->ativo));
        }
        $lista = (!is_array($lista)) ? array('0'=>'- Nenhum Militar Selecionado -') : $lista;
        $lista = ($lista) ? $lista : array('0'=>'- Nenhum Militar Selecionado -');
        asort($lista);
        TSession::setValue(__CLASS__.'_lista_slc',$lista);
        TSession::setValue(__CLASS__.'_slc_opm',$slc_opm);
        TDBSelect::reload('form_historicotrabalho', 'lista_slc', $lista);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *            Limpa a Seleção Existente
 *---------------------------------------------------------------------------------------*/
    public function onClearSelect($param)
    {
        $data = $this->form->getData();
        $lista_opm = TSession::getValue(__CLASS__.'_lista_opm');
        $this->form->setData( $data );
        if ($data->opm)
        {
            self::onSelectOpm_old(array('opm'=>$data->opm,'ativo'=>$data->ativo));
        }
        $lista_slc = array('0'=>'- Nenhum Militar Selecionado -');
        TSession::setValue(__CLASS__.'_slc_opm','');
        TSession::setValue(__CLASS__.'_lista_slc',$lista_slc);
        TDBSelect::reload('form_historicotrabalho', 'lista_slc', $lista_slc);
        
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *             Seleciona um Militar apenas atraves do campo de pesquisa      
 *---------------------------------------------------------------------------------------*/
    public function onSelectMilitar($param)
    {
        $data = $this->form->getData();
        $lista_opm = TSession::getValue(__CLASS__.'_lista_opm');
        $lista_slc = TSession::getValue(__CLASS__.'_lista_slc');
        $slc_opm   = TSession::getValue(__CLASS__.'_slc_opm');
        $ativo = (array_key_exists('ativo',$param)) ? $param['ativo'] : 'N';
        $ativo = ($ativo != 'S') ? 'N' : 'S'; 
        if (!is_array($lista_slc))//Se lista_slc não existe ou não é array...inicia
        {
            $lista_slc = array('0'=>'- Nenhum Militar Selecionado -');
            $slc_opm   = array('0'=>'- Nenhum Militar Selecionado -');
        }
        $rgmilitar = $data->rgmilitar;
        if (strlen($rgmilitar)>3)
        {
            //var_dump($this->up_date_opm);
            //if ($this->up_date_opm)//Verifica se a atualização de PM está ativa.
            //{
                $ci = new TSicadDados;
                $ret = $ci->update_militar($rgmilitar);//atualiza militar avulso
            //}
            try
            {
                TTransaction::open('sicad');
                $militares  = servidor_tag::where('rgmilitar', '=', $rgmilitar)->load();//Lista miliar (objeto)
                TTransaction::close();
            }
            catch (Exception $e)
            {
                new TMessage('error', $e->getMessage()); // shows the exception error message
                TTransaction::rollback(); // undo all pending operations 
                $militares = false; 
            }
            try
            {
                if(!$militares)
                {
                    throw new Exception ('Militar não localizado...');
                }
                foreach ($militares as $militar)
                {
                    $milico = $militar;
                }
                $militar = $milico;
                //print_r($militar);
                //$militar = (array_key_exists(0,$militares)) ? $militares[0] : false;//Extrair somente um militar
                if($militar)
                {
                    //print_r($militar);echo '<br>';echo $militar->status . ' ' . $ativo ;
                    if (($militar->status != 'ATIVO' && $ativo == 'S') || ($militar->status == "ATIVO"))
                    {
                        //var_dump($this->listas);
                        //echo 'ativo verificado';
                        if (!array_search ($militar->unidadeid,$this->listas['lista']) && $this->opm_operador!=0 && $this->nivel_sistema<90)
                        {
                            throw new Exception ('Militar não está nas unidades que gerencia...');
                        }
                        //
                        //echo 'unidade a verificar' . $militar->unidadeid . ' - ' . $data->opm;
                        if ($militar->unidadeid != $data->opm && !empty($data->opm)) 
                        {
                            //Ações para quando o militar não é da Mesma OPM listada na combo OPM
                            $dados = array ('rgmilitar'=>$militar->rgmilitar,'postograd'=>$militar->postograd,
                                            'nome'=>$militar->nome,'unidadeid'=>$militar->unidadeid,'data'=>(array)$data);
                            //echo 'verificar ação'.$this->config[$this->cfg_pm_opm];
                            switch ($this->config[$this->cfg_pm_opm])   
                            {
                                case "NOTIFICA":
                                    {
                                        new TMessage ('info','O Militar a incluir não pertence a OPM selecionada.'.
                                                             '<br>Confira se a seleção está correta');
                                        self::selectMilitar($dados,"NO");
                                        break;
                                    }
                                case "IGNORA":
                                    {
                                        throw new Exception ('Militar está em uma unidade diferente da listada e não será selecionado.');
                                        break;
                                    }
                                case "QUESTIONA":
                                    {
                                        $action1  = new TAction(array($this, 'selectMilitar'));
                                        $action1->setParameter('dados', $dados);
                                        $this->form->setData( $data );
                                        $question = new TQuestion('O militar não se encontra na Unidade Selecionada.'.
                                                                    '<br>Inclui ele na lista de Selecionados ?', $action1);
                                        $this->form->setData( $data );
                                        $key = (!$data->opm || $data->opm=='') ? "XX" : $data->opm; 
                                        self::onSelectOpm_old(array('opm'=>$key,'ativo'=>$data->ativo));
                                        
                                        $lista_slc = ($lista_slc) ? $lista_slc : array('0'=>'-Nenhum Militar Selecionado-');
                                        $slc_opm   = ($slc_opm)   ? $slc_opm   : array('0'=>'-Nenhum Militar Selecionado-');
                                        TDBSelect::reload('form_historicotrabalho', 'lista_slc', $lista_slc);
                                        asort($lista_slc);
                                        TSession::setValue(__CLASS__.'_lista_slc',$lista_slc);
                                        TSession::setValue(__CLASS__.'_slc_opm',$slc_opm);
                                        break;
    
                                    }
                                default :
                                    {
                                        self::selectMilitar($dados,"NO");
                                        break;
                                    }
                            }
                        }
                        else
                        {
                            $dados = array ('rgmilitar'=>$militar->rgmilitar,'postograd'=>$militar->postograd,
                                            'nome'=>$militar->nome,'unidadeid'=>$militar->unidadeid,'data'=>(array)$data);
                            self::selectMilitar($dados,"NO");
                        }
                    }
                    else
                    {
                        throw new Exception ('Militar Aparentemente está inativo, Verifique por favor...');
                    }
                }
            }
            catch (Exception $e)
            {
                $this->form->setData( $data );
                $key = (!$data->opm || $data->opm=='') ? "XX" : $data->opm; 
                self::onSelectOpm_old(array('opm'=>$key,'ativo'=>$data->ativo));
                
                $lista_slc = ($lista_slc) ? $lista_slc : array('0'=>'-Nenhum Militar Selecionado-');
                $slc_opm   = ($slc_opm)   ? $slc_opm   : array('0'=>'-Nenhum Militar Selecionado-');
                TDBSelect::reload('form_historicotrabalho', 'lista_slc', $lista_slc);
                asort($lista_slc);
                TSession::setValue(__CLASS__.'_lista_slc',$lista_slc);
                TSession::setValue(__CLASS__.'_slc_opm',$slc_opm);
                new TMessage('error', $e->getMessage()); // shows the exception error message
            }
        }
    }//Fim Módulo
    
    
/*---------------------------------------------------------------------------------------
 *             Após aprovação dos critérios, realiza seleção de PM    
 *---------------------------------------------------------------------------------------*/
    public function selectMilitar ($param=null,$origem='QUESTION')
    {
        $lista_opm = TSession::getValue(__CLASS__.'_lista_opm');
        $lista_slc = TSession::getValue(__CLASS__.'_lista_slc');
        $slc_opm   = TSession::getValue(__CLASS__.'_slc_opm');
       
        if ($origem == "QUESTION")
        {
            $militar   = (object) $param['dados'];
        }
        elseif ($origem=="NO")
        {
            $militar = (object) $param;
        }
        $data = (object) $militar->data;//$this->form->getData();
        $lista_slc [$militar->rgmilitar] = $militar->rgmilitar.' - '.$militar->postograd.' '.$militar->nome;
        $slc_opm   [$militar->rgmilitar] = $militar->unidadeid;
        if (array_key_exists('0',$lista_slc))
        { 
            unset($lista_slc['0']);
        }
        $this->form->setData( $data );
        $key = (!$data->opm || $data->opm=='') ? "XX" : $data->opm; 
        self::onSelectOpm_old(array('opm'=>$key,'ativo'=>$data->ativo));
        
        $lista_slc = ($lista_slc) ? $lista_slc : array('0'=>'-Nenhum Militar Selecionado-');
        $slc_opm   = ($slc_opm)   ? $slc_opm   : array('0'=>'-Nenhum Militar Selecionado-');
        TDBSelect::reload('form_historicotrabalho', 'lista_slc', $lista_slc);
        asort($lista_slc);
        TSession::setValue(__CLASS__.'_lista_slc',$lista_slc);
        TSession::setValue(__CLASS__.'_slc_opm',$slc_opm);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *            Gera Escala Ordinária
 *---------------------------------------------------------------------------------------*/
    public function onGeraOrdinaria($param)
    {
        $data = $this->form->getData();
        $lista_opm = TSession::getValue(__CLASS__.'_lista_opm');
        $lista_slc = TSession::getValue(__CLASS__.'_lista_slc');
        $slc_opm   = TSession::getValue(__CLASS__.'_slc_opm');
        $fer = new TFerramentas();
        $data->OPM_info_atual = ($data->OPM_info_Atual) ? $data->OPM_info_Atual : 'N';
        $slc_opm_go = ($data->OPM_info_Atual=="N") ? $slc_opm : self::onChangeOPM ($slc_opm,$data->opm_id_info);
        if ($fer->isValidData($data->dataInicial) && $fer->isValidData($data->dataFinal)  && $fer->isValidHora($data->horaInicialOrdinario)
            && $data->turno >0 && $lista_slc)
        {
            $ci = new TBdhGerador();
            $result = $ci->main_escala($lista_slc,$slc_opm_go,'ORDINARIA',$data->dataInicial,$data->dataFinal,
                                        $data->horaInicialOrdinario,$data->turno,
                                        '','','','','','',
                                        "","","","","",$data->opm_id_info);
        }
        else
        {
            new TMessage('info','Não é possível gerar a Escala Ordinária. <br>Preencha os dados corretamente bem como faça a seleção de militares.');
        }
        $this->form->setData( $data );
        if ($data->opm)
        {
            self::onSelectOpm_old(array('opm'=>$data->opm,'ativo'=>$data->ativo));
        }
        TSession::setValue(__CLASS__.'_lista_slc',$lista_slc);
        TDBSelect::reload('form_historicotrabalho', 'lista_slc', $lista_slc);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *            Gera Escala Extra
 *---------------------------------------------------------------------------------------*/
    public function onGeraExtra($param)
    {
        $data = $this->form->getData();
        $lista_opm = TSession::getValue(__CLASS__.'_lista_opm');
        $lista_slc = TSession::getValue(__CLASS__.'_lista_slc');
        $slc_opm   = TSession::getValue(__CLASS__.'_slc_opm');
        $fer = new TFerramentas();
        $data->OPM_info_atual = ($data->OPM_info_Atual) ? $data->OPM_info_Atual : 'N';
        $slc_opm_go = ($data->OPM_info_Atual=="N") ? $slc_opm : self::onChangeOPM ($slc_opm,$data->opm_id_info);
        if ($fer->isValidHora($data->horaInicioExtra) && $data->diasExtra && $data->mesExtra>0 && 
            $data->anoExtra>0 && ((int) $data->horasTrabalhadas>0 && (int) $data->horasTrabalhadas<=24) && ($data->tipoExtra=='N' || $data->tipoExtra=='S'))
        {
            $ci = new TBdhGerador();
            //$dias=null,$mesx=null,$anox=null,$hrinix=null,$hrtrab=null,$tescala='1'
            $result = $ci->main_escala($lista_slc,$slc_opm_go,'EXTRA','','','','',
                                        $data->diasExtra,$data->mesExtra,$data->anoExtra,$data->horaInicioExtra,
                                        $data->horasTrabalhadas,$data->tipoExtra,
                                        "","","","","",$data->opm_id_info);
        }
        else
        {
            new TMessage('info','Não é possível gerar a Escala Extra. <br>Preencha os dados corretamente bem como faça a seleção de militares.');
        }
        $this->form->setData( $data );
        if ($data->opm)
        {
            self::onSelectOpm_old(array('opm'=>$data->opm,'ativo'=>$data->ativo));
        }
        TSession::setValue(__CLASS__.'_lista_slc',$lista_slc);
        TDBSelect::reload('form_historicotrabalho', 'lista_slc', $lista_slc);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *            Gera Afastamentos
 *---------------------------------------------------------------------------------------*/
    public function onGeraAfastamento($param)
    {
        $data = $this->form->getData();
        $lista_opm = TSession::getValue(__CLASS__.'_lista_opm');
        $lista_slc = TSession::getValue(__CLASS__.'_lista_slc');
        $slc_opm   = TSession::getValue(__CLASS__.'_slc_opm');
        $slc_opm_go = ($data->OPM_info_Atual=="N") ? $slc_opm : self::onChangeOPM ($slc_opm,$data->opm_id_info);
        //if (self::is_validAfastamento($data->dtinicioaf,$data->dtfimaf,$data->afasta_id) && $data->bgaf &&$data->anobgaf)
        if (self::is_validAfastamento($data->dtinicioaf,$data->dtfimaf,$data->afasta_id))
        {
            $ci = new TBdhGerador_new();
            //$dias=null,$mesx=null,$anox=null,$hrinix=null,$hrtrab=null,$tescala='1'
            //$dtinicioaf=null,$dtfimaf=null,$bgaf=null,$anobgaf=null,$afasta_id)
            $bgaf     = ($data->bgaf) ? $data->bgaf : "NC";
            $anobgaf  = ($data->bgaf) ? $data->bgaf : "NC";
            $result   = $ci->main_escala($lista_slc,$slc_opm_go,'AFASTA',
                                        '','','','',
                                        '','','','','','',
                                        $data->dtinicioaf,$data->dtfimaf,$bgaf,$anobgaf,
                                        $data->afasta_id,$data->opm_id_info);
        }
        else
        {
            new TMessage('info','É necessário pelo menos as datas inicial e final bem como o Tipo de Afastamento para prosseguir.<br> Os dados do Boletim é opcional.');
        }
        $this->form->setData( $data );
        if ($data->opm)
        {
            self::onSelectOpm_old(array('opm'=>$data->opm,'ativo'=>$data->ativo));
        }
        TSession::setValue(__CLASS__.'_lista_slc',$lista_slc);
        TDBSelect::reload('form_historicotrabalho', 'lista_slc', $lista_slc);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *            Limpa a Escala Total
 *---------------------------------------------------------------------------------------*/
    public function limpaEscala($param)
    {
        $data = (object) $param['data'];
        $form = new stdClass;
        $form = $data;
        $lista_opm = TSession::getValue(__CLASS__.'_lista_opm');
        $lista_slc = TSession::getValue(__CLASS__.'_lista_slc');
        $slc_opm   = TSession::getValue(__CLASS__.'_slc_opm');
        $fer = new TFerramentas();
        $data->OPM_info_atual = ($data->OPM_info_Atual) ? $data->OPM_info_Atual : 'N';
        $slc_opm_go = ($data->OPM_info_Atual=="N") ? $slc_opm : self::onChangeOPM ($slc_opm,$data->opm_id_info);
        if ($fer->isValidData($data->dataInicial) && $fer->isValidData($data->dataFinal) && $lista_slc)
        {
            $ci = new TBdhGerador();
            $result = $ci->main_escala($lista_slc,$slc_opm_go,'CLEARESCALA',$data->dataInicial,$data->dataFinal,
                                        $data->horaInicialOrdinario,$data->turno,
                                        '','','','','','',
                                        "","","","","",$data->opm_id_info);
        }
        else
        {
            new TMessage('info','Não é possível limpar a Escala. <br>Preencha as datas Inicial e Final para prosseguir.<br>É necessário que, também, haja algum militar Selecionado.');
        }
        $this->form->setData($form);
        if ($data->opm)
        {
            self::onSelectOpm_old(array('opm'=>$data->opm,'ativo'=>$data->ativo));
        }
        TSession::setValue(__CLASS__.'_lista_slc',$lista_slc);
        TDBSelect::reload('form_historicotrabalho', 'lista_slc', $lista_slc);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *                   Carrega Militares na OPM
 *---------------------------------------------------------------------------------------*/
    public static function onSelectOpm_old($param)
    {
        if (array_key_exists('opm',$param))
        {
            $key = $param['opm'];
            if ($key=='')
            {
                return;
            }
        }
        else
        {
            return;
        }
        $ativo = (array_key_exists('ativo',$param)) ? $param['ativo'] : 'N';
        $ativo = ($ativo != 'S') ? 'N' : 'S'; 
        //echo $ativo.'---'.$key."<br>";var_dump($key);
        if ($key!="XX")
        {
            if (self::$up_date_opm)//Verifica se a atualização de OPM está ativa.
            {
                $ci = new TSicadDados();
                $ret = $ci->update_pm_opm($key);
                $tranf = $ci->update_transferidos();
            }
        }
        try
        {
            if ($key != "XX")
            {
                TTransaction::open('sicad'); // open a transaction
                TTransaction::setLogger(new TLoggerTXT('tmp/efetivo.txt')); 
                $sql = "SELECT DISTINCT servidor.rgmilitar AS rgmilitar, ". 
                                "'[postograd] ' || servidor.rgmilitar || ' ' || servidor.nome AS nome, ".
                                "item.ordem, item.id AS postograd_id ".
                        "FROM efetivo.servidor JOIN opmv.item ON servidor.postograd = item.nome ".
                        "WHERE unidadeid ='".$key."' ";
                if ($ativo =='N')
                {
                    $sql .= "AND status = 'ATIVO' "; 
                }
                $sql .="ORDER BY item.ordem, nome ASC;";
                TTransaction::log($sql);
                $conn = TTransaction::get();
                $res = $conn->prepare($sql);
                $res->execute();
                $militares = $res->fetchAll();
                TTransaction::close(); // close the transaction
                $lista = array();
                $cara = $ci->caracteristicas_SICAD('postograd_sigla');
                //var_dump($cara);
                foreach ($militares as $militar)
                {
                    $postograd = '';
                    //var_dump($militar);
                    if (array_key_exists('postograd_id',$militar) && $militar['postograd_id'] != null)
                    {
                        if (array_key_exists($militar['postograd_id'],$cara))
                        {
                            $postograd = $cara[$militar['postograd_id']];
                        }
                        else
                        {
                            $postograd = 'NC';
                        }
                    }
                    $militar['nome'] = str_replace("'","`",$militar['nome']);
                    $lista[$militar['rgmilitar']] = str_replace('[postograd]',$postograd . ' RG ',$militar['nome']);
                }
            }
            else
            {
                $lista = array('0'=>'- Nenhuma OPM Selecionada -');
            }
            TSession::setValue(__CLASS__.'_lista_opm', $lista);
            TSession::setValue(__CLASS__.'_id_opm', $key);
            TDBSelect::reload('form_historicotrabalho', 'lista_opm', $lista);
        }
        catch (Exception $e) // in case of exception 
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            var_dump($militar);
            TTransaction::rollback(); // undo all pending operations            
        }

    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *            Carrega Militares na Seleção
 *---------------------------------------------------------------------------------------*/
    public static function onLoadPMSelect($param=null)
    {
        //$data = $this->form->getData();
        $lista_opm = TSession::getValue(__CLASS__.'_lista_opm');
        $lista_slc = TSession::getValue(__CLASS__.'_lista_slc');
        $slc_opm   = TSession::getValue(__CLASS__.'_slc_opm');
        if (!is_array($lista_slc))//Se lista_slc não existe ou não é array...inicia
        {
            $lista_slc = array('0'=>'- Nenhum Militar Selecionado -');
            $slc_opm   = array('0'=>'- Nenhum Militar Selecionado -');
        }
        $militares = (empty($data->lista_opm)) ? false : $data->lista_opm;
        if ($militares)
        {
            foreach ($militares as $rgmilitar)
            {
                $lista_slc [$rgmilitar] = $lista_opm[$rgmilitar];//add militar
                $slc_opm [$rgmilitar]   = $data->opm;            //add OPM
            }
            if (array_key_exists('0',$lista_slc))//Remove elemento zero, se existir
            { 
                unset($lista_slc['0']);
                unset($slc_opm['0']);
            }
         }    
        //$this->form->setData( $data ); //Atualiza form
        //self::onChangeAction_selectopm(array('opm'=>$key));//Atualiza combo
        asort($lista_slc);
        TSession::setValue(__CLASS__.'_lista_slc',$lista_slc);
        TSession::setValue(__CLASS__.'_slc_opm',$slc_opm);
        TDBSelect::reload('form_historicotrabalho', 'lista_slc', $lista_slc);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *                   Carrega Turnos
 *---------------------------------------------------------------------------------------*/
    public static function popula_escalas ($param=null)
    {
        try
        {
            TTransaction::open('sicad'); // open a transaction
            $criteria = new TCriteria; 
            $criteria->add(new TFilter('oculto', '=', 'f'));
            $criteria->add(new TFilter('id', '!=', 13)); 
            $repository = new TRepository('turnos'); 
            $options = $repository->orderBy('id')->load($criteria); 
            TTransaction::close(); // close the transaction
            $lista = array();
            foreach ($options as $option)
            {
                $lista[$option->id] = $option->nome;
            }
            //asort($lista);
            TCombo::reload('form_historicotrabalho', 'turno', $lista);
        }
        catch (Exception $e) // in case of exception 
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations            
        }
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *                   Retorna Listagem
 *---------------------------------------------------------------------------------------*/
     public function onReturn ($param)
     {
         TApplication::loadPage('bdhManagerForm');
     }//Fim módulo
/*---------------------------------------------------------------------------------------
 *                   Abre janela de nova Escala
 *---------------------------------------------------------------------------------------*/
     public function onListaEscala ($param)
     {
            $data = $this->form->getData();
            $this->form->setData( $data );
            if ($data->opm)
            {
                self::onSelectOpm_old(array('opm'=>$data->opm,'ativo'=>$data->ativo));
            }
            $lista_opm = TSession::getValue(__CLASS__.'_lista_opm');
            $lista_slc = TSession::getValue(__CLASS__.'_lista_slc');
            $slc_opm   = TSession::getValue(__CLASS__.'_slc_opm');
            TDBSelect::reload('form_historicotrabalho', 'lista_slc', $lista_slc);
            
            if ((self::is_unitario($data->lista_opm) && !self::is_unitario($data->lista_slc)) || 
                (!self::is_unitario($data->lista_opm) && self::is_unitario($data->lista_slc)))
            {
                $militares = (self::is_unitario($data->lista_opm)) ? $data->lista_opm : $data->lista_slc;
                TApplication::loadPage('escalaCalendarioView','onLoad',$militares);
            }
            else
            {
                new TMessage ('info','Escolha apenas um militar, quer na lista da OPM, quer na lista Seleção...');
            } 
            
     }//Fim módulo 
/*---------------------------------------------------------------------------------------
 *                   Abre janela de Afastamentos
 *---------------------------------------------------------------------------------------*/
     public function onVerAfastamento ($param)
     {
            $data = $this->form->getData();
            $this->form->setData( $data );
            if ($data->opm)
            {
                self::onSelectOpm_old(array('opm'=>$data->opm,'ativo'=>$data->ativo));
            }
            $lista_opm = TSession::getValue(__CLASS__.'_lista_opm');
            $lista_slc = TSession::getValue(__CLASS__.'_lista_slc');
            $slc_opm   = TSession::getValue(__CLASS__.'_slc_opm');
            TDBSelect::reload('form_historicotrabalho', 'lista_slc', $lista_slc);
            
            if ((self::is_unitario($data->lista_opm) && !self::is_unitario($data->lista_slc)) || 
                (!self::is_unitario($data->lista_opm) && self::is_unitario($data->lista_slc)))
            {
    
            }
            else
            {
                new TMessage ('info','Escolha apenas um militar, quer na lista da OPM, quer na lista Seleção...');
            }  
     }//Fim módulo
/*---------------------------------------------------------------------------------------
 *                   Verifica se foi escolhido só um militar
 *---------------------------------------------------------------------------------------*/
     public function is_unitario ($militares)
     {
         if (!is_array($militares))
         {
             return false;
         }
         if (count($militares)==0)
         {
             return false;
         }
         return true;
     }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *                   Question de Apaga de afastamentos
 *---------------------------------------------------------------------------------------*/
    public function is_validAfastamento ($dtinicio,$dtfim,$afasta_id)
    {
        $fer = new TFerramentas;
        if ($fer->isValidData($dtinicio) && $fer->isValidData($dtfim) && $afasta_id)
        {
            return true;
        }
        else
        {
            return false;
        }
    
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *                   Question de Apaga de afastamentos
 *---------------------------------------------------------------------------------------*/
    public function onLimpaEscala ($param)
    {
        $data = $this->form->getData();
        // define the delete action
        $action = new TAction(array($this, 'limpaEscala'));
        $action->setParameters(array('param'=>$param,'data'=>$data)); // pass the key parameter ahead
        // shows a dialog to the user
        new TQuestion("Deseja Realmente Limpar as Escalas destes Militares?", $action);
        $this->form->setData($data);
    
    
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *                   Question de Apaga de afastamentos
 *---------------------------------------------------------------------------------------*/
    public function onLimpaAfastamento ($param)
    {
        $data = $this->form->getData();
        // define the delete action
        $action = new TAction(array($this, 'limpaAfastamento'));
        $action->setParameters(array('param'=>$param,'data'=>$data)); // pass the key parameter ahead
        // shows a dialog to the user
        new TQuestion("Deseja Realmente limpar os afastamentos deste Militares?", $action);
        $this->form->setData($data);
    
    
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *                   Apaga Afastamentos
 *---------------------------------------------------------------------------------------*/
    public function limpaAfastamento($param)
     {
        $data = (object) $param['data'];
        $form = new stdClass;
        $form = $data;
        $lista_opm = TSession::getValue(__CLASS__.'_lista_opm');
        $lista_slc = TSession::getValue(__CLASS__.'_lista_slc');
        $slc_opm   = TSession::getValue(__CLASS__.'_slc_opm');
        if (self::is_validAfastamento($data->dtinicioaf,$data->dtfimaf,$data->afasta_id))
        {
            $ci = new TBdhGerador();
            //$dias=null,$mesx=null,$anox=null,$hrinix=null,$hrtrab=null,$tescala='1'
            //$dtinicioaf=null,$dtfimaf=null,$bgaf=null,$anobgaf=null,$afasta_id)
            $result = $ci->main_escala($lista_slc,$slc_opm,'CLEARAFASTA',
                                        '','','','',
                                        '','','','','','',
                                        $data->dtinicioaf,$data->dtfimaf,$data->bgaf,$data->anobgaf,
                                        $data->afasta_id);
        }
        else
        {
            TMessage('info','É necessário pelo menos as datas inicial e final bem como o Tipo de Afastamento para prosseguir.');
        }
        $this->form->setData($form);
        if ($data->opm)
        {
            self::onSelectOpm_old(array('opm'=>$data->opm,'ativo'=>$data->ativo));
        }
        TSession::setValue(__CLASS__.'_lista_slc',$lista_slc);
        TDBSelect::reload('form_historicotrabalho', 'lista_slc', $lista_slc);
        
    //var_dump($param);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *                   Muda a OPM da lista de selecionados
 *---------------------------------------------------------------------------------------*/
    public function onChangeOPM($lista_opm,$new_opm)
     {
         $new_lista = array();
         $elementos = array_keys($lista_opm);
         foreach ($elementos as $elemento)
         {
             $new_lista[$elemento] = $new_opm;
         }
         return $new_lista;
     }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *                   Carrega Militares na OPM <<<<<<<DESATIVADO>>>>>>>>>>
 *---------------------------------------------------------------------------------------*/
    /*public function onSelectOpm($param)
    {
        return;
        $data = $this->form->getData();
        //var_dump($data);
        //var_dump($param);
        $key = null;
        if (array_key_exists('opm',$param))
        {
            $key = ($param['opm']) ? $param['opm'] : null;
        }
        elseif ($data->opm)
        {
            $key = ($data->opm) ? $data->opm : null;
        }
        if ($key == null)
        {
            $this->form->setData($data);
            return;
        }
        if ($this->up_date_opm)//Verifica se a atualização de OPM está ativa.
        {
            $ci = new TSicadDados();
            $ret = $ci->update_pm_opm($key);
        }
        try
        {
            if ($key != "XX")
            {
                $id_opm    = TSession::getValue(__CLASS__.'_id_opm');
                $lista     = TSession::getValue(__CLASS__.'_lista_opm');
                if ($key != $id_opm || !$lista)
                {
                    TTransaction::open('sicad'); // open a transaction
                    $criteria = new TCriteria; 
                    $criteria->add(new TFilter('unidadeid', '=', $key));
                    $criteria->add(new TFilter('status', '=','ATIVO' )); 
                    
                    $repository = new TRepository('servidor_tag'); 
                    $options = $repository->orderBy('rgmilitar')->load($criteria); 
                    //$options  = servidor_tag::where('unidadeid', '=', $key)->load();//Lista miliares (objeto)
                    TTransaction::close(); // close the transaction
                    $lista = array();
                    foreach ($options as $option)
                    {
                        $lista[$option->rgmilitar] = $option->rgmilitar.' - '.$option->postograd.' '.$option->nome;
                    }
                }
            }
            else
            {
                $lista = array('0'=>'- Nenhuma OPM Selecionada -');
            }
            asort($lista);
            TSession::setValue(__CLASS__.'_lista_opm', $lista);
            TSession::setValue(__CLASS__.'_id_opm', $key);
            TDBSelect::reload('form_historicotrabalho', 'lista_opm', $lista);
        }
        catch (Exception $e) // in case of exception 
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations            
        }
        $this->form->setData($data);
    }//Fim Módulo*/
/*------------------------------------------------------------------------------
 *  Função de aviso de acesso negado
 *------------------------------------------------------------------------------*/    
    public function noAcess ($param=null)
    {
        new TMessage('info', 'Não autorizado para uso.' ); // shows the exception error message   
    }

}//Fim Classe
