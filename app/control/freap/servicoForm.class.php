<?php
/**
 * servicoForm Form
 * @author  <your name here>
 */
class servicoForm extends TPage
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
        $this->form = new TQuickForm('form_servico');
        $this->form->class = 'tform'; // change CSS class
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        $this->form->setFormTitle('Cadastro de Serviços FREAP');

        // create the form fields
        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $codigo = new TEntry('codigo');
        $valor_base = new TEntry('valor_base');
        $oculto = new TCombo('oculto');
        $calcula_multas = new TCombo('calcula_multas');
        $valor_km = new TEntry('valor_km');
        $valor_pm = new TEntry('valor_pm');
        $valor_juros = new TEntry('valor_juros');
        $valor_multa = new TEntry('valor_multa');
        $nome_chave = new TEntry('nome_chave');
        $tipo_servico = new TDBCombo('tipo_servico','freap','tipo','id','nome');
        $valor_diaria = new TEntry('valor_diaria');
        $pm_max = new TEntry('pm_max');
        $pm_min = new TEntry('pm_min');
        $diaria_min = new TEntry('diaria_min');
        $diaria_max = new TEntry('diaria_max');
        $hora_virtual = new TCombo('hora_virtual');
        $valor_diurno = new TEntry('valor_diurno');
        $valor_noturno = new TEntry('valor_noturno');

        //Valores dos demais Combos
        $itemStatus= array();
        $itemStatus['t'] = 'Sim';
        $itemStatus['f'] = 'Não';
        
        $oculto->addItems($itemStatus);
        $oculto->setValue('f');
        
        $hora_virtual->addItems($itemStatus);
        $hora_virtual->setValue('f');
        
        $calcula_multas->addItems($itemStatus);
        $calcula_multas->setValue('f');
        
        //Valores dos campos de entrada
        $valor_base->setValue(0);
        $valor_diaria->setValue(0);
        $valor_juros->setValue(0);
        $valor_km->setValue(0);
        $valor_multa->setValue(0);
        $valor_pm->setValue(0);
        $pm_max->setValue(0);
        $pm_min->setValue(0);
        $diaria_min->setValue(0);
        $diaria_max->setValue(0);
        $valor_diurno->setValue(0);
        $valor_noturno->setValue(0);
        
        //Desativar campos
        self::habilita('0');
        self::habilita_virtual('f');
        
        //Mascaras
        $codigo->setMask('999');
        $pm_max->setMask('999');
        $pm_min->setMask('999');
        $diaria_min->setMask('999');
        $diaria_max->setMask('999');
        
        //Tips
        $mensagem_numerico = 'Preencha o valor monetário ignorando o simbolo $ e usando o ponto para separar as casas decimais...';
        $valor_base->setTip($mensagem_numerico);
        $valor_diaria->setTip($mensagem_numerico);
        $valor_km->setTip($mensagem_numerico);
        $valor_juros->setTip($mensagem_numerico);
        $valor_multa->setTip($mensagem_numerico);
        $valor_pm->setTip($mensagem_numerico);
        $valor_diurno->setTip($mensagem_numerico);
        $valor_noturno->setTip($mensagem_numerico);
        $pm_max->setTip('Deixe zero se a quantidade de Policiais não tem limite preciso ou defina o máximo que pode ser usado...');
        $pm_min->setTip('Deixe zero se a quantidade de Policiais não tem limite preciso ou defina o máximo que pode ser usado...');
        $diaria_max->setTip('Define a quantidade máxima de dias que pode ser cobrado...Deixe zero se não há limite...');
        $diaria_max->setTip('Define a quantidade mínima de dias que pode ser cobrado...Deixe zero se não há limite...');
        $codigo->setTip('Código do Serviço na SEFAZ. Não pode ser repetido...');
        $nome->setTip('Copie a definição legal da atividade conforme a letra da lei...');
        $nome_chave->setTip('Resuma a definição legal em até 40 caracteres. Será usado onde a definição legal, pelo seu tamanho, seja inconveniente...');
        $oculto->setTip('Define se o serviço está ou não disponível para uso. Se ocultar (Sim) o item não irá aparecer quando se buscar fazer um boleto...');
        $tipo_servico->setTip('Define a forma que se dará o calcúlo dos valores. Conforme o tipo o sistema libera para preenchimento os demais campos abaixo...');
        $calcula_multas->setTip('Define se haverá acréscimo de Juros e Multas. Isto mudará a forma de funcionamento do preenchimento das Datas de Vencimento e Pagamento pois uma não pode ser menor que a outra.');
        
        //Formatação Diversa
        $nome_chave->setMaxLength(40);
        $valor_base->setProperty('style','text-align:right');
        $valor_diaria->setProperty('style','text-align:right');
        $valor_juros->setProperty('style','text-align:right');
        $valor_km->setProperty('style','text-align:right');
        $valor_multa->setProperty('style','text-align:right');
        $valor_pm->setProperty('style','text-align:right');
        $pm_max->setProperty('style','text-align:right');
        $pm_min->setProperty('style','text-align:right');
        $diaria_min->setProperty('style','text-align:right');
        $diaria_max->setProperty('style','text-align:right');
        $valor_diurno->setProperty('style','text-align:right');
        $valor_noturno->setProperty('style','text-align:right');

        // add the fields
        $this->form->addQuickField('Id', $id,  100 );
        $this->form->addQuickField('Nome', $nome,  400, new TRequiredValidator );
        $this->form->addQuickField('Nome Chave', $nome_chave,  400, new TRequiredValidator);
        $this->form->addQuickField('Código na SEFAZ', $codigo,  80, new TRequiredValidator);
        $this->form->addQuickField('Tipo Serviço', $tipo_servico,  400 , new TRequiredValidator);
        $this->form->addQuickField('Oculto?', $oculto,  80 );
        $this->form->addQuickField('Calcula Multas/Juros?', $calcula_multas,  80 );
        $this->form->addQuickField('Valor Básico', $valor_base,  100, new TNumericValidator );
        $this->form->addQuickField('Valor por Km', $valor_km,  100, new TNumericValidator );
        $this->form->addQuickField('Valor por PM', $valor_pm,  100, new TNumericValidator );
        $this->form->addQuickField('Valor Juros', $valor_juros,  100, new TNumericValidator );
        $this->form->addQuickField('Valor Multa', $valor_multa,  100, new TNumericValidator );
        $this->form->addQuickField('Valor  por Diária ou Hora', $valor_diaria,  100, new TNumericValidator );
        $this->form->addQuickField('Máximo de PMs', $pm_max,  100 );
        $this->form->addQuickField('Minimo de PMs', $pm_min,  100 );
        $this->form->addQuickField('Diária Min', $diaria_min,  100 );
        $this->form->addQuickField('Diária Max', $diaria_max,  100 );
        $this->form->addQuickField('Tem AC-4?', $hora_virtual,  80 );
        $this->form->addQuickField('Valor AC-4 Diurno', $valor_diurno,  100, new TNumericValidator );
        $this->form->addQuickField('Valor AC-4 Noturno', $valor_noturno,  100, new TNumericValidator );
        
        //Ações
        $change_action = new TAction(array($this, 'onChangeAction_tipo'));
        $tipo_servico->setChangeAction($change_action);

        $change_action = new TAction(array($this, 'onChangeAction_AC4'));
        $hora_virtual->setChangeAction($change_action);
        
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
        $this->form->addQuickAction(_t('Back to the listing'),  new TAction(array('servicoList', 'onReload')), 'fa:table blue');
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'servicoList'));
        $container->add($this->form);
        
        parent::add($container);
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
            
            $this->form->validate(); // validate form data
            
            $object = new servico;  // create an empty object
            $data = $this->form->getData(); // get form data as array
            $codigo = servico::where ('codigo','=',$data->codigo)->load();
            if ($codigo && empty($data->id))
            {
                throw new Exception ('Código da SEFAZ não pode ser Repetido...');
            }
            $object->fromArray( (array) $data); // load the object with data
            $object->store(); // save the object
            
            // get the generated id
            $data->id = $object->id;
            self::habilita($data->tipo_servico);
            self::habilita_virtual($data->hora_virtual);
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
    /**
     * Clear form data
     * @param $param Request
     */
    public function onClear( $param )
    {
        $this->form->clear(TRUE);
    }//Fim Módulo
    
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
                $object = new servico($key); // instantiates the Active Record
                $object->oculto = (strtoupper($object->oculto)=='T' || $object->oculto==true) ? 't' : 'f';//Corrige o campo Oculta
                $object->hora_virtual = (strtoupper($object->hora_virtual)=='T' || $object->hora_virtual==true) ? 't' : 'f';//Corrige o Campo hora_virtual
                $object->calcula_multas = (strtoupper($object->calcula_multas)=='T' || $object->calcula_multas==true) ? 't' : 'f';//Corrige o Campo calcula_multas
                $this->form->setData($object); // fill the form
                self::habilita($object->tipo_servico);
                self::habilita_virtual($object->hora_virtual);
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
/*------------------------------------------------------------------------------
 *        Atualiza o tipo de serviço
 *------------------------------------------------------------------------------*/   
    public static function onChangeAction_tipo($param)
    {
        if (array_key_exists('tipo_servico',$param))
        {
            $key = $param['tipo_servico'];
        }
        else
        {
            return;
        }
        self::habilita($key);
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *        Atualiza o tipo de serviço
 *------------------------------------------------------------------------------*/   
    public static function onChangeAction_AC4($param)
    {
        if (array_key_exists('hora_virtual',$param))
        {
            $key = $param['hora_virtual'];
        }
        else
        {
            return;
        }
        self::habilita_virtual($key);
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *        Habilita Campos para virtural conforme $key
 *------------------------------------------------------------------------------*/   
    static function habilita_virtual ($key)
    {
        if ($key!='t')
        {
            TCombo::disableField('form_servico','valor_diurno');
            TCombo::disableField('form_servico','valor_noturno');
        }
        else if ($key =='t')
        {
            TCombo::enableField('form_servico','valor_diurno');
            TCombo::enableField('form_servico','valor_noturno');
        }
    }
/*------------------------------------------------------------------------------
 *        Habilita Campos conforme $key
 *------------------------------------------------------------------------------*/   
    static function habilita ($key)
    {
        TEntry::disableField('form_servico','valor_juros');
        TEntry::disableField('form_servico','valor_multa');
        switch ($key)
        {
            case '1': //Valor Único
                TEntry::enableField('form_servico','valor_base');
                TEntry::disableField('form_servico','valor_km');
                TEntry::disableField('form_servico','valor_diaria');
                TEntry::disableField('form_servico','valor_pm');
                TEntry::disableField('form_servico','pm_max');
                TEntry::disableField('form_servico','pm_min');
                TEntry::disableField('form_servico','diaria_min');
                TEntry::disableField('form_servico','diaria_max');
                break;
            case '2':// PM X (qnt_horas X valor_diaria)
                TEntry::disableField('form_servico','valor_base');
                TEntry::disableField('form_servico','valor_km');
                TEntry::enableField('form_servico','valor_diaria');
                TEntry::disableField('form_servico','valor_pm');
                TEntry::enableField('form_servico','pm_max');
                TEntry::enableField('form_servico','pm_min');
                TEntry::disableField('form_servico','diaria_min');
                TEntry::disableField('form_servico','diaria_max');
                break;
            case '3'://qnt_dias X valor_diaria
                TEntry::disableField('form_servico','valor_base');
                TEntry::disableField('form_servico','valor_km');
                TEntry::enableField('form_servico','valor_diaria');
                TEntry::disableField('form_servico','valor_pm');
                TEntry::disableField('form_servico','pm_max');
                TEntry::disableField('form_servico','pm_min');
                TEntry::enableField('form_servico','diaria_min');
                TEntry::enableField('form_servico','diaria_max');
                break;
            case '4'://valor base + (qnt_dias X valor_diaria)
                TEntry::enableField('form_servico','valor_base');
                TEntry::disableField('form_servico','valor_km');
                TEntry::enableField('form_servico','valor_diaria');
                TEntry::disableField('form_servico','valor_pm');
                TEntry::disableField('form_servico','pm_max');
                TEntry::disableField('form_servico','pm_min');
                TEntry::enableField('form_servico','diaria_min');
                TEntry::enableField('form_servico','diaria_max');
                break;
            case '5'://(PM X (qnt_horas X valor_horas))+ (qnt_km X valor_km)
                TEntry::disableField('form_servico','valor_base');
                TEntry::enableField('form_servico','valor_km');
                TEntry::enableField('form_servico','valor_diaria');
                TEntry::disableField('form_servico','valor_pm');
                TEntry::enableField('form_servico','pm_max');
                TEntry::enableField('form_servico','pm_min');
                TEntry::disableField('form_servico','diaria_min');
                TEntry::disableField('form_servico','diaria_max');
                break;
            case '6'://valor base + (qnt_km X valor_km)
                TEntry::enableField('form_servico','valor_base');
                TEntry::enableField('form_servico','valor_km');
                TEntry::disableField('form_servico','valor_diaria');
                TEntry::disableField('form_servico','valor_pm');
                TEntry::disableField('form_servico','pm_max');
                TEntry::disableField('form_servico','pm_min');
                TEntry::disableField('form_servico','diaria_min');
                TEntry::disableField('form_servico','diaria_max');
                break;
            case '7'://qnt_horas X valor_horas
                TEntry::disableField('form_servico','valor_base');
                TEntry::disableField('form_servico','valor_km');
                TEntry::enableField('form_servico','valor_diaria');
                TEntry::disableField('form_servico','valor_pm');
                TEntry::disableField('form_servico','pm_max');
                TEntry::disableField('form_servico','pm_min');
                TEntry::disableField('form_servico','diaria_min');
                TEntry::disableField('form_servico','diaria_max');
                break;
            default:
                TEntry::disableField('form_servico','valor_base');
                TEntry::disableField('form_servico','valor_km');
                TEntry::disableField('form_servico','valor_diaria');
                TEntry::disableField('form_servico','valor_pm');
                TEntry::disableField('form_servico','pm_max');
                TEntry::disableField('form_servico','pm_min');
                TEntry::disableField('form_servico','diaria_min');
                TEntry::disableField('form_servico','diaria_max');
                break;
        }
         
    } //Fim Módulo
 
}
