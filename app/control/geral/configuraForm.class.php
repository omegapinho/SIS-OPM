<?php
/**
 * configuraForm Form
 * @author  <your name here>
 */
class configuraForm extends TPage
{
    protected $form; // form
    
    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();
        
        // creates the form
        $this->form = new TQuickForm('form_configura');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        $this->form->setFormTitle('Cria Item para Configuração de Sistema');

        // create the form fields
        $id = new TEntry('id');

        $criteria = new TCriteria; 
        $criteria->add(new TFilter('dominio', '=', 'configura'));
        $dominio = new TDBCombo('dominio','sicad','Item','nome','nome','ordem',$criteria);
        
        $pagina = new TEntry('pagina');
        $name = new TEntry('name');
        $label = new TEntry('label');
        $tip = new TEntry('tip');
        $tamanho = new TEntry('tamanho');
        $type = new TCombo('type');
        $value_combo = new TEntry('value_combo');
        $ativo = new TCombo('ativo');
        $visivel = new TCombo('visivel');
        
        //Valores dos Combos
        $item = array ('S'=>'SIM','N'=>'NÃO');
        $ativo->addItems($item);
        $visivel->addItems($item);
        $item = array ('E'=>'Entrada de Texto','C'=>'Caixa de Combo','S'=>'Caixa de Seleção','D'=>'Calendário');
        $type->addItems($item);
        
        //Valores Default
        $ativo->setValue('S');
        $visivel->setValue('S');
        $type->setValue('E');
        //
        $name->setMaxLength(40);
        $tamanho->setMaxLength(3);
        $tamanho->setNumericMask(0,'','');
        
        //Tips
        $value_combo->setTip("Defina os possíveis para o campo separando-os por / e usando a seguinte notação:".
                "<br>-(código=>Label), onde:<br>-Código é o valor que é armazenado;<br>-Label é o dado apresentado ".
                "na caixa Combo/Select<br>".
                "Exemplo: S=>SIM/N=>NÃO<br>".
                "Pode-se buscar dados de um banco existente para isso siga as anotações:<br>".
                "- Comece a linha pela paravra BANCO;<br>- Separe com / substituindo os nomes a seguir pelos dados corretos: banco/model/'campo id'/'dado visivel'/'campo index'/critério(opcional)<br>".
                "- Caso haja um critério use ][ como separador e substitua: 'campo a pesquisar']['operador'(=,>=,<=,!=,etc)]['valor comparável'.");
        $name->setTip("Entre com o nome do campo. Este nome não deve ter caracteres especiais como acentos bem como trocar o espaço pelo traço sublinhado.".
                "<br>Ao salvar farei uma retificação automática.");
        
        // add the fields
        $this->form->addQuickField('ID', $id,  50 );
        $this->form->addQuickField('Sistema', $dominio,  400 , new TRequiredValidator);
        $this->form->addQuickField('Serviço/Página', $pagina,  400 );
        $this->form->addQuickField('Nome do Item', $name,  200 , new TRequiredValidator);
        $this->form->addQuickField('Rótulo de Apresentação', $label,  400 , new TRequiredValidator);
        $this->form->addQuickField('Dica', $tip,  400 );
        $this->form->addQuickField('Tipo de Campo', $type,  200 , new TRequiredValidator);
        $this->form->addQuickField('Valores para Combo', $value_combo,  400 );
        $this->form->addQuickField('Tamanho do Campo (em pixels)', $tamanho,  80 );
        $this->form->addQuickField('Ativo?', $ativo,  80 );
        $this->form->addQuickField('Visível?', $visivel,  80 );

        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }
        
        /** samples
         $this->form->addQuickFields('Date', array($date1, new TLabel('to'), $date2)); // side by side fields
         $fieldX->addValidation( 'Field X', new TRequiredValidator ); // add validation
         $fieldX->setSize( 100, 40 ); // set size
         **/
         
        // create the form actions
        $this->form->addQuickAction(_t('Save'), new TAction(array($this, 'onSave')), 'fa:floppy-o');
        $this->form->addQuickAction(_t('New'),  new TAction(array($this, 'onClear')), 'bs:plus-sign green');
        $this->form->addQuickAction(_t('Back to the listing'), new TAction(array($this, 'onReturn')),'ico_back.png');
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'configuraList'));
        $container->add($this->form);
        
        parent::add($container);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *         Salva
 *---------------------------------------------------------------------------------------*/
    public function onSave( $param )
    {
        try
        {
            TTransaction::open('sicad'); // open a transaction
            
            /**
            // Enable Debug logger for SQL operations inside the transaction
            TTransaction::setLogger(new TLoggerSTD); // standard output
            TTransaction::setLogger(new TLoggerTXT('log.txt')); // file
            **/
            
            $this->form->validate(); // validate form data
            $fer = new TFerramentas();
            
            $object = new configura;  // create an empty object
            $data = $this->form->getData(); // get form data as array
            $data->name = $fer->removeAcentos(strtolower($data->name),'_');
            $object->fromArray( (array) $data); // load the object with data
            $object->store(); // save the object
            
            // get the generated id
            $data->id = $object->id;
            
            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction
            
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'));
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *         Limpa/Novo
 *---------------------------------------------------------------------------------------*/
    public function onClear( $param )
    {
        $this->form->clear(TRUE);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *         Edição
 *---------------------------------------------------------------------------------------*/
    public function onEdit( $param )
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];  // get the parameter $key
                TTransaction::open('sicad'); // open a transaction
                $object = new configura($key); // instantiates the Active Record
                $this->form->setData($object); // fill the form
                TTransaction::close(); // close the transaction
            }
            else
            {
                $this->form->clear(TRUE);
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *                   Retorna Listagem
 *---------------------------------------------------------------------------------------*/
     public function onReturn ($param)
     {
         TApplication::loadPage('configuraList');
     }//Fim módulo
}//Fim Classe

