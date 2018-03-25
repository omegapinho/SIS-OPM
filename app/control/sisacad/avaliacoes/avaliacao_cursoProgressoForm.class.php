<?php
/**
 * avaliacao_cursoProgressoForm Master/Detail
 * @author  <your name here>
 */
class avaliacao_cursoProgressoForm extends TPage
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
        //$error = new LogErrorSystem('production');    
        parent::__construct();

        $fer   = new TFerramentas();                        // Ferramentas diversas
        $sicad = new TSicadDados();                         // Ferramentas de acesso ao SICAD
        //Realiza definições iniciais de acesso
        $profile = TSession::getValue('profile');           //Profile da Conta do usuário
        if ($this->opm_operador == false)                     //Carrega OPM do usuário
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
        
        $curso_militar     = TSession::getValue('curso_militar');
         if (empty($curso_militar))
         {
              TSession::setValue('curso_militar',null);
              TApplication::loadPage('cursoList','onReload');
              //var_dump($data);
         }
        $this->form = new TForm('form_avaliacao_curso');
        $this->form->class = 'tform'; // CSS class
        $this->form->style = 'display: table;width:100%'; // style
        parent::include_css('app/resources/custom-frame.css');
        
        $table_master = new TTable;
        $table_master->width = '100%';
        
        $table_master->addRowSet( new TLabel('Avaliação de Curso - Verificação de Progresso'), '', '')->class = 'tformtitle';
        
        // add a table inside form
        $table_general = new TTable;
        $table_detail  = new TTable;
        $table_general-> width = '100%';
        $table_detail-> width  = '100%';
        
        $frame_general = new TFrame;
        $frame_general->setLegend('Dados do Curso/Disciplina em Avaliação');
        $frame_general->style = 'background:whiteSmoke';
        $frame_general->add($table_general);
        
        $table_master->addRow()->addCell( $frame_general )->colspan=2;
        $row = $table_master->addRow();
        $row->addCell( $table_detail );
        
        $this->form->add($table_master);
        
        // master fields
        $id                    = new TEntry('id');
        $criteria              = new TCriteria();
        $criteria->add( new TFilter('id','=',$curso_militar->id));
        
        $curso_id              = new TDBCombo('curso_id','sisacad','curso','id','nome','nome',$criteria);
        $materias_previstas_id = new TEntry('materias_previstas_id');
        $dt_inicio             = new TDate('dt_inicio');
        $tipo_avaliacao        = new TCombo('tipo_avaliacao');
        $ch_minima             = new TEntry('ch_minima');
        $media_minima          = new TEntry('media_minima');
        $oculto                = new TCombo('oculto');
        
        if (!empty($id))
        {
            $id->setEditable(FALSE);
            $curso_id->setEditable(FALSE);
            $materias_previstas_id->setEditable(FALSE);
            $dt_inicio->setEditable(FALSE);
            $tipo_avaliacao->setEditable(FALSE);
            $ch_minima->setEditable(FALSE);
            $media_minima->setEditable(FALSE);
            $oculto->setEditable(FALSE);
        }
        //Valores
        $oculto->addItems($fer->lista_sim_nao());
        $tipo_avaliacao->addItems($fer->lista_verificacoes());
        
        //Tamanho dos campos
        $id->setSize(50);
        $curso_id->setSize(300);
        $materias_previstas_id->setSize(250);
        $tipo_avaliacao->setSize(200);
        $dt_inicio->setSize(120);
        $ch_minima->setSize(80);
        $media_minima->setSize(80);
        $oculto->setSize(80);
        
        //Mascaras
        $ch_minima->setMask('999');
        $dt_inicio->setMask('dd/mm/yyyy');
        
        // detail fields
        $detail_id         = new THidden('detail_id');
        $detail_turma_id   = new TEntry('detail_turma_id');
        $detail_materia_id = new TCombo('detail_materia_id');
        $detail_oculto     = new TCombo('detail_oculto');

        /** samples
         $this->form->addQuickFields('Date', array($date1, new TLabel('to'), $date2)); // side by side fields
         $fieldX->addValidation( 'Field X', new TRequiredValidator ); // add validation
         $fieldX->setSize( 100, 40 ); // set size
         **/
        
        // master
        $table_general->addRowSet( array( new TLabel('Id'), $id , new TLabel('Curso'), $curso_id, 
                                          new TLabel('Disciplina'), $materias_previstas_id) );
        $table_general->addRowSet( array(new TLabel('Tipo de Avaliação'), $tipo_avaliacao, 
                                         new TLabel('Data de Início'), $dt_inicio) );
        $table_general->addRowSet( array(new TLabel('CH Mínima'), $ch_minima, new TLabel('Média Minima'), $media_minima, 
                                         new TLabel('Avaliação Encerrada?'), $oculto ));
        
         // detail
        /*$frame_details = new TFrame();
        $frame_details->setLegend('Dados da Turma/Matéria');
        $row = $table_detail->addRow();
        $row->addCell($frame_details);
        
        $btn_save_detail = new TButton('btn_save_detail');
        $btn_save_detail->setAction(new TAction(array($this, 'onSaveDetail')), 'Register');
        $btn_save_detail->setImage('fa:save');
        
        $table_details = new TTable;
        $frame_details->add($table_details);
        
        $table_details->addRowSet( '', $detail_id );
        $table_details->addRowSet( new TLabel('Turma'), $detail_turma_id );
        $table_details->addRowSet( new TLabel('Matéria'), $detail_materia_id );
        $table_details->addRowSet( new TLabel('Finalizado?'), $detail_oculto );
        
        $table_details->addRowSet( $btn_save_detail );*/
        
        $this->detail_list = new TQuickGrid;
        $this->detail_list->setHeight( 175 );
        $this->detail_list->makeScrollable();
        $this->detail_list->disableDefaultClick();
        //$this->detail_list->addQuickColumn('', 'edit', 'left', 40);
        //$this->detail_list->addQuickColumn('', 'delete', 'left', 40);
        
        // items
        $this->detail_list->addQuickColumn('Turma', 'turma_id', 'left', 200);
        //$this->detail_list->addQuickColumn('Matéria', 'materia_id', 'left', 250);
        $this->detail_list->addQuickColumn('Finalizado?', 'oculto', 'left', 50);
        $this->detail_list->addQuickColumn('1ª Chamada', 'primeira', 'left', 160);
        $this->detail_list->addQuickColumn('2ª Chamada', 'segunda', 'left', 160);
        $this->detail_list->addQuickColumn('Recuperação', 'recuperacao', 'left', 160);
        $this->detail_list->addQuickColumn('Resultado', 'resultado', 'left', 160);
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

        // create an return button (retorna)
        $ret_button=new TButton('return');
        $ret_button->setAction(new TAction(array($this, 'onReturn')), 'Retorna');
        $ret_button->setImage('ico_back.png');
                
        // define form fields
        $this->formFields   = array($id,$curso_id,$materias_previstas_id,$dt_inicio,$tipo_avaliacao,$ch_minima,
                                    $media_minima,$oculto) ;//,$detail_turma_id,$detail_materia_id,$detail_oculto);
        //$this->formFields[] = $btn_save_detail;
        //$this->formFields[] = $save_button;
        //$this->formFields[] = $new_button;
        //$this->formFields[] = $detail_id;
        $this->formFields[] = $ret_button;
        $this->form->setFields( $this->formFields );
        
        //$table_master->addRowSet( array($save_button, $new_button), '', '')->class = 'tformaction'; // CSS class
        $table_master->addRowSet( array($ret_button), '', '')->class = 'tformaction'; // CSS class
        
        // create the page container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'cursoList'));
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
            $items[ $key ]['turma_id'] = $data->detail_turma_id;
            $items[ $key ]['materia_id'] = $data->detail_materia_id;
            $items[ $key ]['oculto'] = $data->detail_oculto;
            
            TSession::setValue(__CLASS__.'_items', $items);
            
            // clear detail form fields
            $data->detail_id = '';
            $data->detail_turma_id = '';
            $data->detail_materia_id = '';
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
        $data->detail_turma_id = $item['turma_id'];
        $data->detail_materia_id = $item['materia_id'];
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
            $data->detail_turma_id = '';
            $data->detail_materia_id = '';
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
                $item->delete = $button_del;
                
                $this->formFields[ $item_name.'_edit' ] = $item->edit;
                $this->formFields[ $item_name.'_delete' ] = $item->delete;
                
                // items
                $item->id          = $list_item['id'];
                $item->turma_id    = $list_item['turma_id'];
                $item->materia_id  = $list_item['materia_id'];
                $item->oculto      = $list_item['oculto'];
                $item->primeira    = (isset($list_item['primeira'])) ? $list_item['primeira'] :'';
                $item->segunda     = (isset($list_item['segunda'])) ? $list_item['segunda'] :'';
                $item->recuperacao = (isset($list_item['recuperacao'])) ? $list_item['recuperacao'] :'';
                $item->resultado   = (isset($list_item['resultado'])) ? $list_item['resultado'] :'';
                
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
        $fer = new TFerramentas();
        try
        {
            TTransaction::open('sisacad');
            
            if (isset($param['key']))
            {
                $key = $param['key'];
                
                $object = new avaliacao_curso($key);
                $object->materias_previstas_id = $object->materias_previstas->disciplina->nome;
                $items  = avaliacao_turma::where('avaliacao_curso_id', '=', $key)->load();
                
                
                $session_items = array();
                foreach( $items as $item )
                {
                    $item_key                               = $item->id;
                    $session_items[$item_key]               = $item->toArray();
                    $session_items[$item_key]['id']         = $item->id;
                    $session_items[$item_key]['turma_id']   = $item->turma->nome;
                    $session_items[$item_key]['materia_id'] = $item->materia->disciplina->nome;
                    $session_items[$item_key]['oculto']     = $fer->lista_sim_nao($item->oculto);
                    
                    //Pegas as provas executadas pela turma
                    $lista = array('1C'=>false,'2C'=>false,'RC'=>false);
                    $provas = $item->getavaliacao_provas();
                    foreach ($provas as $prova)
                    {
                        if (isset($prova->id))
                        {
                            $lista[$prova->tipo_prova] = $prova;
                        }
                    }
                    $primeira    = new TTextDisplay('Não aplicada','black',7,'b');
                    $primeira->class='btn btn-info';
                    
                    $segunda     = new TTextDisplay('Não aplicada','black',7,'b');
                    $segunda->class='btn btn-info';
                    
                    $recuperacao = new TTextDisplay('Não aplicada','black',7,'b');
                    $recuperacao->class='btn btn-info';
                    
                    $resultado   = new TTextDisplay('Não Finalizado','black',7,'b');
                    $resultado->class='btn btn-info';
                    
                    //Avaliação finalizada, listar resultado
                    if ($session_items[$item_key]['oculto'] == 'SIM')
                    {
                        $action = new TAction( array($this, 'onResultado' ) );
                        $action->setParameter('key', $item->id);
                        $resultado = 'Finalizado';
                        $resultado = new TActionLink($resultado,$action,'black',7,'','ico_find.png');
                        $resultado->class='btn btn-success';
                    }
                    foreach($lista as $key => $l)
                    {
                        if ($l !== false)
                        {
                            $action = new TAction( array($this, 'onImprime' ) );
                            $action->setParameter('key', $l->id);
                        }
                        switch ($key)
                        {
                            case '1C':
                                if ($l !== false)
                                {
                                    $primeira  = 'Aplicada';
                                    $status    = $fer->lista_status_aplicacao_prova($l->status);
                                    $primeira .= (!empty($status)) ? '-' . $status : '';  
                                    $primeira = new TActionLink($primeira,$action,'black',7,'','ico_find.png');
                                    if ($l->status == 'CO')
                                    {
                                        $primeira->class='btn btn-success';
                                    }
                                    else
                                    {
                                        $primeira->class='btn btn-warning';
                                    }
                                }
                                break;
                            case '2C':
                                if ($l !== false)
                                {
                                    $segunda  = 'Aplicada';
                                    $status   = $fer->lista_status_aplicacao_prova($l->status);
                                    $segunda .= (!empty($status)) ? '-' . $status : '';
                                    $segunda = new TActionLink($segunda,$action,'black',7,'','ico_find.png');
                                    if ($l->status == 'CO')
                                    {
                                        $segunda->class='btn btn-success';
                                    }
                                    else
                                    {
                                        $segunda->class='btn btn-warning';
                                    }
                                }
                                else
                                {
                                    if ($lista['1C'] !== false && $lista['1C']->status == 'CO')
                                    {
                                        $segunda = new TTextDisplay('Não Houve','black',7,'b');
                                        $segunda->class='btn btn-success';
                                    }
                                    else
                                    {
                                        $segunda = new TTextDisplay('Aguardando 1ªChamada','black',7,'b');
                                        $segunda->class='btn btn-warning';
                                    }
                                }
                                break;
                            case 'RC':
                                if ($l !== false)
                                {
                                    $recuperacao  = 'Aplicada';
                                    $status       = $fer->lista_status_aplicacao_prova($l->status);
                                    $recuperacao .= (!empty($status)) ? '-' . $status : '';
                                    $recuperacao = new TActionLink($recuperacao,$action,'black',7,'','ico_find.png');
                                    if ($l->status == 'CO')
                                    {
                                        $recuperacao->class='btn btn-success';
                                    }
                                    else
                                    {
                                        $recuperacao->class='btn btn-warning';
                                    }
                                }
                                else
                                {
                                    if ($lista['1C'] !== false && $lista['1C']->status == 'CO')
                                    {
                                        $recuperacao = new TTextDisplay('Não Houve','black',7,'b');
                                        $recuperacao->class='btn btn-success';
                                    }
                                    else
                                    {
                                        $recuperacao = new TTextDisplay('Aguardando 1ªChamada','black',7,'b');
                                        $recuperacao->class='btn btn-warning';
                                    }
                                }
                                break;
                        }
                    }
                    
                    $session_items[$item_key]['primeira']         = '<center>' . $primeira .'</center>';
                    $session_items[$item_key]['segunda']          = '<center>' . $segunda .'</center>';
                    $session_items[$item_key]['recuperacao']      = '<center>' . $recuperacao .'</center>';
                    $session_items[$item_key]['resultado']        = '<center>' . $resultado .'</center>';
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
            $master = new avaliacao_curso;
            $master->fromArray( (array) $data);
            $this->form->validate(); // form validation
            
            $master->store(); // save master object
            // delete details
            $old_items = avaliacao_turma::where('avaliacao_curso_id', '=', $master->id)->load();
            
            $keep_items = array();
            
            // get session items
            $items = TSession::getValue(__CLASS__.'_items');
            
            if( $items )
            {
                foreach( $items as $item )
                {
                    if (substr($item['id'],0,1) == 'X' ) // new record
                    {
                        $detail = new avaliacao_turma;
                    }
                    else
                    {
                        $detail = avaliacao_turma::find($item['id']);
                    }
                    $detail->turma_id  = $item['turma_id'];
                    $detail->materia_id  = $item['materia_id'];
                    $detail->oculto  = $item['oculto'];
                    $detail->avaliacao_curso_id = $master->id;
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
    public function onImprime ($param = null)
    {
        $data = $this->form->getData();
        $fer = new TFerramentas();
        $sis = new TSisacad();
        try
        {
            TTransaction::open('sisacad');
            $prova  = new avaliacao_prova($param['key']);
            $id_retorno = $prova->avaliacao_turma->avaliacao_curso_id;
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
                    $window = TWindow::create('Resultado de Avaliação', 1000, 500);
                    $window->add($tabela);
                    $window->show();
                }
            }
            
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        $this->onEdit(array('key'=>$id_retorno, 'id'=>$id_retorno));
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Imprime a Prova de Resultado
 *------------------------------------------------------------------------------*/
    public function onResultado ($param = null)
    {
        $data = $this->form->getData();
        $fer = new TFerramentas();
        try
        {
            TTransaction::open('sisacad');
            $provas     = avaliacao_resultado::where('avaliacao_turma_id','=',$param['key'])->load();
            //Se há uma prova de resultado, pegue a primeira
            if (!empty($provas))
            {
                $prova = $provas[0];
            }
            else
            {
                throw new Exception ('Não achei a prova de resultado');
            }
            $id_retorno = $prova->avaliacao_turma->avaliacao_curso_id;
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
        $this->onEdit(array('key'=>$id_retorno, 'id'=>$id_retorno));
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Retorna a edição da turma
 *------------------------------------------------------------------------------*/
    public function onReturn ($param)
    {
         //$data = $this->form->getData();
         TApplication::loadPage('avaliacao_cursoList','onReload');
         //$this->form->setData($data);
    }//Fim Módulo
}//Fim Classe
