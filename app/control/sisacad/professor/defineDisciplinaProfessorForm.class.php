<?php
/**
 * defineDisciplinaProfessorForm Master/Detail
 * @author  <your name here>
 */
class defineDisciplinaProfessorForm extends TPage
{
    protected $form; // form
    protected $table_details;
    protected $detail_row;
    protected $professor_lista;
    
/*------------------------------------------------------------------------------
 * Variáveis de configuração
 *------------------------------------------------------------------------------*/
    protected $nivel_sistema   = false;  //Registra o nível de acesso do usuário
    public    $config          = array();//Array com configuração
    protected $config_load     = false;  //Informa que a configuração está carregada

    var $sistema  = 'SISACAD';           //Nome do sistema que irá configurar(filtro)
    var $servico  = 'Disciplinas';            //Nome da página de serviço.
    
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
        $this->form = new TForm('form_disciplina');
        $this->form->class = 'tform'; // CSS class

        $table_master = new TTable;
        $table_master->width = '100%';
        
        $table_master->addRowSet( new TLabel('Define disciplinas de Interesse para o corpo de Professores - Cadastro/Edição'), '', '')->class = 'tformtitle';
        
        // add a table inside form
        $table_general = new TTable;
        $table_general->width = '100%';
        
        $frame_general = new TFrame;
        $frame_general->class = 'tframe tframe-custom';
        $frame_general->setLegend('Dados da Disciplina');
        $frame_general->style = 'background:whiteSmoke';
        $frame_general->add($table_general);
        
        $frame_details = new TFrame;
        $frame_details->class = 'tframe tframe-custom';
        $frame_details->setLegend('Professores que Ministram aula nesta disciplina');
        
        $table_master->addRow()->addCell( $frame_general )->colspan=2;
        $row = $table_master->addRow();
        $row->addCell( $frame_details );
        
        $this->form->add($table_master);
        
        // master fields
        $id      = new TEntry('id');
        $nome    = new TEntry('nome');
        $sigla   = new TEntry('sigla');
        $oculto  = new TCombo('oculto');

        // sizes
        $id->setSize('80');
        $nome->setSize('400');
        $sigla->setSize('200');
        $oculto->setSize('100');
        
        //Valores
        $oculto->addItems($fer->lista_sim_nao());
        
        $oculto->setValue('N');

        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }
        //Se não é Gestor acima desativa
        if ($this->nivel_sistema<=80)
        {
            $nome->setEditable(FALSE);
            $sigla->setEditable(FALSE);
            $oculto->setEditable(FALSE);
        }
                
        // add form fields to be handled by form
        $this->form->addField($id);
        $this->form->addField($nome);
        $this->form->addField($sigla);
        $this->form->addField($oculto);
        
        // add form fields to the screen
        $table_general->addRowSet( new TLabel('Id'), $id );
        $table_general->addRowSet( new TLabel('Disciplina'), $nome );
        $table_general->addRowSet( new TLabel('Sigla'), $sigla );
        $table_general->addRowSet( new TLabel('Fora de Uso?'), $oculto );
        
        // creates the scroll panel
        $scroll = new TScroll;
        $scroll->setSize('100%',180);

        // detail
        $this->table_details = new TTable;
        $this->table_details-> width = '100%';
        $scroll->add($this->table_details);
        $frame_details->add($scroll);
        
        $this->table_details->addSection('thead');
        $row = $this->table_details->addRow();
        
        // detail header
        $row->addCell( new TLabel('Opções') );
        $row->addCell( new TLabel('Professor da Disciplina') );
        
        // create an action button (save)
        $save_button=new TButton('save');
        $save_button->setAction(new TAction(array($this, 'onSave')), _t('Save'));
        $save_button->setImage('ico_save.png');

        // create an new button (edit with no parameters)
        $new_button=new TButton('new');
        $new_button->setAction(new TAction(array($this, 'onClear')), _t('New'));
        $new_button->setImage('ico_new.png');

        // create an return button
        $local = TSession::getValue('defineDisciplinaProfessorForm_return');
        if (!empty($local) && $local == 'config')
        {
            $ret_button=new TButton('return');
            $ret_button->setAction(new TAction(array('disciplinaList', 'onReload')), 'Retorna a Configuração');
            $ret_button->setImage('ico_back.png');
        }
        else
        {
            $ret_button=new TButton('return');
            $ret_button->setAction(new TAction(array('defineDisciplinaProfessorList', 'onReload')), _t('Back to the listing'));
            $ret_button->setImage('ico_back.png');
        }
         

        // define form fields
        $this->form->addField($save_button);
        if ($this->nivel_sistema>80)
        {
            $this->form->addField($new_button);
        }
        $this->form->addField($ret_button);
        
        if ($this->nivel_sistema>80)
        {
            $table_master->addRowSet( array($save_button, $new_button,$ret_button), '', '')->class = 'tformaction'; // CSS class
        }
        else
        {
            $table_master->addRowSet( array($save_button, $ret_button), '', '')->class = 'tformaction'; // CSS class
        }
        
        $this->detail_row = 0;
        
        // create the page container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'defineDisciplinaProfessorList'));
        $container->add($this->form);
        parent::add($container);
    }
    
    /**
     * Executed whenever the user clicks at the edit button da datagrid
     */
    public function onEdit($param)
    {
        if (array_key_exists('return',$param))
        {
            TSession::setValue(get_class($this).'return','config');
        }
        else
        {
            TSession::setValue(get_class($this).'return','normal');
        }
        $fer   = new TFerramentas();
        $sicad = new TSicadDados();
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
            $this->config        = $fer->getConfig($this->sistema);         //Busca o Nível de acesso que o usuário tem para a Classe
            $this->config_load   = true;                               //Informa que configuração foi carregada
        }
        try
        {
            TTransaction::open('sisacad');
            
            if (isset($param['key']))
            {
                $key = $param['key'];
                
                $object = new disciplina($key);
                $this->form->setData($object);
                
                //$items  = professordisciplina::where('disciplina_id', '=', $key)->load();
                if ($this->nivel_sistema>80)
                {
                    $items  = professordisciplina::where('disciplina_id', '=', $key)->load();
                }
                else
                {
                    $items  = professordisciplina::where('disciplina_id', '=', $key)->
                                         where('opm_id','IN',$this->listas['lista'])->load();
                }
                
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
        $professor_id = new TCombo('professor_id[]');
        $opm_id = new THidden('opm_id[]');

        // set id's
        $professor_id->setId('professor_id_'.$uniqid);
        $opm_id->setId('opm_id_'.$uniqid);

        // set sizes
        $professor_id->setSize('400');
        
        //Valores
        $professor_id->addItems($this->getProfessores());
        
        // set row counter
        $professor_id->{'data-row'} = $this->detail_row;
        $opm_id->{'data-row'} = $this->detail_row;

        // set value
        if (!empty($item->professor_id)) { $professor_id->setValue( $item->professor_id ); }
        if (!empty($item->opm_id)) { $opm_id->setValue( $item->opm_id ); }
        
        // create delete button
        $del = new TImage('fa:trash-o red');
        $del->onclick = 'ttable_remove_row(this)';
        $row = $this->table_details->addRow();
        
        // add cells
        $row->addCell( $del );
        $row->addCell($professor_id);
        $row->addCell($opm_id);
        
        $row->{'data-row'} = $this->detail_row;

        // add form field
        $this->form->addField($professor_id);
        
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
     * Save the disciplina and the professordisciplina's
     */
    public static function onSave($param)
    {
        $fer   = new TFerramentas();
        $sicad = new TSicadDados();
        $profile = TSession::getValue('profile');           //Profile da Conta do usuário
        $opm_operador = ($fer->is_dev()==true) ? 140 : $profile['unidade']['id'];
        $nivel_sistema = $fer->getnivel ('defineDisciplinaProfessorForm');//Verifica qual nível de acesso do usuário
        $saida = TSession::getValue('dados_saida');
        $lista = TSession::getValue('dados_lista');
        try
        {
            TTransaction::open('sisacad');
            
            $id = (int) $param['id'];
            $master = new disciplina;
            $master->fromArray( $param);
            $master->nome  = mb_strtoupper($master->nome,'UTF-8');
            $master->sigla = mb_strtoupper($master->sigla,'UTF-8');
            $master->store(); // save master object
            
            // delete details
            if ($nivel_sistema>80)
            {
                professordisciplina::where('disciplina_id', '=', $master->id)->delete();
            }
            else
            {
                professordisciplina::where('disciplina_id', '=', $master->id)->
                                     where('opm_id','IN',$lista)->delete();
            }
            
            if( !empty($param['professor_id']) AND is_array($param['professor_id']) )
            {
                foreach( $param['professor_id'] as $row => $professor_id)
                {
                    if (!empty($professor_id))
                    {
                        $detail = new professordisciplina;
                        $detail->disciplina_id = $master->id;
                        $detail->professor_id  = $param['professor_id'][$row];
                        $detail->opm_id        = $opm_operador;
                        $detail->store();
                    }
                }
            }
            
            $data = new stdClass;
            $data->id = $master->id;
            TForm::sendData('form_disciplina', $data);
            TTransaction::close(); // close the transaction
            
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'));
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }//Fiim Módulo
/*------------------------------------------------------------------------------
 *  Carrega os professores
 *------------------------------------------------------------------------------*/    
    public function getProfessores($param = null)
    {
        
        if (!empty($this->professor_lista))
        {
            return $this->professor_lista;
        }
        $lista = array(0=>'--- Sem Professores Cadastrados ---');
        try
        {
            TTransaction::open('sisacad');
            $criteria = new TCriteria;
            $criteria->add( new TFilter( 'id', '!=', 0 ));
            $param['order'] = 'nome';
            $param['direction'] = 'asc';
            $criteria->setProperties($param); // order, offset

            $professores  = professor::getObjects($criteria);
            
            if( !empty($professores) )
            {
                $lista = array();
                foreach( $professores as $professor)
                {
                    if (!empty($professor))
                    { 
                        $posto = (!empty($professor->postograd_id)) ? $professor->postograd->sigla : '';
                        $rg    = (!empty($professor->rg)) ? ' RG ' . $professor->rg   : '';
                        $orgao = (!empty($professor->orgaosorigem_id)) ? ' - ' . $professor->orgaosorigem->sigla : '';
                        $lista[$professor->id] = $professor->nome . ' - ' . $posto . $rg . $orgao; 
                    }
                }
            }
            TTransaction::close(); // close the transaction
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        $this->professor_lista = $lista;
        return $lista;

    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Verifica se o professor pode ser editado pelo usuário
 *------------------------------------------------------------------------------*/
    public function onCheckEdit ($param)
    {
         $result = false;
         if (!empty($param))
         {
            $key = $param['key'];
            if ($this->nivel_sistema>80)
            {
                $result = true;
            }
            else
            {
                try
                {
                    // open a transaction with database
                    TTransaction::open('sisacad');
                    $loc = new professor($key);
                    if (!empty($loc))
                    {
                        if (in_array($loc->opm_id,$this->listas['lista']))
                        {
                            $result = true;
                        }
                    }   
                    TTransaction::close(); // close the transaction
                }
                catch (Exception $e) // in case of exception
                {
                    TTransaction::rollback(); // undo all pending operations
                }
            }
         }
         return $result;
    }//Fim do Módulo
}
