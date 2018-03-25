<?php
/**
 * backup Form
 * @author  <your name here>
 */
class backupForm extends TPage
{
    protected $form; // form
    protected $detail_list;
    protected $formFields;
    protected $schemas=array();
    
    const path = 'files/backup/';
    
    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();
        
        // creates the form
        $this->form = new TQuickForm('form_Backup');
        $this->form->class = 'tform'; // change CSS class
        $this->form->style = 'display: table;width:50%'; // change style
        // define the form title
        $this->form->setFormTitle('Backup de Banco de Dados');
        // create the form fields
        $schema   = new TCombo('schema');
        $file     = new TEntry('file');
        $drop     = new TCombo('drop');
        $insert   = new TCombo('insert');
        $tipo     = new TCombo('tipo');
        $lista = self::listagemSchema ();//Cria uma lista de schemas do BD para uso
        if ($lista)
        {
            $schema->addItems($lista);
        }
        else
        {
            $schema->addItems(array('id'=>'--- Nenhum Schema Localizado ---'));
        }
        
        //Define valores padrão
        $item = array();
        $item['f'] = 'Não';
        $item['t'] = 'Sim';
        $drop->addItems($item);
        $insert->addItems($item);
        
        $item = array();
        $item['AMBOS']     = 'Backup de Estrutura e dos Dados (Recomendando)';
        $item['DADOS']     = 'Backup dos Dados SOMENTE.';
        $item['ESTRUTURA'] = 'Backup das Estruturas SOMENTE.';
        $tipo->addItems($item);
        
        //Valores Padrão
        $drop->setValue('t');
        $insert->setValue('t');
        $tipo->setValue('AMBOS');
        
        //Define Tits
        $schema->setTip("Escolha um dos esquemas disponíveis para fazer cópia de segurança.");
        $file->setTip("Dê um nome para o arquivo sem a extensão. Caso deixe em branco, será adotado o nome do Esquema por padrão.");
        $drop->setTip("Marque com sim se deseja que, antes de criar uma tabela ou seqüência, o backup DROP (delete) a mesma. (Recomendando)");
        $insert->setTip("Marque com sim se deseja que as clausulas INSERT saiam com o formato completo. (Recomendado)");
        
        
        // add the fields
        $this->form->addQuickField('Escolha o Esquema:', $schema,  200, new TRequiredValidator );
        $this->form->addQuickField('Nome do Arquivo:', $file,  400 );
        $this->form->addQuickField('Tipo de Backup:', $tipo,  200 );
        $this->form->addQuickField('Insert Completo?:', $insert,  80 );
        $this->form->addQuickField('Drop antes de Criar?:', $drop,  80 );
        // create the form actions
        $this->form->addQuickAction('Backup', new TAction(array($this, 'onBackup')), 'fa:floppy-o');

        $this->detail_list = new TQuickGrid;
        $this->detail_list->setHeight( 200 );
        $this->detail_list->makeScrollable();
        $this->detail_list->disableDefaultClick();
        $this->detail_list->addQuickColumn('', 'restaure', 'center', 90);
        $this->detail_list->addQuickColumn('', 'download', 'center', 90);
        $this->detail_list->addQuickColumn('', 'delete', 'center', 90);
        
        // items
        $this->detail_list->addQuickColumn('Nome do Arquivo', 'nome', 'center', 300);
        $this->detail_list->createModel();
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add(TPanelGroup::pack('', $this->detail_list));        

        parent::add($container);
        if (!$this->loaded)
        {
            self::onReload();
        }
        
    }//Fim Módulo
/*--------------------------------------------------------------------------------
 *    FUNÇÃO: Realiza o backup do schema escolhido
 *--------------------------------------------------------------------------------*/
    public function onBackup( $param )
    {
        try
        {
            $this->form->validate(); // validate form data
            $reload = $this->form->getData(); // get form data as array
            $data = new StdClass;
            $data->file   = $param['file'];
            $data->schema = $param['schema'];
            $data->file = (!$data->file) ? $data->schema : $data->file;//Define o nome do arquivo
            
            $backup = new TBackupRestore ("","","","");
            $backup->Header        = "-- Controle e Gerenciamento de Escalas - Backup      --\n".
                     "-- Módulo desenvolvido por: Fernando de Pinho Araújo --\n";
            $backup->DataOnly      = ($param['tipo']!='DADOS') ? false : true;
            $backup->StructureOnly = ($param['tipo']!='ESTRUTURA') ? false : true;
            $backup->UseDropTable       = ($param['drop']=='t') ? true : false;
            $backup->UseCompleteInsert  = ($param['insert']=='t') ? true : false;
            $result = $backup->Backup($data->file,$data->schema);
            $this->form->setData($reload); // fill form data
            new TMessage('info', 'Backup do esquema '.$data->schema.' realizado com sucesso...');

        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
        }
        self::onReload();
    }//Fim Módulo
/*--------------------------------------------------------------------------------
 *    FUNÇÃO: Limpa Formulário
 *--------------------------------------------------------------------------------*/
    public function onClear( $param )
    {
        $this->form->clear(TRUE);
        self::onReload();
    }
    
    /**
     * Load object to form data
     * @param $param Request
     */
    public function onEdit( $param )
    {
        try
        {
            self::onReload();
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }//Fim Módulo
/*--------------------------------------------------------------------------------
 *    FUNÇÃO: Monta uma lista com os esquemas de tabelas do BD
 *--------------------------------------------------------------------------------*/
     public function listagemSchema ($param = null)
     {
        $banco = ($param) ? $param : 'sicad';
        if ($this->schemas) 
        {
            return $this->schemas;
        }
        try
        {
            TTransaction::open($banco); // open a transaction
            $conn = TTransaction::get();
            $SQL = "SELECT DISTINCT n.nspname as schemaname FROM pg_class as c ".
                    "LEFT JOIN pg_namespace n ON n.oid = c.relnamespace WHERE relkind IN ('r')AND ".
                    "relname NOT LIKE 'pg_%' AND relname NOT LIKE 'sql_%' ORDER BY schemaname;";
            $result = $conn->prepare($SQL);
                
            $result->execute();
            $lsts = $result->fetchAll();
            TTransaction::close();
            $lista = array();
            foreach ($lsts as $lst)
            {
                $lista [$lst[0]] = $lst[0];
            }
            $this->schemas = $lista;
            return $lista;
         }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
            return false;
        }
     }//Fim Módulo
/*--------------------------------------------------------------------------------
 *    FUNÇÃO: 
 *--------------------------------------------------------------------------------*/
     public function onReload ($param = null)
     {
        $ci = new TFerramentas();
        $items = $ci->getDiretorio(self::path);
        
        $this->detail_list->clear(); // clear detail list
        
        if ($items)
        {
            $cont = 1;
            foreach ($items as $list_item_key => $list_item)
            {
                $item_name = 'prod_' . $cont++;
                $item = new StdClass;
                
                // create action buttons
                $action_del = new TAction(array($this, 'onDelete'));
                $action_del->setParameter('nome', $list_item['nome']);
                
                $action_res = new TAction(array($this, 'onRestore'));
                $action_res->setParameter('nome', $list_item['nome']);
                
                $action_dow = new TAction(array($this, 'onDownload'));
                $action_dow->setParameter('nome', $list_item['nome']);
                
                $button_del = new TButton('delete_'.$cont);
                $button_del->class = 'btn btn-default btn-sm';
                $button_del->setAction( $action_del, 'Apaga' );
                $button_del->setImage('fa:trash-o red fa-lg');
                $button_del->setTip('Clique para deletar o arquivo de backup ');
                
                $button_res = new TButton('restaure_'.$cont);
                $button_res->class = 'btn btn-default btn-sm';
                $button_res->setAction( $action_res, 'Restaura' );
                $button_res->setImage('fa:upload blue fa-lg');
                $button_res->setTip('Clique para restaurar o Schemma de dados contido no arquivo '.$list_item['nome']);
                
                $button_dow = new TButton('download_'.$cont);
                $button_dow->class = 'btn btn-default btn-sm';
                $button_dow->setAction( $action_dow, 'Download' );
                $button_dow->setImage('fa:download blue fa-lg');
                $button_dow->setTip('Clique para fazer download do arquivo '.$list_item['nome']);
                
                $item->restaure   = $button_res;
                $item->download   = $button_dow;
                $item->delete     = $button_del;
                
                $this->formFields[ $item_name.'_restaure' ] = $item->restaure;
                $this->formFields[ $item_name.'_download' ] = $item->download;
                $this->formFields[ $item_name.'_delete' ]   = $item->delete;
                
                // items
                $item->nome = $list_item['nome'];
                
                $row = $this->detail_list->addItem( $item );
                $row->onmouseover='';
                $row->onmouseout='';
            }

            $this->form->setFields( $this->formFields );
        }
        
        $this->loaded = TRUE;

     }//Fim Módulo
/*--------------------------------------------------------------------------------
 *    FUNÇÃO: Confirma a restauração de um arquivo de backup
 *--------------------------------------------------------------------------------*/
      public function onRestore ($param)
     {
        // define ação de restaurar
        $action = new TAction(array($this, 'Restaura'));
        $action->setParameters($param); // pass the key parameter ahead
        
        // shows a dialog to the user
        new TQuestion('Deseja restaurar este backup ?', $action);
     }//Fim Módulo
/*--------------------------------------------------------------------------------
 *    FUNÇÃO: Executa restauração
 *--------------------------------------------------------------------------------*/
     public function Restaura ($param)
     {
        try
        {
            $fl = explode('/',$param['nome']);
            $filename = end($fl);
            $backup = new TBackupRestore ("","","","");
            $backup->DataOnly      = false;
            $backup->StructureOnly = false;
            $backup->UseDropTable  = true;
            $backup->UseCompleteInsert = true;
            $result = $backup->restore($param['nome']);
            new TMessage('info', 'Backup Restaurado com Sucesso!!!');
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
        }
        self::onReload();
     }//Fim Módulo
/*--------------------------------------------------------------------------------
 *    FUNÇÃO: Deletar um arquivo de backup
 *--------------------------------------------------------------------------------*/
     public function onDelete ($param)
     {
        // define the delete action
        $action = new TAction(array($this, 'Delete'));
        $action->setParameters($param); // pass the key parameter ahead
        
        // shows a dialog to the user
        new TQuestion('Deseja apagar realmente este backup ?', $action);
     }//Fim Módulo
/*--------------------------------------------------------------------------------
 *    FUNÇÃO: Deletar um arquivo de backup
 *--------------------------------------------------------------------------------*/
     public function Delete ($param)
     {
        if (!unlink($param['nome']))
        {
          new TMessage('error', 'Falha ao deletar o arquivo '.$param['nome'].'!!!'); // shows the exception error message
        }
        else
        {
          new TMessage('info', 'O Arquivo '.$param['nome'].' foi deletado com sucesso!'); // shows the exception error message
        }
        self::onReload();
     }//Fim Módulo
/*--------------------------------------------------------------------------------
 *    FUNÇÃO: Download um arquivo de backup
 *--------------------------------------------------------------------------------*/
     public function onDownload ($param)
     {
        TPage::openFile($param['nome']);
     }//Fim Módulo

}//Fim Classe
