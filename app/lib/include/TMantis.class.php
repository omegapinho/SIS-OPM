<?php
/*
 * TMantis - Manipula a entrada e saída de documentos no BD
 * Versão: 1.0
 * Date: 07/08/2017
 * Author: Fernando de Pinho Araújo <o.megapinho@gmail.com>
 * Changelog:
 * - Versão: 1.0 
 */
class TMantis 
{
/*------------------------------------------------------------------------------
 *    Iniciando Variáveis
 *------------------------------------------------------------------------------*/
    public $chamado;        //Dados do Chamado
    public $chamado_id;     //Id do Chamado no BD
/*------------------------------------------------------------------------------
 *    Construtor da Classe
 *------------------------------------------------------------------------------*/
     public function __construct()
     {
        $this->chamado_id = null;
 
     }//Fim Módulo
/*------------------------------------------------------------------------------
 *    Cadastra um chamado assim como também salva um já criado
 *------------------------------------------------------------------------------*/
     public function criaChamado($param = null)
     {
        $chamado = false;
        try
        {
            TTransaction::open('sisacad');
            if ($param == null)
            {
                $ma = new incidentes;
            }
            else
            {
                $ma = new incidentes($param);
            }
            $ma->fromArray($this->chamado);
            $ma->store();
            $this->chamado_id = $ma->id;
            $chamado          = $ma->id;
            TTransaction::close();
        }
        catch (Exception $e)
        {
            //new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        return $chamado;     
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *    Cadastra um chamado
 *------------------------------------------------------------------------------*/
     public function loadChamado($param)
     {
        $chamado = false;
        try
        {
            TTransaction::open('sisacad');
            $ma = new incidentes ($param);
            $this->chamado_id    = $ma->id;
            $this->chamado       = $ma->toArray();
            $chamado             = $ma->id;
            TTransaction::close();
        }
        catch (Exception $e)
        {
            //new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        return $chamado;     
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *    Adiciona mudaças no histórico
 *------------------------------------------------------------------------------*/
     public function addHistorico($param)
     {
        $chamado = false;
        try
        {
            TTransaction::open('sisacad');
            $ma = new incidentes_historico;
            $ma->fromArray($param);
            $ma->store();
            $chamado = $ma->id;
            TTransaction::close();
        }
        catch (Exception $e)
        {
            //new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        return $chamado; 
     }//Fim Módulo
/*------------------------------------------------------------------------------
 *    Fecha o Chamado
 *------------------------------------------------------------------------------*/
     public function fechaChamado($param, $resolvido = 20 )
     {
        /*  pegar id usuário que fecha, data_final
         *  mudar status para 60
         *  mudar resolucao para (  20 se resolvido e 90 se não resolvido)
         * 
         *  
         */        
        $this->loadChamado($param['key']);
        $data_fim         = date('Y-m-d');
        $profile          = TSession::getValue('profile');
        $user             = $this->FindServidor($profile['login']);
        
        $this->chamado['operador_id'] = $user->id;
        $this->chamado['data_fim']    = $data_fim;
        $this->chamado['data_atual']  = $data_fim;
        $this->chamado['status']      = 60;
        $this->chamado['resolucao']   = $resolvido;
        $this->criaChamado($param['key']);
        
        
  
     }//Fim Módulo
/*------------------------------------------------------------------------------
 *    Adiciona arquivo
 *------------------------------------------------------------------------------*/
     public function addDocumento ($dtArquivo, $dados)
     {
        
        $varImagemTemp = $dtArquivo['tmp_name'];
        $varType       = $dtArquivo['type'];
		$varArquivo    = fopen($varImagemTemp,"rb");
        //var_dump($varImagemTemp);
		if (!empty($varArquivo))
		{
            /*$varImagem = base64_encode(fread($varArquivo,filesize($varImagemTemp)));
            fclose($varArquivo);*/
            
            // Read in a binary file
            $data = file_get_contents( $varImagemTemp );
            
            // Escape the binary data
            $escaped = bin2hex( $data );
            
            // Insert it into the database
            //var_dump($varImagem);
            
            try 
            {
                TTransaction::open('gdocs'); // open a transaction with database
                //$varSQL = "insert into g_message.imagens values ('".$edtNomeArquivo['name']."','" . $varImagem . "')";
                $varSQL = "INSERT INTO g_message.imagens (descricao, imagem, tipo) VALUES ('".$edtNomeArquivo['name']."', decode('{$escaped}' , 'hex'),'".$varType."')";
                //echo $varSQL;
                $varResultado = pg_query($varConexao,$varSQL);
                TTransaction::close();
            }
            catch (Exception $e)
            {
                echo "Erro!";
            }
			if ( !$varResultado )
			{
				$varMensagem = "Erro ao inserir a imagem";
			}
		}
		else
		{
			$varMensagem = "Erro ao abrir o arquivo transferido";
		} 
         
     }//Fim Módulo
/*------------------------------------------------------------------------------
 *    acha um servidor (cpf ou RG)
 *------------------------------------------------------------------------------*/
     public function FindServidor($param, $docente = false)
     {
        $militar = false;
        try
        {
            TTransaction::open('sisacad');
            if (strlen($param) != 11 && $docente == true)
            {
                $militar = new professor($param);
            }
            else
            {
                if (strlen($param) == 11 && $docente == false)
                {
                    $servs = servidor::where('cpf','=',$param)->load();
                }
                else if (strlen($param) == 11 && $docente == true)
                {
                    $servs = professor::where('cpf','=',$param)->load();
                }
                else if ($docente == false)
                {
                    $servs = servidor::where('rgmilitar','=',$param)->load();
                }
                foreach($servs as $serv)
                {
                    $militar = $serv;
                }
            }
            TTransaction::close();
            //var_dump($militar);
        }
        catch (Exception $e)
        {
            //new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        return $militar;
     }//Fim Módulo
/*------------------------------------------------------------------------------
 *    acha um sistema
 *------------------------------------------------------------------------------*/
     public function FindSistema($param)
     {
        $sistema = false;
        
        try
        {
            TTransaction::open('sisacad');
            if (!empty($param))
            {
                $criteria = new TCriteria;
                $criteria->add(new TFilter('nome','=',$param));
                $criteria->add(new TFilter('dominio','=','configura'));
                $systems = Item::getObjects($criteria);
            }
            foreach($systems as $system)
            {
                $sistema = $system;
            }
            TTransaction::close();
            //var_dump($sistema);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage().'Houve um erro na pesquisa ');
            TTransaction::rollback();
        }
        return $sistema;
     }//Fim Módulo
    
}//Fim Classe

