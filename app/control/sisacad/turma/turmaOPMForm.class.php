<?php
/**
 * turmaOPMForm Master/Detail
 * @author  <your name here>
 */
class turmaOPMForm extends TPage
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
        $this->form = new TForm('form_OPM');
        $this->form->class = 'tform'; // CSS class
        
        $table_master = new TTable;
        $table_master->width = '100%';
        
        $table_master->addRowSet( new TLabel('OPM'), '', '')->class = 'tformtitle';
        
        // add a table inside form
        $table_general = new TTable;
        $table_general->width = '100%';
        
        $frame_general = new TFrame;
        $frame_general->class = 'tframe tframe-custom';
        $frame_general->setLegend('OPM');
        $frame_general->style = 'background:whiteSmoke';
        $frame_general->add($table_general);
        
        $frame_details = new TFrame;
        $frame_details->class = 'tframe tframe-custom';
        $frame_details->setLegend('Dados das Turmas que a OPM irá Gerenciar');
        
        $table_master->addRow()->addCell( $frame_general )->colspan=2;
        $row = $table_master->addRow();
        $row->addCell( $frame_details );
        
        $this->form->add($table_master);
        
        // master fields
        $id = new THidden('id');
        $superior = new TEntry('superior');
        $nome = new TEntry('nome');

        // sizes
        $superior->setSize('200');
        $nome->setSize('400');

        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }
        $superior->setEditable(false);
        $nome->setEditable(false);
        
        // add form fields to be handled by form
        $this->form->addField($id);
        $this->form->addField($superior);
        $this->form->addField($nome);
        
        // add form fields to the screen
        $table_general->addRowSet( new TLabel('Unidade Superior'), $superior );
        $table_general->addRowSet( new TLabel('OPM'), $nome );
        $table_general->addRowSet( new TLabel(''), $id );
        
        // detail
        $this->table_details = new TTable;
        $this->table_details-> width = '100%';
        $frame_details->add($this->table_details);
        
        $this->table_details->addSection('thead');
        $row = $this->table_details->addRow();
        
        // detail header
        $row->addCell( new TLabel('Opções') );
        $row->addCell( new TLabel('Turma') );
        $row->addCell( new TLabel('Encerrado?') );
        
        // create an action button (save)
        $save_button=new TButton('save');
        $save_button->setAction(new TAction(array($this, 'onSave')), _t('Save'));
        $save_button->setImage('ico_save.png');

        // create an new button (edit with no parameters)
        $new_button=new TButton('new');
        $new_button->setAction(new TAction(array($this, 'onClear')), _t('New'));
        $new_button->setImage('ico_new.png');
        
        // create an action button (go to list)
        $return_button=new TButton('back');
        $return_button->setAction(new TAction(array('turmaOPMList', 'onReload')), _t('Back to the listing'));
        $return_button->setImage('ico_back.png');
        
        // define form fields
        $this->form->addField($save_button);
        $this->form->addField($new_button);
        $this->form->addField($return_button);
        
        $table_master->addRowSet( array($save_button, $new_button,$return_button), '', '')->class = 'tformaction'; // CSS class
        
        $this->detail_row = 0;
        
        // create the page container
        $container = new TVBox;
        $container->style = 'width: 100%';
        //$container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
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
                
                $object = new OPM($key);
                $this->form->setData($object);
                
                $items  = turmaopm::where('opm_id', '=', $key)->load();
                
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
        
        $fer = new TFerramentas();
        // create fields
        $criteria = new TCriteria;
        $criteria->add( new TFilter('oculto','!=','S'));
        $turma_id = new TDBCombo('turma_id[]','sisacad','turma','id','nome','nome',$criteria);
        $oculto = new TCombo('oculto[]');

        //Valores
        $oculto->addItems($fer->lista_sim_nao());
        $oculto->setValue('N');
        
        // set id's
        $turma_id->setId('turma_id_'.$uniqid);
        $oculto->setId('oculto_'.$uniqid);

        // set sizes
        $turma_id->setSize('400');
        $oculto->setSize('120');
        
        // set row counter
        $turma_id->{'data-row'} = $this->detail_row;
        $oculto->{'data-row'} = $this->detail_row;

        // set value
        if (!empty($item->turma_id)) { $turma_id->setValue( $item->turma_id ); }
        if (!empty($item->oculto)) { $oculto->setValue( $item->oculto ); }
        
        // create delete button
        $del = new TImage('fa:trash-o red');
        $del->onclick = 'ttable_remove_row(this)';
        
        $row = $this->table_details->addRow();
        // add cells
        $row->addCell( $del );
        $row->addCell($turma_id);
        $row->addCell($oculto);
        
        $row->{'data-row'} = $this->detail_row;
        
        // add form field
        $this->form->addField($turma_id);
        $this->form->addField($oculto);
        
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
     * Save the OPM and the turmaopm's
     */
    public static function onSave($param)
    {
        try
        {
            TTransaction::open('sisacad');
            
            $id = (int) $param['id'];
            $master = new OPM;
            $master->fromArray( $param);
            $master->store(); // save master object
            
            // delete details
            turmaopm::where('opm_id', '=', $master->id)->delete();
            
            if( !empty($param['turma_id']) AND is_array($param['turma_id']) )
            {
                foreach( $param['turma_id'] as $row => $turma_id)
                {
                    if (!empty($turma_id))
                    {
                        $detail = new turmaopm;
                        $detail->opm_id = $master->id;
                        $detail->turma_id = $param['turma_id'][$row];
                        $detail->oculto = $param['oculto'][$row];
                        $detail->store();
                    }
                }
            }
            
            $data = new stdClass;
            $data->id = $master->id;
            TForm::sendData('form_OPM', $data);
            TTransaction::close(); // close the transaction
            
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'));
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
