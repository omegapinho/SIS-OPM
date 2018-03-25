<?php
/**
 * avaliacao_turmaForm Master/Detail
 * @author  <your name here>
 */
class avaliacao_turmaForm extends TPage
{
    protected $form; // form
    protected $formFields;
    protected $detail_list;
    
    private $curso_id;
    
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
        $this->form = new TForm('form_avaliacao_turma');
        $this->form->class = 'tform'; // CSS class
        $this->form->style = 'max-width:100%'; // style
        parent::include_css('app/resources/custom-frame.css');
        
        $table_master = new TTable;
        $table_master->width = '100%';
        
        $table_master->addRowSet( new TLabel('Gestor de Provas - Edição'), '', '')->class = 'tformtitle';
        
        // add a table inside form
        $table_general = new TTable;
        $table_detail  = new TTable;
        $table_general-> width = '100%';
        $table_detail-> width  = '100%';
        
        $frame_general = new TFrame;
        $frame_general->setLegend('Dados da turma e da disciplina');
        $frame_general->style = 'background:whiteSmoke';
        $frame_general->add($table_general);
        
        $table_button = new TTable;
        $table_button->width = '100%';
        /*----------------------------------------------------------------------
         * Monta os botãos de chamadas
         *----------------------------------------------------------------------*/
        // create an action button (1ª chamada)
        $chamada_1 = new TButton('chamada_1');
        $chamada_1->setAction(new TAction(array($this, 'onPrimeira')), 'Cria 1ª Chamada');
        $chamada_1->setImage('fa:file-text green');
        
        // create an action button (Ver Resultado)
        $resultado=new TButton('resultado');
        $resultado->setAction(new TAction(array($this, 'onResultado')), 'Resultado');
        $resultado->setImage('fa:bomb red');
        //Add botões
        $table_button->addRowSet(array($chamada_1,$resultado));
        
        $frame_button = new TFrame;
        $frame_button->style = 'text-align: center;';
        $frame_button->add($table_button);

        
        $table_master->addRow()->addCell( $frame_general )->colspan=2;
        $row = $table_master->addRow();
        $row->addCell( $frame_button );
        $row = $table_master->addRow();
        $row->addCell( $table_detail );
        
        $this->form->add($table_master);
        
        // master fields
        $id = new TEntry('id');
        if ($this->nivel_sistema<=80)//Gestores e/Operadores
        {
            $criteria = new TCriteria();
            $query = "(SELECT DISTINCT id FROM sisacad.turma WHERE opm_id IN (".$this->listas['valores']."))";
            $criteria->add (new TFilter ('id','IN',$query), TExpression::OR_OPERATOR);
            $query = "(SELECT DISTINCT turma_id FROM sisacad.turmaopm WHERE opm_id IN (".$this->listas['valores']."))";
            $criteria->add (new TFilter ('id','IN',$query), TExpression::OR_OPERATOR);
        }
        else
        {
            $criteria = null;
        }
        
        $turma_id           = new TDBCombo('turma_id','sisacad','turma','id','nome','nome',$criteria);
        $materia_id         = new TEntry('materia_id');
        $oculto             = new TCombo('oculto');
        $tipo_avaliacao     = new TEntry('tipo_avaliacao');
        $avaliacao_curso_id = new THidden('avaliacao_curso_id');
        
        //Valores
        $oculto->addItems($fer->lista_sim_nao());
        
        if (!empty($id))
        {
            $id->setEditable(FALSE);
            $turma_id->setEditable(FALSE);
            $materia_id->setEditable(FALSE);
            $oculto->setEditable(FALSE);
            $tipo_avaliacao->setEditable(FALSE);
        }
        
        // detail fields
        $detail_id                 = new THidden('detail_id');
        $detail_dt_aplicacao       = new TDate('detail_dt_aplicacao');
        $detail_tipo_prova         = new TCombo('detail_tipo_prova');
        $detail_usuario_liberador  = new THidden('detail_usuario_liberador');
        $detail_data_liberacao     = new THidden('detail_data_liberacao');
        $detail_oculto             = new TCombo('detail_oculto');
        $detail_status             = new TCombo('detail_status');
        
        //Tamanhos
        $id->setSize(80);
        $turma_id->setSize(300);
        $materia_id->setSize(280);
        $oculto->setSize(120);
        $tipo_avaliacao->setSize(200);
        
        $detail_dt_aplicacao->setSize(120);
        $detail_tipo_prova->setSize(150);
        $detail_oculto->setSize(80);
        $detail_status->setSize(220);
        
        //Mascaras
        $detail_dt_aplicacao->setMask('dd/mm/yyyy');
        
        //Valores
        $detail_tipo_prova->addItems($fer->lista_tipo_prova());
        $detail_oculto->addItems($fer->lista_sim_nao());
        $detail_oculto->setValue('N');
        $detail_status->addItems($fer->lista_status_aplicacao_prova());
        $detail_status->setValue('AG');

        
        // master
        $table_general->addRowSet(array( new TLabel('Id'), $id, new TLabel('Turma'), $turma_id, new TLabel('Matéria'), $materia_id ));
        $table_general->addRowSet( array(new TLabel('Tipo de Avaliação'), $tipo_avaliacao, new TLabel('Finalizada?'), $oculto) );
        $table_general->addRowSet( '', $avaliacao_curso_id );
        
         // detail
        $frame_details = new TFrame();
        $frame_details->setLegend('Dados da Prova');
        $row = $table_detail->addRow();
        $row->addCell($frame_details);
        $frame_details->oid = 'frame-measures'; 
        
        $btn_save_detail = new TButton('btn_save_detail');
        $btn_save_detail->setAction(new TAction(array($this, 'onSaveDetail')), 'Register');
        $btn_save_detail->setImage('fa:save');
        
        $table_details = new TTable;
        $frame_details->add($table_details);
        
        $table_details->addRowSet( new TLabel('Data da Aplicação'), $detail_dt_aplicacao );
        $table_details->addRowSet( new TLabel('Tipo de Prova'), $detail_tipo_prova );
        $table_details->addRowSet( new TLabel('Status'), $detail_status );
        $table_details->addRowSet( new TLabel('Notas Lançadas?'), $detail_oculto );
        $table_details->addRowSet( '', $detail_usuario_liberador );
        $table_details->addRowSet( '', $detail_data_liberacao );
        $table_details->addRowSet( '', $detail_id );
        
        $table_details->addRowSet( $btn_save_detail );
        
        $this->detail_list = new TQuickGrid;
        $this->detail_list->setHeight( 175 );
        $this->detail_list->makeScrollable();
        $this->detail_list->disableDefaultClick();
        $this->detail_list->addQuickColumn('', 'edit', 'center', 30);
        $this->detail_list->addQuickColumn('', 'delete', 'center', 30);
        $this->detail_list->addQuickColumn('', 'avancar', 'center', 30);
        $this->detail_list->addQuickColumn('', 'pendencia', 'center', 30);
        $this->detail_list->addQuickColumn('', 'imprime', 'center', 30);
        
        // items
        $this->detail_list->addQuickColumn('Data da Aplicação', 'dt_aplicacao', 'right', 120);
        $this->detail_list->addQuickColumn('Tipo de Prova', 'tipo_prova', 'center', 150);
        $this->detail_list->addQuickColumn('Status', 'status', 'center', 150);
        $this->detail_list->addQuickColumn('Notas Lançadas?', 'oculto', 'center', 80);
        //$this->detail_list->addQuickColumn('Usuario Liberador', 'usuario_liberador', 'left', 120);
        //$this->detail_list->addQuickColumn('Data de Liberação', 'data_liberacao', 'left', 120);
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
        //(empty($item['status'])) ? 'AG' : $item['status'];
        //Retorna para Listagem
        $ret_button=new TButton('return');
        $ret_button->setAction(new TAction(array('avaliacao_turmaList', 'onReload')), _t('Back to the listing'));
        $ret_button->setImage('ico_back.png');
        // define form fields
        $this->formFields   = array($id,$turma_id,$materia_id,$oculto,$avaliacao_curso_id,$detail_status,$detail_oculto,
                                    $detail_dt_aplicacao,$detail_tipo_prova,$detail_usuario_liberador,$detail_data_liberacao);
        $this->formFields[] = $btn_save_detail;
        $this->formFields[] = $chamada_1;
        $this->formFields[] = $resultado;
        $this->formFields[] = $save_button;
        //$this->formFields[] = $new_button;
        $this->formFields[] = $ret_button;
        $this->formFields[] = $detail_id;
        $this->form->setFields( $this->formFields );
        
        //$table_master->addRowSet( array($save_button, $new_button,$ret_button), '', '')->class = 'tformaction'; // CSS class
        $table_master->addRowSet( array($save_button, $ret_button), '', '')->class = 'tformaction'; // CSS class
        
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
            
            if (! $data->detail_dt_aplicacao)
                throw new Exception('A data de aplicação é necessária');
            if (! $data->detail_tipo_prova)
                throw new Exception('É necessário escolher qual o tipo da prova.');
            
            $items = TSession::getValue(__CLASS__.'_items');
            $key = empty($data->detail_id) ? 'X'.mt_rand(1000000000, 1999999999) : $data->detail_id;
            //var_dump($data);
            $items[ $key ]                      = array();
            $items[ $key ]['id']                = $key;
            $items[ $key ]['dt_aplicacao']      = TDate::date2us($data->detail_dt_aplicacao);
            $items[ $key ]['tipo_prova']        = $data->detail_tipo_prova;
            $items[ $key ]['usuario_liberador'] = TSession::getValue('login');//$data->detail_usuario_liberador;
            $items[ $key ]['data_liberacao']    = date('Y-m-d');//$data->detail_data_liberacao;
            $items[ $key ]['oculto']            = (empty($data->detail_oculto)) ? 'N' : $data->detail_oculto;
            $items[ $key ]['status']            = (empty($data->detail_status)) ? 'AG' : $data->detail_status;
            
            //var_dump($items[$key]);
            TSession::setValue(__CLASS__.'_items', $items);
            
            // clear detail form fields
            $data->detail_id                = '';
            $data->detail_dt_aplicacao      = '';
            $data->detail_tipo_prova        = '';
            $data->detail_usuario_liberador = '';
            $data->detail_data_liberacao    = '';
            $data->detail_oculto            = '';
            $data->detail_status            = '';
            
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
        
        $data->detail_id                = $item['id'];
        $data->detail_dt_aplicacao      = $item['dt_aplicacao'];
        $data->detail_tipo_prova        = $item['tipo_prova'];
        $data->detail_usuario_liberador = $item['usuario_liberador'];
        $data->detail_data_liberacao    = $item['data_liberacao'];
        $data->detail_oculto            = $item['oculto'];
        $data->detail_status            = $item['status'];
        
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
            $data->detail_dt_aplicacao      = '';
            $data->detail_tipo_prova        = '';
            $data->detail_usuario_liberador = '';
            $data->detail_data_liberacao    = '';
            $data->detail_oculto       = '';
            $data->detail_status       = '';
        
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
            //var_dump($items);
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
                
                $action_ava = new TAction(array($this, 'onAplicaProva'));
                $action_ava->setParameter('item_key', $list_item_key);
                
                $action_pen = new TAction(array('avaliacao_prova_pendenciasForm', 'onEdit'));
                $action_pen->setParameter('key', $list_item_key);

                $action_imp = new TAction(array('avaliacao_turmaForm', 'onImprime'));
                $action_imp->setParameter('key', $list_item_key);

                $button_ava = new TButton('avancar_detail'.$cont);
                $button_ava->class = 'btn btn-default btn-sm';
                $button_ava->setAction( $action_ava, '' );
                $button_ava->setImage('fa:flag green fa-lg');
                $button_ava->setTip('Aplica a prova.');
                $button_ava->popover = 'true';
                $button_ava->popside = 'bottom';
                $button_ava->poptitle = 'Aplica prova.';
                $button_ava->popcontent = 'Muda o status da prova para Aplicação liberando acesso ao professor.<br>';
                
                $button_pen = new TButton('pendencia_detail'.$cont);
                $button_pen->class = 'btn btn-default btn-sm';
                $button_pen->setAction( $action_pen, '' );
                $button_pen->setImage('fa:chain-broken blue fa-lg');
                $button_pen->popover = 'true';
                $button_pen->popside = 'bottom';
                $button_pen->poptitle = 'Verifica Pendências';
                $button_pen->popcontent = 'Abre validação de ausências.<br>' . 
                                          'Faltas não justificadas não podem realizar a 2ª Chamada.';

                $button_imp = new TButton('imprime_detail'.$cont);
                $button_imp->class = 'btn btn-default btn-sm';
                $button_imp->setAction( $action_imp, '' );
                $button_imp->setImage('fa:print blue fa-lg');
                $button_imp->setTip('Imprime o resultado atual da prova');
                $button_imp->popover = 'true';
                $button_imp->popside = 'bottom';
                $button_imp->poptitle = 'Imprime prova.';
                $button_imp->popcontent = 'Imprime o resultado atual da prova. <br>' .
                                          'Se ela ainda não foi finalizada, será impresso seu atual estado';
 
                if (!$fer->is_dev())
                {
                    $button_edi->disableField('form_avaliacao_turma','edit_detail'.$cont);
                }
                //Destiva o botão delete se já tiver passado da fase Aguardando Aplicação
                if ($list_item['status'] != 'AG' ) 
                {
                    $button_del->disableField('form_avaliacao_turma','delete_detail'.$cont);
                }
                //Desativa a verificação de pendências se a fase não for de Pendências
                if ($list_item['oculto'] == 'S' && $list_item['status'] == 'PE')// &&
                    //$list_item['tipo_prova'] != 'RC') 
                {
                    $button_pen->enableField('form_avaliacao_turma','pendencia_detail'.$cont);
                } 
                else
                {
                    $button_pen->disableField('form_avaliacao_turma','pendencia_detail'.$cont);
                }
                //Desativa Aplicar prova se a fase não for Aguardando
                if ($list_item['status'] != 'AG')
                {
                    $button_ava->disableField('form_avaliacao_turma','avancar_detail'.$cont);
                }

                $item->edit      = $button_edi;
                $item->delete    = $button_del;
                $item->pendencia = $button_pen;
                $item->avancar   = $button_ava;
                $item->imprime   = $button_imp;
                
                $this->formFields[ $item_name.'_edit' ] = $item->edit;
                $this->formFields[ $item_name.'_delete' ] = $item->delete;
                $this->formFields[ $item_name.'_pendencia' ] = $item->pendencia;
                $this->formFields[ $item_name.'_avancar' ] = $item->avancar;
                $this->formFields[ $item_name.'_imprime' ] = $item->imprime;
                
                // items
                $item->id                = $list_item['id'];
                $item->dt_aplicacao      = $list_item['dt_aplicacao'];
                $item->tipo_prova        = $fer->lista_tipo_prova($list_item['tipo_prova']);
                $item->usuario_liberador = $list_item['usuario_liberador'];
                $item->data_liberacao    = $list_item['data_liberacao'];
                
                $div = new TElement('span');
                $div->class = 'label label-' . ($list_item['oculto'] == 'S' ? 'success' : 'danger');
                $div->add($fer->lista_sim_nao($list_item['oculto']));
                
                $item->oculto            = $div;
                $item->status            = $fer->lista_status_aplicacao_prova($list_item['status']);
                

                
                $row = $this->detail_list->addItem( $item );
                $row->onmouseover='';
                $row->onmouseout='';
            }

            $this->form->setFields( $this->formFields );
        }
        
        $this->loaded = TRUE;
        $obj = new StdClass;
        $obj->materia_id = $this->getDisciplinas(TSession::getValue(__CLASS__.'_materia_id'));
        TForm::sendData('form_avaliacao_turma',$obj);
    }
    
    /**
     * Load Master/Detail data from database to form/session
     */
    public function onEdit($param)
    {
        $fer = new TFerramentas();
        try
        {
            TTransaction::open('sisacad');
            
            if (isset($param['key']))
            {
                $key = $param['key'];
                
                $object = new avaliacao_turma($key);
                
                $tipo_avaliacao = $object->avaliacao_curso->tipo_avaliacao;

                if ($this->nivel_sistema <= 80)
                {
                    TScript::create("$('[oid=frame-measures]').slideToggle(); 
                     $(this).toggleClass('active')"); 
                }
                
                $items  = avaliacao_prova::where('avaliacao_turma_id', '=', $key)->
                          orderBy('dt_aplicacao','ASC')->load();
                
                $session_items = array();
                foreach( $items as $item )
                {
                    $item_key = $item->id;
                    $session_items[$item_key]                      = $item->toArray();
                    $session_items[$item_key]['id']                = $item->id;
                    $session_items[$item_key]['dt_aplicacao']      = TDate::date2br($item->dt_aplicacao);
                    $session_items[$item_key]['tipo_prova']        = $item->tipo_prova;
                    $session_items[$item_key]['usuario_liberador'] = $item->usuario_liberador;
                    $session_items[$item_key]['data_liberacao']    = $item->data_liberacao;
                    $session_items[$item_key]['oculto']            = $item->oculto;
                    $session_items[$item_key]['status']            = $item->status;
                }
                TSession::setValue(__CLASS__.'_items', $session_items);
                
                $this->form->setData($object); // fill the form with the active record data
                //var_dump($object);
                TSession::setValue(__CLASS__.'_materia_id', $object->materia_id);
                $this->onReload( $param ); // reload items list
                if (!empty($tipo_avaliacao))
                {
                    $ob = new stdClass;
                    $ob->tipo_avaliacao = $fer->lista_verificacoes($tipo_avaliacao);
                    $this->form->sendData('form_avaliacao_turma',$ob);
                }
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
            $master = new avaliacao_turma;
            $master->fromArray( (array) $data);
            $materia = TSession::getValue(__CLASS__.'_materia_id');
            if (isset($materia))
            {
                $master->materia_id = $materia;
            }
            $this->form->validate(); // form validation
            // delete details
            $old_items = avaliacao_prova::where('avaliacao_turma_id', '=', $master->id)->load();
            
            $keep_items = array();
            
            // get session items
            $items = TSession::getValue(__CLASS__.'_items');
            
            if( $items )
            {
                foreach( $items as $item )
                {
                    if (substr($item['id'],0,1) == 'X' ) // new record
                    {
                        $detail = new avaliacao_prova;
                    }
                    else
                    {
                        $detail = avaliacao_prova::find($item['id']);
                    }
                    $data_l = (empty($item['data_liberacao'])) ? date('Y-m-d') : $item['data_liberacao'];
                    $user_l = (empty($item['usuario_liberador'])) ? TSession::getValue('login') : $item['usuario_liberador'];
                    $status = (empty($item['status'])) ? 'AG' : $item['status'];
                    $oculto = (empty($item['oculto'])) ? 'N' : $item['oculto'];
                    $detail->dt_aplicacao       = $item['dt_aplicacao'];
                    $detail->tipo_prova         = $item['tipo_prova'];
                    $detail->usuario_liberador  = $user_l;
                    $detail->data_liberacao     = $data_l;
                    $detail->avaliacao_turma_id = (int) $master->id;
                    $detail->oculto             = $oculto;
                    $detail->status             = $status;

                    //var_dump($detail);
                    $detail->store();
                    $keep_items[]               = $detail->id;
                }
            }
            
            if ($old_items)
            {
                foreach ($old_items as $old_item)
                {
                    if (!in_array( $old_item->id, $keep_items))
                    {
                        //Verificar se já existe prova lançada
                        //$alunos->getavaliacao_alunos();
                        if (empty($alunos))
                        {
                            $old_item->delete();
                        }
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
 *    Monta combo box de Disciplinas
 *------------------------------------------------------------------------------*/
    public static function getDisciplinas($key = null,$disciplina = null)
    {
        //TEntry::reload('form_avaliacao_turma','materia_id',$this->getDisciplinas(TSession::getValue(__CLASS__.'_curso_id')));
        //$lista = array(0=>' --- Sem Disciplinas ---');
        $lista = ' --- Sem Disciplinas ---';
        try
        {
            TTransaction::open('sisacad');
            
            $materia = new materia($key);
            $lista = $materia->disciplina->nome;
            
            /*if (!empty($key))
            {
                $materias = materias_previstas::where('curso_id','=',$key)->load();
            }
            else if (!empty($disciplina))
            {
                $materias = materias_previstas::where('id','=',$disciplina)->load();
            }
            else
            {
                $materias = false;
            }
            //var_dump($materias);
            if ($materias)
            {
                $lista = array();
                foreach ($materias as $materia)
                {
                    if ($materia->disciplina->id == $disciplina || $disciplina == null)
                    {
                        //$disciplina = $materia->get_disciplina();
                        $lista[$materia->id] = $materia->disciplina->nome;
                    }
                }
            }*/
            TTransaction::close();

        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        //var_dump($lista);
        return $lista;

    }//Fim Módulo
/*------------------------------------------------------------------------------
 *    Checa se há ausentes
 *  @$param['key'] = id de avaliacao_prova para busca em avaliacao_aluno
 *  @Irá retornar verdadeiro se houver ausentes naquela prova.
 *------------------------------------------------------------------------------*/
    public static function get_ausentes($param = null)
    {
        $key = (is_array($param) && array_key_exists('key',$param)) ? $param['key'] : $param; 
        $fer = new TFerramentas();
        $retorno = false;
        try
        {
            TTransaction::open('sisacad');
            $prova = avaliacao_aluno::where('avaliacao_prova_id','=',$key)->
                                      where('status','!=','P')->load(); 
            if (count($prova)>0)
            {
                $retorno = true;
            }
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        return $retorno;
    }//Fim módulo
/*------------------------------------------------------------------------------
 *    Checa se há médias para recuperar
 *  @$param['key'] = id em avaliacao_turma
 *------------------------------------------------------------------------------*/
    public static function get_mediaMenor($param = null)
    {
        $key = (is_array($param) && array_key_exists('key',$param)) ? $param['key'] : $param; 
        $fer = new TFerramentas();
        $retorno = false;
        try
        {
            TTransaction::open('sisacad');
            $sql = "(SELECT id FROM sisacad.avaliacao_prova WHERE avaliacao_turma_id = " . $key . ")";
            $prova = avaliacao_aluno::where('avaliacao_prova_id','IN',$sql)->
                                      where('nota','<',5)->load(); 
            if (count($prova)>0)
            {
                $retorno = true;
            }
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        return $retorno;
    }//Fim módulo
    
/*-------------------------------------------------------------------------------
 *   Funçao retorna array com tipos de provas
 *  $param['key']  id da prova
 *------------------------------------------------------------------------------- */
    public function get_tipo_prova($param=null)
    {
        $ret = array('1C'=>'1ª CHAMADA');
        if ($param == null)
        {
            return $ret;
        }
        $key = (is_array($param) && array_key_exists('key',$param)) ? $param['key'] : $param;
        try
        {
            TTransaction::open('sisacad');
            $prova = avaliacao_prova($key);
            TTransaction::close();
            if ($prova)
            {
                if ($this->get_ausentes(array('key'=>$prova->id)))
                {
                    $ret['2C'] = '2ª CHAMADA';
                }
                if ($this->get_mediaMenor(array('key'=>$prova->avaliacao_turma_id)))
                {
                    $ret['RC'] = 'RECUPERAÇÃO';
                }
            }

        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        return $ret;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *    Abre a primeira chamada
 *  @$param['key'] = id em avaliacao_turma
 *------------------------------------------------------------------------------*/
    public function onPrimeira($param = null)
    {
        $key = (is_array($param) && array_key_exists('id',$param)) ? $param['id'] : $param; 
        $fer = new TFerramentas();
        try
        {
            TTransaction::open('sisacad');
            $sql = "(SELECT id FROM sisacad.avaliacao_prova WHERE avaliacao_turma_id = " . $key ." AND ". 
                   "tipo_prova = '1C')";
            $result = avaliacao_prova::where ('id','IN',$sql)->count();
            if ($result != 0)
            {
                throw new Exception('Já existe uma prova de 1ª Chamada criada.');
            }
            
            $result = new avaliacao_prova();
            $result->avaliacao_turma_id = $key;
            $result->tipo_prova = '1C';
            $result->status = 'AG';
            $result->store();
            new TMessage('info','Prova criada! Aguardando liberação para aplicar.');
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        $this->onEdit(array('key'=>$key));
    }//Fim módulo
/*------------------------------------------------------------------------------
 *    Aplica a Prova
 *  @$param['key'] = id em avaliacao_turma
 *------------------------------------------------------------------------------*/
    public static function onAplicaProva($param = null)
    {
        $key = (is_array($param) && array_key_exists('item_key',$param)) ? $param['item_key'] : $param; 
        $fer = new TFerramentas();

        $id        = new THidden('id');
        $item_key  = new THidden('item_key');
        $dt_aplica = new TDate('dt_aplica');
        
        $id->setValue($param['id']);
        $item_key->setValue($param['item_key']);
        $dt_aplica->setValue(date('Y-m-d'));
        
        //$dt_aplica->setMask('dd-mm-yyyy');
        
        $dt_aplica->setSize(120);
        
        $form = new TForm('input_form');
        $form->style = 'padding:20px';
        
        $table = new TTable;
        $table->addRowSet( '', $id );
        $table->addRowSet( '', $item_key );
        $table->addRowSet( $lbl = new TLabel('Data da Aplicação: '), $dt_aplica );
        $lbl->setFontColor('red');
        
        $form->setFields(array($id, $item_key, $dt_aplica));
        $form->add($table);
        // show the input dialog
        $action = new TAction(array('avaliacao_turmaForm', 'AplicaProva'));
        $action->setParameter('stay-open', 1);
        new TInputDialog('Define data de Apliação de prova.', $form, $action, 'Confirma');
    }//Fim módulo
/*------------------------------------------------------------------------------
 *    Aplica a Prova
 *  @$param['key'] = id em avaliacao_turma
 *------------------------------------------------------------------------------*/
    public static function AplicaProva($param = null)
    {
        $key = (is_array($param) && array_key_exists('item_key',$param)) ? $param['item_key'] : $param; 
        $fer = new TFerramentas();
        try
        {
            TTransaction::open('sisacad');
            $result = new avaliacao_prova($key);
            if (!$result)
            {
                throw new Exception('Houve um erro, não achei a prova.');
            }
            if ($result->status == 'AG')
            {
                $result->status            = 'AP';
                $result->dt_aplicacao      = $param['dt_aplica'];
                $result->usuario_liberador = TSession::getValue('login');
                $result->store();
                $action = new TAction(array('avaliacao_turmaForm', 'onEdit'));
                $action->setParameter('key', $param['id']);
                new TMessage('info','Prova liberada para aplicação.',$action);
            }
            else
            {
                throw new Exception ('Prova já liberada para aplicação.');
            }
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }//Fim módulo
    
/*------------------------------------------------------------------------------
 *    Encaminha para visualização dos Resultados, depois de fechado a avaliação
 *------------------------------------------------------------------------------*/
    public function onResultado($param = null)
    {
        $data = $this->form->getData();
        try
        {
            TTransaction::open('sisacad');
            $key = $data->id;
            $objects = avaliacao_resultado::where('avaliacao_turma_id','=',$key)->load();
            if (!empty($objects));
            {
                foreach($objects as $object)
                {
                    $avaliacao_turma_id = $object->id;
                }
            }
            if (!empty($avaliacao_turma_id))
            {
                TApplication::gotoPage('avaliacao_resultadoForm','onEdit',array('key'=>$avaliacao_turma_id));
            }
            else
            {
                throw new Exception('Não há nenhum resultado para essa Avaliação.<br>'.
                                    'Aguarde a conclusão do processo como um todo.');
            }
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
 *  Imprime a Prova
 *------------------------------------------------------------------------------*/
    public function onImprime ($param = null)
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
}//Fim Classe
