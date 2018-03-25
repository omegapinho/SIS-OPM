<?php
/**
 * avaliacao_finalForm Master/Detail
 * @author  <your name here>
 */
class avaliacao_finalForm extends TPage
{
    protected $form; // form
    protected $formFields;
    protected $detail_list;
    
    protected $nivel_sistema   = false;  //Registra o nível de acesso do usuário
    public    $config          = array();//Array com configuração
    protected $config_load     = false;  //Informa que a configuração está carregada

    var $sistema  = 'SISACAD';           //Nome do sistema que irá configurar(filtro)
    var $servico  = 'Avaliações';        //Nome da página de serviço.
    
    private $opm_operador    = false;     // Unidade do Usuário
    private $listas          = false;           // Lista de valores e array de OPM
   
    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct()
    {
        parent::__construct();

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
            TSession::setValue('SISACAD_CONFIG', $fer->getConfig($this->sistema));         //Busca o Nível de acesso que o usuário tem para a Classe

            $this->config_load = true;                               //Informa que configuração foi carregada
        }
        
        // creates the form
        $this->form = new TForm('form_materia');
        $this->form->class = 'tform'; // CSS class
        $this->form->style = 'display: table;width:100%'; // style - Mudado para aproveitar 100% da tela
        parent::include_css('app/resources/custom-frame.css');
        
        $table_master = new TTable;
        $table_master->width = '100%';
        
        $table_master->addRowSet( new TLabel('Resultados das Avaliações das Matérias - Listagem com Resultados'), '', '')->class = 'tformtitle';
        
        // add a table inside form
        $table_general = new TTable;
        $table_detail  = new TTable;
        $table_general-> width = '100%';
        $table_detail-> width  = '100%';
        
        $frame_general = new TFrame;
        $frame_general->setLegend('Identificação da Turma');
        $frame_general->style = 'background:whiteSmoke';
        $frame_general->add($table_general);
        
        $table_master->addRow()->addCell( $frame_general )->colspan=2;
        $row = $table_master->addRow();
        $row->addCell( $table_detail );
        
        $this->form->add($table_master);
        
        $turma = TSession::getValue('turma_militar');
        if (empty($turma))
        {
            TApplication::loadPage('turmaList','onReload');
        }
        
        // master fields
        $turma_id      = new TEntry('turma_id');
        $disciplina_id = new TCombo('disciplina_id');
        $oculto        = new TCombo('oculto');
        
        if (!empty($turma_id))
        {
            $turma_id->setEditable(FALSE);
        }
        
        // detail fields
        $nao_realizado   = new TButton('nao_realizado');
        $primeira_cham   = new TButton('primeira_cham');
        $segunda_cham    = new TButton('segunda_cham');
        $recupera        = new TButton('recupera');
        $resultado       = new TButton('resultado');
        
        $nao_realizado->setImage('fa:ban black');
        $primeira_cham->setImage('fa:star black');
        $segunda_cham->setImage('fa:paper-plane black');
        $recupera->setImage('fa:recycle black');
        $resultado->setImage('fa:exclamation black');
        
        $nao_realizado->class = 'btn btn-info';
        $primeira_cham->class = 'btn btn-info';
        $segunda_cham->class = 'btn btn-info';
        $recupera->class = 'btn btn-info';
        $resultado->class = 'btn btn-info';

         //Tamanhos
         $turma_id->setSize(250);
         $disciplina_id->setSize(250);
         $oculto->setSize(80);
         //Valores
         $oculto->addItems($fer->lista_sim_nao());
        
        // master
        $table_general->addRowSet( new TLabel('Turma'), $turma_id );
        
         // detail
        $frame_details = new TFrame();
        $frame_details->setLegend('Legendas');
        $row = $table_detail->addRow();
        $row->addCell($frame_details);
       
        $table_details = new TTable;
        $frame_details->add($table_details);
        //Legendas        
        $table_details->addRowSet( array($nao_realizado, new TLabel('Não realizado')  , $primeira_cham, new TLabel('Primeira Chamada'),
                                         $segunda_cham , new TLabel('Segunda Chamada'), $recupera,      new TLabel('Recuperação'),
                                         $resultado    , new TLabel('Resultado da Avaliação')));
        $table_details->addRowSet( array($p_1 = new TLabel('1ªVC'), '=>1ª Verificação',$p_2 = new TLabel('VU'), '=>Verificação Única',
                                         $p_3 = new TLabel('2ªVC'), '=>2ª Verificação',$p_4 = new TLabel('VF'), '=>Verificação Final'));
        $p_1->setFontColor('red');
        $p_2->setFontColor('red');
        $p_3->setFontColor('red');
        $p_4->setFontColor('red');
        $this->detail_list = new TQuickGrid;
        $this->detail_list->setHeight( 175 );
        $this->detail_list->makeScrollable();
        $this->detail_list->disableDefaultClick();
        
        // items
        $this->detail_list->addQuickColumn('Disciplina', 'disciplina', 'left', 200);
        $this->detail_list->addQuickColumn('Data Finalizada', 'data_fim', 'left', 100);
        $this->detail_list->addQuickColumn('Encerrada?', 'oculto', 'left', 50);
        $this->detail_list->addQuickColumn('1ª VC/VU', 'primeira', 'center', 80);
        $this->detail_list->addQuickColumn('2ª VC', 'segunda', 'center', 80);
        $this->detail_list->addQuickColumn('VF', 'final', 'center', 80);
        $this->detail_list->addQuickColumn('RF', 'recupera', 'center', 80);
        $this->detail_list->addQuickColumn('Resultado', 'resultado', 'center', 80);
        $this->detail_list->createModel();
        
        $row = $table_detail->addRow();
        $row->addCell($this->detail_list);
        
        // create an action button (save)
        $save_button=new TButton('save');
        $save_button->setAction(new TAction(array($this, 'onSave')), _t('Save'));
        $save_button->setImage('ico_save.png');

        // create an new button (edit with no parameters)
        $new_button=new TButton('new');
        $new_button->setAction(new TAction(array($this, 'onClear')), _t('New'));
        $new_button->setImage('ico_new.png');
        
        $ret_button = new TButton('retorno');
        $ret_button->setAction(new TAction(array($this,'onReturn')), 'Retorna a Turma');
        $ret_button->setImage('ico_back.png');
        
        // define form fields
        $this->formFields   = array($turma_id,$disciplina_id,$oculto);//,$detail_data_fim,$detail_oculto);
        $this->formFields[] = $ret_button;
        $this->form->setFields( $this->formFields );
        
        $table_master->addRowSet( array($ret_button), '', '')->class = 'tformaction'; // CSS class
        
        // create the page container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'turmaList'));
        $container->add($this->form);
        parent::add($container);
    }
    
    
    /**
     * Clear form
     * @param $param URL parameters
     */
    public function onClear($param)
    {
        $this->form->clear(TRUE);
        TSession::setValue(__CLASS__.'_items', array());
        $this->onReload( $param );
    }
    
    /**
     * Save an item from form to session list
     * @param $param URL parameters
     */
    public function onSaveDetail( $param )
    {
        try
        {
            TTransaction::open('sisacad');
            $data = $this->form->getData();
            
            /** validation sample
            if (! $data->fieldX)
                throw new Exception('The field fieldX is required');
            **/
            
            $items = TSession::getValue(__CLASS__.'_items');
            $key = empty($data->detail_id) ? 'X'.mt_rand(1000000000, 1999999999) : $data->detail_id;
            
            $items[ $key ] = array();
            $items[ $key ]['id'] = $key;
            $items[ $key ]['data_fim'] = $data->detail_data_fim;
            $items[ $key ]['oculto'] = $data->detail_oculto;
            
            TSession::setValue(__CLASS__.'_items', $items);
            
            // clear detail form fields
            $data->detail_id = '';
            $data->detail_data_fim = '';
            $data->detail_oculto = '';
            
            TTransaction::close();
            $this->form->setData($data);
            
            $this->onReload( $param ); // reload the items
        }
        catch (Exception $e)
        {
            $this->form->setData( $this->form->getData());
            new TMessage('error', $e->getMessage());
        }
    }
    
    /**
     * Load an item from session list to detail form
     * @param $param URL parameters
     */
    public function onEditDetail( $param )
    {
        $data = $this->form->getData();
        
        // read session items
        $items = TSession::getValue(__CLASS__.'_items');
        
        // get the session item
        $item = $items[ $param['item_key'] ];
        
        $data->detail_id = $item['id'];
        $data->detail_data_fim = $item['data_fim'];
        $data->detail_oculto = $item['oculto'];
        
        // fill detail fields
        $this->form->setData( $data );
    
        $this->onReload( $param );
    }
    
    /**
     * Delete an item from session list
     * @param $param URL parameters
     */
    public function onDeleteDetail( $param )
    {
        $data = $this->form->getData();
        
        // reset items
            $data->detail_data_fim = '';
            $data->detail_oculto = '';
        
        // clear form data
        $this->form->setData( $data );
        
        // read session items
        $items = TSession::getValue(__CLASS__.'_items');
        
        // delete the item from session
        unset($items[ $param['item_key'] ] );
        TSession::setValue(__CLASS__.'_items', $items);
        
        // reload items
        $this->onReload( $param );
    }
    
    /**
     * Load the items list from session
     * @param $param URL parameters
     */
    public function onReload($param)
    {
        // read session items
        $fer = new TFerramentas();
        $items = TSession::getValue(__CLASS__.'_items');
        
        $this->detail_list->clear(); // clear detail list
        $data = $this->form->getData();
        
        if ($items)
        {
            $cont = 1;
            foreach ($items as $list_item_key => $list_item)
            {
                $item_name = 'prod_' . $cont++;
                $item = new StdClass;
                
                // create action buttons
                $action_del = new TAction(array($this, 'onDeleteDetail'));
                $action_del->setParameter('item_key', $list_item_key);
                
                $action_edi = new TAction(array($this, 'onEditDetail'));
                $action_edi->setParameter('item_key', $list_item_key);
                
                $button_del = new TButton('delete_detail'.$cont);
                $button_del->class = 'btn btn-default btn-sm';
                $button_del->setAction( $action_del, '' );
                $button_del->setImage('fa:trash-o red fa-lg');
                
                $button_edi = new TButton('edit_detail'.$cont);
                $button_edi->class = 'btn btn-default btn-sm';
                $button_edi->setAction( $action_edi, '' );
                $button_edi->setImage('fa:edit blue fa-lg');
                
                $oculto = new TButton('final'.$cont);
                $oculto->class = 'btn btn-' . (($list_item['oculto'] == 'S') ? 'success' : 'danger') . ' btn-xs';
                $oculto_label = ($list_item['oculto'] != 'S') ? 'N' : 'S';
                $oculto->setLabel($fer->lista_sim_nao($oculto_label));
                
                $item->edit   = $button_edi;
                $item->delete = $button_del;
                
                $this->formFields[ $item_name.'_edit' ] = $item->edit;
                $this->formFields[ $item_name.'_delete' ] = $item->delete;
                
                // items
                $item->id           = $list_item['id'];
                $item->data_fim     = TDate::date2br($list_item['data_fim']);
                $item->oculto       = $oculto;
                $item->disciplina   = $list_item['disciplina'];
                $item->primeira     = $list_item['primeira'];
                $item->segunda      = $list_item['segunda'];
                $item->final        = $list_item['final'];
                $item->recupera     = $list_item['recupera'];
                $item->resultado    = $list_item['resultado'];
                
                $row = $this->detail_list->addItem( $item );
                $row->onmouseover='';
                $row->onmouseout='';
            }

            $this->form->setFields( $this->formFields );
        }
        
        $this->loaded = TRUE;
    }
    
    /**
     * Load Master/Detail data from database to form/session
     */
    public function onEdit($param = null)
    {
        $fer = new TFerramentas();
        $turma = TSession::getValue('turma_militar');
        //Verifica se a chamada foi feita por turmaForm
        if (empty($turma))
        {
            TApplication::loadPage('turmaList','onReload');//Retorna para o turmaList
        }
        $param['key'] = $turma->id;//Carrega o id para o key fazer a busca
        //var_dump($turma);
        try
        {
            TTransaction::open('sisacad');
            
            if (isset($param['key']))
            {
                $key = $param['key'];
                
                $object = new turma ($key);
                $object->turma_id = $object->nome . ' ('.$object->curso->sigla . ')';
                $materias = $object->getmaterias();//Busca as Matérias vinculadas com a turma
                $session_items = array();//Lista que será armazenado nas seções

                //Simbolo padrão de ausência de avaliação
                $nop = new TButton('nop');
                $nop->setImage('fa:ban');
                $nop->class='btn btn-danger btn-xs';
                /*
                Nota:
                Icones = 1ªChamada   =>thumbtack
                         2ªChamada   =>paper-plane
                         Recuperação =>recycle
                         Resultado   =>exclamation
                
                */
                $icones = array ('1C'=>'star','2C'=>'paper-plane','RC'=>'recycle');
                $cores  = array ('AG'=>'danger','AP'=>'info','PE'=>'warning','CO'=>'success');
                //verifica se há matérias Matérias
                if (!empty($materias))
                {
                    //Loop de Matérias, vai colocar todas matérias no grid
                    foreach ($materias as $materia)
                    {
                        $item_key                                 = $materia->id;//Id da Matérias da turma
                        //Inicia o preenchimento do array que irá para sessão
                        $session_items[$item_key]                 = $materia->toArray();
                        $session_items[$item_key]['id']           = $materia->id;
                        $session_items[$item_key]['oculto']       = $materia->oculto;
                        $session_items[$item_key]['disciplina']   = $materia->disciplina->nome;
                        //Pega a data final da avaliação resultado (se tiver)
                        $avaliacao_final                          = $materia->avaliacao_final;
                        //Se for finalizado carrega a data que findou
                        $session_items[$item_key]['data_fim']     = (!empty($avaliacao_final)) ? $avaliacao_final->data_fim : '';
                        //Busca as avaliações que a turma teve
                        $avaliacao_turmas                         = $materia->avaliacao_turma;
                        //Carga inicial das provas
                        $prova                                    = array('V1'=>$nop,'V2'=>$nop,'VF'=>$nop,'RF'=>$nop);
                        if (!empty($avaliacao_turmas))
                        {
                            foreach ($avaliacao_turmas as $av)
                            {
                                //Carregas as provas que a Avaliação teve(1C, 2C e RC)
                                $provas = $av->getavaliacao_provas();
                                $r = '';
                                if (!empty($provas))
                                {
                                    foreach($provas as $p)
                                    {
                                        //Definições de ação para cada prova (1ª, 2ª Chamada e Recuperação Corrente) 
                                        $action = new TAction( array($this, 'onProva' ) );//Abre a prova
                                        $action->setParameter('key', $p->id);
                                        $res = new TActionLink('',$action,'black',7,'','fa:'.$icones[$p->tipo_prova]);
                                        //muda a cor conforme status
                                        if (!empty($p->status) && strpos('CO/AG/AP/PE',$p->status) !== false )
                                        {
                                            $res->class='btn btn-' . $cores[$p->status];
                                        }
                                        else
                                        {
                                            $res->class='btn btn-danger';
                                        }                                        
                                        //Adiciona o botão
                                        $r .= $res;
                                    }
                                }
                                //Pega o resultado de cada avaliação
                                $avaliacao_resultado = $av->avaliacao_resultado;
                                //Se finalizou e há resultado cria o botão para mostrar o resultado da avaliação
                                if ($av->oculto == 'S' && !empty($avaliacao_resultado))
                                {
                                    //Definições de ação para abrir o resultado
                                    $action = new TAction( array($this, 'onProvaResultado' ) );
                                    $action->setParameter('key', $avaliacao_resultado->id);
                                    $fim = new TActionLink('',$action,'black',7,'','fa:exclamation');
                                    $fim->class='btn btn-success';
                                }
                                else
                                {
                                    //Não há resultado cria botão NOP
                                    $action = new TAction( array($this, 'onNOP' ) );//Abre a prova
                                    $action->setParameter('key', 0);
                                    $fim = new TActionLink('',$action,'black',7,'','fa:exclamation');
                                    $fim->class='btn btn-warning';
                                }    
                                //Destina a cada avalição seus dados pertinentes
                                if ($av->avaliacao_curso->tipo_avaliacao == 'VU')//VU terá o status de V1
                                {
                                    //Carrega VU e V1 no mesmo lugas
                                    $prova['V1'] = $r . $fim;

                                }
                                else
                                {
                                    //Carrega demais provas
                                    $prova[$av->avaliacao_curso->tipo_avaliacao] = $r . $fim;
                                }
                                //Se não houve recuperação, aplica botão NOP
                                if ($av->avaliacao_curso->tipo_avaliacao == 'RF' && empty($r))
                                {
                                    $prova[$av->avaliacao_curso->tipo_avaliacao] = $nop;
                                }
                            }
                        }
                        //Confere se há resultado
                        if (!empty($avaliacao_final))
                        {
                                //Definições de ação para abrir prova final
                                $action           = new TAction( array($this, 'onProvaFinal' ) );
                                $action->setParameter('key', $avaliacao_final->id);
                                $resultado        = new TActionLink('',$action,'black',7,'','fa:exclamation');
                                $resultado->class = 'btn btn-success';
                        }
                        else
                        {
                            $resultado            = $nop;
                        }
                        //Carrega cada avaliação em seu respectivo lugar na seção
                        $session_items[$item_key]['primeira']     = $prova['V1'];//1ª VC ->avaliacao_resultado
                        $session_items[$item_key]['segunda']      = $prova['V2'];//2ª VC  ->avaliacao_resultado
                        $session_items[$item_key]['final']        = $prova['VF'];//VF       ->avaliacao_resultado
                        $session_items[$item_key]['recupera']     = $prova['RF'];//VR ->avaliacao_resultado
                        $session_items[$item_key]['resultado']    = $resultado;//Resultado ->avaliacao_final
                    }
                }
                //Armazena tudo na variável de sessão
                TSession::setValue(__CLASS__.'_items', $session_items);
                
                $this->form->setData($object); // fill the form with the active record data
                $this->onReload( $param ); // reload items list
                TTransaction::close(); // close transaction
            }
            else
            {
                $this->form->clear(TRUE);
                TSession::setValue(__CLASS__.'_items', null);
                $this->onReload( $param );
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }//Fim módulo
    
    /**
     * Save the Master/Detail data from form/session to database
     */
    public function onSave()
    {
        try
        {
            // open a transaction with database
            TTransaction::open('sisacad');
            
            $data = $this->form->getData();
            $master = new materia;
            $master->fromArray( (array) $data);
            $this->form->validate(); // form validation
            
            $master->store(); // save master object
            // delete details
            $old_items = avaliacao_final::where('materia_id', '=', $master->id)->load();
            
            $keep_items = array();
            
            // get session items
            $items = TSession::getValue(__CLASS__.'_items');
            
            if( $items )
            {
                foreach( $items as $item )
                {
                    if (substr($item['id'],0,1) == 'X' ) // new record
                    {
                        $detail = new avaliacao_final;
                    }
                    else
                    {
                        $detail = avaliacao_final::find($item['id']);
                    }
                    $detail->data_fim  = $item['data_fim'];
                    $detail->oculto  = $item['oculto'];
                    $detail->materia_id = $master->id;
                    $detail->store();
                    
                    $keep_items[] = $detail->id;
                }
            }
            
            if ($old_items)
            {
                foreach ($old_items as $old_item)
                {
                    if (!in_array( $old_item->id, $keep_items))
                    {
                        $old_item->delete();
                    }
                }
            }
            TTransaction::close(); // close the transaction
            
            // reload form and session items
            $this->onEdit(array('key'=>$master->id));
            
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'));
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback();
        }
    }
    
    /**
     * Show the page
     */
    public function show()
    {
        // check if the datagrid is already loaded
        if (!$this->loaded AND (!isset($_GET['method']) OR $_GET['method'] !== 'onReload') )
        {
            $this->onReload( func_get_arg(0) );
        }
        parent::show();
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Imprime a Prova
 *------------------------------------------------------------------------------*/
    public function onProva ($param = null)
    {
        //var_dump($param);return;
        $data = $this->form->getData();
        $fer = new TFerramentas();
        $sis = new TSisacad();
        try
        {
            TTransaction::open('sisacad');
            $prova  = new avaliacao_prova($param['key']);
            $alunos = $prova->getavaliacao_alunos();
            if (!empty($prova) && !empty($alunos))
            {
                $cabecalho = '';
                $lista = array();
                $cabecalho  = '<h4>Resultado da ';
                
                $turma       = $prova->avaliacao_turma->turma->nome;
                $curso       = $prova->avaliacao_turma->turma->curso->sigla;
                $materia     = $prova->avaliacao_turma->materia->disciplina->nome;
                $tipo_avalia = $prova->avaliacao_turma->avaliacao_curso->tipo_avaliacao;
                $nome_turma  = $fer->lista_verificacoes($tipo_avalia) . ' da disciplina ' . $materia . ' - ' . $turma . '(' . $curso . ')';
                
                $cabecalho .= $nome_turma . '</h4>';
                $head       = array('Identificação','Tipo de prova/Status','Nota','Extenso');
                $items = TSession::getValue(__CLASS__.'_items');
                $lista = array();
                if ($alunos)
                {
                    foreach ($alunos as $aluno)
                    {
                        $dado = array();
                        // items
                        $cpf = $aluno->aluno->cpf;
                        $dados = $sis->getDadosAluno($cpf);//Busca dados do Aluno para preencher campo
                        if ($dados != false)//Se retornar os dados do aluno, preenche
                        {
                            if (!empty($dados->rgmilitar))
                            {
                                $rg = ' RG ' . $dados->rgmilitar; 
                            }
                            else if (!empty($dados->rgcivil))
                            {
                                $rg = ' CI ' . $dados->rgcivil;
                            }
                            else
                            {
                                $rg = '';
                            }
                            $rg .= ' ';
                            $posto = $dados->postograd;
                            $posto = (!empty($posto)) ? $sis->getPostograd($posto) : '';
                            $ident = $posto . $rg . $dados->nome . ', CPF '.$dados->cpf;
                        }
                        else
                        {
                            $ident = '-- Dados do aluno não localizado -- ';
                        }
                        
                        $dado['identificacao']         = '<p style="text-align: center;">' . 
                                                         $ident . '</p>';
                        $dado['tipo_verificacao']      = '<p style="text-align: center;">';
                        if ($aluno->status != 'P')
                        {
                            $tipo = $fer->lista_status_prova($aluno->status);
                        }
                        else
                        {
                            $tipo = $fer->lista_tipo_prova($prova->tipo_prova);
                        }
                        $dado['tipo_verificacao']     .= $tipo . '</p>';
                        $nota                          = number_format($aluno->nota,2,'.','');
                        $dado['nota']                  = '<p style="text-align: right;">' . $nota . '</p>';
                        $dado['extenso']               = '<p style="text-align: center;">' . $fer->numeroExtenso($nota) . '</p>';
                       
                       $lista[] = $dado;
        
                    }
                    $tabela = $fer->geraTabelaHTML($lista,$head,array('tab'=>'border="1px" '.
                                                'bordercolor="black" width="100%" '.
                                                'style="border-collapse: collapse;"',
                                                'cab'=>'style="background: lightblue; text-align: center;"',
                                                'cel'=>'style="background: blue;"'));
        
                }
                if (!empty($tabela))
                {
                    $rel = new TBdhReport();
                    $bot = $rel->scriptPrint();
                    $cab = '<center>ESTADO DE GOIÁS<br>POLÍCIA MILITAR DO ESTADO DE GOIÁS'.
                           '<h5>COMANDO DA ACADEMIA DE POLICIAL MILITAR - CAPM</h5>'.
                            $cabecalho . '<br></center>';
                    $botao = '<center>'.$bot['botao'].'</center>';
                    $tabela = $bot['codigo'] . '<div id="relatorio">' . $cab . $tabela . '</div>' . $botao;
                    $window = TWindow::create('Resultado de Avaliação', 1400, 500);
                    $window->add($tabela);
                    $window->show();
                }
            }//if !empty($prova) && !empty($aluos)
            
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        $this->form->setData($data);
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Imprime a Prova de Resultado
 *------------------------------------------------------------------------------*/
    public function onProvaResultado ($param = null)
    {
        $data = $this->form->getData();
        $fer = new TFerramentas();
        try
        {
            TTransaction::open('sisacad');
            $prova     = new avaliacao_resultado($param['key']);
            //Monta o nome da turma
            $turma      = $prova->avaliacao_turma->turma->sigla;
            $curso      = $prova->avaliacao_turma->avaliacao_curso->curso->sigla;
            $materia    = $prova->avaliacao_turma->materia->disciplina->nome;
            $nome_turma = $materia . ' - Turma: ' . $turma . ' (' . $curso . ')';  
            
            $cabecalho = '';
            $lista = array();
            $cabecalho  = '<h4>Resultado de Verificação: ';
            $cabecalho .= $nome_turma . '</h4>';
            $head       = array('Identificação','Tipo de Verificação','Nota','Extenso');
            $items = $prova->avaliacao_resultadoalunos;
            $lista = array();
            if ($items)
            {
                foreach ($items as $item)
                {
                    $dado = array();
                    // items
                    $dado['identificacao']         = '<p style="text-align: center;">' . $item->aluno->getIdentificacao() . '</p>';
                    $dado['tipo_verificacao']      = '<p style="text-align: center;">' . 
                                                     $fer->lista_verificacoes($item->tipo_avaliacao) . '</p>';                        
                    $nota                          = number_format($item->nota,2,'.','');
                    $dado['nota']                  = '<p style="text-align: right;">' . $nota . '</p>';
                    $dado['extenso']               = '<p style="text-align: center;">' . $fer->numeroExtenso($nota) . '</p>';
                   
                   $lista[] = $dado;
    
                }
                $tabela = $fer->geraTabelaHTML($lista,$head,array('tab'=>'border="1px" '.
                                            'bordercolor="black" width="100%" '.
                                            'style="border-collapse: collapse;"',
                                            'cab'=>'style="background: lightblue; text-align: center;"',
                                            'cel'=>'style="background: blue;"'));
    
            }
            if (!empty($tabela))
            {
                $rel = new TBdhReport();
                $bot = $rel->scriptPrint();
                $cab = '<center>ESTADO DE GOIÁS<br>POLÍCIA MILITAR DO ESTADO DE GOIÁS'.
                       '<h5>COMANDO DA ACADEMIA DE POLICIAL MILITAR - CAPM</h5>'.
                        $cabecalho . '<br></center>';
                $botao = '<center>'.$bot['botao'].'</center>';
                $tabela = $bot['codigo'] . '<div id="relatorio">' . $cab . $tabela . '</div>' . $botao;
                $window = TWindow::create('Resultado de Avaliação', 1000, 500);
                $window->add($tabela);
                $window->show();
            }
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        $this->onEdit();
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  faz nada
 *------------------------------------------------------------------------------*/
    public function onNOP ($param = null)
    {
        $this->onEdit();
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Imprime a Final da Prova
 *------------------------------------------------------------------------------*/
    public function onProvaFinal ($param = null)
    {
        $data = $this->form->getData();
        $fer = new TFerramentas();
        try
        {
            TTransaction::open('sisacad');
            $prova     = new avaliacao_final($param['key']);
            //Monta o nome da turma
            $turma      = $prova->materia->turma->sigla;
            $curso      = $prova->materia->turma->curso->sigla;
            $materia    = $prova->materia->disciplina->nome;
            $nome_turma = $materia . ' - Turma: ' . $turma . ' (' . $curso . ')';  
            
            $cabecalho = '';
            $lista = array();
            $cabecalho  = '<h4>Resultado de Final de Verificação: ';
            $cabecalho .= $nome_turma . '</h4>';
            $head       = array('Identificação','Nota','Extenso');
            $items = $prova->avaliacao_finalalunos;
            $lista = array();
            if ($items)
            {
                foreach ($items as $item)
                {
                    $dado = array();
                    // items
                    $dado['identificacao']         = '<p style="text-align: center;">' . $item->aluno->getIdentificacao() . '</p>';
                    //$dado['tipo_verificacao']      = '<p style="text-align: center;">Resultado final</p>';                        
                    $nota                          = number_format($item->nota,2,'.','');
                    $dado['nota']                  = '<p style="text-align: right;">' . $nota . '</p>';
                    $dado['extenso']               = '<p style="text-align: center;">' . $fer->numeroExtenso($nota) . '</p>';
                   
                   $lista[] = $dado;
    
                }
                $tabela = $fer->geraTabelaHTML($lista,$head,array('tab'=>'border="1px" '.
                                            'bordercolor="black" width="100%" '.
                                            'style="border-collapse: collapse;"',
                                            'cab'=>'style="background: lightblue; text-align: center;"',
                                            'cel'=>'style="background: blue;"'));
    
            }
            if (!empty($tabela))
            {
                $rel = new TBdhReport();
                $bot = $rel->scriptPrint();
                $cab = '<center>ESTADO DE GOIÁS<br>POLÍCIA MILITAR DO ESTADO DE GOIÁS'.
                       '<h5>COMANDO DA ACADEMIA DE POLICIAL MILITAR - CAPM</h5>'.
                        $cabecalho . '<br></center>';
                $botao = '<center>'.$bot['botao'].'</center>';
                $tabela = $bot['codigo'] . '<div id="relatorio">' . $cab . $tabela . '</div>' . $botao;
                $window = TWindow::create('Resultado Final de Avaliação', 1000, 500);
                $window->add($tabela);
                $window->show();
            }
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        $this->onEdit();
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Retorna a edição da turma
 *------------------------------------------------------------------------------*/
    public function onReturn ($param)
    {
         $data = $this->form->getData();
         $turma = TSession::getValue('turma_militar');
         TSession::setValue('turma_militar',null);
         if (!empty($turma))
         {
              TApplication::loadPage('turmaForm','onEdit', array('key'=>$turma->id));
         }
         else
         {
            TApplication::loadPage('turmaList','onReload');
         }

         //$this->form->setData($data);
    }//Fim Módulo 
}//Fim Classe
