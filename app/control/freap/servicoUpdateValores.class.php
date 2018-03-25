<?php
/**
 * servicoUpdateValores
 *
 * @version    1.0
 * @author     Fernando de Pinho Araújo
 */
class servicoUpdateValores extends TStandardList
{
    protected $form;     // registration form
    protected $datagrid; // listing
    protected $pageNavigation;
    protected $formgrid;
    protected $saveButton;
    
    /**
     * Page constructor
     */
    public function __construct()
    {
        parent::__construct();
        
        parent::setDatabase('sicad');
        parent::setActiveRecord('servico');
        parent::setDefaultOrder('id', 'asc');
        // add the filter (filter field, operator, form field)
        parent::addFilterField('codigo', 'like', 'codigo');
        parent::addFilterField('nome', 'like', 'nome');
        
        // creates the form
        $this->form = new TQuickForm('form_search_Product');
        $this->form->class='tform';
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('Atualização de Valores');
        
        // create the form fields
        $codigo = new TEntry('codigo');
        $codigo->setTip('Filtragem pelo Código do Serviço na SEFAZ...');
        $this->form->addQuickField('Código SEFAZ', $codigo,  '120' );

        $nome = new TEntry('nome');
        $nome->setTip('Filtragem pela descrição do Serviço');
        $this->form->addQuickField('Descrição do Serviço', $nome,  '400' );        
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');


        // keep the form filled with session data
        $this->form->setData( TSession::getValue('codigo_filter_data') );
        
        // creates the datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid->style = 'width: 100%';
        
        // create the datagrid columns
        $column_id           = new TDataGridColumn('id', 'Id', 'left');
        $column_codigo       = new TDataGridColumn('codigo', 'Código', 'left');
        $column_nome         = new TDataGridColumn('nome', 'Descrição do Serviço', 'center');
        $column_oculto       = new TDataGridColumn('oculto_widget', 'Oculto?', 'right');
        $column_valor_base   = new TDataGridColumn('valor_base_widget', 'Valor Base', 'right');
        $column_valor_km     = new TDataGridColumn('valor_km_widget', 'Valor KM', 'right');
        $column_valor_pm     = new TDataGridColumn('valor_pm_widget', 'Valor PM', 'right');
        $column_valor_diaria = new TDataGridColumn('valor_diaria_widget', 'Valor Diária/Hora', 'right');

        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_codigo);
        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_oculto);
        $this->datagrid->addColumn($column_valor_base);
        $this->datagrid->addColumn($column_valor_km);
        $this->datagrid->addColumn($column_valor_pm);
        $this->datagrid->addColumn($column_valor_diaria);

        // create the datagrid model
        $this->datagrid->createModel();
        
        // creates the pagination
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());
        
        $this->datagrid->disableDefaultClick();
        
        // put datagrid inside a form
        $this->formgrid = new TForm;
        $this->formgrid->add($this->datagrid);
        
        // creates the update collection button
        $this->saveButton = new TButton('update_collection');
        $this->saveButton->setAction(new TAction(array($this, 'onSaveCollection')), 'Save');
        $this->saveButton->setImage('fa:save green');
        $this->formgrid->addField($this->saveButton);
        
        $gridpack = new TVBox;
        $gridpack->style = 'width: 100%';
        $gridpack->add($this->formgrid);
        $gridpack->add($this->saveButton)->style = 'background:whiteSmoke;border:1px solid #cccccc; padding: 3px;padding: 5px;';
        
        // define the datagrid transformer method
        parent::setTransformer(array($this, 'onBeforeLoad'));
        
        // vertical box container
        $container = new TVBox;
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($gridpack);
        $container->add($this->pageNavigation);
        
        parent::add($container);
    }
    
    /**
     * Run before the datagrid is loaded
     */
    public function onBeforeLoad($objects, $param)
    {
        // update the action parameters to pass the current page to action
        // without this, the action will only work for the first page
        $saveAction = $this->saveButton->getAction();
        $saveAction->setParameters($param); // important!
        
        $gridfields = array( $this->saveButton );
        $itemStatus= array();
        $itemStatus['t'] = 'Sim';
        $itemStatus['f'] = 'Não';
        foreach ($objects as $object)
        {
            $object->valor_base_widget = new TEntry('valor_base' . '-' . $object->id);
            $object->valor_base_widget->setValue( $object->valor_base );
            $object->valor_base_widget->setNumericMask(2,'.','');
            $object->valor_base_widget->setSize(120);
            $object->valor_base_widget->setTip('Valor Base se refere a uma taxa de preço fixo ou quando o serviço já tem um valor inicial...');
            $gridfields[] = $object->valor_base_widget; // important

            $object->valor_km_widget = new TEntry('valor_km' . '-' . $object->id);
            $object->valor_km_widget->setValue( $object->valor_km );
            $object->valor_km_widget->setNumericMask(2,'.','');
            $object->valor_km_widget->setSize(120);
            $object->valor_km_widget->setTip('Referente ao custo de deslocamento por Quilometro...');
            $gridfields[] = $object->valor_km_widget; // important
            
            $object->valor_pm_widget = new TEntry('valor_pm' . '-' . $object->id);
            $object->valor_pm_widget->setValue( $object->valor_pm );
            $object->valor_pm_widget->setNumericMask(2,'.','');
            $object->valor_pm_widget->setSize(120);
            $object->valor_pm_widget->setTip('Referente ao custo por PM no serviço...');
            $gridfields[] = $object->valor_pm_widget; // important
            
            $object->valor_diaria_widget = new TEntry('valor_diaria' . '-' . $object->id);
            $object->valor_diaria_widget->setValue( $object->valor_diaria );
            $object->valor_diaria_widget->setNumericMask(2,'.','');
            $object->valor_diaria_widget->setSize(120);
            $object->valor_base_widget->setTip('Referente a taxas cobradas por dia ou hora, ou ainda, Valor de prestação de serviço por dia/hora...');
            $gridfields[] = $object->valor_diaria_widget; // important
            
            $object->oculto_widget = new TCombo('oculto' . '-' . $object->id);
            $object->oculto_widget->addItems($itemStatus);
            $valor = ($object->oculto) ? 't' : 'f';
            $object->oculto_widget->setValue( $valor);
            $object->oculto_widget->setSize(50);
            $object->oculto_widget->setTip('Opção que some com o serviço dos demais formulários...');
            $gridfields[] = $object->oculto_widget; // important
            
        }
        
        $this->formgrid->setFields($gridfields);
    }
    
    /**
     * Save the datagrid objects
     */
    public function onSaveCollection()
    {
        $data = $this->formgrid->getData(); // get datagrid form data
        $this->formgrid->setData($data); // keep the form filled
        
        try
        {
            // open transaction
            TTransaction::open('sicad');
            
            // iterate datagrid form objects
            $objects = array();
            foreach ($this->formgrid->getFields() as $name => $field)
            {
                if ($field instanceof TEntry)
                {
                    $parts = explode('-', $name);
                    $id = end($parts);
                    switch ($parts[0])
                    {
                        case 'valor_base':
                            $objects[$id]['valor_base'] = str_replace(',', '', $field->getValue());
                            break;
                        case 'valor_km':
                            $objects[$id]['valor_km'] = str_replace(',', '', $field->getValue());
                            break;
                        case 'valor_pm':
                            $objects[$id]['valor_pm'] = str_replace(',', '', $field->getValue());
                            break;
                        case 'valor_diaria':
                            $objects[$id]['valor_diaria'] = str_replace(',', '', $field->getValue());
                            break;
                        case 'oculto':
                            $objects[$id]['oculto'] = ($field->getValue()) ? 't' : 'f';
                            break;
                    }
                }
            }
            foreach ($objects as $key=> $object)
            {
                $dado = servico::find($key);
                if ($dado)
                {
                    $dado->fromArray( (array) $object);
                    $dado->store();
                }
            }
            
            new TMessage('info', AdiantiCoreTranslator::translate('Records updated'));
            
            // close transaction
            TTransaction::close();
        }
        catch (Exception $e)
        {
            // show the exception message
            new TMessage('error', $e->getMessage());
        }
    }
}