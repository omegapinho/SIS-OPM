<?php
/*
 * pgBackupRestore v2.1 
 * Date: 30th November 2007
 * Author: Michele Brodoloni <michele.brodoloni@xtnet.it>
 * update: Fernando de Pinho Araújo <o.megapinho@gmail.com>
 * Changelog:
 * - Fixed issue with bytea fields
 * - Fixed issue with empty values in NOT NULL fields
 * - Added custom header
 * - Added 2 more options to backup data preserving database structure (DataOnly, UseTruncateTable)
 * - Added some default statements included in every backup file (~ line 227)
 * - Added encoding support 
 * - Improved error checking
 * >>> Updates: para Versão 2.1 <<<
 * - Busca pelos esquemas da tabela (assim a busca por dados se efetiva)
 * - Otimização das variáveis
 * - 
 */

class TBackupRestore
{
   //------------------------------------//
   //---[  Configuration variables   ]---//
   //---| SET THEM FROM YOUR SCRIPT  |---//
   //---| Defina-os no seu código    |---//
   //------------------------------------//
  
   // Header to be written on file
   // Cabeçalho do back-up
   var $Header = "";

   // Remove comments from SQL file ( pgBackupRestore::commentSQL() method )
   // Remove os comentários do arquivo backup
   var $StripComments = false;

   // Include table names into INSERT statement
   // Acrescenta o nome da tabela na inserção de valores
   var $UseCompleteInsert = true;

   // Drop the table before re-creating it
   // Remove a tabela antes de recria-la
   var $UseDropTable = false;

   // Adds TRUNCATE TABLE statement (for data only dump)
   // Adiciona a instrução Truncate Table (somente para dataonly = true)
   var $UseTruncateTable = false;

   // Dump table structure only, not data
   // Copia só estrutura
   var $StructureOnly = false;

   // Dump only table data without structure
   // Copia somente os dados
   var $DataOnly = true;

   // Script keeps running after encountering a fatal error
   // Pula os erros fatais
   var $IgnoreFatalErrors = false;

   // Database Encoding 
   // (Supported are: SQL_ASCII and UTF8. Unknown behaviour with others.)
   // Codificação da tabela (default = SQL_ASCII)
   var $Encoding = "UTF8";
   
   //Pasta para backup
   var $target_path   = 'files/backup/';
   
   var $squema        = false;
   
   var $extension = ".sql";
   
   var $idNumeric = false;

   //------------------------------------//
   //---| NO NEED TO EDIT BELOW HERE |---//
   //------------------------------------//

   //---[ File related variables
   var $fpSQL;

   //---[ Database related variables   
   var $Connected = false;
   var $Database;
   var $Link_ID;
   var $Query_ID;
   var $Record  = array();
   var $Tables  = array();
   var $Schemas = array();
   var $grupo   = array();
   var $BackupOnlyTables = array();
   var $ExcludeTables = array();
   var $Row = 0;
   
   //---[ Error Handling
   var $GotSQLerror = false;
   var $LastSQLerror = "";

   //---[ Protected keywords
   var $pKeywords = array("desc");

/*--------------------------------------------------------------------------------
 *            CLASSE CONSTRUTORA
 *--------------------------------------------------------------------------------*/
   public function __construct ($uiHost, $uiUser, $uiPassword, $uiDatabase = 'backup', $uiPort = 5432)
   {
        try 
        {
            TTransaction::open('sicad'); // open a transaction
            $DBinfo     = TTransaction::getDatabaseInfo();
            $uiHost     = $DBinfo['host'];
            $uiUser     = $DBinfo['user'];
            $uiPassword = $DBinfo['pass'];
            $uiDatabase = $DBinfo['name'];
            TTransaction::close();            
            $this->Link_ID = pg_pconnect("host=${uiHost} port=${uiPort} dbname=${uiDatabase} user=${uiUser} password=${uiPassword}");
            if (!$this->Link_ID)
            {
                throw new Exception ('Não foi possível conectar com o banco de dados');
            }
            $this->Database = $uiDatabase;//Também é o nome do arquivo a ser gerado.
            $this->Connected = ($this->Link_ID) ? true : false;
            pg_set_client_encoding($this->Link_ID, $this->Encoding);

        }
        catch (Exception $e) // in case of exception
        {
            $this->Database = '';
            $this->Connected = false;
            new TMessage('error', $e->getMessage()); // shows the exception error message
        }
   }//Fim Módulo
/*--------------------------------------------------------------------------------
 *            Fixa Opções incompatíveis
 *--------------------------------------------------------------------------------*/
   function _FixOptions()
   {
      // Checks and fix for incompatible options
      if ($this->StructureOnly==true)
      {
          $this->DataOnly = false;
          $this->UseTruncateTable = false;
      }

      if ($this->DataOnly==true)
      {
         $this->StructureOnly = false;
         $this->UseDropTable = false;
      }
   }//Fim Módulo
/*--------------------------------------------------------------------------------
 *           Funções de query no BD 
 *--------------------------------------------------------------------------------*/
/*--------------------------------------------------------------------------------
 *    FUNÇÃO: Realiza um query no BD e retorna o resultado armazenado em Query_ID       
 *--------------------------------------------------------------------------------*/ 
   // Queries the PostgreSQL database.
   // If a SQL error is encountered it will be written on 
   // $this->LastSQLerror variable and $this->GotSQLerror 
   // will be set to TRUE. Returns the query id.
   //
   function query($uiSQL)
   {
      try
      {
          if (!$this->Connected) return (false);
          $this->Row = 0;
          $this->Query_ID = @pg_query($this->Link_ID, $uiSQL);
          $this->LastSQLerror = trim(str_replace("ERROR:", "", pg_last_error($this->Link_ID)));
          $this->GotSQLerror = ($this->LastSQLerror) ? true : false;
          return $this->Query_ID;
      }
      catch (Exception $e) // in case of exception
      {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->LastSQLerror = trim(str_replace("ERROR:", "", pg_last_error($this->Link_ID)));
            $this->GotSQLerror = ($this->LastSQLerror) ? true : false;
      }
   }//Fim Módulo
/*--------------------------------------------------------------------------------
 *    FUNÇÃO: Retorna o próximo registro do rol de resultados
 *--------------------------------------------------------------------------------*/
   // Returns the next record of a query resultset.
   // Values can be accessed through $this->Record[field_name]
   // or by $this->Record[field_id] (see pg_fetch_array())
   //
   function next_record()
   {
      if (!$this->Query_ID) return (false);

      $this->Record = @pg_fetch_array($this->Query_ID, $this->Row++);
      if (is_array($this->Record)) 
         return(true);
      else 
      {      
         pg_free_result($this->Query_ID);
         $this->Query_ID = 0;
         return(false);
      }
   }//Fim Módulo
/*--------------------------------------------------------------------------------
 *    FUNÇÃO: Retorna um valor específico de um registro
 *--------------------------------------------------------------------------------*/
   // Returns a value from a record.
   // Just pass the wanted field name to this.
   //
   function get($uiField)
   {
      if (is_array($this->Record) && array_key_exists($uiField, $this->Record))
         return $this->Record[$uiField];
      else
         return (NULL);
   }//Fim Módulo
/*--------------------------------------------------------------------------------
 *    FUNÇÃO: Retorna um array com a lista de campos de uma tabela 
 *--------------------------------------------------------------------------------*/
   // Returns an array containing the field names
   // returned by a query. 
   // Useful when doing a "SELECT * FROM table" query
   //
   function field_names()
   {
      if (!$this->Query_ID) return(false);
      $n = @pg_num_fields($this->Query_ID);
      $columns = Array();

      for ($i=0; $i<$n ; $i++ )
         $columns[] = @pg_field_name($this->Query_ID, $i);

      return $columns;
   }//Fim Módulo
/*--------------------------------------------------------------------------------
 *    FUNÇÃO: Retorna uma string entre aspas se esta estiver na array pKeywords
 *--------------------------------------------------------------------------------*/
   // Return a quoted string if the $this->pKeywords array
   // contains it. It is used when a table name match
   // a PostgreSQL keyword such as "DESC", "PRIMARY"
   // and others, causing a SQL syntax error when restoring
   //
   function escape_keyword($uiKeyword)
   {
      if (in_array($uiKeyword, $this->pKeywords))
         return('"'.$uiKeyword.'"');
      else
         return($uiKeyword);
   }//Fim Módulo
/*--------------------------------------------------------------------------------
 *    Funções diversas 
 *--------------------------------------------------------------------------------*/
/*--------------------------------------------------------------------------------
 *    FUNÇÃO: Escreve um dado no arquivo de backup
 *--------------------------------------------------------------------------------*/
   // Writes text into the SQL file
   // Called within $this->Backup() method.
   //
   function writeSQL($uiString)
   {
      if (!$this->fpSQL) return(false);
      fwrite($this->fpSQL, $uiString);
   }//Fim Módulo
/*--------------------------------------------------------------------------------
 *    FUNÇÃO: Escreve comentário no arquivo de backup 
 *--------------------------------------------------------------------------------*/
   // Writes comments into the SQL file when
   // $this->StripComments is set to FALSE
   // Called within $this->Backup() method.
   // 
   function commentSQL($uiComment)
   {
      if (!$this->fpSQL) return(false);

      if (!$this->StripComments)
         $this->writeSQL("-- $uiComment");
   }

   // Creates a SQL file containing structure, data, indexes
   // relationships, sequences and so on..
   //
/*-------------------------------------------------------------------------------
 *   Função Back - up
 *-------------------------------------------------------------------------------*/
   public function Backup($uiFilename = NULL, $uiSchema = NULL)
   {
      set_time_limit (15 * 60);//Define o prazo de espera antes de acusar erro.
      $strSQL = "";
      if (!$this->Connected) return (false);//Verifica se há conecção.
      self::checaPath ();// Verifica se a path para backup já existe, se não a cria.
      if (is_null($uiFilename))
      {
         $this->Filename = $this->target_path.$this->Database.$this->extension;//define o nome do arquivo para backup genérico
      }
      else
      {
         $this->Filename = $this->target_path.$uiFilename.$this->extension;//define o nome do arquivo para backup com a definição do usuário
      }
      $this->_FixOptions();//Corrige opções incompativeis
// --- 1 Passo: Abre o Arquivo para escrita
      $this->fpSQL = @fopen($this->Filename, "w");//Abre o arquivo para escrita
      if (!$this->fpSQL) 
      {
         $this->Error("Não posso abrir o arquivo ". $this->Filename ." para escrita!", true);
      }
 // Grava o cabeçalho se ele não for vazio
      if(!empty($this->Header))
      {
          $this->writeSQL($this->Header."\n");
      }
// --- 1.1 Passo: Grava o Cabeçalho e as definições iniciais
      // Define as opções padrão
      $this->commentSQL("Default options\n");
      $this->writeSQL("SET client_encoding = '".$this->Encoding."';\n");
      $this->writeSQL("SET standard_conforming_strings = off;\n");
      $this->writeSQL("SET check_function_bodies = false;\n");
      $this->writeSQL("SET client_min_messages = warning;\n");
      $this->writeSQL("SET escape_string_warning = off;\n");
      $this->writeSQL("\n");
// --- 2 Passo: Obtem a lista de tabelas do BD
      // If the tables array is not empy, it means that
      // the method $this->BackupOnlyTables was used
      if (empty($this->Tables))
      {
         if ($uiSchema!=NULL)
         {
             $SQL = "SELECT relname as tablename, n.nspname as schemaname FROM pg_class as c ".
                     "LEFT JOIN pg_namespace n ON n.oid = c.relnamespace WHERE relkind IN ('r')AND ".
                     "relname NOT LIKE 'pg_%' AND relname NOT LIKE 'sql_%' AND ".
                     "n.nspname ='".$uiSchema."' ORDER BY relname;";
         }
         else
         {
             $SQL = "SELECT relname as tablename, n.nspname as schemaname FROM pg_class as c ".
                     "LEFT JOIN pg_namespace n ON n.oid = c.relnamespace WHERE relkind IN ('r')AND ".
                     "relname NOT LIKE 'pg_%' AND relname NOT LIKE 'sql_%' ORDER BY relname;";
         }
         $this->query($SQL);
//Verifica se a tabela não está no rol das excluídas e a remove 
         while ($this->next_record())
         {
            $Table  = $this->get("tablename");
            $Schema = $this->get("schemaname");
            if (!in_array($Table, $this->ExcludeTables))
            {
               $this->Tables[]  = $this->escape_keyword($Table);
               $this->Schemas[] = $this->escape_keyword($Schema);
               $this->grupo[$this->escape_keyword($Table)]   = $this->escape_keyword($Schema);
            }
 
         }
      } 
// --- 3 Passo: Gerando a estruturas de cada tabela
      foreach($this->Tables as $Table)
      {
         // Use DROP TABLE statement before INSERT ?
         if ($this->UseDropTable==true)
            $this->writeSQL("DROP TABLE ".$Schema.".${Table} CASCADE;\n");
         elseif ($this->UseTruncateTable==true)
            $this->writeSQL("TRUNCATE TABLE ".$Schema.".${Table};\n");
         
         //Cria a estrutura das tabelas se não for para pegar só os dados
         if ($this->DataOnly==false || $this->StructureOnly==true) 
         {
            $_sequences = array();
            
            $this->commentSQL("Structure for table '".$Schema.".${Table}'\n");
            if (!isset($strSQL))
            {
                $strSQL = '';
            }
            $strSQL .= "CREATE TABLE ".$Schema.".${Table} (";
         
            $SQL = "SELECT attnum, attname, typname, atttypmod-4 AS atttypmod, attnotnull, atthasdef, adsrc AS def\n".
                   "FROM pg_attribute, pg_class, pg_type, pg_attrdef\n".
                   "WHERE pg_class.oid=attrelid\n".
                   "AND pg_type.oid=atttypid AND attnum>0 AND pg_class.oid=adrelid AND adnum=attnum\n".
                   "AND atthasdef='t' AND lower(relname)='${Table}' UNION\n".
                   "SELECT attnum, attname, typname, atttypmod-4 AS atttypmod, attnotnull, atthasdef, '' AS def\n".
                   "FROM pg_attribute, pg_class, pg_type WHERE pg_class.oid=attrelid\n".
                   "AND pg_type.oid=atttypid AND attnum>0 AND atthasdef='f' AND lower(relname)='${Table}'\n";
            $this->query($SQL);
            while ( $this->next_record() )
            {
               $_attnum     = $this->get('attnum');
               $_attname    = $this->escape_keyword( $this->get('attname') );
               $_typname    = $this->get('typname');
               $_atttypmod  = $this->get('atttypmod'); 
               $_attnotnull = $this->get('attnotnull');
               $_atthasdef  = $this->get('atthasdef');
               $_def        = $this->get('def');     

               if (preg_match("/^nextval/", $_def))
               {
                  $_t = explode("'", $_def);
                  if (substr($_t[1],0,strlen($Schema))!=$Schema)
                  {
                      $_sequences[] =  $Schema.'.'.$_t[1];//Acrescenta o nome do Schemma
                      $_def = str_replace($_t[1],$Schema.'.'.$_t[1],$_def);
                  }
                  else
                  {
                      $_sequences[] = $_t[1];
                  } 
               }

               $strSQL .= "${_attname} ${_typname}";
               if ($_typname == "varchar") $strSQL .= "(${_atttypmod})";
               if ($_attnotnull == "t")    $strSQL .= " NOT NULL";
               if ($_atthasdef == "t")     $strSQL .= " DEFAULT ${_def}";
               $strSQL .= ","; 
            }
            $strSQL  = rtrim($strSQL, ",");
            $strSQL .= ");\n";

// --- 3.1 Passo: Cria as sequencias
            if ($_sequences)
            {
               foreach($_sequences as $_seq_name)
               {
                  $SQL = "SELECT * FROM ".$Schema."${_seq_name}\n";
                  $this->query($SQL);
                  $this->next_record();
                  
                  $_incrementby = ($this->get('increment_by')) ? $this->get('increment_by') : '1';
                  $_minvalue    = ($this->get('min_value')) ? $this->get('min_value') : '1';
                  $_maxvalue    = ($this->get('max_value')) ? 'MAXVALUE '.$this->get('max_value') : '';
                  $_lastvalue   = ($this->get('last_value')) ? 'START '.$this->get('last_value') : '';
                  $_cachevalue  = ($this->get('cache_value')) ? 'CACHE '.$this->get('cache_value') : '';

                  if ($this->UseDropTable==true)
                      $this->writeSQL("DROP SEQUENCE ${_seq_name} CASCADE;\n");
                  $this->writeSQL("CREATE SEQUENCE ${_seq_name} INCREMENT BY ${_incrementby} MINVALUE ${_minvalue} ".
                                  "${_maxvalue} ${_lastvalue} ${_cachevalue};\n");
              }
            }
            $this->writeSQL($strSQL);
         }
         //Gera dados da tabelas
         $alerta = (substr($Table,0,8) == 'arquivos') ? true : false;
         if (($this->StructureOnly==false || $this->DataOnly==true))
         {  
            $field_attribs = array();
// --- 4 Passo: Cria a os Dados para Insert nas Tabelas
            $this->commentSQL("Data for table '${Table}'\n");
// --- 4.1 Passo: Pega os atributos dos campos para verificar se são nulos ou bytea 
            $tmpschema = $this->grupo[$Table];
            $SQL = "SELECT * FROM ".$tmpschema.".${Table} LIMIT 0;\n";
            $this->query($SQL);
            $fields = $this->field_names();
            if($fields)
            {
                foreach ($fields as $Field)
                {
                   $field_attribs[$Field] = $this->GetFieldInfo($Table, $Field);
                }
            }
// --- 4.1 Passo: Fim
            $SQL = "SELECT * FROM ".$tmpschema.".${Table}\n";
            if ($alerta == true)
            {
                $SQL = "SELECT * FROM ".$tmpschema.".${Table} WHERE id<1\n";
            }
            $this->query($SQL);
            while ( $this->next_record() )
            {
               $Record = array();
               foreach($fields as $f)
               {
                  $data = $this->get($f);
                  if ($field_attribs[$f]['is_binary'])
                  {  // Binary Data
                     $Record[$f] = addcslashes(pg_escape_bytea($data),"\$");
                  }
                  else
                  {  // Strings
                     $data = preg_replace("/\x0a/", "", $data);
                     $data = preg_replace("/\x0d/", "\r", $data);
                     $Record[$f] = pg_escape_string(trim($data));
                  }
               }
               $FieldNames = ($this->UseCompleteInsert) ?  "(".implode(",",$fields).")" : "";
               
               $strSQL = "INSERT INTO ".$Schema.".${Table}${FieldNames} VALUES({". (implode("},{",$fields))."});";
               foreach($fields as $f)
               {
                  //print_r ($field_attribs[$f]);
                  if ($Record[$f] != '')
                  {
                      if (strtoupper($f) == 'ID' || strpos("_ID",strtoupper($f)) || strpos("ID_",strtoupper($f)))
                      {
                          $str = ($this->idNumeric) ? $Record[$f] : sprintf("'%s'", $Record[$f]);
                      }
                      else
                      {
                         $str = sprintf("'%s'", $Record[$f]);
                      }
                  }
                  else
                  {
                     $str = ($field_attribs[$f]['not_null']) ? "''" : "NULL";
                  }
                     
 
                  $strSQL = preg_replace("/{".$f."}/", $str, $strSQL);
               }
               //echo $strSQL;
               $this->writeSQL($strSQL."\n");
               unset($strSQL);
            }
         }
         if ($this->DataOnly!=true)
         {
// --- 5 Passo: Cria os Indexes (Primary Key)
            $this->commentSQL("Indexes for table '${Table}'\n");
            $SQL = "SELECT pg_index.indisprimary, pg_catalog.pg_get_indexdef(pg_index.indexrelid)\n".
                   "FROM pg_catalog.pg_class c, pg_catalog.pg_class c2, pg_catalog.pg_index AS pg_index\n".
                   "WHERE c.relname = '${Table}'\n".
                   "AND c.oid = pg_index.indrelid\n".
                   "AND pg_index.indexrelid = c2.oid\n";
            $this->query($SQL);
            while ( $this->next_record() )
            {
               $_pggetindexdef = $this->get('pg_get_indexdef');
               $_indisprimary = $this->get('indisprimary');

               if (preg_match("/^CREATE UNIQUE INDEX/i", $_pggetindexdef))
               {
                  $_keyword = ($_indisprimary == 't') ? 'PRIMARY KEY' : 'UNIQUE';
                  $strSQL = str_replace("CREATE UNIQUE INDEX", "" , $this->get('pg_get_indexdef'));
                  $strSQL = str_replace("USING btree", "|", $strSQL);
                  $strSQL = str_replace("ON", "|", $strSQL);
                  $strSQL = str_replace("\x20","", $strSQL);
                  list($_pkey, $_tablename, $_fieldname) = explode("|", $strSQL);
                  $this->writeSQL("ALTER TABLE ONLY ${_tablename} ADD CONSTRAINT ${_pkey} ${_keyword} ${_fieldname};\n");
                  unset($strSQL);
               } 
               else $this->writeSQL("${_pggetindexdef};\n");
            }
         }
      }
// --- 6 Passo: Cria relacionamentos (Chave Estrangeiras)
    $this->commentSQL("Relationships for tables...\n");
 
    $SQL = "SELECT cl.relname AS table, ct.conname, pg_get_constraintdef(ct.oid), n.nspname as schemaname\n".
           "FROM pg_catalog.pg_attribute a\n".
           "JOIN pg_catalog.pg_class cl ON (a.attrelid = cl.oid AND cl.relkind = 'r')\n".
           "JOIN pg_catalog.pg_namespace n ON (n.oid = cl.relnamespace)\n".
           "JOIN pg_catalog.pg_constraint ct ON (a.attrelid = ct.conrelid AND ct.confrelid != 0 AND ct.conkey[1] = a.attnum)\n".
           "JOIN pg_catalog.pg_class clf ON (ct.confrelid = clf.oid AND clf.relkind = 'r')\n".
           "JOIN pg_catalog.pg_namespace nf ON (nf.oid = clf.relnamespace)\n".
           "JOIN pg_catalog.pg_attribute af ON (af.attrelid = ct.confrelid AND af.attnum = ct.confkey[1]) ".
           "WHERE n.nspname ='".$uiSchema."'order by cl.relname\n";
    $this->query($SQL);
    while ( $this->next_record() )
    {
       $_table   = $this->get('table');
       $_conname = $this->get('conname');
       $_constraintdef = $this->get('pg_get_constraintdef');
       $this->writeSQL("ALTER TABLE ONLY ".$Schema.".${_table} ADD CONSTRAINT ${_conname} ${_constraintdef};\n");
    }
// --- 7 Passo: Finaliza o arquivo
      fclose($this->fpSQL);
      return (filesize($this->Filename) > 0)? true : false;
   }//Fim Módulo
/*--------------------------------------------------------------------------------
 *    FUNÇÃO: Verifica se um campo pode ser nulo ou não e corrige no arquivo de backup 
 *--------------------------------------------------------------------------------*/
    // Checks if a field can be null, in order to replace it with '' or NULL
    // when building backup SQL statements
   function GetFieldInfo($uiTable, $uiField)
   {
      if (!$this->Connected) return(false);
      $response = array();
      $SQL = "SELECT typname, attnotnull \n".
             "FROM pg_attribute, pg_class, pg_type WHERE pg_class.oid=attrelid \n".
             "AND pg_type.oid=atttypid AND attnum>0 AND lower(relname)='${uiTable}' and attname = '${uiField}';\n";
      $this->query($SQL);
      $this->next_record();
      $not_null   = $this->get('attnotnull');
      $field_type = $this->get('typname');
      $response['not_null']  = ($not_null == 't') ? true : false;
      $response['is_binary'] = ($field_type == 'bytea') ? true : false;
      return $response;
   }//Fim Módulo
/*--------------------------------------------------------------------------------
 *    FUNÇÃO: Restaura um BD conforme o arquivo de backup 
 *--------------------------------------------------------------------------------*/
   // Restore the database from a SQL file
   //
   function Restore($uiFilename = NULL)
   {
      set_time_limit(600);
      echo 'Iniciando Restauração';
      $this->Errors = array();
      if (!$this->Connected) return(false);
      self::checaPath ();// Verifica se a path para backup já existe, se não a cria.
      if (is_null($uiFilename))
      {
         $this->Filename = $this->target_path.$this->Database.$this->extension;//define o nome do arquivo para backup genérico
      }
      else
      {
         $this->Filename = $uiFilename;//define o nome do arquivo para backup com a definição do usuário
      }
      if (!is_readable($this->Filename))
         $this->Error("O arquivo {$this->Filename} não pode ser aberto", true);

      $_CurrentLine = 0;
      $_fpSQL = fopen($this->Filename, "r");
      while ( $_readSQL = fgets($_fpSQL) )
      {
         $_CurrentLine++;
         if (preg_match("/^-/", $_readSQL) || preg_match("/^[\s]+$/", $_readSQL)) 
         {
             continue; // Don't bother about comments and blank lines
         }
         if ($this->Encoding == 'UTF8')
         {
            if(mb_detect_encoding($_readSQL) != "UTF-8")
            { 
                $this->query(utf8_encode($_readSQL));
            }
            else
            {
                $this->query($_readSQL);
            }
         }
         else
         {
            $this->query($_readSQL);
         }
         if ($this->GotSQLerror)
         {
            $this->Error("SQL syntax error on line ${_CurrentLine} (". $this->LastSQLerror .")", true);
         }
      }
      new TMessage('info','Arquivo Restaurado com sucesso.');
   }//Fim Módulo
/*--------------------------------------------------------------------------------
 *    FUNÇÃO: Metodo de exclusão de tabelas. Para quando algumas não forem necessárias 
 *--------------------------------------------------------------------------------*/
   // Use this method when you don't need to backup
   // some specific tables. The passed value can
   // be a string or an array.
   //
   function ExcludeTables($uiTables)
   {
      if (empty($uiTables)) return(false);

      if (is_array($uiTables))
         foreach ($uiTables as $item)
            $this->ExcludeTables[] = $item;
      else
         $this->ExcludeTables[] = $uiTables; 
   }//Fim do Módulo
/*--------------------------------------------------------------------------------
 *    FUNÇÃO: Realiza o Backup só de algumas tabelas 
 *--------------------------------------------------------------------------------*/
   // Use this methon when you need to backup
   // ONLY some specific tables. The passed value
   // can be a string or an array.
   //
   function BackupOnlyTables($uiTables)
   {
      if (empty($uiTables)) return(false);

      if (is_array($uiTables))
         foreach ($uiTables as $item)
            $this->Tables[] = $item;
      else
         $this->Tables[] = $uiTables;
   }//Fim Módulo
/*--------------------------------------------------------------------------------
 *    FUNÇÃO: Manuseio de Erros (nativo) 
 *--------------------------------------------------------------------------------*/
   // Error printing function.
   // When outputting a fatal error it will exit the script.
   // php-cli coloured output included ;)
   //
   function Error($uiErrStr, $uiFatal = false)
   {
      $_error = "";
      $_error_type = ($uiFatal) ? "Erro Fatal!" : "Erro!";
      printf("<font face='tahoma' size='2'><b>%s:</b>&nbsp;%s</font><br>\n", $_error_type, $uiErrStr);
      if ($uiFatal && !$this->IgnoreFatalErrors) exit;
   }//Fim Módulo
/*--------------------------------------------------------------------------------
 *    FUNÇÃO: Verifica se a Pasta de armazenagem existe, se não a cria. 
 *--------------------------------------------------------------------------------*/
    public function checaPath ()
    {
        if (!file_exists($this->target_path))
        {
            if (!mkdir($this->target_path, 0777, true))
            {
                throw new Exception('Permissão negada ao criar a pasta: '. $this->target_path);
            }
        }
    }//Fim Módulo

}//Fim da Classe