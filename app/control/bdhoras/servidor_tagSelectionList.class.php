<?php
/**
 * servidor_tagSelectionList Record selection
 * @author  <your name here>
 */
class servidor_tagSelectionList extends TStandardList
{
    protected $form;     // search form
    protected $datagrid; // listing
    protected $pageNavigation;
    
    /**
     * Page constructor
     */
    public function __construct()
    {
        parent::__construct();
        
        parent::setDatabase('sicad');            // defines the database
        parent::setActiveRecord('servidor_tag');   // defines the active record
        parent::setDefaultOrder('id', 'asc');         // defines the default order
        // parent::setCriteria($criteria) // define a standard filter

        parent::addFilterField('unidade', 'like', 'unidade'); // filterField, operator, formField
        parent::addFilterField('nome', 'like', 'nome'); // filterField, operator, formField
        parent::addFilterField('status', 'like', 'status'); // filterField, operator, formField
        parent::addFilterField('rgmilitar', 'like', 'rgmilitar'); // filterField, operator, formField
        parent::addFilterField('sexo', 'like', 'sexo'); // filterField, operator, formField

        
        // creates the form
        $this->form = new TQuickForm('form_search_servidor_tag');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('servidor_tag');
        

        // create the form fields
        $id = new TEntry('id');
        $unidade = new TEntry('unidade');
        $nome = new TEntry('nome');
        $postograd = new TEntry('postograd');
        $status = new TEntry('status');
        $rgmilitar = new TEntry('rgmilitar');
        $sexo = new TEntry('sexo');
 

        // add the fields
        $this->form->addQuickField('Id', $id,  200 );
 
        $this->form->addQuickField('Unidade', $unidade,  200 );
        $this->form->addQuickField('Nome', $nome,  200 );
        $this->form->addQuickField('Postograd', $postograd,  200 );
        $this->form->addQuickField('Status', $status,  200 );
        $this->form->addQuickField('Rgmilitar', $rgmilitar,  200 );
        $this->form->addQuickField('Sexo', $sexo,  200 );

        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('servidor_tag_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $this->form->addQuickAction( 'Show results', new TAction(array($this, 'showResults')), 'fa:check-circle-o green' );
        
        // creates a DataGrid
        $this->datagrid = new TDataGrid;
        
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);
        // $this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_id = new TDataGridColumn('id', 'Id', 'right');
        $column_unidade = new TDataGridColumn('unidade', 'Unidade', 'left');
        $column_nome = new TDataGridColumn('nome', 'Nome', 'left');
        $column_postograd = new TDataGridColumn('postograd', 'Postograd', 'left');
        $column_status = new TDataGridColumn('status', 'Status', 'left');
        $column_rgmilitar = new TDataGridColumn('rgmilitar', 'Rgmilitar', 'left');
        $column_sexo = new TDataGridColumn('sexo', 'Sexo', 'left');


        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_unidade);
        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_postograd);
        $this->datagrid->addColumn($column_status);
        $this->datagrid->addColumn($column_rgmilitar);
        $this->datagrid->addColumn($column_sexo);

        $column_id->setTransformer(array($this, 'formatRow') );
        
        // creates the datagrid actions
        $action1 = new TDataGridAction(array($this, 'onSelect'));
        $action1->setUseButton(TRUE);
        $action1->setButtonClass('btn btn-default');
        $action1->setLabel(AdiantiCoreTranslator::translate('Select'));
        $action1->setImage('fa:check-circle-o blue');
        $action1->setField('id');
        
        // add the actions to the datagrid
        $this->datagrid->addAction($action1);
        
        // create the datagrid model
        $this->datagrid->createModel();
        
        // create the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        // $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($this->datagrid);
        $container->add($this->pageNavigation);
        
        parent::add($container);
    }
    
    /**
     * Save the object reference in session
     */
    public function onSelect($param)
    {
        // get the selected objects from session 
        $selected_objects = TSession::getValue(__CLASS__.'_selected_objects');
        
        TTransaction::open('sicad');
        $object = new servidor_tag($param['key']); // load the object
        if (isset($selected_objects[$object->id]))
        {
            unset($selected_objects[$object->id]);
        }
        else
        {
            $selected_objects[$object->id] = $object->toArray(); // add the object inside the array
        }
        TSession::setValue(__CLASS__.'_selected_objects', $selected_objects); // put the array back to the session
        TTransaction::close();
        
        // reload datagrids
        $this->onReload( func_get_arg(0) );
    }
    
    /**
     * Highlight the selected rows
     */
    public function formatRow($value, $object, $row)
    {
        $selected_objects = TSession::getValue(__CLASS__.'_selected_objects');
        
        if ($selected_objects)
        {
            if (in_array( (int) $value, array_keys( $selected_objects ) ) )
            {
                $row->style = "background: #FFD965";
            }
        }
        
        return $value;
    }
    
    /**
     * Show selected records
     */
    public function showResults()
    {
        $datagrid = new BootstrapDatagridWrapper(new TQuickGrid);
        
        $datagrid->addQuickColumn('Id', 'id', 'right');
        $datagrid->addQuickColumn('Unidade', 'unidade', 'left');
        $datagrid->addQuickColumn('Nome', 'nome', 'left');
        $datagrid->addQuickColumn('Postograd', 'postograd', 'left');
        $datagrid->addQuickColumn('Status', 'status', 'left');
        $datagrid->addQuickColumn('Rgmilitar', 'rgmilitar', 'left');
        $datagrid->addQuickColumn('Sexo', 'sexo', 'left');
        
        // create the datagrid model
        $datagrid->createModel();
        
        $selected_objects = TSession::getValue(__CLASS__.'_selected_objects');
        ksort($selected_objects);
        if ($selected_objects)
        {
            $datagrid->clear();
            foreach ($selected_objects as $selected_object)
            {
                $datagrid->addItem( (object) $selected_object );
            }
        }
        
        $win = TWindow::create('Results', 0.6, 0.6);
        $win->add($datagrid);
        $win->show();
    }
}
