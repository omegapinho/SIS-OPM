<?php
/**
 * professor Active Record
 * @author  <your-name-here>
 */
class professor extends TRecord
{
    const TABLENAME = 'sisacad.professor';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $orgaosorigem;
    private $postograd;
    private $materias;
    private $controle_aulas;
    private $disciplinas;
    private $escolaridades;
    private $opm;
    
    private $system_user_groups = array();
    private $system_user_programs = array();

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('postograd');
        parent::addAttribute('cpf');
        parent::addAttribute('orgao_origem');
        parent::addAttribute('oculto');
        parent::addAttribute('telefone');
        parent::addAttribute('email');
        parent::addAttribute('celular');
        parent::addAttribute('lattes');
        parent::addAttribute('status_documento');
        parent::addAttribute('data_nascimento');
        parent::addAttribute('rg');
        parent::addAttribute('orgao_expeditor');
        parent::addAttribute('uf_expeditor');
        parent::addAttribute('status_funcional');
        parent::addAttribute('sexo');
        parent::addAttribute('logradouro');
        parent::addAttribute('quadra');
        parent::addAttribute('lote');
        parent::addAttribute('numero');
        parent::addAttribute('bairro');
        parent::addAttribute('cidade');
        parent::addAttribute('uf_residencia');
        parent::addAttribute('orgaosorigem_id');
        parent::addAttribute('postograd_id');
        parent::addAttribute('quadro');
        //Acrescimo em 20/09/2017
        parent::addAttribute('opm_id');
        //Acrescimo em 02/12/2017
        parent::addAttribute('password');
    }

    
    /**
     * Method set_orgaosorigem
     * Sample of usage: $professor->orgaosorigem = $object;
     * @param $object Instance of orgaosorigem
     */
    public function set_orgaosorigem(orgaosorigem $object)
    {
        $this->orgaosorigem = $object;
        $this->orgaosorigem_id = $object->id;
    }
    
    /**
     * Method get_orgaosorigem
     * Sample of usage: $professor->orgaosorigem->attribute;
     * @returns orgaosorigem instance
     */
    public function get_orgaosorigem()
    {
        // loads the associated object
        if (empty($this->orgaosorigem))
            $this->orgaosorigem = new orgaosorigem($this->orgaosorigem_id);
    
        // returns the associated object
        return $this->orgaosorigem;
    }
    
    /**
     * Method get_orgaosorigem
     * Sample of usage: $professor->opm->attribute;
     * @returns opm instance
     */
    public function get_opm()
    {
        // loads the associated object
        if (empty($this->opm))
            $this->opm = new OPM($this->opm_id);
    
        // returns the associated object
        return $this->opm;
    }
    
    /**
     * Method set_postograd
     * Sample of usage: $professor->postograd = $object;
     * @param $object Instance of postograd
     */
    public function set_postograd(postograd $object)
    {
        $this->postograd = $object;
        $this->postograd_id = $object->id;
    }
    
    /**
     * Method get_postograd
     * Sample of usage: $professor->postograd->attribute;
     * @returns postograd instance
     */
    public function get_postograd()
    {
        // loads the associated object
        if (empty($this->postograd))
            $this->postograd = new postograd($this->postograd_id);
    
        // returns the associated object
        return $this->postograd;
    }
    
    
    /**
     * Method addmateria
     * Add a materia to the professor
     * @param $object Instance of materia
     */
    public function addmateria(materia $object)
    {
        $this->materias[] = $object;
    }
    
    /**
     * Method getmaterias
     * Return the professor' materia's
     * @return Collection of materia
     * @$param = TFilter ou um array(TFilter);
     */
    public function getmaterias($param = null)
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('professor_id', '=', $this->id));
        if (!empty($param))
        {
            if (is_array($param))
            {
                foreach ($param as $p)
                {
                    $criteria->add($p);
                }
            }
            else
            {
                $criteria->add($param);
            }
        }
        
        return professormateria::getObjects( $criteria );
    }
    
    /**
     * Method addcontrole_aula
     * Add a controle_aula to the professor
     * @param $object Instance of controle_aula
     */
    public function addcontrole_aula(controle_aula $object)
    {
        $this->controle_aulas[] = $object;
    }
    
    /**
     * Method getcontrole_aulas
     * Return the professor' controle_aula's
     * @return Collection of controle_aula
     */
    public function getcontrole_aulas()
    {
        return $this->controle_aulas;
    }
    
    /**
     * Method adddisciplina
     * Add a disciplina to the professor
     * @param $object Instance of disciplina
     */
    public function adddisciplina(disciplina $object)
    {
        $this->disciplinas[] = $object;
    }
    
    /**
     * Method getdisciplinas
     * Return the professor' disciplina's
     * @return Collection of disciplina
     */
    public function getdisciplinas()
    {
        return $this->disciplinas;
    }
    
    /**
     * Method addescolaridade
     * Add a escolaridade to the professor
     * @param $object Instance of escolaridade
     */
    public function addescolaridade(escolaridade $object)
    {
        $this->escolaridades[] = $object;
    }
    
    /**
     * Method getescolaridades
     * Return the professor' escolaridade's
     * @return Collection of escolaridade
     */
    public function getescolaridades()
    {
        return $this->escolaridades;
    }

    
    /**
     * Method getincidente_pedagogicos
     */
    public function getincidente_pedagogicos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('professor_id', '=', $this->id));
        return incidente_pedagogico::getObjects( $criteria );
    }
    
    
    /**
     * Method getinquerito_pedagogicos
     */
    public function getinquerito_pedagogicos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('professor_id', '=', $this->id));
        return inquerito_pedagogico::getObjects( $criteria );
    }
    

    /**
     * Reset aggregates
     */
    public function clearParts()
    {
        $this->materias = array();
        $this->controle_aulas = array();
        $this->disciplinas = array();
        $this->escolaridades = array();
    }

    /**
     * Load the object and its aggregates
     * @param $id object ID
     */
    /*public function load($id)
    {
    
        //$this->postograd = new postograd($this->postograd_id);
        //$this->orgaosorigem = new orgaosorigem($this->orgaosorigem_id);
        // load the related materia objects
        $repository = new TRepository('professormateria');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('professor_id', '=', $id));
        $professor_materias = $repository->load($criteria);
        if ($professor_materias)
        {
            foreach ($professor_materias as $professor_materia)
            {
                $materia = new materia( $professor_materia->materia_id );
                $this->addmateria($materia);
            }
        }
    
        // load the related controle_aula objects
        $repository = new TRepository('professorcontrole_aula');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('professor_id', '=', $id));
        $professor_controle_aulas = $repository->load($criteria);
        if ($professor_controle_aulas)
        {
            foreach ($professor_controle_aulas as $professor_controle_aula)
            {
                $controle_aula = new controle_aula( $professor_controle_aula->controle_aula_id );
                $this->addcontrole_aula($controle_aula);
            }
        }
    
        // load the related disciplina objects
        $repository = new TRepository('professordisciplina');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('professor_id', '=', $id));
        $professor_disciplinas = $repository->load($criteria);
        if ($professor_disciplinas)
        {
            foreach ($professor_disciplinas as $professor_disciplina)
            {
                $disciplina = new disciplina( $professor_disciplina->disciplina_id );
                $this->adddisciplina($disciplina);
            }
        }
    
        // load the related escolaridade objects
        $repository = new TRepository('escolaridade');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('professor_id', '=', $id));
        $this->escolaridades = $repository->load($criteria);
    
        // load the object itself
        return parent::load($id);
    }*/

    /**
     * Store the object and its aggregates
     */
    /*public function store()
    {
        // store the object itself
        parent::store();
    
        // delete the related professormateria objects
        $criteria = new TCriteria;
        $criteria->add(new TFilter('professor_id', '=', $this->id));
        $repository = new TRepository('professormateria');
        $repository->delete($criteria);
        // store the related professormateria objects
        if ($this->materias)
        {
            foreach ($this->materias as $materia)
            {
                $professor_materia = new professormateria;
                $professor_materia->materia_id = $materia->id;
                $professor_materia->professor_id = $this->id;
                $professor_materia->store();
            }
        }
        // delete the related professorcontrole_aula objects
        $criteria = new TCriteria;
        $criteria->add(new TFilter('professor_id', '=', $this->id));
        $repository = new TRepository('professorcontrole_aula');
        $repository->delete($criteria);
        // store the related professorcontrole_aula objects
        if ($this->controle_aulas)
        {
            foreach ($this->controle_aulas as $controle_aula)
            {
                $professor_controle_aula = new professorcontrole_aula;
                $professor_controle_aula->controle_aula_id = $controle_aula->id;
                $professor_controle_aula->professor_id = $this->id;
                $professor_controle_aula->store();
            }
        }
        // delete the related professordisciplina objects
        $criteria = new TCriteria;
        $criteria->add(new TFilter('professor_id', '=', $this->id));
        $repository = new TRepository('professordisciplina');
        $repository->delete($criteria);
        // store the related professordisciplina objects
        if ($this->disciplinas)
        {
            foreach ($this->disciplinas as $disciplina)
            {
                $professor_disciplina = new professordisciplina;
                $professor_disciplina->disciplina_id = $disciplina->id;
                $professor_disciplina->professor_id = $this->id;
                $professor_disciplina->store();
            }
        }
        // delete the related escolaridade objects
        $criteria = new TCriteria;
        $criteria->add(new TFilter('professor_id', '=', $this->id));
        $repository = new TRepository('escolaridade');
        $repository->delete($criteria);
        // store the related escolaridade objects
        if ($this->escolaridades)
        {
            foreach ($this->escolaridades as $escolaridade)
            {
                unset($escolaridade->id);
                $escolaridade->professor_id = $this->id;
                $escolaridade->store();
            }
        }
    }*/

    /**
     * Delete the object and its aggregates
     * @param $id object ID
     */
    public function delete($id = NULL)
    {
        $id = isset($id) ? $id : $this->id;
        // Verifica Permissão
        $repository = new TRepository('professorcontrole_aula');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('professor_id', '=', $id));
        $aulas_dadas = professorcontrole_aula::countObjects($criteria);
        //Verifica se o Professor tem aulas lançadas
        if ($aulas_dadas > 0)
        {
            throw new Exception ('Não posso excluir o professor, pois o mesmo possui aulas cadastradas.');
        }
        else
        {
            //Executa a deleção dos itens do professor 
            $repository->delete($criteria);
            // delete the related professormateria objects
            $repository = new TRepository('professormateria');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('professor_id', '=', $id));
            $repository->delete($criteria);
            
            // delete the related professorcontrole_aula objects
            /*$repository = new TRepository('professorcontrole_aula');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('professor_id', '=', $id));
            $repository->delete($criteria);*/
            
            // delete the related professordisciplina objects
            $repository = new TRepository('professordisciplina');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('professor_id', '=', $id));
            $repository->delete($criteria);
            
            // delete the related escolaridade objects
            $repository = new TRepository('escolaridade');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('professor_id', '=', $id));
            $repository->delete($criteria);
            
        
            // delete the object itself
            parent::delete($id);
        }
    }
    /**
     * Authenticate the professor user
     * @param $login String with user login
     * @param $password String with user password
     * @returns TRUE if the password matches, otherwise throw Exception
     */
    public static function authenticate($login, $password)
    {
        $user = self::newFromLogin($login);
        
        if ($user instanceof professor)
        {
            if ($user->oculto == 'S')
            {
                throw new Exception('Professor afastado.');
            }
            else if (isset( $user->password ) AND ($user->password == md5($password)) )
            {
                return $user;
            }
            else
            {
                throw new Exception('A senha do Professor está errada.');
            }
        }
        else
        {
            throw new Exception('Professor não localizado');
        }
    }
    /**
     * Returns a SystemUser object based on its login
     * @param $login String with user login
     */
    static public function newFromLogin($login)
    {
        $repos = new TRepository('professor');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('cpf', '=', $login));
        $objects = $repos->load($criteria);
        if (isset($objects[0]))
        {
            return $objects[0];
        }
    }
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
        //Busca grupo para professor
        $system_user_system_user_groups = SystemGroup::where('name','like','%- Professor%')->load();
        if (isset($system_user_system_user_groups[0]))
        {
            $group_id = $system_user_system_user_groups[0];
        }
        $system_user_groups[] = new SystemGroup( $group_id->id );
        //var_dump($system_user_groups);
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
/*------------------------------------------------------------------------------
 *   Gera o Identificador do Professor dado pelo retorno
 ²   @$param = 'PNCO'
 *   @P = posto / N = nome / C = cpf e O = órgão
 *   @Pode omitir item, mas não pode trocar a ordem 'PNCO'
 *------------------------------------------------------------------------------*/
    public function getidentificacao ($param = '!P!N!C!O')
    {
        $nome  = $this->nome;
        if (empty($this->postograd))    self::get_postograd();
        if (empty($this->orgaosorigem)) self::get_orgaosorigem();
        $posto = $this->postograd->sigla;
        $orgao = $this->orgaosorigem->sigla;
        $cpf   = $this->cpf;
        
        $nome   = (!empty($nome))  ? $nome             : '- ERRO NA IDENTIFICAÇÃO -';
        $posto  = (!empty($posto)) ? $posto . ' '      : 'NC ';
        $orgao  = (!empty($orgao)) ? '(' . $orgao .')' : '(NC)';
        $cpf    = (!empty($cpf))   ? ',CPF:' .$cpf     : ',CPF:NC';
        
        $indice = array ('!P','!N','!C','!O');
        $trocar = array ($posto,$nome,$cpf,$orgao);
        
        return str_replace($indice, $trocar,$param);

    }
    
}