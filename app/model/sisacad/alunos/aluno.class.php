<?php
/**
 * aluno Active Record
 * @author  <your-name-here>
 */
class aluno extends TRecord
{
    const TABLENAME = 'sisacad.aluno';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    

    private $aluno;                         //Assossiação
    private $identidade;                    //requisção
    private $turma;                         //Assossiação
    
    private $system_user_groups = array();
    private $system_user_programs = array();
   
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('status');
        parent::addAttribute('cpf');
        parent::addAttribute('resultado');
        parent::addAttribute('restricao');
        parent::addAttribute('obs');
        parent::addAttribute('turma_id');
        parent::addAttribute('password');//Acrescimo 2017-12-22
    }

    /**
     * Method set_turma
     * Sample of usage: $aluno->turma = $object;
     * @param $object Instance of turma
     */
    public function set_turma(turma $object)
    {
        $this->turma = $object;
        $this->turma_id = $object->id;
    }
    
    /**
     * Method get_aluno (dados do servidor)
     * Sample of usage: $aluno->aluno->attribute;
     * @returns aluno instance
     */
    public function get_aluno()
    {
        // loads the associated object
        if (empty($this->aluno))
        {
            $dados = servidor::where('cpf','=',$this->cpf)->load();
            foreach ($dados as $dado)
            {
                $servidor = $dado;
            }
            $this->aluno = $servidor;
        }
        // returns the associated object
        return $this->aluno;
    }
    /**
     * Method get_aluno (dados do servidor)
     * Sample of usage: $aluno->aluno->attribute;
     * @returns aluno instance
     */
    public function getIdentificacao()
    {
        // loads the associated object
        $sis = new TSisacad();
        if (!empty($this->identidade))
        {
            return $this->identidade;
        }
        if (empty($this->aluno))
        {
            self::get_aluno();
        }
        if (!empty($this->aluno))//Se retornar os dados do aluno, preenche
        {
            if (!empty($this->aluno->rgmilitar))
            {
                $rg = ' RG ' . $this->aluno->rgmilitar; 
            }
            else if (!empty($this->aluno->rgcivil))
            {
                $rg = ' CI ' . $this->aluno->rgcivil;
            }
            else
            {
                $rg = '';
            }
            $rg .= ' ';
            $posto = $this->aluno->postograd;
            $posto = (!empty($posto)) ? $sis->getPostograd($posto) : '';
            $ident = $posto . $rg . $this->aluno->nome . ', CPF '.$this->aluno->cpf;
        }
        else
        {
            $ident = '-- Dados do aluno não localizado -- ';
        }
        // returns the associated object
        $this->identidade = $ident;
        return $this->identidade;
    }
    /**
     * Method get_turma
     * Sample of usage: $aluno->turma->attribute;
     * @returns turma instance
     */
    public function get_turma()
    {
        // loads the associated object
        if (empty($this->turma))
            $this->turma = new turma($this->turma_id);
    
        // returns the associated object
        return $this->turma;
    }//Fim Módulo
    /**
     * Authenticate the aluno user
     * @param $login String with user login
     * @param $password String with user password
     * @returns TRUE if the password matches, otherwise throw Exception
     */
    public static function authenticate($login, $password)
    {
        $user = self::newFromLogin($login);
        
        if ($user instanceof servidor)
        {
            if ($user->matriculado != 'S')
            {
                throw new Exception('Servidor não matriculado em nenhum curso.');
            }
            else if (isset( $user->password ) AND ($user->password == md5($password)) )
            {
                return $user;
            }
            else
            {
                throw new Exception('A senha do está errada.');
            }
        }
        else
        {
            throw new Exception('Aluno não localizado');
        }
    }
    /**
     * Returns a SystemUser object based on its login
     * @param $login String with user login
     */
    static public function newFromLogin($login)
    {
        $repos = new TRepository('servidor');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('cpf', '=', $login));
        $objects = $repos->load($criteria);
        if (isset($objects[0]))
        {
            $servidor = $objects[0];
            $repos = new TRepository('aluno');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('cpf', '=', $login));
            $criteria->add(new TFilter('oculto', '!=', 'S'));
            $objects = $repos->load($criteria);
            if (isset($objects[0]))
            {
                $servidor->matriculado = 'S';
            }
            return $servidor;
        }
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
//------------------------------------------------------------------------------
//Acrescido em 2018-04-05
   /**
     * Method getaluno_presencas
     */
    public function getaluno_presencas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('aluno_id', '=', $this->id));
        return aluno_presenca::getObjects( $criteria );
    }
    
    
    /**
     * Method getavaliacao_alunos
     */
    public function getavaliacao_alunos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('aluno_id', '=', $this->id));
        return avaliacao_aluno::getObjects( $criteria );
    }
    
    
    /**
     * Method getavaliacao_resultadoalunos
     */
    public function getavaliacao_resultadoalunos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('aluno_id', '=', $this->id));
        return avaliacao_resultadoaluno::getObjects( $criteria );
    }
    
    
    /**
     * Method getavaliacao_finalalunos
     */
    public function getavaliacao_finalalunos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('aluno_id', '=', $this->id));
        return avaliacao_finalaluno::getObjects( $criteria );
    }
    
    
    /**
     * Method getavaliacao_rankingalunos
     */
    public function getavaliacao_rankingalunos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('aluno_id', '=', $this->id));
        return avaliacao_rankingaluno::getObjects( $criteria );
    }
    
    
    /**
     * Method getranking_alunos
     */
    public function getranking_alunos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('aluno_id', '=', $this->id));
        return ranking_aluno::getObjects( $criteria );
    }
}//Fim Classe
