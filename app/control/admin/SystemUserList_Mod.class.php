<?php
/**
 * SystemUserList Listing
 * @author  <your name here>
 */
class SystemUserList_Mod extends TStandardList
{
    protected $form;     // registration form
    protected $datagrid; // listing
    protected $pageNavigation;
    protected $formgrid;
    protected $deleteButton;
    protected $transformCallback;
    
    var $sistema  = 'Administração de Usuário';  //Nome do sistema que irá configurar(filtro)
    var $servico  = 'Listagem';                  //Nome da página de serviço.
    
    protected $nivel_sistema = false;  //Registra o nível de acesso do usuário
    protected $config        = array();//Array com configuração
    protected $config_load   = false;  //Informa que a configuração está carregada
    
    /**
     * Page constructor
     */
    public function __construct()
    {
        parent::__construct();
        
        parent::setDatabase('permission');            // defines the database
        parent::setActiveRecord('SystemUser');   // defines the active record
        parent::setDefaultOrder('id', 'asc');         // defines the default order
        parent::addFilterField('id', '=', 'id'); // filterField, operator, formField
        parent::addFilterField('name', 'like', 'name'); // filterField, operator, formField
        parent::addFilterField('email', 'like', 'email'); // filterField, operator, formField
        parent::addFilterField('active', '=', 'active'); // filterField, operator, formField
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_search_SystemUser');
        $this->form->setFormTitle("Usuários de Sistema - Listagem");
        
        $fer   = new TFerramentas();                        // Ferramentas diversas
        if (!$this->nivel_sistema || $this->config_load == false)    //Carrega informação sobre o acesso
        {
            $this->nivel_sistema = $fer->getnivel (get_class($this));//Verifica qual nível de acesso do usuário
            $this->config = $fer->getConfig($this->sistema);         //Busca o Nível de acesso que o usuário tem para a Classe
            $this->config_load = true;                               //Informa que configuração foi carregada
        }

        // create the form fields
        $id = new TEntry('id');
        $name = new TEntry('name');
        $email = new TEntry('email');
        $active = new TCombo('active');
        
        $active->addItems( [ 'Y' => _t('Yes'), 'N' => _t('No') ] );
        
        // add the fields
        $this->form->addFields( [new TLabel('Id')], [$id] );
        $this->form->addFields( [new TLabel(_t('Name'))], [$name] );
        $this->form->addFields( [new TLabel(_t('Email'))], [$email] );
        $this->form->addFields( [new TLabel(_t('Active'))], [$active] );
        
        $id->setSize(80);
        $name->setSize(300);
        $email->setSize(200);
        $active->setSize(80);
        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('SystemUser_filter_data') );
        
        // add the search form actions
        $this->form->addAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        //echo $this->nivel_sistema;
        if ($this->nivel_sistema > 80)
        {
            $this->form->addAction(_t('New'),  new TAction(array('SystemUserForm_Mod', 'onEdit')), 'bs:plus-sign green');
        }
        
        // creates a DataGrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->datatable = 'true';
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);
        

        // creates the datagrid columns
        $column_id = new TDataGridColumn('id', 'Id', 'center', 50);
        $column_name = new TDataGridColumn('name', _t('Name'), 'left');
        $column_login = new TDataGridColumn('login', _t('Login'), 'left');
        $column_email = new TDataGridColumn('email', _t('Email'), 'left');
        $column_active = new TDataGridColumn('active', _t('Active'), 'center');
        
        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_name);
        $this->datagrid->addColumn($column_login);
        $this->datagrid->addColumn($column_email);
        $this->datagrid->addColumn($column_active);

        $column_active->setTransformer( function($value, $object, $row) {
            $class = ($value=='N') ? 'danger' : 'success';
            $label = ($value=='N') ? _t('No') : _t('Yes');
            $div = new TElement('span');
            $div->class="label label-{$class}";
            $div->style="text-shadow:none; font-size:12px; font-weight:lighter";
            $div->add($label);
            return $div;
        });
        
        // creates the datagrid column actions
        $order_id = new TAction(array($this, 'onReload'));
        $order_id->setParameter('order', 'id');
        $column_id->setAction($order_id);
        
        $order_name = new TAction(array($this, 'onReload'));
        $order_name->setParameter('order', 'name');
        $column_name->setAction($order_name);
        
        $order_login = new TAction(array($this, 'onReload'));
        $order_login->setParameter('order', 'login');
        $column_login->setAction($order_login);
        
        $order_email = new TAction(array($this, 'onReload'));
        $order_email->setParameter('order', 'email');
        $column_email->setAction($order_email);
        

        
        // create EDIT action
        $action_edit = new TDataGridAction(array('SystemUserForm_Mod', 'onEdit'));
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel(_t('Edit'));
        $action_edit->setImage('fa:pencil-square-o blue fa-lg');
        $action_edit->setField('id');
        $this->datagrid->addAction($action_edit);
        //Verifica se o nível do usuário é de administrador
        if ($this->nivel_sistema>=100)
        {
        // create DELETE action
            $action_del = new TDataGridAction(array($this, 'onDelete'));
            $action_del->setButtonClass('btn btn-default');
            $action_del->setLabel(_t('Delete'));
            $action_del->setImage('fa:trash-o red fa-lg');
            $action_del->setField('id');
            $this->datagrid->addAction($action_del);
        
        // create ONOFF action
            $action_onoff = new TDataGridAction(array($this, 'onTurnOnOff'));
            $action_onoff->setButtonClass('btn btn-default');
            $action_onoff->setLabel(_t('Activate/Deactivate'));
            $action_onoff->setImage('fa:power-off fa-lg orange');
            $action_onoff->setField('id');
            $this->datagrid->addAction($action_onoff);
         }
        
        // create the datagrid model
        $this->datagrid->createModel();
        
        // create the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add(TPanelGroup::pack('', $this->datagrid));
        $container->add($this->pageNavigation);
        
        parent::add($container);
    }
    
    /**
     * Turn on/off an user
     */
    public function onTurnOnOff($param)
    {
        try
        {
            TTransaction::open('permission');
            $user = SystemUser::find($param['id']);
            if ($user instanceof SystemUser)
            {
                $user->active = $user->active == 'Y' ? 'N' : 'Y';
                $user->store();
            }
            
            TTransaction::close();
            
            $this->onReload($param);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
