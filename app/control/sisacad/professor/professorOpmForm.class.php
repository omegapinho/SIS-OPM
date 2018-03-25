<?php
/**
 * professorOpmForm Master/Detail
 * @author  <your name here>
 */
class professorOpmForm extends TPage
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
    var $servico  = 'Professor';            //Nome da página de serviço.
    
    private $opm_operador    = false;    // Unidade do Usuário
    private $listas          = false;    // Lista de valores e array de OPM
    protected $chamado = false;          //Controle de correção de chamado
   
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
        $this->form = new TForm('form_professor');
        $this->form->class = 'tform'; // CSS class
        
        $table_master = new TTable;
        $table_master->width = '100%';
        
        $table_master->addRowSet( new TLabel('Gestão de Professores em OPMs'), '', '')->class = 'tformtitle';
        
        // add a table inside form
        $table_general = new TTable;
        $table_general->width = '100%';
        
        $frame_general = new TFrame;
        $frame_general->class = 'tframe tframe-custom';
        $frame_general->setLegend('Dados Básicos do Professor');
        $frame_general->style = 'background:whiteSmoke';
        $frame_general->add($table_general);
        
        $frame_details = new TFrame;
        $frame_details->class = 'tframe tframe-custom';
        $frame_details->setLegend('OPM de vínculo Auxiliar');
        
        $table_master->addRow()->addCell( $frame_general )->colspan=2;
        $row = $table_master->addRow();
        $row->addCell( $frame_details );
        
        $this->form->add($table_master);
        
        // master fields
        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $criteria = new TCriteria;
        $criteria->add (new TFilter ('oculto','!=','S'));
        $orgaosorigem_id     = new TDBCombo('orgaosorigem_id','sisacad','orgaosorigem','id','nome','nome',$criteria);
        
        $postograd_id        = new TDBCombo('postograd_id','sicad','postograd','id','nome','nome');
        $opm_id = new TDBCombo('opm_id','sisacad','OPM','id','nome','nome');

        // sizes
        $id->setSize('100');
        $nome->setSize('400');
        $orgaosorigem_id->setSize('400');
        $postograd_id->setSize('300');
        $opm_id->setSize('400');

        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }
        $nome->setEditable(FALSE);
        $postograd_id->setEditable(FALSE);
        $opm_id->setEditable(FALSE);
        $orgaosorigem_id->setEditable(FALSE);
        
        // add form fields to be handled by form
        $this->form->addField($id);
        $this->form->addField($nome);
        $this->form->addField($orgaosorigem_id);
        $this->form->addField($postograd_id);
        $this->form->addField($opm_id);
        
        // add form fields to the screen
        $table_general->addRowSet( new TLabel('Id'), $id );
        $table_general->addRowSet( new TLabel('Professor'), $nome );
        $table_general->addRowSet( new TLabel('Órgão de Origem'), $orgaosorigem_id );
        $table_general->addRowSet( new TLabel('Cargo'), $postograd_id );
        $table_general->addRowSet( new TLabel('OPM de Vínculo'), $opm_id );
        
        // detail
        $this->table_details = new TTable;
        $this->table_details-> width = '100%';
        $frame_details->add($this->table_details);
        
        $this->table_details->addSection('thead');
        $row = $this->table_details->addRow();
        
        // detail header
        $row->addCell( new TLabel('') );
        $row->addCell( new TLabel('OPM Auxiliar') );
        $row->addCell( new TLabel('OBS') );
        
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
        $return_button->setAction(new TAction(array('professorList', 'onReload')), _t('Back to the listing'));
        $return_button->setImage('ico_back.png');
        
        // define form fields
        $this->form->addField($save_button);
        $this->form->addField($return_button);
        //$this->form->addField($new_button);
        
        $table_master->addRowSet( array($save_button,$return_button), '', '')->class = 'tformaction'; // CSS class
        
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
                
                $object = new professor($key);
                $this->form->setData($object);
                
                $items  = professor_opm::where('professor_id', '=', $key)->load();
                
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
        $opm_id = new TDBCombo('opm_id[]','sisacad','OPM','id','nome','nome');
        $obs = new TText('obs[]');

        // set id's
        $opm_id->setId('opm_id_'.$uniqid);
        $obs->setId('obs_'.$uniqid);

        // set sizes
        $opm_id->setSize('300');
        $obs->setSize('200','40');
        
        // set row counter
        $opm_id->{'data-row'} = $this->detail_row;
        $obs->{'data-row'} = $this->detail_row;

        // set value
        if (!empty($item->opm_id)) { $opm_id->setValue( $item->opm_id ); }
        if (!empty($item->obs)) { $obs->setValue( $item->obs ); }
        
        // create delete button
        $del = new TImage('fa:trash-o red');
        $del->onclick = 'ttable_remove_row(this)';
        
        $row = $this->table_details->addRow();
        // add cells
        $row->addCell( $del );
        $row->addCell($opm_id);
        $row->addCell($obs);

        $row->{'data-row'} = $this->detail_row;
        
        // add form field
        $this->form->addField($opm_id);
        $this->form->addField($obs);
        
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
     * Save the professor and the professor_opm's
     */
    public static function onSave($param)
    {
        try
        {
            TTransaction::open('sisacad');
            
            $id = (int) $param['id'];
            $master = new professor;
            $master->fromArray( $param);
            $master->store(); // save master object
            
            // delete details
            professor_opm::where('professor_id', '=', $master->id)->delete();
            
            if( !empty($param['opm_id']) AND is_array($param['opm_id']) )
            {
                foreach( $param['opm_id'] as $row => $opm_id)
                {
                    if (!empty($opm_id))
                    {
                        $detail = new professor_opm;
                        $detail->professor_id = $master->id;
                        $detail->opm_id = $param['opm_id'][$row];
                        $detail->obs = $param['obs'][$row];
                        $detail->store();
                    }
                }
            }
            
            $data = new stdClass;
            $data->id = $master->id;
            TForm::sendData('form_professor', $data);
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
