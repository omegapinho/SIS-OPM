<?php
/**
 * controle_geracaoForm Listing
 * @author  <your name here>
 */
class controle_geracaoForm extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $formgrid;
    private $loaded;
    private $deleteButton;
    
    var $sistema  = 'SISTEMA';           //Nome do sistema que irá configurar(filtro)
    var $servico  = 'Controle de Geração';            //Nome da página de serviço.
    
    private $opm_operador    = false;    // Unidade do Usuário
    private $listas          = false;    // Lista de valores e array de OPM
   
    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct()
    {
        parent::__construct();

/*------------------------------------------------------------------------------
 * Carrega configurações
 *------------------------------------------------------------------------------*/
        $fer   = new TFerramentas();                                            //Ferramentas diversas
        $sicad = new TSicadDados();                                             //Ferramentas SICAD
        $profile = TSession::getValue('profile');                               //Profile da Conta do usuário
        if (!$this->nivel_sistema || $this->config_load == false)               //Carrega OPMs que tem acesso
        {
            $this->opm_operador  = $sicad->get_OPM();                           //Carrega OPM do Usuário
            $this->nivel_sistema = $fer->getnivel (get_class($this));           //Verifica qual nível de acesso do usuário
            $this->listas        = $sicad->get_OPMsUsuario();                   //Carrega Listas de OPMs
            $this->config        = $fer->getConfig($this->sistema);             //Carrega config
            TSession::setValue('SISACAD_CONFIG', $this->config);                //Busca o Nível de acesso que o usuário tem para a Classe
            $this->config_load = true;                                          //Informa que configuração foi carregada
        }
        
        // creates the form
        $this->form = new TQuickForm('form_search_professorcontrole_aula');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('Controle de Relatórios Emitidos - Edição');
        
        // create the form fields
        $aulas_saldo = new TEntry('aulas_saldo');
        $aulas_pagas = new TEntry('aulas_pagas');
        $data_aula = new TEntry('data_aula');
        $historico_pagamento = new TEntry('historico_pagamento');


        // add the fields
        $this->form->addQuickField('Aulas Saldo', $aulas_saldo,  200 );
        $this->form->addQuickField('Aulas Pagas', $aulas_pagas,  200 );
        $this->form->addQuickField('Data Aula', $data_aula,  200 );
        $this->form->addQuickField('Historico Pagamento', $historico_pagamento,  200 );

        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('professorcontrole_aula_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $this->form->addQuickAction('Relatório',  new TAction(array($this, 'onGeraRelatorio')), 'fa:file');
        $this->form->addQuickAction(_t('Back to the listing'),  new TAction(array('controle_geracaoList', 'onReload')), 'ico_back.png');
        
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        
        $this->datagrid->style = 'width: 100%';
        //$this->datagrid->makeScrollable();
        //$this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        //$column_check               = new TDataGridColumn('check', '', 'center');
        //$column_professor_id        = new TDataGridColumn('professor_id', 'Professor', 'center',280);
        $column_controle_aula_id    = new TDataGridColumn('controle_aula_id', 'Turma/Matéria', 'center',200);
        $column_aulas_saldo         = new TDataGridColumn('aulas_saldo', 'Aulas', 'right',50);
        $column_data_aula           = new TDataGridColumn('data_aula', 'Data', 'right',80);
        $column_valor_aula          = new TDataGridColumn('valor_aula', 'R$/aula', 'right',80);
        $column_aulas_pagas         = new TDataGridColumn('aulas_pagas', 'Aulas Pagas', 'right',50);

        // add the columns to the DataGrid
        //$this->datagrid->addColumn($column_check);
        $this->datagrid->setGroupColumn('professor_id', '<b>Professor</b>: <font color="red"><i>{professor_id}</i></font>');
        
        //$this->datagrid->addColumn($column_professor_id);
        $this->datagrid->addColumn($column_controle_aula_id);
        $this->datagrid->addColumn($column_aulas_saldo);
        $this->datagrid->addColumn($column_data_aula);
        $this->datagrid->addColumn($column_valor_aula);
        $this->datagrid->addColumn($column_aulas_pagas);
        
        // create EDIT action
        /*$action_edit = new TDataGridAction(array('controle_geracaoEditForm', 'onEdit'));
        $action_edit->setUseButton(TRUE);
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel(_t('Edit'));
        $action_edit->setImage('fa:pencil-square-o blue fa-lg');
        $action_edit->setField('id');
        $this->datagrid->addAction($action_edit);*/
        
        // Cria botão de Estorno de Aula
        $action_est = new TDataGridAction(array($this, 'onEstorno'));
        $action_est->setUseButton(TRUE);
        $action_est->setButtonClass('btn btn-default');
        $action_est->setLabel('Estorno');
        $action_est->setImage('fa:undo red fa-lg');
        $action_est->setField('id');
        $this->datagrid->addAction($action_est);
        
        // create the datagrid model
        $this->datagrid->createModel();
        
        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());
        
        $this->datagrid->disableDefaultClick();
        
        // put datagrid inside a form
        $this->formgrid = new TForm;
        $this->formgrid->add($this->datagrid);
        
        // creates the delete collection button
        /*$this->deleteButton = new TButton('delete_collection');
        $this->deleteButton->setAction(new TAction(array($this, 'onDeleteCollection')), AdiantiCoreTranslator::translate('Delete selected'));
        $this->deleteButton->setImage('fa:remove red');
        $this->formgrid->addField($this->deleteButton);*/
        
        $gridpack = new TVBox;
        $gridpack->style = 'width: 100%';
        $gridpack->add($this->formgrid);
        $gridpack->add($this->deleteButton)->style = 'background:whiteSmoke;border:1px solid #cccccc; padding: 3px;padding: 5px;';
        
        $this->transformCallback = array($this, 'onBeforeLoad');


        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'sisacadRelatorios'));
        $container->add($this->form);
        $container->add($gridpack);
        $container->add($this->pageNavigation);
        
        parent::add($container);
    }
    
    /**
     * Inline record editing
     * @param $param Array containing:
     *              key: object ID value
     *              field name: object attribute to be updated
     *              value: new attribute content 
     */
    public function onInlineEdit($param)
    {
        try
        {
            // get the parameter $key
            $field = $param['field'];
            $key   = $param['key'];
            $value = $param['value'];
            
            TTransaction::open('sisacad'); // open a transaction with database
            $object = new professorcontrole_aula($key); // instantiates the Active Record
            $object->{$field} = $value;
            $object->store(); // update the object in the database
            TTransaction::close(); // close the transaction
            
            $this->onReload($param); // reload the listing
            new TMessage('info', "Record Updated");
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', '<b>Error</b> ' . $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }
    
    /**
     * Register the filter in the session
     */
    public function onSearch()
    {
        // get the search form data
        $data = $this->form->getData();
        
        // clear session filters
        TSession::setValue('controle_geracaoForm_filter_professor_id',   NULL);
        TSession::setValue('controle_geracaoForm_filter_aulas_saldo',   NULL);
        TSession::setValue('controle_geracaoForm_filter_aulas_pagas',   NULL);
        TSession::setValue('controle_geracaoForm_filter_data_aula',   NULL);
        TSession::setValue('controle_geracaoForm_filter_historico_pagamento',   NULL);

        if (isset($data->professor_id) AND ($data->professor_id)) {
            $filter = new TFilter('professor_id', 'like', "%{$data->professor_id}%"); // create the filter
            TSession::setValue('controle_geracaoForm_filter_professor_id',   $filter); // stores the filter in the session
        }


        if (isset($data->aulas_saldo) AND ($data->aulas_saldo)) {
            $filter = new TFilter('aulas_saldo', 'like', "%{$data->aulas_saldo}%"); // create the filter
            TSession::setValue('controle_geracaoForm_filter_aulas_saldo',   $filter); // stores the filter in the session
        }


        if (isset($data->aulas_pagas) AND ($data->aulas_pagas)) {
            $filter = new TFilter('aulas_pagas', 'like', "%{$data->aulas_pagas}%"); // create the filter
            TSession::setValue('controle_geracaoForm_filter_aulas_pagas',   $filter); // stores the filter in the session
        }


        if (isset($data->data_aula) AND ($data->data_aula)) {
            $filter = new TFilter('data_aula', 'like', "%{$data->data_aula}%"); // create the filter
            TSession::setValue('controle_geracaoForm_filter_data_aula',   $filter); // stores the filter in the session
        }


        if (isset($data->historico_pagamento) AND ($data->historico_pagamento)) {
            $filter = new TFilter('historico_pagamento', 'like', "%{$data->historico_pagamento}%"); // create the filter
            TSession::setValue('controle_geracaoForm_filter_historico_pagamento',   $filter); // stores the filter in the session
        }

        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('professorcontrole_aula_filter_data', $data);
        
        $param=array();
        $param['offset']    =0;
        $param['first_page']=1;
        $this->onReload($param);
    }
    
    /**
     * Load the datagrid with data
     */
    public function onReload($param = NULL)
    {
        //var_dump($param);
        $test = TSession::getValue(__CLASS__.'controle_id');
        if ($test != '')
        {
            $key = $test;
        }
        else
        {
            if (isset($param['key']))
            {
                $key = $param['key'];
            }
            else
            {
                TApplication::loadPage('controle_geracaoList','onReload');
            }
        }
        $fer = new TFerramentas;
        try
        {
            // open a transaction with database 'sisacad'
            TTransaction::open('sisacad');
            
            // creates a repository for professorcontrole_aula
            $repository = new TRepository('professorcontrole_aula');
            $limit = 10;
            // creates a criteria
            $criteria = new TCriteria;
            
            // default order
            if (empty($param['order']))
            {
                $param['order'] = 'professor_id,data_aula';
                $param['direction'] = 'asc';
            }
            $criteria->setProperties($param); // order, offset
            $criteria->setProperty('limit', $limit);
            
            $criteria->add(new TFilter('historico_pagamento','LIKE','%[CTR='.$key.'!%'));

            if (TSession::getValue('controle_geracaoForm_filter_professor_id')) {
                $criteria->add(TSession::getValue('controle_geracaoForm_filter_professor_id')); // add the session filter
            }


            if (TSession::getValue('controle_geracaoForm_filter_aulas_saldo')) {
                $criteria->add(TSession::getValue('controle_geracaoForm_filter_aulas_saldo')); // add the session filter
            }


            if (TSession::getValue('controle_geracaoForm_filter_aulas_pagas')) {
                $criteria->add(TSession::getValue('controle_geracaoForm_filter_aulas_pagas')); // add the session filter
            }


            if (TSession::getValue('controle_geracaoForm_filter_data_aula')) {
                $criteria->add(TSession::getValue('controle_geracaoForm_filter_data_aula')); // add the session filter
            }


            if (TSession::getValue('controle_geracaoForm_filter_historico_pagamento')) {
                $criteria->add(TSession::getValue('controle_geracaoForm_filter_historico_pagamento')); // add the session filter
            }

            
            // load the objects according to criteria
            $objects = $repository->load($criteria, FALSE);
            
            if (is_callable($this->transformCallback))
            {
                call_user_func($this->transformCallback, $objects, $param);
            }
            
            $this->datagrid->clear();
            if ($objects)
            {
                // iterate the collection of active records
                foreach ($objects as $object)
                {
                    // add the object inside the datagrid
                    //Nome do professor
                    $posto = $object->professor->postograd->sigla;
                    $posto = (empty($posto)) ? '' : $posto . ' ';
                    $orgao = $object->professor->orgaosorigem->sigla;
                    $orgao = (empty($orgao)) ? '' : ' (' . $orgao . ')';
                    
                    //Turma/Aula/disciplina
                    $turma   = $object->controleaula->materia->turma->sigla;
                    $turma = (empty($turma)) ? '' : $turma;
                    $materia = $object->controleaula->materia->disciplina->nome;
                    $materia = (empty($materia)) ? '' : '(' . $materia . ')';
                    
                    //Busca Aulas pagas no Documento Referenciado
                    $ctr_ini = strpos($object->historico_pagamento,'[CTR='.$key.'!CH-');
                    if (false !== $ctr_ini)
                    {
                        $ch_ini  = strpos($object->historico_pagamento,'!CH-',$ctr_ini);
                        $next    = strpos($object->historico_pagamento,'[CTR=',$ch_ini);
                        if (false === $next)//Não há mais pagamento após este
                        {
                            $tam     = (strpos($object->historico_pagamento,']',$ch_ini)) - $ch_ini;//Evita pegar além do necessário
                            $hora    = substr($object->historico_pagamento,$ch_ini,$tam);//Pega a hora
                        }
                        else//Há outro pagamento no histórico, pegar só um pedaço
                        {
                            $hora    = substr($object->historico_pagamento,$ch_ini, ($next - $ch_ini));
                        }
                    }
                    else
                    {
                        $hora = '0';
                    }
                    $hora = $fer->soNumeros($hora);
                    //Remonta os dados para o DataGrid
                    $object->professor_id     = $posto . $object->professor->nome . $orgao;
                    $object->controle_aula_id = $turma.$materia;
                    $object->aulas_saldo      = $object->aulas_saldo;
                    $object->data_aula        = TDate::date2br($object->data_aula);
                    $object->valor_aula       = 'R$ '.number_format($object->valor_aula,2,'.','');
                    $object->aulas_pagas      = $hora;
                    
                    $this->datagrid->addItem($object);
                }
            }
            
            // reset the criteria for record count
            $criteria->resetProperties();
            $count= $repository->count($criteria);
            
            $this->pageNavigation->setCount($count); // count of records
            $this->pageNavigation->setProperties($param); // order, page
            $this->pageNavigation->setLimit($limit); // limit
            
            // close the transaction
            TTransaction::close();
            $this->loaded = true;
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            // undo all pending operations
            TTransaction::rollback();
        }
    }
    
/*---------------------------------------------------------------------------------------
 *  Rotina: Questiona o Estorno
 *---------------------------------------------------------------------------------------*/
    public function onEstorno($param)
    {
        // Solicita Confirmação de Estorno
        $action = new TAction(array($this, 'EstornaAula'));
        $action->setParameters($param); // pass the key parameter ahead
        
        // Faz a pergunta ao usuário
        new TQuestion('Deseja Realmente estornar somente essa Aula?', $action);
    }//Fim Módulo
/*--------------------------------------------------------------------------
 *    Executa a ação de estorno de aula.
 *--------------------------------------------------------------------------*/    
    public function EstornaAula( $param )
    {
        //echo 'Parametros iniciais - >';var_dump($param);

        $fer    = new TFerramentas();
        $relato = new TSisacadFinanceiroReport();
        $lista  = false;
        $salvar = 'S';
        $controle_id = TSession::getValue(__CLASS__.'controle_id');//Controle de Geração
        $key = $param['id'];//Item a estornar
        $gravado = false;
        $estorno = 'NC';
        try
        {
            TTransaction::open('sisacad'); // open a transaction
            

            $object = new professorcontrole_aula ($key);
            
            $ids_usados = array();
            //$maximo = self::$valor_maximo;
            $teto = false;
            $a_pagar = 0;
            $hh_trab = 0;
            $dt_trab = '';
            $ctr_ini = strpos($object->historico_pagamento,'[CTR='.$controle_id.'!CH-');
            if (false !== $ctr_ini)
            {
                $ch_ini  = strpos($object->historico_pagamento,'!CH-',$ctr_ini);
                $next    = strpos($object->historico_pagamento,'[CTR=',$ch_ini);
                if (false === $next)//Não há mais pagamento após este
                {
                    $hora    = substr($object->historico_pagamento,$ch_ini);
                }
                else//Há outro pagamento no histórico, pegar só um pedaço
                {
                    $hora    = substr($object->historico_pagamento,$ch_ini, ($next - $ch_ini));
                }
                $ch_estorno = $fer->soNumeros($hora);
                $er_estorno = $ch_estorno;
                if ($object->aulas_saldo == $object->aulas_pagas)
                {
                    $ch_estorno = $relato->recalculaSaldo(array('historico'=>$object->historico_pagamento,
                                                              'saldo'=>$object->aulas_saldo,
                                                              'ch_estorno'=>$ch_estorno,
                                                              'controle_id'=>$controle_id,
                                                              'id'=>$object->id));
                    echo 'Correção ->' . $ch_estorno . '<br>';
                }
                if ($ch_estorno > 0)
                {
                    $ids_usados[] = $object->controle_aula_id;      //Define as Ids usadas
                    if ($ch_estorno > $object->aulas_saldo)
                    {
                        $ch_estorno = $object->aulas_saldo;
                    }
                    else if ($ch_estorno < 0)
                    {
                        $ch_estorno = 0;
                    }
                    $v_aula = $object->valor_aula * ($ch_estorno);
                    $a_pagar = $a_pagar + $v_aula;                  //Soma o valor desta aula ao acumulado
                    $hh_trab = $hh_trab + $ch_estorno;
                    $dt_trab .= ((!empty($dt_trab)) ? ', ' : '') . TDate::date2br($object->data_aula);
                    if ($salvar == 'S')
                    {
                        $aula                      = new professorcontrole_aula($object->id);
                        $aula->aulas_pagas         = $aula->aulas_pagas - $ch_estorno;
                        $aula->data_pagamento      = '';
                        $aula->data_quitacao       = '';
                        $aula->historico_pagamento = str_replace('[CTR=' . $controle_id . '!CH-' . $er_estorno . ']',
                                                                 '[RET=' . $controle_id . '|HS-' . $ch_estorno . ']',
                                                                 $aula->historico_pagamento);
                        $estorno = '[professorcontrole_aula=' . $aula->id . '][RET=' . $controle_id . '|HS-' . $er_estorno . ']';
                        
                        //var_dump($aula);
                        $aula->store();
                    }
                }
                else if ($ch_estorno == 0) //Para o caso de se achar um histórico com zero
                {
                    $aula                      = new professorcontrole_aula($object->id);
                    $aula->historico_pagamento = str_replace('[CTR=' . $controle_id . '!CH-' . $ch_estorno . ']',
                                                             '',
                                                             $aula->historico_pagamento);
                    //var_dump($aula);
                    $aula->store();
                }
            }//Fim if($ctr_in)
            TTransaction::close();
            $gravado = true;
            
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
        if ($gravado)
        {
            $grv         = new TControleGeracao();
            $grv->motivo = $estorno; 
            //Muda o Status do Controle para retificado
            $grv->alteraControle($controle_id, 2 , 5  );
            // Retorna
            //$param = array('key'=>TSession::getValue(__CLASS__.'controle_id'),'retorno'=>true);
            $posAction = new TAction(array($this, 'onReload'));
            //$posAction->setParameters( $param);
            //echo 'Retorno->' . TSession::getValue(__CLASS__.'controle_id'); var_dump($param);
            new TMessage('info', 'Aula Estornada com Sucesso!', $posAction);
        }

        //self::onReload($param);
        
        
    }//Fim Módulo    
    
    /**
     * Transform datagrid objects
     * Create the checkbutton as datagrid element
     */
    public function onBeforeLoad($objects, $param)
    {
        // update the action parameters to pass the current page to action
        // without this, the action will only work for the first page
        //$deleteAction = $this->deleteButton->getAction();
        //$deleteAction->setParameters($param); // important!
        
        //$gridfields = array( $this->deleteButton );
        
        /*foreach ($objects as $object)
        {
            $object->check = new TCheckButton('check' . $object->id);
            $object->check->setIndexValue('on');
            $gridfields[] = $object->check; // important
        }
        
        $this->formgrid->setFields($gridfields);*/
    }

    
    /**
     * method show()
     * Shows the page
     */
    public function show()
    {
        // check if the datagrid is already loaded
        if (!$this->loaded AND (!isset($_GET['method']) OR !(in_array($_GET['method'],  array('onReload', 'onSearch')))) )
        {
            if (func_num_args() > 0)
            {
                $this->onReload( func_get_arg(0) );
            }
            else
            {
                $this->onReload();
            }
        }
        parent::show();
    }
    public function onEdit($param = null)
    {
        $key = null;
        if (isset($param['key']) && $param != '')
        {
            $key = $param['key'];
            TSession::setValue(__CLASS__.'controle_id',$key);
        }
        $this->onReload(array('key'=>$key));
    }
/*---------------------------------------------------------------------------------------
 *  Rotina: Gera relatório para professor
 *---------------------------------------------------------------------------------------*/
    public function getProfessores ($param = null)
    {
        $lista = false;
        try
        {
            TTransaction::open('sisacad');
            $criteria = new TCriteria();
            
            $sql         = "(SELECT DISTINCT professor_id FROM sisacad.professorcontrole_aula WHERE historico_pagamento LIKE '%[CTR=" . 
                           $param['id'] . "!%')";
            $professores = professor::where ('id','IN',$sql)->load();
            
            //var_dump($professores);
            if ($professores)
            {
                $lista = array();
                foreach($professores as $professor)
                {
                    //Nome do professor
                    $posto = $professor->postograd->sigla;
                    $posto = (empty($posto)) ? '' : $posto . ' ';
                    $orgao = $professor->orgaosorigem->sigla;
                    $orgao = (empty($orgao)) ? '' : ' (' . $orgao . ')';
                    
                    $lista[$professor->id] = $posto . $professor->nome . $orgao;
                }
                
            }
            TTransaction::close();
        }
        catch (Exception $e) // in case of exception
        {
            TTransaction::rollback();
        }
        return $lista;
    }
    
/*---------------------------------------------------------------------------------------
 *  Rotina: Gera relatório para professor
 *---------------------------------------------------------------------------------------*/
    public function onGeraRelatorio ($param = null)
    {
        //var_dump($param);
        $data = $this->form->getData();
        TSession::setValue('controle_geracaoForm',$data);
        $fer = new TFerramentas();
        $controle_id      = new THidden('controle_id');
        $professor_id     = new TCombo('professor_id');
        $opcionais        = new TCheckGroup('opcionais');
        
        //Valores
        $professores = self::getProfessores(array('id'=>TSession::getValue(__CLASS__.'controle_id')));
        if ($professores == false)
        {
            $this->form->setData($data);
            new TMessage ('error','Não localizei Professores nesta folha de pagamento. Verifique!');
            return;
        }
        $professor_id->addItems($professores);
        //$opcionais->addItems(array('C'=>'Controles de Aula','T'=>'Titulos','D'=>'Documentação Pessoal','E'=>'Extrato de Aulas'));
        $opcionais->addItems(array('C'=>'Controles de Aula','E'=>'Extrato de Aulas'));
        $controle_id->setValue(TSession::getValue(__CLASS__.'controle_id'));
        
        //Tamanho
        $professor_id->setSize(350);

        $form = new TForm('input_form');
        $form->style = 'padding:20px';
        
        $table = new TTable;
        $table->addRowSet( '', $controle_id );
        $table->addRowSet( $lbl = new TLabel('Professor: '), $professor_id );
        $table->addRowSet( '', $opcionais );
        $lbl->setFontColor('red');

        $form->setFields(array($controle_id, $professor_id, $opcionais));
        $form->add($table);
        
        // show the input dialog
        $action = new TAction(array($this, 'GeraRelatorio'));
        $action->setParameter('stay-open', 1);
        new TInputDialog('Selecione o Professor e os Opcionais', $form, $action, 'Confirma');
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Rotina: Gera relatório para professor
 *---------------------------------------------------------------------------------------*/
    public function GeraRelatorio ($param = null)
    {
        //var_dump($param);
        $fer = new TFerramentas();
        try 
        {
            if (!is_array($param))
            {
                throw new Exception('Erro interno. Não carregado as definições de professor ou de opções.');
            }
            if (!array_key_exists('professor_id',$param) || $param['professor_id'] == '')
            {
                throw new Exception ('Professor não definido.');
            } 
            TTransaction::open('sisacad');
            //Carrega identificação do professor
            
            $professor = new professor($param['professor_id']);
            //Carregas as aulas deste controle de aula filtrando o professor
            $aulas     = professorcontrole_aula::where('professor_id','=',$param['professor_id'])->
                                                 where('historico_pagamento','LIKE', "%[CTR=" . $param['controle_id'] . "!%")->
                                                 load();
            //Monta o Nome do professor
            if ($professor)
            {
                $posto = $professor->postograd->sigla;
                $posto = (empty($posto)) ? '' : $posto . ' ';
                $orgao = $professor->orgaosorigem->sigla;
                $orgao = (empty($orgao)) ? '' : ' (' . $orgao . ')';
                $professor_nome = $posto . $professor->nome . $orgao;
            }
            else
            {
                throw new Exception ('Erro ao identificar o professor.');
            }
            $erros = array();
            $erro  = false;
            //Monta lista de aulas com base na turma e datas da aula do professor
            if ($aulas)
            {
                //var_dump($aulas);
                $lista_aula = array();
                foreach ($aulas as $aula)
                {
                    $turma_id = $aula->controleaula->materia->turma->id;
                    $data     = $aula->data_aula;
                    //Cria array com indice feito com base nos dados da turma e data da aula
                    //Assim se houver uma outra aula no mesmo dia irá sobrescrever o mesmo item
                    //evitando de puxar duas vezes o mesmo formulário de controle de aula
                    $lista_aula[ 't-' . $turma_id . '-dt-' . $data ] = array('turma_id'=>$turma_id,'data_aula'=>$data); 
                }
            }
            else
            {
                throw new Exception ('Erro ao localizar as aulas do professor ' . $professor_nome);
            }
            //var_dump($lista_aula);
            //
            if (array_key_exists('opcionais',$param))
            {
                $opcionais = $param['opcionais'];
            }
            else
            {
                throw new Exception('Erro ao carregar opções de relatório.');
            }
            $cont_arq = 1;
            if (file_exists('tmp/'. TSession::getValue('login') .'-parte-000.pdf'))
            {
                unlink('tmp/'. TSession::getValue('login') .'-parte-000.pdf');
            }
            foreach($opcionais as $opcao)
            {
                switch ($opcao) {
                    case 'C'://Recria os controle de aula em PDF
                        foreach ($lista_aula as $aula)
                        {
                            $doc = documentos_turma::where('turma_id','=',$aula['turma_id'])->
                                                     where('data_aula','=',$aula['data_aula'])->
                                                     where('comprovante','=','S')->load();
                            //var_dump($doc);
                            if (!empty($doc))
                            {
                                $arquivo = $doc['0']->arquivos_externos_id;
                                if ($arquivo)
                                {
                                    $sql = "SELECT DISTINCT encode(contend, 'base64')as contend FROM sisacad.arquivos_externos WHERE id=".$arquivo;
                                    $conn = TTransaction::get();
                                    $res = $conn->prepare($sql);
                                    $res->execute();
                                    $dados = $res->fetchAll();
                                    
                                    if ($dados)
                                    {
                                        $object = new arquivos_externos($arquivo); // instantiates the Active Record
                                        $arquivo = $dados['0']['contend'];//pg_unescape_bytea($dados['0']['contend']);
                                        $file = 'tmp/'. TSession::getValue('login') .'-parte-' . 
                                                str_pad($cont_arq, 3, '0', STR_PAD_LEFT) . '.pdf';//Cria o nome de arquivo na sequencia
                                        if (strtolower(substr($object->filename, -3)) == 'pdf')
                                        {
                                            file_put_contents($file,base64_decode($arquivo));
                                        }
                                    }
                                    $cont_arq ++;
                                }
                            }
                            else
                            {
                                $erro = true;
                                if ($aula['data_aula'])
                                {
                                    $data_a = TDate::date2br($aula['data_aula']);
                                }
                                else
                                {
                                    $data_a = ' -ERRO- ';
                                }
                                $erros[] = 'Documento do Dia ' . $data_a . ' não Localizado.';
                                //new TMessage('info','Controle de Aula não carregado para o Banco');
                            }
                            //echo $file . ' Criado <br>';

                        }
                        break;
                    case 'T':

                        break;
                    case 'D':

                        break;
                    case 'E'://Cria um PDF com a movimentação do professor

                        if ($aulas)
                        {
                            $widths = array(60, 320, 240, 50, 60,80);
                            $tr = new TTableWriterPDF($widths , 'L');
                            if (!empty($tr))
                            {
                                // create the document styles
                                $tr->addStyle('title', 'Arial', '10', 'BI',  '#000000', '#ADD8E6');
                                $tr->addStyle('datap', 'Arial', '10', '',    '#000000', '#869FBB');
                                $tr->addStyle('datai', 'Arial', '10', '',    '#000000', '#ffffff');
                                $tr->addStyle('header', 'Times', '14', 'BI', '#000000', '#ADD8E6');
                                $tr->addStyle('footer', 'Times', '12', 'BI', '#000000', '#ADD8E6');
                                
                                // add a header row

                                $cab = array('MOVIMENTAÇÃO FINANCEIRA DO PROFESSOR - ' .$professor_nome) ;
                                foreach($cab as $c)
                                {
                                    $tr->addRow();
                                    $tr->addCell($c, 'center', 'header', 6);
                                }
                                
                                // add titles row
                                $tr->addRow();
                                $tr->addCell('Data',      'center', 'title');
                                $tr->addCell('Turma',     'center', 'title');
                                $tr->addCell('Matéria',   'center', 'title');
                                $tr->addCell('Aulas',     'center', 'title');
                                $tr->addCell('R$/Aula',   'center', 'title');
                                $tr->addCell('R$/Total',  'center', 'title');
                                
                                // controls the background filling
                                $colour= FALSE;
                                
                                // data rows
                                foreach ($aulas as $aula)
                                {
                                    //Busca Aulas pagas no Documento Referenciado
                                    $ctr_ini = strpos($aula->historico_pagamento,'[CTR='.$param['controle_id'].'!CH-');
                                    if (false !== $ctr_ini)
                                    {
                                        $ch_ini  = strpos($aula->historico_pagamento,'!CH-',$ctr_ini);
                                        $next    = strpos($aula->historico_pagamento,'[CTR=',$ch_ini);
                                        if (false === $next)//Não há mais pagamento após este
                                        {
                                            $hora    = substr($aula->historico_pagamento,$ch_ini);
                                        }
                                        else//Há outro pagamento no histórico, pegar só um pedaço
                                        {
                                            $hora    = substr($aula->historico_pagamento,$ch_ini, ($next - $ch_ini));
                                        }
                                    }
                                    else
                                    {
                                        $hora = '0';
                                    }
                                    $hora = $fer->soNumeros($hora);
                                    
                                    $style = $colour ? 'datap' : 'datai';
                                    $tr->addRow();
                                    $tr->addCell(TDate::date2br($aula->data_aula)                       , 'right', $style);
                                    $tr->addCell($aula->controleaula->materia->turma->nome              , 'center', $style);
                                    $tr->addCell($aula->controleaula->materia->disciplina->nome         , 'center', $style);
                                    $tr->addCell($hora                                                  , 'right', $style);
                                    $tr->addCell(number_format($aula->valor_aula,2,'.','')              , 'right', $style);
                                    $tr->addCell('R$ '.number_format($hora * $aula->valor_aula,2,'.',''), 'right', $style);
                                    
                                    $colour = !$colour;
                                }
                                
                                // footer row
                                $tr->addRow();
                                $tr->addCell(date('Y-m-d h:i:s'), 'center', 'footer', 6);
                                
                                $file = 'tmp/'. TSession::getValue('login') .'-parte-000.pdf';//Cria o nome de arquivo na sequencia
                                        
                                // stores the file
                                if (file_exists($file))
                                {
                                    unlink($file);
                                }
                                
                                $tr->save($file);//Grava Arquivo PDF com extrato das aulas
                                $cont_arq ++;
                                parent::openFile($file);
                            }
                        }
                        break;
                }//Fim Switch
                
            }//Fim Foreach
           
            TTransaction::close();
        }
        catch (Exception $e) // in case of exception
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
            $data = TSession::getValue('controle_geracaoForm');
            self::onEdit (array('key'=>$data->id));
        }
        
        if ($erro)
        {
            $msg = implode('<br>',$erros);
            new TMessage('info',$msg);
        }
        
        try
        {
            $merge_pdf = new PDFMerger;
            //error_reporting(0);
            $merged    = 'tmp/'. TSession::getValue('login') .'-merged.pdf';
            for ($i = 0; $i < ($cont_arq); $i++)
            {
                $file = 'tmp/'. TSession::getValue('login') .'-parte-' . 
                        str_pad($i, 3, '0', STR_PAD_LEFT) . '.pdf';
                //var_dump($file);
                if (file_exists($file))
                {
                    $merge_pdf->addPDF($file);
                    //echo $file . ' adicionado <br>';
                    //TPage::openFile($file);
                }
            }
            if (file_exists($merged))
            {
                //unlink($merged);
            }
            $merge_pdf->merge('file',$merged);
        }
        catch (Exception $e)
        {
            if (!file_exists($merged))
            {
                new TMessage('error','Erro ao criar relatório.<br>' . $e->getMessage());
                self::onReload();
            }
            
        }
        if (file_exists($merged))
        {
            TPage::openFile($merged);
        }

        $this->onReload();
    }//Fim Módulo
    
    public function onCorrige ( $param = null)
    {
        $controle_id = 19;
        $fer = new TFerramentas;
        try
        {
            TTransaction::open('sisacad');

            $aulas = professorcontrole_aula::where ('historico_pagamento','LIKE','%[RET='.$controle_id.'|%')->load();
            
            //var_dump($professores);
            if ($aulas)
            {
                foreach($aulas as $aula)
                {
                    $historico = $aula->historico_pagamento;
                    
                    $historico = str_replace('[RET=' . $controle_id.'|',
                                             '[CTR=' . $controle_id.'!',
                                             $historico);
                    $pos   = strpos($historico,'HS-'); 
                    $saldo = $fer->soNumeros(substr($historico,$pos));
                    $historico = str_replace('HS-',
                                             'CH-',
                                             $historico);
                    $aula->historico_pagamento = $historico;
                    $aula->aulas_pagas = $saldo;
                    $aula->store();
                    echo 'Historico ->' . $historico . ' ---- Saldo -> ' . $saldo .'<br>';
                    
                }
                
            }
            TTransaction::close();
        }
        catch (Exception $e) // in case of exception
        {
            TTransaction::rollback();
        }
    }
    
}//Fim Classe
