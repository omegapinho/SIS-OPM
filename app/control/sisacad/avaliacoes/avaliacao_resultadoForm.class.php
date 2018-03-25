<?php
/**
 * avaliacao_resultadoForm Master/Detail
 * @author  <your name here>
 */
class avaliacao_resultadoForm extends TPage
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
        $this->form        = new TForm('form_avaliacao_resultado');
        $this->form->class = 'tform'; // CSS class
        $this->form->style = 'display: table;width:100%;max-width:900px'; // style
        parent::include_css('app/resources/custom-frame.css');
        
        $table_master = new TTable;
        $table_master->width = '100%';
        
        $table_master->addRowSet( new TLabel('Resultado de Avaliação Corrente'), '', '')->class = 'tformtitle';
        
        // add a table inside form
        $table_general = new TTable;
        $table_detail  = new TTable;
        $table_general-> width = '100%';
        $table_detail-> width  = '100%';
        
        $frame_general = new TFrame;
        $frame_general->setLegend('Dados da Avaliação');
        $frame_general->style = 'width:100%;background:whiteSmoke';
        $frame_general->add($table_general);
        
        $table_master->addRow()->addCell( $frame_general )->colspan=2;
        $row = $table_master->addRow();
        $row->addCell( $table_detail );
        
        $this->form->add($table_master);
        
        // master fields
        $id                 = new TEntry('id');
        $data_fim           = new TDate('data_fim');
        $usuario_encerra    = new TEntry('usuario_encerra');
        $oculto             = new TCombo('oculto');
        $avaliacao_turma_id = new THidden('avaliacao_turma_id');
        $nome_turma_id      = new TEntry('nome_turma_id');
        
        if (!empty($id))
        {
            $id->setEditable(FALSE);
            $oculto->setEditable(FALSE);
            $avaliacao_turma_id->setEditable(FALSE);
            $usuario_encerra->setEditable(FALSE);
            $data_fim->setEditable(FALSE);
            $nome_turma_id->setEditable(FALSE);
        }
        //Valores
        $oculto->addItems($fer->lista_sim_nao());
        
        //Tamanhos
        $id->setSize(50);
        $nome_turma_id->setSize(400);
        $data_fim->setSize(120);
        $usuario_encerra->setSize(300);
        $oculto->setSize(80);
        
        //Mascaras
        $data_fim->setMask('dd/mm/yyyy');
        
        // detail fields
        $detail_id             = new THidden('detail_id');
        $detail_nota           = new TEntry('detail_nota');
        $detail_tipo_avaliacao = new TCombo('detail_tipo_avaliacao');
        $detail_aluno_id       = new THidden('detail_aluno_id');
        $detail_recuperado     = new TCombo('detail_recuperado');
        $detail_ident          = new TEntry('detail_ident');

        /** samples
         $this->form->addQuickFields('Date', array($date1, new TLabel('to'), $date2)); // side by side fields
         $fieldX->addValidation( 'Field X', new TRequiredValidator ); // add validation
         $fieldX->setSize( 100, 40 ); // set size
         **/
        
        //Tamanhos
        $detail_nota->setSize(50);
        $detail_tipo_avaliacao->setSize(120);
        $detail_ident->setSize(400);
        $detail_recuperado->setSize(50);
        //Valores
        $detail_tipo_avaliacao->addItems($fer->lista_verificacoes());
        $detail_recuperado->addItems($fer->lista_sim_nao());
        //Bloqueios
        $detail_tipo_avaliacao->setEditable(FALSE);
        $detail_ident->setEditable(FALSE);
        
        // master
        $table_general->addRowSet( new TLabel('ID'), $id ,$avaliacao_turma_id);
        $table_general->addRowSet( new TLabel('Matéria/Turma'), $nome_turma_id );
        $table_general->addRowSet( new TLabel('Data Fechamento'), $data_fim );
        $table_general->addRowSet( new TLabel('Usuário Encerrador'), $usuario_encerra );
        $table_general->addRowSet( new TLabel('Finalizada?'), $oculto );

         // detail
        $frame_details = new TFrame();
        $frame_details->setLegend('Resultado dos Alunos');
        $frame_details->style = 'width:100%;';
        $row = $table_detail->addRow();
        $row->addCell($frame_details);
        
        $btn_save_detail = new TButton('btn_save_detail');
        $btn_save_detail->setAction(new TAction(array($this, 'onSaveDetail')), 'Register');
        $btn_save_detail->setImage('fa:save');
        
        $table_details = new TTable;
        $frame_details->add($table_details);
        
        //Formatação
        $detail_nota->setMask('99.99');
        $table_details->addRowSet( '', $detail_id );
        $table_details->addRowSet( new TLabel('Aluno'), $detail_ident );
        $table_details->addRowSet( new TLabel('Tipo Avaliação'), $detail_tipo_avaliacao );
        $table_details->addRowSet( new TLabel('Nota'), $detail_nota );
        $table_details->addRowSet( new TLabel('Recuperado?'), $detail_recuperado );
        $table_details->addRowSet( '', $detail_aluno_id );
        
        $table_details->addRowSet( $btn_save_detail );
        
        $this->detail_list = new TQuickGrid;
        $this->detail_list->setHeight( 175 );
        $this->detail_list->makeScrollable();
        $this->detail_list->disableDefaultClick();
        $this->detail_list->style = 'width: 100%; font-size: 08pt;';
        $this->detail_list->addQuickColumn('', 'edit', 'center', 50);
        //$this->detail_list->addQuickColumn('', 'delete', 'center', 50);
        
        // items
        $this->detail_list->addQuickColumn('Aluno', 'ident', 'left', 450);
        $this->detail_list->addQuickColumn('Tipo Avaliação', 'tipo_avaliacao', 'center', 100);
        $this->detail_list->addQuickColumn('Nota', 'nota', 'right', 50);
        $this->detail_list->addQuickColumn('Recuperação?', 'recuperado', 'center', 100);
        $this->detail_list->createModel();
        
        $row = $table_detail->addRow();
        $row->addCell($this->detail_list);
        
        // create an action button (save)
        $save_button=new TButton('save');
        $save_button->setAction(new TAction(array($this, 'onSave')), _t('Save'));
        $save_button->setImage('ico_save.png');

        // create an new button (edit with no parameters)
        /*$new_button=new TButton('new');
        $new_button->setAction(new TAction(array($this, 'onClear')), _t('New'));
        $new_button->setImage('ico_new.png');*/
        
        // create an print button (imprime a prova)
        $prt_button=new TButton('print');
        $prt_button->setAction(new TAction(array($this, 'onImprime')), 'Imprime');
        $prt_button->setImage('ico_print.png');

        // create an return button (retorna)
        $ret_button=new TButton('return');
        $ret_button->setAction(new TAction(array($this, 'onReturn')), 'Retorna');
        $ret_button->setImage('ico_back.png');
                
        // define form fields
        $this->formFields   = array($id,$data_fim,$usuario_encerra,$oculto,$avaliacao_turma_id,$detail_nota,
                                    $detail_tipo_avaliacao,$detail_aluno_id,$detail_recuperado,
                                    $detail_ident,$nome_turma_id);
        $this->formFields[] = $btn_save_detail;
        $this->formFields[] = $save_button;
        //$this->formFields[] = $new_button;
        $this->formFields[] = $prt_button;
        $this->formFields[] = $ret_button;
        $this->formFields[] = $detail_id;
        $this->form->setFields( $this->formFields );
        
        //$table_master->addRowSet( array($save_button, $new_button), '', '')->class = 'tformaction'; // CSS class
        $table_master->addRowSet( array($save_button,$prt_button,$ret_button), '', '')->class = 'tformaction'; // CSS class
        
        // create the page container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'avaliacao_provaList'));
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
            
            $items[ $key ]                   = array();
            $items[ $key ]['id']             = $key;
            $items[ $key ]['nota']           = $data->detail_nota;
            $items[ $key ]['tipo_avaliacao'] = $data->detail_tipo_avaliacao;
            $items[ $key ]['aluno_id']       = $data->detail_aluno_id;
            $items[ $key ]['recuperado']     = $data->detail_recuperado;
            $items[ $key ]['ident']          = $data->detail_ident;
            
            TSession::setValue(__CLASS__.'_items', $items);
            
            // clear detail form fields
            $data->detail_id             = '';
            $data->detail_nota           = '';
            $data->detail_tipo_avaliacao = '';
            $data->detail_aluno_id       = '';
            $data->detail_recuperado     = '';
            $data->detail_ident          = '';
            
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
        $item                        = $items[ $param['item_key'] ];
        
        $data->detail_id             = $item['id'];
        $data->detail_nota           = str_pad(number_format($item['nota'],2,'.',''), 2, '0',STR_PAD_LEFT) ;//number_format($list_item['nota'],2,'.','');
        $data->detail_tipo_avaliacao = $item['tipo_avaliacao'];
        $data->detail_aluno_id       = $item['aluno_id'];
        $data->detail_recuperado     = $item['recuperado'];
        $data->detail_ident          = $item['ident'];

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
            $data->detail_nota = '';
            $data->detail_tipo_avaliacao = '';
            $data->detail_aluno_id = '';
            $data->detail_recuperado = '';
        
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
        $fer   = new TFerramentas;
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
                
                $item->edit   = $button_edi;
                //$item->delete = $button_del;
                
                $this->formFields[ $item_name.'_edit' ] = $item->edit;
                //$this->formFields[ $item_name.'_delete' ] = $item->delete;
                
                // items
                $item->id             = $list_item['id'];
                $item->nota           = number_format($list_item['nota'],2,'.','');
                $item->tipo_avaliacao = $fer->lista_verificacoes($list_item['tipo_avaliacao']);
                $item->aluno_id       = $list_item['aluno_id'];
                $item->recuperado     = $fer->lista_sim_nao($list_item['recuperado']);
                $item->ident          = $list_item['ident'];
                
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
    public function onEdit($param)
    {
        $fer = new TFerramentas;
        $sis = new TSisacad();
        try
        {
            TTransaction::open('sisacad');
            
            if (isset($param['key']))
            {
                $key = $param['key'];
                
                $object = new avaliacao_resultado($key);
                //Reformatando
                $object->data_fim = TDate::date2br($object->data_fim);
                
                $turma = $object->avaliacao_turma->turma->sigla;
                $curso = $object->avaliacao_turma->turma->curso->sigla;
                $materia = $object->avaliacao_turma->materia->disciplina->nome;
                
                $object->nome_turma_id = $materia . ' - Turma: ' . $turma . ' (' . $curso . ')';

                
                $items  = avaliacao_resultadoaluno::where('avaliacao_resultado_id', '=', $key)->load();
                
                $session_items = array();
                foreach( $items as $item )
                {
                    
                    $cpf = $item->aluno->cpf;
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
                    
                    //$item->aluno_id = $ident;

                    $item_key = $item->id;
                    $session_items[$item_key]                   = $item->toArray();
                    $session_items[$item_key]['id']             = $item->id;
                    $session_items[$item_key]['nota']           = $item->nota;
                    $session_items[$item_key]['tipo_avaliacao'] = $item->tipo_avaliacao;
                    $session_items[$item_key]['aluno_id']       = $item->aluno_id;
                    $session_items[$item_key]['recuperado']     = $item->recuperado;
                    $session_items[$item_key]['ident']          = $ident;
                }
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
    }
    
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
            $master = new avaliacao_resultado;
            $master->fromArray( (array) $data);
            $this->form->validate(); // form validation
            
            $master->store(); // save master object
            // delete details
            $old_items = avaliacao_resultadoaluno::where('avaliacao_resultado_id', '=', $master->id)->load();
            
            $keep_items = array();
            
            // get session items
            $items = TSession::getValue(__CLASS__.'_items');
            
            if( $items )
            {
                foreach( $items as $item )
                {
                    if (substr($item['id'],0,1) == 'X' ) // new record
                    {
                        $detail = new avaliacao_resultadoaluno;
                    }
                    else
                    {
                        $detail = avaliacao_resultadoaluno::find($item['id']);
                    }
                    $detail->nota  = $item['nota'];
                    $detail->tipo_avaliacao  = $item['tipo_avaliacao'];
                    $detail->aluno_id  = $item['aluno_id'];
                    $detail->recuperado  = $item['recuperado'];
                    $detail->avaliacao_resultado_id = $master->id;
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
 *   Retorna
 *------------------------------------------------------------------------------*/
    public function onReturn($param = null)
    {
        $data = $this->form->getData();
        if (isset($data->avaliacao_turma_id) && !empty($data->avaliacao_turma_id))
        {
            TApplication::gotoPage('avaliacao_turmaForm','onEdit',array('key'=>$data->avaliacao_turma_id));
        }
        else
        {
            TApplication::loadPage('avaliacao_turmaList','onReload');
        }
    }//Fim Módulo 
/*------------------------------------------------------------------------------
 *  Imprime a Prova
 *------------------------------------------------------------------------------*/
    public function onImprime ($param = null)
    {
        $data = $this->form->getData();
        $fer = new TFerramentas();
        $cabecalho = '';
        $lista = array();
        $cabecalho  = '<h4>Resultado de Verificação: ';
        $cabecalho .= $data->nome_turma_id . '</h4>';
        $head       = array('Identificação','Tipo de Verificação','Nota','Extenso');
        $items = TSession::getValue(__CLASS__.'_items');
        $lista = array();
        if ($items)
        {
            foreach ($items as $list_item_key => $list_item)
            {
                $dado = array();
                // items
                $dado['identificacao']         = '<p style="text-align: center;">' . $list_item['ident'] . '</p>';
                $dado['tipo_verificacao']      = '<p style="text-align: center;">' . 
                                                 $fer->lista_verificacoes($list_item['tipo_avaliacao']) . '</p>';                        
                $nota                          = number_format($list_item['nota'],2,'.','');
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
        $this->form->setData($data);
    }//Fim Módulo
}//Fim Classe
