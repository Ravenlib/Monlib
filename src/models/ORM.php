<?php

namespace Monlib\Models;

use Monlib\Models\Database;

use PDO;

class ORM {
    
    protected PDO $pdo;
    protected string $table;
    protected Database $database;

    public function __construct(string $table) {
        $this->table    =   $table;
        $this->database =   new Database;
        $this->pdo      =   $this->database->getPDO();
    }
    
    public function select(array $conditions, string|array $fields = '*', int $offset = 0, int $limit = 50) {
        $query = "SELECT ";
    
        if (is_array($fields)) {
            $query .= implode(', ', $fields);
        } else {
            $query .= $fields;
        }
    
        $query .= " FROM {$this->table}";
    
        if (!empty($conditions)) {
            $query .= " WHERE ";
            $whereConditions = [];
    
            foreach ($conditions as $field => $value) {
                $whereConditions[] = "$field = :$field";
            }
    
            $query .= implode(" AND ", $whereConditions);
        }
    
        // Adicione a cláusula LIMIT com o valor do limite e do offset
        $query .= " LIMIT :offset, :limit";
    
        $statement = $this->pdo->prepare($query);
    
        if (!empty($conditions)) {
            foreach ($conditions as $field => $value) {
                $statement->bindValue(":$field", $value);
            }
        }
    
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
    
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

	public function create(array $data = []) {
		$columns    =   implode(', ', array_keys($data));
		$values     =   ':' . implode(', :', array_keys($data));
		$query      =   "INSERT INTO $this->table ($columns) VALUES ($values)";
		
		$statement = $this->pdo->prepare($query);

		foreach ($data as $key => $value) {
			$statement->bindValue(
				":$key", $value
			);
		}

		$statement->execute();
		return $this->pdo->lastInsertId();
	}
    
    public function delete(array $conditions = []) {
        $query = "DELETE FROM {$this->table}";
    
        if (!empty($conditions)) {
            $query .= " WHERE ";
            $whereConditions = [];
    
            foreach ($conditions as $field => $value) {
                $whereConditions[] = "$field = :$field";
            }
    
            $query .= implode(" AND ", $whereConditions);
        }
    
        $statement = $this->pdo->prepare($query);
    
        if (!empty($conditions)) {
            foreach ($conditions as $field => $value) {
                $statement->bindValue(":$field", $value);
            }
        }
    
        $statement->execute();
        return $statement->rowCount();
    }

    public function update(array $data = [], array $conditions = []) {
        if (empty($data)) {
            return false;
        }
    
        $query = "UPDATE {$this->table} SET ";
        $setValues = [];
    
        foreach ($data as $field => $value) {
            $setValues[] = "$field = :$field";
        }
    
        $query .= implode(", ", $setValues);
    
        if (!empty($conditions)) {
            $query .= " WHERE ";
            $whereConditions = [];
    
            foreach ($conditions as $field => $value) {
                $whereConditions[] = "$field = :$field";
            }
    
            $query .= implode(" AND ", $whereConditions);
        }
    
        $statement = $this->pdo->prepare($query);
    
        foreach ($data as $field => $value) {
            $statement->bindValue(":$field", $value);
        }
    
        if (!empty($conditions)) {
            foreach ($conditions as $field => $value) {
                $statement->bindValue(":$field", $value);
            }
        }
    
        $statement->execute();
        return $statement->rowCount();
    }

    public function select_like(array $columns, string $searchTerm, string|array $fields = '*', int $limit = 50, int $offset = 0) {
        $query      =    "SELECT ";
        
        if (is_array($fields)) {
            $query  .=  implode(', ', $fields);
        } else {
            $query  .=   $fields;
        }
    
        $conditions =   [];
        $query      .=   " FROM {$this->table} WHERE ";
    
        foreach ($columns as $column) { $conditions[] = "$column LIKE :searchTerm"; }
    
        $query      .=   implode(" OR ", $conditions);
        $query      .=   " LIMIT :offset, :limit";
        $statement  =    $this->pdo->prepare($query);
        
        $statement->bindValue(':searchTerm', "%$searchTerm%");
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
    
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function count(array $conditions = []) {
        $query = "SELECT COUNT(*) as total FROM {$this->table}";
    
        if (!empty($conditions)) {
            $query .= " WHERE ";
            $whereConditions = [];
    
            foreach ($conditions as $field => $value) {
                $whereConditions[] = "$field = :$field";
            }
    
            $query .= implode(" AND ", $whereConditions);
        }
    
        $statement = $this->pdo->prepare($query);
    
        if (!empty($conditions)) {
            foreach ($conditions as $field => $value) {
                $statement->bindValue(":$field", $value);
            }
        }
    
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
    
        return intval($result['total']);
    }

	public function innerJoin(string $tableToJoin, string $onCondition, array $conditions = [], array|string $fields = '*', int $limit = 50, int $offset = 0) {
		return $this->join('INNER JOIN', $tableToJoin, $onCondition, $conditions, $fields, $limit, $offset);
	}
	
	public function leftJoin(string $tableToJoin, string $onCondition, array $conditions = [], array|string $fields = '*', int $limit = 50, int $offset = 0) {
		return $this->join('LEFT JOIN', $tableToJoin, $onCondition, $conditions, $fields, $limit, $offset);
	}
	
	public function rightJoin(string $tableToJoin, string $onCondition, array $conditions = [], array|string $fields = '*', int $limit = 50, int $offset = 0) {
		return $this->join('RIGHT JOIN', $tableToJoin, $onCondition, $conditions, $fields, $limit, $offset);
	}
	
	public function fullJoin(string $tableToJoin, string $onCondition, array $conditions = [], array|string $fields = '*', int $limit = 50, int $offset = 0) {
		return $this->join('FULL JOIN', $tableToJoin, $onCondition, $conditions, $fields, $limit, $offset);
	}
	
	private function join(string $type, string $tableToJoin, string $onCondition, array $conditions = [], array|string $fields = '*', int $limit = 50, int $offset = 0) {
		$query = "SELECT ";
	
		if (is_array($fields)) {
			$query .= implode(', ', $fields);
		} else {
			$query .= $fields;
		}
	
		$query .= " FROM {$this->table} {$type} {$tableToJoin} ON {$onCondition}";
	
		if (!empty($conditions)) {
			$query .= " WHERE ";
			$whereConditions = [];
	
			foreach ($conditions as $field => $value) {
				$whereConditions[] = "$field = :$field";
			}
	
			$query .= implode(" AND ", $whereConditions);
		}
	
		// Adicione a cláusula LIMIT com o valor do limite e do offset
		$query .= " LIMIT :offset, :limit";
	
		$statement = $this->pdo->prepare($query);
	
		if (!empty($conditions)) {
			foreach ($conditions as $field => $value) {
				$statement->bindValue(":$field", $value);
			}
		}
	
		$statement->bindValue(':limit', $limit, PDO::PARAM_INT);
		$statement->bindValue(':offset', $offset, PDO::PARAM_INT);
	
		$statement->execute();
		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}	

}
