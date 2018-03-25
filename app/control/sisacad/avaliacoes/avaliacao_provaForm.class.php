<?php
/**
 * avaliacao_provaForm Master/Detail
 * @author  <your name here>
 */
class avaliacao_provaForm extends TPage
{
    protected $form; // form
    protected $table_details;
    protected $detail_row;
    
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
        $this->form = new TForm('form_avaliacao_prova');
        $this->form->class = 'tform'; // CSS class
        
        $table_master = new TTable;
        $table_master->width = '100%';
        
        $table_master->addRowSet( new TLabel('Gestor de Provas - Lançamento de Notas'), '', '')->class = 'tformtitle';
        
        // add a table inside form
        $table_general = new TTable;
        $table_general->width = '100%';
        
        $frame_general = new TFrame;
        $frame_general->class = 'tframe tframe-custom';
        $frame_general->setLegend('Dados da Prova/Verificação');
        $frame_general->style = 'background:whiteSmoke';
        $frame_general->add($table_general);
        
        $frame_details = new TFrame;
        $frame_details->class = 'tframe tframe-custom';
        $frame_details->setLegend('Alunos da Turma para lançamento das notas');
        
        $scroll = new TScroll();
        $scroll->setSize('100%',200);
        
        $table_master->addRow()->addCell( $frame_general )->colspan=2;
        $row = $table_master->addRow();
        $row->addCell( $frame_details );
        
        $this->form->add($table_master);
        
        // master fields
        $id              = new TEntry('id');
        $dt_aplicacao    = new TDate('dt_aplicacao');
        $tipo_prova      = new TCombo('tipo_prova');
        $tipo_avaliacao = new TEntry('tipo_avaliacao');
        $turma_materia   = new TEntry('turma_materia'); 

        // sizes
        $id->setSize('50');
        $dt_aplicacao->setSize('100');
        $tipo_prova->setSize('150');
        $turma_materia->setSize('400');
        $tipo_avaliacao->setSize('200');

        //Mascara
        $dt_aplicacao->setMask('dd/mm/yyyy');
        
        //Valores
        $tipo_prova->addItems($fer->lista_tipo_prova());
        
        if (!empty($id))
        {
            $id->setEditable(FALSE);
            $dt_aplicacao->setEditable(FALSE);
            $tipo_prova->setEditable(FALSE);
            $turma_materia->setEditable(FALSE);
            $tipo_avaliacao->setEditable(FALSE);
        }
        
        // add form fields to be handled by form
        $this->form->addField($id);
        $this->form->addField($dt_aplicacao);
        $this->form->addField($tipo_prova);
        $this->form->addField($turma_materia);
        
        // add form fields to the screen
        $table_general->addRowSet( array(new TLabel('Id'), $id, new TLabel('Matéria/Turma'),$turma_materia));
        $table_general->addRowSet( array(new TLabel('Tipo de Avaliação'), $tipo_avaliacao, new TLabel('Tipo de Prova'), 
                                   $tipo_prova,new TLabel('Data da Aplicação'), $dt_aplicacao
                                    ));
        
        // detail
        $this->table_details = new TTable;
        $this->table_details-> width = '100%';
        $scroll->add($this->table_details);
        $frame_details->add($scroll);
        
        $this->table_details->addSection('thead');
        $row = $this->table_details->addRow();
        
        // detail header
        $row->addCell( new TLabel('') );
        $row->addCell( new TLabel('Aluno') );
        $row->addCell( new TLabel('Nota') );
        $row->addCell( new TLabel('Status') );
        $row->addCell( new TLabel('') );
        $row->addCell( new TLabel('') );
        
        // create an action button (save)
        $save_button=new TButton('save');
        $save_button->setAction(new TAction(array($this, 'onSave')), _t('Save'));
        $save_button->setImage('ico_save.png');

        //Encerra o trabalho de lançamento de notas
        $end_button=new TButton('concluir');
        $end_button->setAction(new TAction(array($this, 'onConclui')), 'Conclui Lançamento');
        $end_button->setImage('fa:circle green');
        
        // create an new button (edit with no parameters)
        /*$new_button=new TButton('new');
        $new_button->setAction(new TAction(array($this, 'onClear')), _t('New'));
        $new_button->setImage('ico_new.png');*/
        
        //Retorna para Listagem
        $ret_button=new TButton('return');
        $ret_button->setAction(new TAction(array('avaliacao_provaList', 'onReload')), _t('Back to the listing'));
        $ret_button->setImage('ico_back.png');
        
        // define form fields
        $this->form->addField($save_button);
        $this->form->addField($ret_button);
        $this->form->addField($end_button);
        //$this->form->addField($new_button);
        
        $table_master->addRowSet( array($save_button,$end_button,$ret_button), '', '')->class = 'tformaction'; // CSS class
        
        $this->detail_row = 0;
        
        // create the page container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'avaliacao_provaList'));
        $container->add($this->form);
        parent::add($container);
    }
    
    /**
     * Executed whenever the user clicks at the edit button da datagrid
     */
    function onEdit($param)
    {
        $fer = new TFerramentas();
        $sis = new TSisacad();
        try
        {
            TTransaction::open('sisacad');
            
            if (isset($param['key']))
            {
                $key = $param['key'];
                
                $object = new avaliacao_prova($key);
                
                $tipo_avaliacao = $object->avaliacao_turma->avaliacao_curso->tipo_avaliacao;
                
                $object->dt_aplicacao = TDate::date2br($object->dt_aplicacao);
                
                $turma   = $object->avaliacao_turma->turma->sigla;
                $curso   = $object->avaliacao_turma->turma->curso->sigla;
                $materia = $object->avaliacao_turma->materia->disciplina->nome;
                
                $object->turma_materia = $materia . ' - ' . $turma . ' (' . $curso . ')';
                
                //echo $tipo_avaliacao;//var_dump($object);
                
                $this->form->setData($object);
                if (!empty($tipo_avaliacao))
                {
                    $ob = new stdClass;
                    $ob->tipo_avaliacao = $fer->lista_verificacoes($tipo_avaliacao);
                    $this->form->sendData('form_avaliacao_prova',$ob);
                }
                
                
                $items  = avaliacao_aluno::where('avaliacao_prova_id', '=', $key)->orderBy('id')->load();
                
                $this->table_details->addSection('tbody');
                if ($items)
                {
                    foreach($items  as $item )
                    {
                        //Busca dados do Aluno para preencher campo
                        $item->aluno_id = $item->aluno->getIdentificacao();
                        $this->addDetailRow($item);
                    }
                    
                    // create add button
                    /*$add = new TButton('clone');
                    $add->setLabel('Add');
                    $add->setImage('fa:plus-circle green');
                    $add->addFunction('ttable_clone_previous_row(this)');
                    
                    // add buttons in table
                    $this->table_details->addRowSet([$add]);*/
                }
                else
                {
                    $this->onClear($param);
                }
                
                TTransaction::close(); // close transaction
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    /**
     * Add detail row
     */
    public function addDetailRow($item)
    {
        $fer = new TFerramentas();
        $uniqid = mt_rand(1000000, 9999999);
        
        // create fields
        $detail_id        = new THidden('detail_id[]');
        $aluno_id         = new TEntry('aluno_id[]');
        $nota             = new TEntry('nota[]');
        $status           = new TCombo('status[]');
        $usuario_lancador = new THidden('usuario_lancador[]');
        $data_lancamento  = new THidden('data_lancamento[]');

        // set id's
        $detail_id->setId('detail_id_'.$uniqid);
        $aluno_id->setId('aluno_id_'.$uniqid);
        $nota->setId('nota_'.$uniqid);
        $status->setId('status_'.$uniqid);
        $usuario_lancador->setId('usuario_lancador_'.$uniqid);
        $data_lancamento->setId('data_lancamento_'.$uniqid);

        // set sizes
        $detail_id->setSize('60');
        $aluno_id->setSize('500');
        $nota->setSize('60');
        $status->setSize('220');
        //$usuario_lancador->setSize('120');
        //$data_lancamento->setSize('120');
        
        //Bloqueios
        $aluno_id->setEditable(FALSE);
        $detail_id->setEditable(FALSE);
        
        //valores
        $status->addItems(array('P'=>'PRESENTE PARA PROVA',
                     'A'=>'AUSENTE'
                     ));//addItems($fer->lista_status_prova());
        
        //Mascara
        $nota->setMask('99.99');
        
        // set row counter
        $detail_id->{'data-row'}        = $this->detail_row;
        $aluno_id->{'data-row'}         = $this->detail_row;
        $nota->{'data-row'}             = $this->detail_row;
        $status->{'data-row'}           = $this->detail_row;
        $usuario_lancador->{'data-row'} = $this->detail_row;
        $data_lancamento->{'data-row'}  = $this->detail_row;

        // set value
        if (!empty($item->id)) { $detail_id->setValue( $item->id ); }
        if (!empty($item->aluno_id)) { $aluno_id->setValue( $item->aluno_id ); }
        if (!empty($item->nota)) { $nota->setValue( number_format($item->nota, 2, '.', '') ); }
        if (!empty($item->status)) { $status->setValue( $item->status ); }
        if (!empty($item->usuario_lancador)) { $usuario_lancador->setValue( $item->usuario_lancador ); }
        if (!empty($item->data_lancamento)) { $data_lancamento->setValue( $item->data_lancamento ); }
        
        // create delete button
        $del = new TImage('fa:trash-o red');
        $del->onclick = 'ttable_remove_row(this)';
        
        $row = $this->table_details->addRow();
        // add cells
        $row->addCell($detail_id);
        $row->addCell($aluno_id);
        $row->addCell($nota);
        $row->addCell($status);
        $row->addCell($usuario_lancador);
        $row->addCell($data_lancamento);
        
        //$row->addCell( $del );
        $row->{'data-row'} = $this->detail_row;
        
        // add form field
        $this->form->addField($detail_id);
        $this->form->addField($aluno_id);
        $this->form->addField($nota);
        $this->form->addField($status);
        $this->form->addField($usuario_lancador);
        $this->form->addField($data_lancamento);
        
        $this->detail_row ++;
    }
    
    /**
     * Clear form
     */
    public function onClear($param)
    {
        $this->table_details->addSection('tbody');
        $this->addDetailRow( new stdClass );
        
        // create add button
        $add = new TButton('clone');
        $add->setLabel('Add');
        $add->setImage('fa:plus-circle green');
        $add->addFunction('ttable_clone_previous_row(this)');
        
        // add buttons in table
        $this->table_details->addRowSet(array($add));
    }
    
    /**
     * Save the avaliacao_prova and the avaliacao_aluno's
     */
    public static function onSave($param, $conclui = 'N')
    {
        try
        {
            TTransaction::open('sisacad');
            
            $id = (int) $param['id'];
            $master = new avaliacao_prova($id);
            //$concluso = $master->oculto;//Para verificar se já não foi con
            //$master->fromArray( $param);
            if ($conclui == 'S')
            {
                $master->status = 'PE';
                $master->oculto = 'S';
                $master->store(); // save master object
            }
            //
            
            // delete details
            //avaliacao_aluno::where('avaliacao_prova_id', '=', $master->id)->delete();
            
            if( !empty($param['aluno_id']) AND is_array($param['aluno_id']) )
            {
                foreach( $param['aluno_id'] as $row => $aluno_id)
                {
                    if (!empty($aluno_id))
                    {
                        $detail = new avaliacao_aluno($param['detail_id'][$row]);
                        //$detail->avaliacao_prova_id = $master->id;
                        //$detail->aluno_id = $param['aluno_id'][$row];
                        $detail->nota             = (empty($param['nota'][$row]))   ?  0  : $param['nota'][$row];
                        $detail->status           = (empty($param['status'][$row])) ? 'A' : $param['status'][$row];
                        $detail->usuario_lancador = TSession::getValue('login');//$param['usuario_lancador'][$row];
                        $detail->data_lancamento  = date('Y-m-d');//$param['data_lancamento'][$row];
                        /*if ($master->tipo_prova == '2C' && $concluso != 'S')
                        {
                            $detail->nota = $detail->nota * $detail->fator_moderador;
                        }*/
                        //Se está ausente, automáticamente a nota é zero
                        if ($detail->status == 'A')
                        {
                            $detail->nota = 0;
                        }
                        $detail->store();
                    }
                }
            }
            
            if ($conclui == 'N')
            {
                $data = new stdClass;
                $data->id = $master->id;
                TForm::sendData('form_avaliacao_prova', $data);
            }
            $msg    = '';
            $action = null;
            if ($conclui == 'N')
            {
                $msg = TAdiantiCoreTranslator::translate('Record saved');
            }
            else
            {
                $action = new TAction(array('avaliacao_provaForm','onEdit'),array('key'=>$master->id));
                $msg = 'Lançamento de Notas Concluso.<br>Clique em OK para recarregar.';
            }

            TTransaction::close(); // close the transaction
            new TMessage('info',$msg,$action);

        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Questiona antes da conclusão
 *------------------------------------------------------------------------------*/
    public static function onConclui($param)
    {
        // define the delete action
        //var_dump($param);
        //$data = $this->form->getData();
        $action = new TAction(array('avaliacao_provaForm', 'Conclui'));
        //$action->setParameters($param); // pass the key parameter ahead
        TSession::setValue('avaliacao_provaForm_Conclui', $param);
        
        // shows a dialog to the user
        new TQuestion('Finaliza o lançamento de Nota?<br>' .
                      'O professor ao finalizar passa a incumbência de avaliação da aplicação para Seção de Ensino '.
                      'que verificará as ausências e a nessidade de aplicação de ' .
                      'prova de 2ª Chamada bem como recuperação, se pertinente.', $action);
    }
/*------------------------------------------------------------------------------
 *   Conclusão
 *------------------------------------------------------------------------------*/
    public static function Conclui($param = null)
    {
        $param = TSession::getValue('avaliacao_provaForm_Conclui');
        self::onSave($param, 'S');
    }
}
