<?php
/**
 * bdhFechamentoForm Form
 * @author  <your name here>
 */
class bdhConfigForm extends TPage
{
    protected $form; // form
    private $lista;
    private $lista_slc;
    private $campos;
    var $sistema = 'Banco de Horas';//Nome do sistema que irá configurar(filtro)
    protected $nivel_sistema = false;//Registra o nível de acesso do usuário

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();
        
        // creates the form
        $this->form = new TQuickForm('form_config');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        $this->form->setFormTitle($this->sistema.' - Configuração de Funcionamento');
        
        if (!$this->nivel_sistema)
        {
            $fer = new TFerramentas();
            $this->nivel_sistema=$fer->getnivel (get_class($this));//Verifica qual nível de acesso do usuário
        }
        if ($this->nivel_sistema>=80)
        {
            //echo $this->nivel_sistema;
    
            // create the form fields
            if (empty($this->campos))
            {
                $fields = self::getConfig();
            }
            else
            {
                $fields = $this->campos;
            }
            $part = array();
            foreach ($fields as $field)
            {
                switch ($field['type'])
                {
                        case 'C':
                            $item = self::defineCombo($field['value_combo']);
                            if (array_key_exists('banco',$item)==false)
                            {
                                $part[$field['name']] = new TCombo($field['name']);
                                $part[$field['name']]->addItems($item);
                                $part[$field['name']]->setValue($field['value']);
                            }
                            else
                            {
                                if (is_array($item['criteria']))
                                {
                                    $criteria = new TCriteria;
                                    $cri = $item['criteria'];
                                    $criteria->add(new TFilter($cri[0], $cri[1], $cri[2]));
                                    $part[$field['name']] = new TDBCombo($field['name'],$item['banco'],$item['model'],$item['id'],$item['show'],$item['index'],$criteria);
    
                                }
                                else
                                {
                                    $part[$field['name']] = new TDBCombo($field['name'],$item['banco'],$item['model'],$item['id'],$item['show'],$item['index']);
                                }
                                $part[$field['name']]->setValue($field['value']);
                                
                            }
                            break;
                        case 'S':
                            $item = self::defineCombo($field['value_combo']);
                            if (array_key_exists('banco',$item)==false)
                            {
                                $part[$field['name']] = new TSelect($field['name']);
                                $part[$field['name']]->addItems($item);
                                $part[$field['name']]->setValue($field['value']);
                            }
                            else
                            {
                                if (is_array($item['criteria']))
                                {
                                    $criteria = new TCriteria;
                                    $cri = $item['criteria'];
                                    $criteria->add(new TFilter($cri[0], $cri[1], $cri[2]));
                                    $part[$field['name']] = new TDBSelect($field['name'],$item[1],$item[2],$item[3],$item[4],$item[5],$criteria);
    
                                }
                                else
                                {
                                    $part[$field['name']] = new TDBSelect($field['name'],$item[1],$item[2],$item[3],$item[4],$item[5]);
                                }
                                $part[$field['name']]->setValue($field['value']);
                                
                            }
                            break;
                        case 'D':
                            $part[$field['name']] = new TDate($field['name']);
                            $d = TDate::date2br($field['value']);
                            $part[$field['name']]->setValue($d);
                            $part[$field['name']]->setMask('dd/mm/yyyy');
    
                            break;
                        default:
                            $part[$field['name']] = new TEntry($field['name']);
                            $part[$field['name']]->setValue($field['value']);
                            break;
                }
                $part[$field['name']]->setTip($field['tip']);
                $part[$field['name']]->setSize($field['tamanho']);
                $this->form->addQuickField($field['label'], $part[$field['name']],  $field['tamanho'] );
            }
            
            //Botão Gera Escala Ordinária
            $runSave = new TButton('runOrd');
            $runSave->setImage('fa:floppy-o red');
            $runSave->class = 'btn btn-primary btn-sm';
            $save = new TAction(array($this, 'onSave'));//Troca o tipo de serviço e seus dados
            $runSave->setAction($save);
            $runSave->setLabel('Salva');
            $this->form->add($runSave);
            
            // add the fields
    
            //Valores dos Itens
            
            //Ações
    
           
            //$this->form->setFields($part);            //Inclui os campos no formulário
            $this->form->setFields(array($runSave));  //Inclui os comandos no formulário
            // vertical box container
            $container = new TVBox;
            $container->style = 'width: 90%';
            $container->add(new TXMLBreadCrumb('menu.xml', 'bdhConfigForm'));
            $container->add($this->form);
            
            parent::add($container);
        }
        else
        {
            new TMessage ('info','Seu nível de Acesso não permite entrar nesta Serviço.');
        }
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function onSave( $param )
    {
        
        $data = (object) $param; // get form data as array
        //print_r($param);
        try
        {
            TTransaction::open('sicad'); // open a transaction
            $campos = $this->campos;
            foreach ($campos as $campo)
            {
                $object = new configura($campo['id']);
                //$id = array_search($campo['name'],$param);
                if ($campo['type']=='D')
                {
                    $object->value = TDate::date2us($param[$campo['name']]);
                }
                else if ($campo['type']=='S')
                {
                    $object->value = implode('/=/',$param[$campo['name']]);
                }
                else
                {
                    $object->value = $param[$campo['name']];
                }
                TTransaction::log("Atualizando Configuração...".$this->sistema); 
                $object->store();
            }
            TTransaction::close(); // close the transaction
            TForm::sendData('form_config', $data); // Recarrega form
            new TMessage('info', 'As configurações foram alteradas');
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TForm::sendData('form_config', $data); // Recarrega form
            TTransaction::rollback(); // undo all pending operations
        }
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function onReload ($param)
    {

    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function show()
    {
        // check if the datagrid is already loaded
        if (!$this->loaded AND (!isset($_GET['method']) OR $_GET['method'] !== 'onReload') )
        {
            $this->onReload( func_get_arg(0) );
        }
        parent::show();
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function onReturn($param)
    {
        TApplication::loadPage('bdhManagerForm');
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function getConfig ($param=null)
    {
        try
        {
            if (empty($this->campos))
            {
                TTransaction::open('sicad');
                $sql = "SELECT DISTINCT * FROM g_geral.configura WHERE dominio='".$this->sistema."' AND ativo='S' AND visivel='S';";
                $conn = TTransaction::get(); 
                $res = $conn->prepare($sql);
                $res->execute();
                $res->setFetchMode(PDO::FETCH_NAMED);
                $this->campos = $res->fetchAll();
                //var_dump($this->campos);
                TTransaction::close();
            }  
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->campos = ''; // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
        return $this->campos;
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function defineCombo ($param=null)
    {
        $arr = explode('/', $param); // transforma a string em array.
        $fer = new TFerramentas();
        switch ($arr[0])
        {
            case 'BANCO':
                $cri = (array_key_exists(6,$arr)) ? explode('][',$arr[6]) : '';
                $arrN = array ('banco'=>$arr[1],'model'=>$arr[2],'id'=>$arr[3],'show'=>$arr[4],'index'=>$arr[5],'criteria'=>$cri);
                break;
            case 'MÊS':
                $arrN = $fer->lista_meses();
                break;
            case 'ANO':
                $arrN = $fer->lista_anos();
                break;
            case 'SEMANA':
                $arrN = $fer->lista_semana();
                break;
            case 'SIM-NÃO':
                $arrN = $fer->lista_sim_nao();
                break;
            case 'NIVELACESSO':
                $arrN = $fer->lista_nivel_acesso();
                break;
            default:
                $arrN = array();
                foreach($arr as $item)
                {
                    $valor = explode('=>', $item); // quebra o elemento atual em um array com duas posições,
                                                  // onde o indice zero é a chave e o um o valor em $arrN
                    $arrN[$valor[0]] = $valor[1];
                }
                break;
        }
        return $arrN;
    }//Fim Módulo
}//Fim Classe
