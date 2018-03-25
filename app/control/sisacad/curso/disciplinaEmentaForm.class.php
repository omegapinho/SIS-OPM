<?php
/**
 * cursoForm Master/Detail
 * @author  <your name here>
 */
class disciplinaEmentaForm extends TPage
{
    protected $form; // form
    protected $table_details;
    protected $detail_row;
    
/*------------------------------------------------------------------------------
 * Variáveis de configuração
 *------------------------------------------------------------------------------*/
    protected $nivel_sistema   = false;  //Registra o nível de acesso do usuário
    public    $config          = array();//Array com configuração
    protected $config_load     = false;  //Informa que a configuração está carregada

    var $sistema  = 'SISACAD';           //Nome do sistema que irá configurar(filtro)
    var $servico  = 'Turmas';            //Nome da página de serviço.
    
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
        $this->form = new TForm('form_curso');
        $this->form->class = 'tform'; // CSS class
        
        $table_master = new TTable;
        $table_master->width = '100%';
        
        $table_master->addRowSet( new TLabel('Ementa de Disciplinas do Curso'), '', '')->class = 'tformtitle';
        
        // add a table inside form
        $table_general = new TTable;
        $table_general->width = '100%';
        
        $frame_general = new TFrame;
        $frame_general->class = 'tframe tframe-custom';
        $frame_general->setLegend('Dados Curso');
        $frame_general->style = 'background:whiteSmoke';
        $frame_general->add($table_general);
        
        $frame_details = new TFrame;
        $frame_details->class = 'tframe tframe-custom';
        $frame_details->setLegend('Rol de Disciplinas Previstas');
        
        $scroll = new TScroll();
        $scroll->setSize('100%',200);
        
        $table_master->addRow()->addCell( $frame_general )->colspan=2;
        $row = $table_master->addRow();
        $row->addCell( $frame_details );
        
        $this->form->add($table_master);
        
        $fer = new TFerramentas();
        $curso = TSession::getValue('curso_militar');
        
        // master fields
        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $sigla = new TEntry('sigla');
        $data_inicio = new TDate('data_inicio');
        $ementa_ok = new THidden('ementa_ok');

        // sizes
        $id->setSize('80');
        $nome->setSize('400');
        $sigla->setSize('200');
        $data_inicio->setSize('120');
        $ementa_ok->setSize('200');
        
        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }
        if (!empty($curso))
        {
            $id->setValue($curso->id);
            $nome->setValue($curso->nome);
            $nome->setEditable(false);
            $sigla->setValue($curso->sigla);
            $sigla->setEditable(false);
            $data_inicio->setValue($curso->data_inicio);
            $data_inicio->setEditable(false);
        }

        //Mascara
        $data_inicio->setMask('dd-mm-yyyy');
        
        // add form fields to be handled by form
        $this->form->addField($id);
        $this->form->addField($nome);
        $this->form->addField($sigla);
        $this->form->addField($data_inicio);
        $this->form->addField($ementa_ok);
        
        // add form fields to the screen
        $table_general->addRowSet( array(new TLabel('Id'), $id,  new TLabel('Nome'), $nome ));
        $table_general->addRowSet( array(new TLabel('Sigla'), $sigla , new TLabel('Data de Início'), $data_inicio ));
        $table_general->addRowSet($ementa_ok );
        
        // detail
        $this->table_details = new TTable;
        $this->table_details-> width = '100%';
        $scroll->add($this->table_details);
        $frame_details->add($scroll);
        
        $this->table_details->addSection('thead');
        $row = $this->table_details->addRow();
        
        // detail header
        $row->addCell( new TLabel('Opções') );
        $row->addCell( new TLabel('Disciplina') );        
        $row->addCell( new TLabel('Carga Horária') );

        
        // create an action button (save)
        $save_button=new TButton('save');
        $save_button->setAction(new TAction(array($this, 'onSave')), _t('Save'));
        $save_button->setImage('ico_save.png');

        // create an new button (edit with no parameters)
        $new_button=new TButton('new');
        $new_button->setAction(new TAction(array($this, 'onClear')), _t('New'));
        $new_button->setImage('ico_new.png');
        
        // create an new button (edit with no parameters)
        $ret_button=new TButton('return');
        $ret_button->setAction(new TAction(array($this, 'onReturn')), 'Retorna para Curso');
        $ret_button->setImage('ico_back.png');
        
        // create an valida Ementa button (edit with no parameters)
        $ok_button=new TButton('ementa_validada');
        $ok_button->setAction(new TAction(array($this, 'onValida')), 'Valida Ementa');
        $ok_button->setImage('ico_apply.png');
        
        // create an Clona Ementa button (edit with no parameters)
        $clone_button=new TButton('clone_validada');
        $clone_button->setAction(new TAction(array($this, 'onCopiaEmenta')), 'Copia uma Ementa');
        $clone_button->setImage('fa:clone black');

        // define form fields
        $this->form->addField($save_button);
        $this->form->addField($ret_button);
        $this->form->addField($ok_button);
        $this->form->addField($clone_button);
        
        $table_master->addRowSet( array($save_button,$clone_button,$ok_button, $ret_button), '', '')->class = 'tformaction'; // CSS class
        
        $this->detail_row = 0;
        
        // create the page container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'cursoList'));
        $container->add($this->form);
        parent::add($container);
    }
    
    /**
     * Executed whenever the user clicks at the edit button da datagrid
     */
    function onEdit($param)
    {
        try
        {
            TTransaction::open('sisacad');
            
            if (isset($param['key']))
            {
                $key = $param['key'];
                
                $object = new curso($key);
                $object->data_inicio = TDate::date2br($object->data_inicio);
                $object->data_final  = TDate::date2br($object->data_final);
                $this->form->setData($object);
                
                $items  = materias_previstas::where('curso_id', '=', $key)->load();
                
                $this->table_details->addSection('tbody');
                if ($items)
                {
                    foreach($items  as $item )
                    {
                        $this->addDetailRow($item);
                    }
                    
                    // create add button
                    $add = new TButton('clone');
                    $add->setLabel('Add');
                    $add->setImage('fa:plus-circle green');
                    $add->addFunction('ttable_clone_previous_row(this)');
                    
                    // add buttons in table
                    $this->table_details->addRowSet([$add]);
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
        $uniqid = mt_rand(1000000, 9999999);
        
        // create fields
        $carga_horaria = new TEntry('carga_horaria[]');
        $criteria = new TCriteria();
        $criteria->add(new TFilter('oculto','!=','S'));
        $disciplina_id = new TDBCombo('disciplina_id[]','sisacad','disciplina','id','nome','nome',$criteria);

        // set id's
        $carga_horaria->setId('carga_horaria_'.$uniqid);
        $disciplina_id->setId('disciplina_id_'.$uniqid);

        // set sizes
        $carga_horaria->setSize('120');
        $disciplina_id->setSize('400');
        
        // set row counter
        $carga_horaria->{'data-row'} = $this->detail_row;
        $disciplina_id->{'data-row'} = $this->detail_row;

        // set value
        if (!empty($item->carga_horaria)) { $carga_horaria->setValue( $item->carga_horaria ); }
        if (!empty($item->disciplina_id)) { $disciplina_id->setValue( $item->disciplina_id ); }
        
        // create delete button
        $del = new TImage('fa:trash-o red');
        $del->onclick = 'ttable_remove_row(this)';
        
        $row = $this->table_details->addRow();
        // add cells
        $row->addCell( $del );
        $row->addCell($disciplina_id);
        $row->addCell($carga_horaria);

        $row->{'data-row'} = $this->detail_row;
        
        // add form field
        $this->form->addField($carga_horaria);
        $this->form->addField($disciplina_id);
        
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
        $this->table_details->addRowSet([$add]);
    }
    
    /**
     * Save the curso and the materias_previstas's
     */
    public static function onSave($param)
    {
        try
        {
            TTransaction::open('sisacad');
            
            $id = (int) $param['id'];
            $master = new curso;
            $master->fromArray( $param);
            
            $master->data_inicio = TDate::date2us($master->data_inicio);
            $master->data_final  = TDate::date2us($master->data_final);
            
            $master->store(); // save master object
            
            // delete details
            materias_previstas::where('curso_id', '=', $master->id)->delete();
            
            if( !empty($param['carga_horaria']) AND is_array($param['carga_horaria']) )
            {
                foreach( $param['carga_horaria'] as $row => $carga_horaria)
                {
                    if (!empty($carga_horaria))
                    {
                        $detail = new materias_previstas;
                        $detail->curso_id = $master->id;
                        $detail->carga_horaria = $param['carga_horaria'][$row];
                        $detail->disciplina_id = $param['disciplina_id'][$row];
                        $detail->store();
                    }
                }
            }
            
            $data = new stdClass;
            $data->id = $master->id;
            TForm::sendData('form_curso', $data);
            TTransaction::close(); // close the transaction
            
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'));
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }//Fim Módulo
    /**
     * Valida Ementa
     */
    public static function onValida($param)
    {
        try
        {
            TTransaction::open('sisacad');
            
            $id = (int) $param['id'];
            $master = new curso;
            $param['ementa_ok'] = 'S';
            $curso = TSession::getValue('curso_militar');
            $curso->ementa_ok = 'S';
            TSession::getValue('curso_militar',$curso);
            $master->fromArray( $param);
            $master->data_inicio = TDate::date2us($master->data_inicio);
            $master->data_final  = TDate::date2us($master->data_final);
            
            $master->store(); // save master object
            
            $data = new stdClass;
            $data->id = $master->id;
            $data->ementa_ok = 'S';
            TForm::sendData('form_curso', $data);
            TTransaction::close(); // close the transaction
            
            new TMessage('info', 'Ementa Validada!<br>'.
                            'Para fins de sistema já é possível incluir turmas.');
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *
 *------------------------------------------------------------------------------*/
     public function onReturn ($param)
     {
         //var_dump($param);
         TApplication::loadPage('cursoForm','onEdit', array('key'=>$param['id']));
     }
/*---------------------------------------------------------------------------------------
 *  Rotina: Copia Ementa
 *---------------------------------------------------------------------------------------*/
    public static function onCopiaEmenta ($param = null)
    {
        //var_dump($param);exit;
        $id     = new THidden('id');
        
        $criteria = new TCriteria;
        $criteria->add(new TFilter('ementa_ok','=','S'));
        $criteria->add(new TFilter('id','!=',$param['id']));
        $copia   = new TDBCombo('copia','sisacad','curso','id','nome','nome',$criteria);
        
        //Valores        
        $id->setValue($param['id']);
      
        //Tamanho
        $id->setSize(50);
        $copia->setSize(250);
        
        //Trava
        $id->setEditable(false);
         
        $form = new TForm('input_form');
        $form->style = 'padding:20px';
        
        $table = new TTable;
        $table->addRowSet( '', $id );
        $table->addRowSet( $lbl = new TLabel('Curso de Origem: '), $copia );
        $lbl->setFontColor('red');
        
        $form->setFields(array($copia,$id));
        $form->add($table);
        
        // show the input dialog
        $action = new TAction(array('disciplinaEmentaForm', 'CopiaEmenta'));
        $action->setParameter('stay-open', 1);
        new TInputDialog('Copia Ementa Validada de Outro Curso', $form, $action, 'Confirma');
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Rotina: Executa a Copia da Ementa
 *---------------------------------------------------------------------------------------*/
    public static function CopiaEmenta ($param)
    {
        if (!empty($param['copia']) && $param['id'] != $param['copia'])
        {
            try
            {
                TTransaction::open('sisacad');
                
                $destino = (int) $param['id'];
                $origem  = (int) $param['copia'];
                materias_previstas::where('curso_id','=',$destino)->delete();
                $disciplinas = materias_previstas::where('curso_id','=',$origem)->load();
                if (!empty($disciplinas))
                {
                    
                    foreach($disciplinas as $disciplina)
                    {
                        if (!empty($disciplina->disciplina_id) && !empty($disciplina->carga_horaria))
                        {
                            $incluir = new materias_previstas();
                            $incluir->disciplina_id = $disciplina->disciplina_id;
                            $incluir->carga_horaria = $disciplina->carga_horaria;
                            $incluir->curso_id = $destino;
                            $incluir->store();
                        }
                    }
                }
    
    
                TTransaction::close(); // close the transaction
                $action = new TAction(array('disciplinaEmentaForm', 'onEdit'));
                $action->setParameter('key', $destino);   
                new TMessage('info', 'Ementa Copiada com sucesso!<br>'.
                                'Para fins de sistema ainda é necessário validá-la.',$action);
            }
            catch (Exception $e) // in case of exception
            {
                new TMessage('error', $e->getMessage());
                TTransaction::rollback();
            }
        }
        else
        {
            new TMessage('error', 'Escolha um curso para prosseguir...');
        }
    }
}
