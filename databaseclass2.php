<?php

class Database
{
    const MYSQL = 1;
    const MSSQL = 2;

    protected $type;
    protected $pdo;
    protected $stmt;
    protected $errMode;

    public function GetPdo()
    {
        return $this->pdo;
    }

    public function GetStatement()
    {
        return $this->stmt;
    }

    public function GetType()
    {
        return $this->type;
    }

    public function __construct($type = self::MYSQL, $errMode = PDO::ERRMODE_EXCEPTION)
    {
        $this->type = $type;
        $this->errMode = $errMode;
    }

    public function Connect($host, $database, $user, $pass)
    {
        switch ($this->type)
        {
            case self::MSSQL:
                $conStr = 'sqlsrv:Server=' . $host . ';Database=' . $database;
                break;
            case self::MYSQL:
            default:
                $conStr = 'mysql:host=' . $host . ';dbname=' . $database;
                break;
        }

        $this->pdo = new PDO($conStr, $user, $pass);

        // Set PDO's error mode
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, $this->errMode);
    }

    public function Query($sql, $params = [], $singleRow = false, $resultType = PDO::FETCH_ASSOC)
    {
        return $this->dbCall(true, $sql, $params, $singleRow, $resultType);
    }

    public function Execute($sql, $params = [], $resultType = PDO::FETCH_ASSOC)
    {
        return $this->dbCall(false, $sql, $params, false, $resultType);
    }

    protected function dbCall($results, $sql, $params, $singleRow, $resultType)
    {
        $params = (array) $params;

        if ($params)
        {
            $stmt = $this->pdo->prepare($sql);

            // Check if first parameter is an array; if so, these are named parameters
            if (is_array($params[0]))
            {
                // Named parameters are in the format: [[ name => value ]]
                // e.g., [[':name', 'value']]
                foreach ($params as $name => $value)
                {
                    $stmt->bindValue($name, $value, $this->getParamType($value));
                }

                $stmt->execute();
            }
            else
            {
                $stmt->execute($params);
            }

            if (!$results)
            {
                $rows = $stmt->rowCount();
                $stmt = null;
            }
        }
        else
        {
            if ($results)
            {
                $stmt = $this->pdo->query($sql);
            }
            else
            {
                $rows = $this->pdo->exec($sql);
            }
        }

        if ($results)
        {
            $data = ($singleRow) ? $stmt->fetch($resultType) : $stmt->fetchAll($resultType);
            $stmt = null;
            return $data;
        }
        else
        {
            return $rows;
        }
    }

    public function PrepareStatement($sql)
    {
        $this->stmt = $this->db->prepare($sql);
        return $this;
    }

    public function ExecuteStatement($params = [])
    {
        $this->checkStmt();

        if (!is_array($params))
        {
            $params = [$params];
        }

        $this->stmt->execute($params);
        return $this;
    }

    public function BindParam($paramName = null, $paramValue = null, $paramType = null, $paramLength = null)
    {
        $this->checkStmt();
        $this->stmt->bindParam($paramName, $paramValue, $paramType, $paramLength);
        return $this;
    }

    public function Fetch($singleRow = false, $resultType = PDO::FETCH_ASSOC)
    {
        $this->checkStmt();
        return ($singleRow) ? $stmt->fetch($resultType) : $stmt->fetchAll($resultType);
    }

    public function ClearStatement()
    {
        $this->stmt = null;
        return $this;
    }

    public function BeginTransaction()
    {
        $this->pdo->beginTransaction();
        return $this;
    }

    public function Rollback()
    {
        $this->pdo->rollBack();
        return $this;
    }

    public function Commit()
    {
        $this->pdo->commit();
        return $this;
    }

    public function LastInsertID()
    {
        return $this->pdo->lastInsertId();
    }

    protected function getParamType($input)
    {
        if (is_bool($input))
        {
            return PDO::PARAM_BOOL;
        }

        if (is_int($input))
        {
            return PDO::PARAM_INT;
        }

        if ($input === null)
        {
            return PDO::PARAM_NULL;
        }

        return PDO::PARAM_STR;
    }

    private function checkStmt()
    {
        if (!$this->stmt)
        {
            throw new BadMethodCallException('No active statement');
        }
    }
}

?>
