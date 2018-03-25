<?php
/**
 * servidor Active Record
 * @author  Fernando de Pinho Araújo
 */
class servidor extends TRecord
{
    const TABLENAME = 'efetivo.servidor';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    private $dependente;
    private $endereco;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('codpessoa');
        parent::addAttribute('codservidor');
        
        //Documentos
        parent::addAttribute('cpf');
        parent::addAttribute('rgmilitar');
        
        parent::addAttribute('rgcivil');//RG Civil
        parent::addAttribute('orgaoexpedicaorg');//RG Civil
        parent::addAttribute('ufexpedicaorg');//RG Civil
        parent::addAttribute('dtexpedicaorg');//RG Civil  - Acrescimo 2017-10-08
        
        parent::addAttribute('tituloeleitor');
        parent::addAttribute('zonatituloeleitor');
        parent::addAttribute('secaotituloeleitor');
        parent::addAttribute('municipiotituloeleitoral');
        parent::addAttribute('ufdotituloeleitoral');
        
        parent::addAttribute('cnh');
        parent::addAttribute('codcategoriacnh');
        parent::addAttribute('categoriacnh');
        parent::addAttribute('dtexpedicaocnh');
        parent::addAttribute('dtvalidadecnh');
        parent::addAttribute('ufcnh');
        
        parent::addAttribute('pispasep');//Acrescimo 2017-11-08
        parent::addAttribute('reservista');//Acrescimo 2017-11-08
        parent::addAttribute('orgaoexpedicaoreservista');//Acrescimo 2017-11-08
        parent::addAttribute('dtexpedicaoreservista');//Acrescimo 2017-11-08
        
        //Dados da Pessoa
        parent::addAttribute('nome');
        parent::addAttribute('nomepai');
        parent::addAttribute('nomemae');
        parent::addAttribute('nomeguerra');
        parent::addAttribute('sexo');
        parent::addAttribute('dtnascimento');
        parent::addAttribute('estadocivil');//Acrescimo 2017-10-08
        parent::addAttribute('naturalidade');//Acrescimo 2017-10-08
        parent::addAttribute('ufnaturalidade');//Acrescimo 2017-10-08
        parent::addAttribute('filhos');//Acrescimo 2017-11-08
        parent::addAttribute('escolaridade');//Acrescimo 2017-10-08
        parent::addAttribute('descricaoescolaridade');//Acrescimo 2017-11-08
        
        //Profissional
        parent::addAttribute('unidadeid');//Usado na tabela
        parent::addAttribute('unidade');//Usado na tabela
        parent::addAttribute('siglaunidade');
        parent::addAttribute('dtpromocao');
        parent::addAttribute('postograd');//usado na tabela
        parent::addAttribute('quadro');
        parent::addAttribute('lotacao');
        parent::addAttribute('funcao');
        parent::addAttribute('status');
        parent::addAttribute('situacao');

        //Residência
        parent::addAttribute('logradouro');
        parent::addAttribute('numero');
        parent::addAttribute('quadra');
        parent::addAttribute('lote');
        parent::addAttribute('complemento');
        parent::addAttribute('bairro');
        parent::addAttribute('codbairro');
        parent::addAttribute('municipio');
        parent::addAttribute('codmunicipio');
        parent::addAttribute('uf');
        parent::addAttribute('cep');
        
        //Contato
        parent::addAttribute('email');
        parent::addAttribute('telefoneresidencial');
        parent::addAttribute('telefonecelular');
        parent::addAttribute('telefonetrabalho');
        parent::addAttribute('lattes');//Acrescimo 2017-11-08
        
        //Caracteristicas físicas
        parent::addAttribute('peso');
        parent::addAttribute('altura');
        parent::addAttribute('tiposangue');//Acrescimo 2017-11-08
        parent::addAttribute('fatorrh');//Acrescimo 2017-11-08
        parent::addAttribute('codigocorbarba');
        parent::addAttribute('codigotipobarba');
        parent::addAttribute('codigocorbigote');
        parent::addAttribute('codigocorpele');
        parent::addAttribute('codigocorcabelo');
        parent::addAttribute('codigocorolho');
        parent::addAttribute('codigomaoqueescreve');
        parent::addAttribute('codigosabenadar');
        parent::addAttribute('codigotipobigode');
        parent::addAttribute('codigotipocabelo');
        parent::addAttribute('codigotipoboca');
        parent::addAttribute('codigotipocalvice');
        parent::addAttribute('codigotiponariz');
        
        //Romaneio
        parent::addAttribute('coturnoromaneio');//Acrescimo 2017-11-08
        parent::addAttribute('camisetaromaneio');//Acrescimo 2017-11-08
        parent::addAttribute('shortromaneio');//Acrescimo 2017-11-08
        parent::addAttribute('coberturaromaneio');//Acrescimo 2017-11-08
        parent::addAttribute('camisaromaneio');//Acrescimo 2017-11-08
        parent::addAttribute('calcaromaneio');//Acrescimo 2017-11-08
        
        parent::addAttribute('dt_inclusao');//Acrescimo 2017-12-13
        parent::addAttribute('usuario_inclusao');//Acrescimo 2017-12-13
        parent::addAttribute('corporacao');//Acrescimo 2017-12-22
        parent::addAttribute('password');//Acrescimo 2017-12-22
        
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Função: Adicionar dependente
 *------------------------------------------------------------------------------*/
    public function addDependente(dependente $object)
    {
        $this->dependente[] = $object;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Função: Delete com remoção de dependentes
 *------------------------------------------------------------------------------*/
    public function getdependentes()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('servidor_id', '=', $this->id));
        return dependente::getObjects( $criteria );
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Função: limpa os dependentes
 *------------------------------------------------------------------------------*/
    public function clearParts()
    {
        $this->dependente = array();
        $this->endereco = array();
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Função: Adicionar endereço
 *------------------------------------------------------------------------------*/
    public function addEndereco(endereco $object)
    {
        $this->endereco[] = $object;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Função: Delete com remoção de dependentes
 *------------------------------------------------------------------------------*/
    public function getEndereco()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('servidor_id', '=', $this->id));
        return endereco::getObjects( $criteria );
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Função: load com carga de Dependentes e endereços
 *------------------------------------------------------------------------------*/
    public function load($id)
    {
        // load associados
        $this->dependente = dependente::where('servidor_id', '=', $id)->load();
        $this->endereco   = endereco::where('servidor_id', '=', $id)->load();
        // load the object itself
        return parent::load($id);
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Função: store com armazenamento de Dependente e endereços
 *------------------------------------------------------------------------------*/
    public function store()
    {
        // store the object itself
        setlocale(LC_CTYPE, 'pt_BR.iso-8859-1');
        $this->nome = mb_strtoupper($this->nome,'UTF-8');
        $this->nomemae = mb_strtoupper($this->nomemae,'UTF-8');
        $this->nomepai = mb_strtoupper($this->nomepai,'UTF-8');
        $this->nomeguerra = mb_strtoupper($this->nomeguerra,'UTF-8');
        $this->logradouro = mb_strtoupper($this->logradouro,'UTF-8');
        $this->bairro = mb_strtoupper($this->bairro,'UTF-8');
        $this->complemento = mb_strtoupper($this->complemento,'UTF-8');
        
        parent::store();
        
        // delete contacts
        dependente::where('servidor_id', '=', $this->id)->delete();
        endereco::where('servidor_id', '=', $this->id)->delete();
        // save associados
        if ($this->dependente) 
        { 
            foreach ($this->dependente as $dependente) 
            {
                unset($dependente->id);
                $dependente->servidor_id = $this->id;
                $dependente->store();
            } 
        }
        if ($this->endereco) 
        { 
            foreach ($this->endereco as $endereco) 
            {
                unset($endereco->id);
                $endereco->servidor_id = $this->id;
                $endereco->store();
            } 
        }
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Função: Delete com remoção de dependentes e endereços
 *------------------------------------------------------------------------------*/
    public function delete($id = NULL)
    {
        // delete the related
        $id = isset($id) ? $id : $this->id;
        
        // delete associados
        dependente::where('servidor_id', '=', $id)->delete();
        endereco::where('servidor_id', '=', $id)->delete();
        // delete the object itself
        parent::delete($id);
    }//Fim Módulo

    /**
     * Return the programs the user has permission to run
     */
    public function getPrograms()
    {
        $programs = array();
        
        foreach( $this->getSystemUserGroups() as $group )
        {
            foreach( $group->getSystemPrograms() as $prog )
            {
                $programs[$prog->controller] = true;
            }
        }
                
        foreach( $this->getSystemUserPrograms() as $prog )
        {
            $programs[$prog->controller] = true;
        }
        
        return $programs;
    }
    
    /**
     * Return the programs the user has permission to run
     */
    public function getProgramsList()
    {
        $programs = array();
        
        foreach( $this->getSystemUserGroups() as $group )
        {
            foreach( $group->getSystemPrograms() as $prog )
            {
                $programs[$prog->controller] = $prog->name;
            }
        }
                
        foreach( $this->getSystemUserPrograms() as $prog )
        {
            $programs[$prog->controller] = $prog->name;
        }
        
        asort($programs);
        return $programs;
    }
    
    /**
     * Check if the user is within a group
     */
    public function checkInGroup( SystemGroup $group )
    {
        $user_groups = array();
        foreach( $this->getSystemUserGroups() as $user_group )
        {
            $user_groups[] = $user_group->id;
        }
    
        return in_array($group->id, $user_groups);
    }
    
    /**
     *
     */
    public static function getInGroups( $groups )
    {
        $collection = array();
        $users = self::all();
        if ($users)
        {
            foreach ($users as $user)
            {
                foreach ($groups as $group)
                {
                    if ($user->checkInGroup($group))
                    {
                        $collection[] = $user;
                    }
                }
            }
        }
        return $collection;
    }
    /**
     * Method getSystem_user_groups
     * Return the System_user' System_user_group's
     * @return Collection of System_user_group
     */
    public function getSystemUserGroups()
    {
        $system_user_groups = array();
        
        // load the related System_user_group objects
        //Busca grupo para Aluno
        $system_user_system_user_groups = SystemGroup::where('name','like','%- Aluno%')->load();
        if (isset($system_user_system_user_groups[0]))
        {
            $group_id = $system_user_system_user_groups[0];
        }
        $system_user_groups[] = new SystemGroup( $group_id->id );
        return $system_user_groups;
    }
    /**
     * Method getSystem_user_programs
     * Return the System_user' System_user_program's
     * @return Collection of System_user_program
     */
    public function getSystemUserPrograms()
    {
        $system_user_programs = array();
        return $system_user_programs;
    }
    /**
     * Get user group ids
     */
    public function getSystemUserGroupIds()
    {
        $groupnames = array();
        $groups = $this->getSystemUserGroups();
        if ($groups)
        {
            foreach ($groups as $group)
            {
                $groupnames[] = $group->id;
            }
        }
        
        return implode(',', $groupnames);
    }
    /**
     * Get user group names
     */
    public function getSystemUserGroupNames()
    {
        $groupnames = array();
        $groups = $this->getSystemUserGroups();
        if ($groups)
        {
            foreach ($groups as $group)
            {
                $groupnames[] = $group->name;
            }
        }
        
        return implode(',', $groupnames);
    }
    /**
     * Returns the frontpage name
     */
    public function get_frontpage_name()
    {
        // loads the associated object
        //if (empty($this->frontpage))
        //    $this->frontpage = new SystemProgram($this->frontpage_id);
        //    
        // returns the associated object
        return '-- NC --';//$this->frontpage->name;
    }
    
    /**
     * Returns the frontpage
     */
    public function get_frontpage()
    {
        // loads the associated object
        //if (empty($this->frontpage))
        //    $this->frontpage = new SystemProgram($this->frontpage_id);
        //
        // returns the associated object
        return '--NC--';//$this->frontpage;
    }

}
