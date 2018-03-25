<?php
/**
 * contribuinteForm Form
 * @author  <your name here>
 */
class contribuinteForm extends TPage
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
        $this->form = new TForm('form_contribuinte');
        $this->form->class = 'tform'; // change CSS class
        $this->form->style = 'display: table;width:100%'; // change style
        //Cria as abas
        $table_pes    = new TTable;
        $table_loc     = new TTable;
        $notebook = new TNotebook(700, 420);
        // add the notebook inside the form
        $this->form->add($notebook);
        $notebook->appendPage('Informações Pessoais', $table_pes);
        $notebook->appendPage('Endereço', $table_loc);

        // create the form fields
        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $cpf = new TEntry('cpf');
        $logradouro = new TEntry('logradouro');
        $bairro = new TEntry('bairro');
        $uf = new TDBCombo('uf','sisacad','estados','sigla','sigla');
        //Filtrando o campo cidades
        $criteria = new TCriteria;
        $criteria->add(new TFilter('uf','=','GO'));
        $cidade = new TDBCombo('cidade','sisacad','cidades','nome','nome','nome',$criteria);
        $cep = new TEntry('cep');
        $data_cadastro = new THidden('data_cadastro');
        $oculto = new TCombo('oculto');
        $cnpj = new TEntry('cnpj');
        $razao_social = new TEntry('razao_social');
        $telefone = new TEntry('telefone');
        $celular = new TEntry('celular');
        $sexo = new TCombo('sexo');
        $data_nascimento = new TDate('data_nascimento');
        $estado_civil = new TCombo('estado_civil');
        $ocupacao = new TEntry('ocupacao');
        $email = new TEntry('email');
        
        //Valores dos demais Combos
        $itemStatus= array();
        $itemStatus['t'] = 'Sim';
        $itemStatus['f'] = 'Não';
        $oculto->addItems($itemStatus);
        $oculto->setValue('f');
        
        $itemEstado= array();
        $itemEstado['Solteiro'] = 'Solteiro(a)';
        $itemEstado['Casado'] = 'Casado(a)';
        $itemEstado['Viúvo'] = 'Viúvo(a)';
        $itemEstado['Divorciado'] = 'Divorciado(a)';
        $itemEstado['Amasiado'] = 'Amasiado(a)';
        $estado_civil->addItems($itemEstado);
        $estado_civil->setValue('Solteiro');
        
        $itemGender = array();
        $itemGender['Masculino'] = 'Masculino';
        $itemGender['Feminino'] = 'Feminino';
        $sexo->addItems($itemGender);
        
        //Tamanho
        $id->setSize(50);
        $nome->setSize(400);
        $cpf->setSize(120);
        $oculto->setSize(80);
        $cnpj->setSize(160);
        $razao_social->setSize(400);
        $data_nascimento->setSize(80);
        $estado_civil->setSize(200);
        $ocupacao->setSize(400);
        $email->setSize(400);
        $sexo->setSize(120);
        
        $uf->setSize(50);
        $cidade->setSize(400);
        $logradouro->setSize(400);
        $bairro->setSize(400);
        $telefone->setSize(120);
        $celular->setSize(120);
        $cep->setSize(80);
        
        //Mascaras
        $data_nascimento->setMask('dd/mm/yyyy');
        $telefone->setMask('(99)99999-9999');
        $celular->setMask('(99)99999-9999');
        
        //Requeridos
        $nome->addValidation('Nome', new TRequiredValidator);
        $data_nascimento->addValidation('Data de Nascimento', new TRequiredValidator);
        $cpf->addValidation('CPF', new TRequiredValidator);
        $cnpj->addValidation('CNPJ', new TRequiredValidator);
        $razao_social->addValidation('Razão Social', new TRequiredValidator);
        
        //Ações
        $change_action = new TAction(array($this, 'onChangeAction_cidade'));
        $uf->setChangeAction($change_action);

        // add the fields
        //Aba Pessoal
        $table_pes->addRowSet(array(new TLabel('Id:'), $id ));
        $table_pes->addRowSet(array(new TLabel('Nome:'), $nome));
        $table_pes->addRowSet(array(new TLabel('CPF:'), $cpf));

        $table_pes->addRowSet(array(new TLabel('Oculto?:'), $oculto));
        $table_pes->addRowSet(array(new TLabel('CNPJ:'), $cnpj));
        $table_pes->addRowSet(array(new TLabel('Razão Social:'), $razao_social));

        $table_pes->addRowSet(array(new TLabel('Sexo:'), $sexo));
        $table_pes->addRowSet(array(new TLabel('Data Nascimento:'), $data_nascimento));
        $table_pes->addRowSet(array(new TLabel('Estado Civil:'), $estado_civil));
        $table_pes->addRowSet(array(new TLabel('Ocupação:'), $ocupacao) );
        $table_pes->addRowSet(array(new TLabel('Email:'), $email));
        //Aba Endereço
        $table_loc->addRowSet(array(new TLabel('Telefone:'), $telefone));
        $table_loc->addRowSet(array(new TLabel('Celular:'), $celular) );
        $table_loc->addRowSet(array(new TLabel('Logradouro:'), $logradouro));
        $table_loc->addRowSet(array(new TLabel('Bairro:'), $bairro));
        $table_loc->addRowSet(array(new TLabel('UF:'), $uf));
        $table_loc->addRowSet(array(new TLabel('Cidade:'), $cidade));
        $table_loc->addRowSet(array(new TLabel('CEP:'), $cep));
        $table_loc->addRowSet(array($data_cadastro));        
        
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
        // create an action button
        $save_button=new TButton('save');
        $save_button->setAction(new TAction(array($this, 'onSave')), _t('Save'));
        $save_button->setImage('ico_save.png');
        
        $new_button=new TButton('new');
        $new_button->setAction(new TAction(array($this, 'onClear')), _t('New'));
        $new_button->setImage('ico_new.png');
                
        // create an action button (go to list)
        $return_button=new TButton('back');
        $return_button->setAction(new TAction(array($this, 'onBack')), _t('Back to the listing'));
        $return_button->setImage('ico_back.png');
        
        // define wich are the form fields
        $this->form->setFields(array($id, $nome,$cpf,$bairro,$cidade,$estado_civil,$cep,$oculto,$sexo,$ocupacao,$celular,
                                    $telefone,$razao_social,$cnpj,$uf,$data_nascimento,$data_cadastro,
                                    $save_button,$new_button,$return_button,));
         
        $subtable = new TTable;
        $row = $subtable->addRow();
        $row->addCell($save_button);
        $row->addCell($new_button);
        $row->addCell($return_button);
        
        // wrap the page content
        $vbox = new TVBox;
        $vbox->add(new TXMLBreadCrumb('menu.xml', 'contribuinteList'));
        $vbox->add($this->form);
        $vbox->add($subtable);
        
        // add the form inside the page
        parent::add($vbox);
    }

    /**
     * Save form data
     * @param $param Request
     */
    public function onSave( $param )
    {
        try
        {
            TTransaction::open('freap'); // open a transaction
            
            /**
            // Enable Debug logger for SQL operations inside the transaction
            TTransaction::setLogger(new TLoggerSTD); // standard output
            TTransaction::setLogger(new TLoggerTXT('log.txt')); // file
            **/
            
            //$this->form->validate(); // validate form data
            $object = new contribuinte;  // create an empty object
            $data = $this->form->getData(); // get form data as array
            $relatos = self::isValidSave($data);//Valida o Formulário antes de salvar
            if (!empty($relatos))
            {
                $validar='';
                foreach ($relatos as $relato)
                {
                    if (!empty($relato))
                    {
                        $validar.=$relato.'<br>';//Cria o relatório dos erros
                    }
                }
                throw new Exception ($validar);
            }
            if (empty($data->data_cadastro))
            {
                $data->data_cadastro = date ('Y-m-d H:i:s');
            }
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
    }
    
    /**
     * Clear form data
     * @param $param Request
     */
    public function onClear( $param )
    {
        $this->form->clear(TRUE);
    }
    
    /**
     * Load object to form data
     * @param $param Request
     */
    public function onEdit( $param )
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];  // get the parameter $key
                TTransaction::open('freap'); // open a transaction
                $object = new contribuinte($key); // instantiates the Active Record
                $object->oculto = (strtoupper($object->oculto)=='T') ? 't' : 'f';//Corrige o campo Oculta
                $object->sexo = (strtoupper($object->sexo)=='FEMININO') ? 'Feminino' : 'Masculino';//Corrige o campo Sexo
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
    }//Fim módulo
/*
 *    Atualiza Municipios
 */    
    public static function onChangeAction_cidade($param)
    {
        if (array_key_exists('uf',$param))
        {
            $key = $param['uf'];
        }
        else
        {
            return;
        }
        try
        {
                TTransaction::open('sisacad'); // open a transaction
                $options  = cidades::where('uf', '=', $key)->load();//Lista de Cidades Filtradas
                TTransaction::close(); // close the transaction
                $lista = array();
                foreach ($options as $option)
                {
                    $lista[$option->id] = $option->nome;
                    
                }
                TDBCombo::reload('form_contribuinte', 'cidade', $lista);
        }
        catch (Exception $e) // in case of exception 
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations            
        }

    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Rotina: Retorno a Listagem
 *---------------------------------------------------------------------------------------*/
    public function onBack ()
    {
        TApplication::loadPage('contribuinteList');
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *        Valida formulário para salvar. Recebe os dados do Form para validar
 *------------------------------------------------------------------------------*/
    static function isValidSave ($obj)
    {
        $nosave = array();
        $ci = new TFerramentas;
        
        if (empty($obj->cpf) && empty($obj->nome) && empty($obj->cnpj) && empty($obj->razao_social)) 
        { 
            $nosave[] = '<li>É necessário preencher ou o nome do contribuinte e seu respectivo CPF ou a Razão Social com sua CNPJ.</li>';
        }
        if ( (!empty($obj->cpf) && empty($obj->nome)) || (empty($obj->cpf) && !empty($obj->nome)) ) 
        { 
            $nosave[] = '<li>É necessário preencher o nome do contribuinte e seu respectivo CPF.</li>';
        }
        if ( (!empty($obj->cnpj) && empty($obj->razao_social)) || (empty($obj->cnpj) && !empty($obj->razao_social)) ) 
        { 
            $nosave[] = '<li>É necessário preencher a Razão Social com sua respectiva CNPJ.</li>';
        }

        if (!empty($obj->cpf) && !$ci->isValidCPF($obj->cpf))
        {
            $nosave[] = '<li>O CPF é inválido...</li>';
        }
        if (!empty($obj->cnpj) && !$ci->isValidCNPJ($obj->cnpj))
        {
            $nosave[] = '<li>O CNPJ é inválido...</li>';
        }
        if (empty($obj->telefone) && empty($obj->celular) && empty($obj->email))
        {
            $nosave[] = '<li>É necessário alguma forma de contado no cadastro (telefone, celular ou email).</li>';
        }
        return $nosave;

    }//Fim Módulo
}
