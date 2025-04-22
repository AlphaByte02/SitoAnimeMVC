<?php

namespace Mvc;

use Mvc\Helpers\Singleton;
use PDO;
use PDOException;
use PDOStatement;

/**
 * Classe Database che utilizza PDO per l'interazione con il database.
 * Supporta diversi driver (es. MySQL, PostgreSQL) tramite configurazione.
 * Utilizza prepared statements per sicurezza e portabilità.
 */
class Database extends Singleton
{
    /** @var PDO|null Connessione PDO al database */
    protected ?PDO $db = null;

    /** @var int Livello di annidamento della transazione */
    protected int $transactionLevel = 0;

    /** @var string Query SQL in costruzione */
    protected string $query = "";

    /** @var array Valori da associare ai segnaposto nella query */
    protected array $bindings = [];

    /** @var string Nome della tabella corrente */
    protected string $table = "";

    /** @var int Numero di righe affette dall'ultima query INSERT/UPDATE/DELETE */
    protected int $affectedRow = 0;

    /** @var string|false ID dell'ultima riga inserita */
    protected string|false $lastInsertedId = false;

    /** @var int Numero di righe restituite dall'ultima query SELECT */
    protected int $numRows = 0;

    /** @var array Righe restituite dall'ultima query SELECT */
    protected array $rows = [];

    /** @var string|null Ultimo messaggio di errore del database */
    protected ?string $lastError = null;

    /** @var PDOStatement|false Oggetto statement PDO risultante dalla preparazione */
    protected PDOStatement|false $statement = false;


    /**
     * Inizializza la connessione al database usando PDO.
     * Richiede che la configurazione includa 'driver', 'hostname', 'dbname', 'user', 'passwd'.
     * Opzionalmente può includere 'port' e 'charset'.
     *
     * @throws \Exception Se la connessione fallisce o la configurazione è incompleta/errata.
     * @return self
     */
    public function init(): self
    {
        if (!is_null($this->db)) {
            return $this; // Già inizializzato
        }

        $config = config("database");

        // Verifica che i parametri essenziali siano presenti
        $requiredKeys = ['driver', 'hostname', 'dbname', 'user', 'passwd'];
        foreach ($requiredKeys as $key) {
            if (!isset($config[$key])) {
                throw new \Exception("Configurazione database incompleta. Manca la chiave: '$key'.");
            }
        }

        $driver = strtolower(trim($config['driver'])); // Driver in minuscolo
        $host = $config['hostname'];
        $dbName = $config['dbname'];
        $user = $config['user'];
        $pass = $config['passwd'];
        $port = $config['port'] ?? null; // Porta opzionale

        $dsn = "";

        // Costruisci il DSN (Data Source Name) in base al driver
        switch ($driver) {
            case 'mysql':
                $dsn = "mysql:host=$host;dbname=$dbName";
                if ($port) $dsn .= ";port=$port"; // Aggiungi porta se specificata

                $charset = $config['charset'] ?? 'utf8mb4'; // Default a utf8mb4 se non specificato
                $dsn .= ";charset=$charset"; // Charset per MySQL nel DSN
                break;

            case 'pgsql':
                $dsn = "pgsql:host=$host;dbname=$dbName";
                if ($port) $dsn .= ";port=$port"; // Aggiungi porta se specificata
                break;

            // Aggiungi altri driver se necessario (es. sqlite, sqlsrv)
            // case 'sqlite':
            //     $dsn = "sqlite:" . $config['dbfile']; // SQLite usa un percorso file
            //     break;

            default:
                throw new \Exception("Driver database non supportato: '$driver'.");
        }

        // Opzioni standard per PDO
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lancia eccezioni per errori SQL
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,      // Restituisce array associativi
            // PDO::ATTR_EMULATE_PREPARES   => false,                 // Usa prepared statements nativi del DB
            // PDO::ATTR_PERSISTENT => true, // Considera se hai bisogno di connessioni persistenti (valuta pro/contro)
        ];

        try {
            // Crea l'istanza PDO
            $this->db = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // Gestione errore connessione
            // Non mostrare $e->getMessage() direttamente in produzione per sicurezza
            $this->lastError = "Errore di connessione al database.";
            error_log("Errore Connessione PDO: " . $e->getMessage()); // Logga l'errore reale
            throw new \Exception($this->lastError); // Rilancia un'eccezione generica
        }

        $this->resetQuery(); // Resetta lo stato interno
        return $this;
    }

    /**
     * Resetta lo stato della query builder.
     */
    public function resetQuery(): void
    {
        $this->query = "";
        $this->bindings = [];
        $this->table = "";
        $this->rows = [];
        $this->numRows = 0;
        $this->affectedRow = 0;
        $this->lastInsertedId = false;
        $this->lastError = null;
        // Non chiudere lo statement qui, potrebbe servire dopo run() per ispezione
        // if ($this->statement) {
        //     $this->statement->closeCursor();
        // }
        $this->statement = false;
    }

    /**
     * Appende una stringa alla query SQL in costruzione.
     *
     * @param string $query Parte della query da aggiungere.
     * @return self
     */
    protected function setQuery(string $query): self
    {
        $this->query .= " " . trim($query);
        $this->query = trim($this->query);
        return $this;
    }

    /**
     * Aggiunge un valore all'array dei bindings per i prepared statements.
     *
     * @param mixed $value Il valore da associare.
     * @return self
     */
    protected function addBinding($value): self
    {
        $this->bindings[] = $value;
        return $this;
    }

    /**
     * Helper per quotare identificatori (nomi tabelle/colonne).
     * Usa virgolette doppie (standard SQL, supportato da PgSQL) o backtick (MySQL).
     *
     * @param string $identifier Nome da quotare.
     * @return string Identificatore quotato.
     */
    protected function quoteIdentifier(string $identifier): string
    {
        if (!$this->db) {
           // Se non connesso, usa un default (potrebbe non essere perfetto)
           return "`" . str_replace("`", "``", $identifier) . "`";
        }
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        switch ($driver) {
            case 'pgsql':
            case 'sqlite': // SQLite supporta anche le doppie virgolette
                 // Standard SQL: virgolette doppie, raddoppia le virgolette doppie interne
                return '"' . str_replace('"', '""', $identifier) . '"';
            case 'mysql':
            default:
                // MySQL: backtick, raddoppia i backtick interni
                return '`' . str_replace('`', '``', $identifier) . '`';
        }
    }


    /**
     * Imposta la tabella per la query. Resetta la query precedente.
     *
     * @param string $table Nome della tabella.
     * @return self
     */
    public function table(string $table): self
    {
        $this->resetQuery();
        $this->table = $this->quoteIdentifier($table); // Quota il nome della tabella
        return $this;
    }

    /**
     * Inizia una query SELECT per tutte le colonne (*).
     *
     * @param string $table Nome della tabella.
     * @return self
     */
    public function selectAll(string $table): self
    {
        $this->table($table); // Usa il metodo table per resettare e quotare
        return $this->select(); // Chiama select senza colonne per ottenere '*'
    }

    /**
     * Inizia una query SELECT specificando le colonne.
     *
     * @param string ...$columns Nomi delle colonne (o '*' o vuoto per tutte).
     * @return self
     * @throws \Exception Se la tabella non è stata impostata.
     */
    public function select(string ...$columns): self
    {
        if (empty($this->table)) {
            throw new \Exception("La query DEVE iniziare con il metodo table().");
        }

        if (empty($columns) || $columns === ['*']) {
            $selectClause = '*';
        } else {
            // Quota ogni nome di colonna
            $quotedColumns = array_map([$this, 'quoteIdentifier'], $columns);
            $selectClause = implode(', ', $quotedColumns);
        }

        $query = "SELECT {$selectClause} FROM {$this->table}";
        return $this->setQuery($query);
    }

    /**
     * Inizia una query SELECT DISTINCT specificando le colonne.
     *
     * @param string ...$columns Nomi delle colonne (o '*' o vuoto per tutte).
     * @return self
     * @throws \Exception Se la tabella non è stata impostata.
     */
    public function selectDistinct(string ...$columns): self
    {
        if (empty($this->table)) {
            throw new \Exception("La query DEVE iniziare con il metodo table().");
        }

        if (empty($columns) || $columns === ['*']) {
            $selectClause = '*';
        } else {
            $quotedColumns = array_map([$this, 'quoteIdentifier'], $columns);
            $selectClause = implode(', ', $quotedColumns);
        }

        // Aggiunge DISTINCT alla query SELECT
        $query = "SELECT DISTINCT {$selectClause} FROM {$this->table}";
        return $this->setQuery($query);
    }

    // --- INSERT ---
    /**
     * Prepara una query INSERT.
     * Supporta inserimento singolo o multiplo.
     *
     * @param array $data Dati da inserire. Array associativo per riga singola, array di array associativi per righe multiple.
     * @param array|null $columns Colonne esplicite (opzionale, deduce da $data se null).
     * @return self
     * @throws \Exception Se manca la tabella, $data è vuoto o $data non è valido.
     */
    public function insert(array $data, ?array $columns = null): self
    {
        if (empty($this->table)) throw new \Exception("La query DEVE iniziare con il metodo table().");
        if (empty($data)) throw new \Exception("Nessun dato fornito per l'inserimento.");

        // Determina se è inserimento multiplo e ottieni le chiavi/colonne
        $isMultiInsert = is_array(reset($data)) && is_string(key(reset($data))); // Array di array associativi?
        $firstRow = $isMultiInsert ? reset($data) : $data;

        if (!is_array($firstRow) || empty($firstRow)) {
             throw new \Exception("Formato dati non valido per l'inserimento.");
        }

        // Se le colonne non sono specificate, prendile dalla prima riga
        if (empty($columns)) {
            $columns = array_keys($firstRow);
        }

        $quotedColumns = implode(", ", array_map([$this, 'quoteIdentifier'], $columns));
        $placeholders = "(" . implode(", ", array_fill(0, count($columns), "?")) . ")";

        $query = "INSERT INTO {$this->table} ({$quotedColumns}) VALUES ";

        if ($isMultiInsert) {
            $allPlaceholders = [];
            foreach ($data as $rowIndex => $row) {
                 if (!is_array($row)) throw new \Exception("Dati non validi nell'inserimento multiplo alla riga: $rowIndex");
                $allPlaceholders[] = $placeholders;
                // Aggiungi i bindings nell'ordine delle colonne specificate
                foreach ($columns as $colName) {
                    // Usa null se la chiave manca nella riga specifica
                    $this->addBinding($row[$colName] ?? null);
                }
            }
            $query .= implode(", ", $allPlaceholders);
        } else {
            // Inserimento singolo
            $query .= $placeholders;
            foreach ($columns as $colName) {
                $this->addBinding($data[$colName] ?? null);
            }
        }

        return $this->setQuery($query);
    }

    // --- UPDATE ---
    /**
     * Prepara una query UPDATE.
     *
     * @param array $data Dati da aggiornare (array associativo 'colonna' => 'valore').
     * @return self
     * @throws \Exception Se manca la tabella o $data è vuoto.
     */
    public function update(array $data): self
    {
        if (empty($this->table)) throw new \Exception("La query DEVE iniziare con il metodo table().");
        if (empty($data)) throw new \Exception("Nessun dato fornito per l'aggiornamento.");

        $setClauses = [];
        foreach ($data as $key => $value) {
            // Quota il nome della colonna e aggiungi segnaposto
            $setClauses[] = $this->quoteIdentifier($key) . " = ?";
            $this->addBinding($value); // Aggiungi il valore ai bindings
        }

        if (empty($setClauses)) {
             throw new \Exception("Nessun valore da aggiornare specificato."); // Teoricamente impossibile se $data non è vuoto
        }

        $query = "UPDATE {$this->table} SET " . implode(", ", $setClauses);
        return $this->setQuery($query);
    }

    // --- DELETE ---
    /**
     * Prepara una query DELETE.
     *
     * @return self
     * @throws \Exception Se manca la tabella.
     */
    public function delete(): self
    {
        if (empty($this->table)) throw new \Exception("La query DEVE iniziare con il metodo table().");

        $query = "DELETE FROM {$this->table}";
        return $this->setQuery($query);
    }

    // --- WHERE Conditions ---

    /**
     * Helper interno per aggiungere clausole WHERE/AND/OR.
     * Gestisce operatori, IS NULL, IS NOT NULL, IN, NOT IN.
     *
     * @param string $type Tipo di clausola ('WHERE', 'AND', 'OR').
     * @param string $column Nome della colonna.
     * @param mixed $value Valore da confrontare (o array per IN/NOT IN).
     * @param string $operator Operatore SQL (es. '=', '!=', 'LIKE', 'IN', 'IS NULL').
     * @return self
     * @throws \Exception Se la query non è iniziata o per operatori/valori non validi.
     */
    protected function addCondition(string $type, string $column, $value, string $operator = "="): self
    {
        if (empty($this->query)) {
            throw new \Exception("La query deve iniziare con SELECT, INSERT, UPDATE o DELETE prima di aggiungere condizioni.");
        }

        // Determina se usare WHERE o AND/OR
        $keyword = str_contains(strtoupper($this->query), " WHERE ") ? strtoupper($type) : "WHERE";
        $quotedColumn = $this->quoteIdentifier($column);
        $operator = strtoupper(trim($operator)); // Normalizza operatore

        $conditionSql = "";

        // Gestione IS NULL / IS NOT NULL
        if ($operator === 'IS NULL' || ($operator === '=' && is_null($value))) {
            $conditionSql = "{$quotedColumn} IS NULL";
        } elseif ($operator === 'IS NOT NULL' || (($operator === '!=' || $operator === '<>') && is_null($value))) {
            $conditionSql = "{$quotedColumn} IS NOT NULL";
        }
        // Gestione IN / NOT IN
        elseif ($operator === 'IN' || $operator === 'NOT IN') {
            if (!is_array($value)) {
                throw new \Exception("Il valore per l'operatore {$operator} deve essere un array.");
            }
            if (empty($value)) {
                // Gestione array vuoto per IN/NOT IN:
                // WHERE col IN () è errore SQL
                // WHERE col NOT IN () è sempre vero (ma potenzialmente non intuitivo)
                 if ($operator === 'IN') {
                     // WHERE col IN (): Equivale a non trovare nulla, aggiungi condizione sempre falsa
                     $conditionSql = "1 = 0"; // Condizione sempre falsa
                 } else { // NOT IN
                     // WHERE col NOT IN (): Equivale a non escludere nulla, non aggiungere la condizione
                     // O si potrebbe aggiungere una condizione sempre vera (1=1), ma è ridondante.
                      return $this; // Non aggiunge la condizione, il risultato non viene filtrato da questo NOT IN vuoto.
                 }
            } else {
                $placeholders = implode(',', array_fill(0, count($value), '?'));
                $conditionSql = "{$quotedColumn} {$operator} ({$placeholders})";
                foreach ($value as $val) {
                    $this->addBinding($val);
                }
            }
        }
        // Gestione operatori standard
        else {
            if (is_null($value)) {
                 throw new \Exception("Usare 'IS NULL' o 'IS NOT NULL' come operatore per valori NULL.");
            }
            $conditionSql = "{$quotedColumn} {$operator} ?";
            $this->addBinding($value);
        }

        $queryPart = "{$keyword} {$conditionSql}";
        return $this->setQuery($queryPart);
    }

    /**
     * Aggiunge la prima clausola WHERE.
     *
     * @param string $column Nome colonna.
     * @param mixed $value Valore (o array per IN/NOT IN).
     * @param string $operator Operatore (default '=', supporta '!=', '<>', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'IS NULL', 'IS NOT NULL').
     * @return self
     * @throws \Exception Se WHERE è già presente.
     */
    public function where(string $column, $value, string $operator = "="): self
    {
        if (str_contains(strtoupper($this->query), " WHERE ")) {
            throw new \Exception("WHERE già presente. Usare 'andWhere()' o 'orWhere()' per condizioni aggiuntive.");
        }
        return $this->addCondition("WHERE", $column, $value, $operator);
    }

    /**
     * Aggiunge una clausola AND WHERE.
     *
     * @param string $column Nome colonna.
     * @param mixed $value Valore (o array per IN/NOT IN).
     * @param string $operator Operatore (vedi where()).
     * @return self
     * @throws \Exception Se WHERE non è presente.
     */
    public function and(string $column, $value, string $operator = "="): self
    {
        if (!str_contains(strtoupper($this->query), " WHERE ")) {
            throw new \Exception("Aggiungere una clausola WHERE prima di usare 'andWhere()'.");
        }
        return $this->addCondition("AND", $column, $value, $operator);
    }

    /**
     * Aggiunge una clausola OR WHERE.
     *
     * @param string $column Nome colonna.
     * @param mixed $value Valore (o array per IN/NOT IN).
     * @param string $operator Operatore (vedi where()).
     * @return self
     * @throws \Exception Se WHERE non è presente.
     */
    public function or(string $column, $value, string $operator = "="): self
    {
        if (!str_contains(strtoupper($this->query), " WHERE ")) {
            throw new \Exception("Aggiungere una clausola WHERE prima di usare 'orWhere()'.");
        }
        return $this->addCondition("OR", $column, $value, $operator);
    }

        /**
     * Aggiunge la prima clausola WHERE verificando IS NULL.
     * Alias per where($column, null, 'IS NULL').
     *
     * @param string $column Nome colonna.
     * @return self
     */
    public function whereIsNull(string $column): self
    {
        return $this->where($column, null, 'IS NULL');
    }

    /**
     * Aggiunge una clausola AND WHERE verificando IS NULL.
     * Alias per andWhere($column, null, 'IS NULL').
     *
     * @param string $column Nome colonna.
     * @return self
     */
    public function andIsNull(string $column): self
    {
        return $this->andWhere($column, null, 'IS NULL');
    }

    /**
     * Aggiunge una clausola OR WHERE verificando IS NULL.
     * Alias per orWhere($column, null, 'IS NULL').
     *
     * @param string $column Nome colonna.
     * @return self
     */
    public function orIsNull(string $column): self
    {
        return $this->orWhere($column, null, 'IS NULL');
    }

    /**
     * Aggiunge la prima clausola WHERE verificando IS NOT NULL.
     * Alias per where($column, null, 'IS NOT NULL').
     *
     * @param string $column Nome colonna.
     * @return self
     */
    public function whereIsNotNull(string $column): self
    {
        return $this->where($column, null, 'IS NOT NULL');
    }

    /**
     * Aggiunge una clausola AND WHERE verificando IS NOT NULL.
     * Alias per andWhere($column, null, 'IS NOT NULL').
     *
     * @param string $column Nome colonna.
     * @return self
     */
    public function andIsNotNull(string $column): self
    {
        return $this->andWhere($column, null, 'IS NOT NULL');
    }

    /**
     * Aggiunge una clausola OR WHERE verificando IS NOT NULL.
     * Alias per orWhere($column, null, 'IS NOT NULL').
     *
     * @param string $column Nome colonna.
     * @return self
     */
    public function orIsNotNull(string $column): self
    {
        return $this->orWhere($column, null, 'IS NOT NULL');
    }


    // --- WHERE RAW (Usare con estrema cautela!) ---

    /**
     * Aggiunge una clausola WHERE grezza (raw).
     * ATTENZIONE: Bypassando i prepared statements, questa parte della query
     * è vulnerabile a SQL Injection se contiene input utente non sanitizzato!
     * Usare solo se strettamente necessario e con input sicuro/controllato.
     *
     * @param string $rawCondition Condizione SQL grezza (es. "DATE(created_at) = CURDATE()").
     * @param array $bindings Bindings opzionali per i segnaposto '?' presenti nella $rawCondition.
     * @param string $type Tipo di collegamento ('WHERE', 'AND', 'OR').
     * @return self
     */
    protected function addRawCondition(string $rawCondition, array $bindings = [], string $type = 'AND'): self
    {
        if (empty($this->query)) throw new \Exception("La query deve iniziare prima di aggiungere condizioni.");

        $keyword = str_contains(strtoupper($this->query), " WHERE ") ? strtoupper($type) : "WHERE";

        $queryPart = "{$keyword} ({$rawCondition})"; // Parentesi per sicurezza

        // Aggiungi eventuali bindings passati
        foreach ($bindings as $binding) {
            $this->addBinding($binding);
        }

        return $this->setQuery($queryPart);
    }

     /**
     * Aggiunge la prima clausola WHERE raw. Vedi avvertenze su addRawCondition.
     * @param string $rawCondition Condizione SQL grezza.
     * @param array $bindings Bindings opzionali per i segnaposto '?'.
     * @return self
     */
    public function whereRaw(string $rawCondition, array $bindings = []): self
    {
        if (str_contains(strtoupper($this->query), " WHERE ")) throw new \Exception("WHERE già presente. Usare 'andWhereRaw()' o 'orWhereRaw()'.");
        return $this->addRawCondition($rawCondition, $bindings, 'WHERE');
    }

    /**
     * Aggiunge una clausola AND WHERE raw. Vedi avvertenze su addRawCondition.
     * @param string $rawCondition Condizione SQL grezza.
     * @param array $bindings Bindings opzionali per i segnaposto '?'.
     * @return self
     */
    public function andWhereRaw(string $rawCondition, array $bindings = []): self
    {
         if (!str_contains(strtoupper($this->query), " WHERE ")) throw new \Exception("Aggiungere WHERE prima di 'andWhereRaw()'.");
        return $this->addRawCondition($rawCondition, $bindings, 'AND');
    }

     /**
     * Aggiunge una clausola OR WHERE raw. Vedi avvertenze su addRawCondition.
     * @param string $rawCondition Condizione SQL grezza.
     * @param array $bindings Bindings opzionali per i segnaposto '?'.
     * @return self
     */
    public function orWhereRaw(string $rawCondition, array $bindings = []): self
    {
         if (!str_contains(strtoupper($this->query), " WHERE ")) throw new \Exception("Aggiungere WHERE prima di 'orWhereRaw()'.");
        return $this->addRawCondition($rawCondition, $bindings, 'OR');
    }

    /**
     * Aggiunge una clausola WHERE con una subquery raw.
     * ATTENZIONE: La subquery fornita ($subQuery) non viene parametrizzata
     * ed è vulnerabile a SQL Injection se contiene input non sicuro!
     * Usare con estrema cautela.
     *
     * @param string $column Colonna da confrontare.
     * @param string $subQuery La subquery SQL grezza (es. "SELECT id FROM other_table WHERE ...").
     * @param string $operator Operatore di confronto (es. '=', 'IN', 'NOT IN', '>', etc.). Default '='.
     * @return self
     * @throws \Exception Se WHERE è già presente.
     */
    public function whereRawSubQuery(string $column, string $subQuery, string $operator = "="): self
    {
        if (str_contains(strtoupper($this->query), " WHERE ")) {
            throw new \Exception("WHERE già presente. Usare 'andWhereRawSubQuery()' o 'orWhereRawSubQuery()' per condizioni aggiuntive.");
        }
        if (empty($this->query)) {
            throw new \Exception("La query deve iniziare prima di aggiungere condizioni.");
        }

        $keyword = "WHERE";
        $quotedColumn = $this->quoteIdentifier($column);
        $operator = strtoupper(trim($operator));

        // La subquery è inserita direttamente - ATTENZIONE ALLA SICUREZZA
        $conditionSql = "{$quotedColumn} {$operator} ({$subQuery})";

        $queryPart = "{$keyword} {$conditionSql}";
        return $this->setQuery($queryPart);

        // TODO: Implementare andWhereRawSubQuery e orWhereRawSubQuery se necessario,
        // seguendo la logica di andWhereRaw/orWhereRaw ma costruendo la condizione come sopra.
    }


    // --- JOIN ---
    /**
     * Aggiunge una clausola JOIN alla query.
     *
     * @param string $table Tabella a cui fare il join.
     * @param string $on Condizione di join (es. "users.id = posts.user_id").
     * @param string $type Tipo di join (es. 'INNER', 'LEFT', 'RIGHT', 'FULL OUTER'). Default 'INNER'.
     * @return self
     * @throws \Exception Se la query non è iniziata o il tipo di join non è valido.
     */
    public function join(string $table, string $on, string $type = "INNER"): self
    {
        if (empty($this->query)) {
            throw new \Exception("La query deve iniziare prima di aggiungere un JOIN.");
        }

        $type = strtoupper(trim($type));
        // Validazione base del tipo di JOIN
        $allowedTypes = ["INNER", "NATURAL", "CROSS", "LEFT" , "LEFT OUTER", "NATURAL LEFT", "RIGHT" , "RIGHT OUTER", "NATURAL RIGHT", "FULL" , "FULL OUTER" /*. "SELF" */];
        if (!in_array($type, $allowedTypes)) {
            throw new \Exception("Tipo di JOIN non valido: '$type'.");
        }

        // Quota la tabella del join
        $quotedTable = $this->quoteIdentifier($table);

        // Nota: La condizione $on è trattata come raw. Se contiene input utente, deve essere sanitizzata!
        // Idealmente, le condizioni di join dovrebbero usare identificatori quotati.
        // Es: $this->join('posts', $this->quoteIdentifier('users.id') . ' = ' . $this->quoteIdentifier('posts.user_id'))
        $queryPart = "{$type} JOIN {$quotedTable} ON {$on}";

        return $this->setQuery($queryPart);
    }

    // --- ORDER BY ---
    /**
     * Aggiunge una clausola ORDER BY.
     *
     * @param string ...$columns Colonne per l'ordinamento (es. 'name ASC', 'date DESC').
     * @return self
     * @throws \Exception Se la query non è iniziata o non vengono fornite colonne.
     */
    public function orderBy(string ...$columns): self
    {
        if (empty($this->query)) throw new \Exception("La query deve iniziare prima di aggiungere ORDER BY.");
        if (empty($columns)) throw new \Exception("Specificare almeno una colonna per ORDER BY.");

        // Nota: Non quota automaticamente le colonne qui perché potrebbero contenere ASC/DESC o funzioni.
        // L'utente è responsabile di passare nomi di colonna validi e sicuri.
        // Se si volesse quotare, bisognerebbe separare il nome dalla direzione (ASC/DESC).
        $orderByClause = implode(', ', $columns);

        $queryPart = "ORDER BY {$orderByClause}";
        return $this->setQuery($queryPart);
    }

    /**
     * Aggiunge DESC all'ultima clausola ORDER BY aggiunta.
     *
     * @return self
     * @throws \Exception Se la query non contiene ORDER BY.
     */
    public function orderDesc(): self
    {
        if (empty($this->query)) {
            throw new \Exception("La query deve iniziare prima di aggiungere ORDER BY DESC.");
        }
        // Cerca l'ultima occorrenza di ORDER BY case-insensitive
        $orderByPos = strripos($this->query, "ORDER BY");
        if ($orderByPos === false) {
            throw new \Exception("La query deve contenere ORDER BY prima di usare orderDesc().");
        }

        // Verifica che non ci sia già un LIMIT/OFFSET dopo ORDER BY
        // (Questo controllo è semplice, potrebbe non coprire tutti i casi)
        $stringAfterOrderBy = substr($this->query, $orderByPos);
        if (strripos($stringAfterOrderBy, "LIMIT ") !== false || strripos($stringAfterOrderBy, "OFFSET ") !== false) {
             throw new \Exception("orderDesc() deve essere chiamato subito dopo orderBy().");
        }

        // Appende DESC alla fine della query attuale
        return $this->setQuery("DESC");
    }

    // --- LIMIT / OFFSET ---
    /**
     * Aggiunge una clausola LIMIT e OFFSET (opzionale).
     * Gestisce la sintassi diversa tra i database (es. MySQL vs PostgreSQL).
     *
     * @param int $limit Numero massimo di righe da restituire.
     * @param int $offset Numero di righe da saltare (inizia da 0).
     * @return self
     * @throws \Exception Se la query non è iniziata.
     */
    public function limit(int $limit, int $offset = 0): self
    {
        if (empty($this->query)) throw new \Exception("La query deve iniziare prima di aggiungere LIMIT.");
        if ($limit <= 0) throw new \Exception("Il limite deve essere un intero positivo.");
        if ($offset < 0) throw new \Exception("L'offset non può essere negativo.");


        $driver = $this->db ? $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) : null;

        // Sintassi standard SQL (PostgreSQL, SQLite)
        if ($driver === 'pgsql' || $driver === 'sqlite') {
             $queryPart = "LIMIT ? OFFSET ?";
             $this->addBinding($limit);
             $this->addBinding($offset);
        }
        // Sintassi MySQL
        elseif ($driver === 'mysql') {
             $queryPart = "LIMIT ?, ?";
             // ATTENZIONE: MySQL vuole prima l'offset, poi il limit
             $this->addBinding($offset);
             $this->addBinding($limit);
        }
        // Default (prova sintassi standard, potrebbe fallire su altri DB)
        else {
             $queryPart = "LIMIT ? OFFSET ?";
             $this->addBinding($limit);
             $this->addBinding($offset);
        }

        return $this->setQuery($queryPart);
    }

    // --- EXECUTION ---

    /**
     * Esegue la query costruita utilizzando prepared statements.
     *
     * @return bool True in caso di successo, False in caso di fallimento.
     * I risultati sono disponibili tramite getRows(), getAffectedRow(), ecc.
     * @throws \Exception Se la connessione non è attiva o la query è vuota.
     */
    public function run(): bool
    {
        if (empty($this->query)) {
            $this->lastError = "Nessuna query da eseguire.";
            return false; // O lancia eccezione? Dipende dalla filosofia
            // throw new \Exception($this->lastError);
        }
        if (!$this->db) {
            throw new \Exception("Connessione al database non inizializzata.");
        }

        // Resetta risultati precedenti prima di eseguire
        $this->rows = [];
        $this->numRows = 0;
        $this->affectedRow = 0;
        $this->lastInsertedId = false;
        $this->lastError = null;

        try {
            // Prepara la query
            $this->statement = $this->db->prepare($this->query);

            if (!$this->statement) {
                // Questo non dovrebbe accadere con PDO::ERRMODE_EXCEPTION attivo
                 $errorInfo = $this->db->errorInfo();
                 $this->lastError = "[PREPARE FAILED] " . ($errorInfo[2] ?? 'Errore sconosciuto');
                 return false;
            }

            // Esegui con i bindings raccolti
            $success = $this->statement->execute($this->bindings);

            if (!$success) {
                // Anche questo non dovrebbe accadere con PDO::ERRMODE_EXCEPTION
                $errorInfo = $this->statement->errorInfo();
                $this->lastError = "[EXECUTE FAILED] " . ($errorInfo[2] ?? 'Errore sconosciuto');
                return false;
            }

            // Determina il tipo di query per gestire i risultati
            // Controlla l'inizio della stringa query (ignorando spazi e maiuscole/minuscole)
            $queryStart = strtoupper(ltrim($this->query));

            // Gestisci i risultati
            if (str_starts_with($queryStart, 'SELECT') || str_starts_with($queryStart, 'SHOW') /* Altri comandi che restituiscono righe? */) {
                $this->rows = $this->statement->fetchAll(PDO::FETCH_ASSOC);
                // rowCount() non è affidabile per SELECT in molti driver, contiamo l'array risultante
                $this->numRows = count($this->rows);
                $this->affectedRow = 0; // Non applicabile
            } elseif (str_starts_with($queryStart, 'INSERT')) {
                $this->affectedRow = $this->statement->rowCount();
                // Ottieni l'ultimo ID inserito
                try {
                     // Per PostgreSQL, potrebbe essere necessario il nome della sequenza se non si usa SERIAL/IDENTITY
                     // Es: $this->db->lastInsertId('nome_tabella_id_seq');
                     // Prova senza nome, funziona spesso con SERIAL/IDENTITY.
                    $this->lastInsertedId = $this->db->lastInsertId();
                } catch (PDOException $e) {
                     // lastInsertId potrebbe fallire se la tabella non ha auto-incremento
                     // o se il driver non lo supporta in questo contesto.
                     $this->lastInsertedId = false;
                     // Potresti voler loggare l'errore $e->getMessage() qui
                }
                $this->numRows = 0; // Non applicabile
            } elseif (str_starts_with($queryStart, 'UPDATE') || str_starts_with($queryStart, 'DELETE')) {
                $this->affectedRow = $this->statement->rowCount();
                $this->numRows = 0; // Non applicabile
                $this->lastInsertedId = false; // Non applicabile
            } else {
                // Altri tipi di query (CREATE, ALTER, DROP, etc.)
                $this->affectedRow = $this->statement->rowCount(); // Potrebbe dare 0 o un numero > 0
                $this->numRows = 0;
                $this->lastInsertedId = false;
            }

            // Chiudi il cursore per liberare risorse (importante per alcuni driver/configurazioni)
            $this->statement->closeCursor();

            // Non resettare query/bindings se il debug è attivo (opzionale)
            // if (!config("debug", false)) {
                 // Potrebbe essere utile mantenere query/bindings per debug post-run
            // }

            return true; // Esecuzione riuscita

        } catch (PDOException $e) {
            // Cattura errori PDO durante prepare() o execute()
            $this->lastError = $e->getMessage();
            error_log("Errore PDO: " . $e->getMessage() . " | Query: " . $this->query . " | Bindings: " . print_r($this->bindings, true));
            // Chiudi il cursore anche in caso di errore, se lo statement è stato creato
            $this->statement?->closeCursor();
            return false; // Esecuzione fallita
        }
    }


    /**
     * Esegue una query SQL grezza senza preparazione automatica o bindings di questa classe.
     * Usa PDO::query() per SELECT o PDO::exec() per INSERT/UPDATE/DELETE/DDL.
     *
     * ATTENZIONE: Estremamente pericoloso se $query contiene input utente non validato/sanitizzato!
     * Non offre protezione contro SQL Injection per la query fornita.
     * Usare solo per query sicure, controllate o DDL.
     *
     * @param string $query La stringa SQL completa da eseguire.
     * @return bool|int|array False in caso di fallimento.
     * Per SELECT: array dei risultati.
     * Per INSERT/UPDATE/DELETE/DDL: numero di righe affette.
     * @throws \Exception Se la connessione non è attiva.
     */
    public function runRawQuery(string $query)
    {
        if (!$this->db) {
            throw new \Exception("Connessione al database non inizializzata.");
        }
        // Resetta lo stato interno prima di eseguire la query raw
        $this->resetQuery();
        // Memorizza la query raw per riferimento (debug)
        $this->query = $query; // Nota: i bindings saranno vuoti

        try {
            $queryStart = strtoupper(ltrim($query));

            if (str_starts_with($queryStart, 'SELECT') || str_starts_with($queryStart, 'SHOW')) {
                // Usa query() per SELECT, restituisce PDOStatement o false
                $stmt = $this->db->query($query);
                if ($stmt instanceof PDOStatement) {
                    $this->rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $this->numRows = count($this->rows);
                    $stmt->closeCursor();
                    return $this->rows; // Ritorna l'array dei risultati
                } else {
                    // query() ha fallito
                    $errorInfo = $this->db->errorInfo();
                    $this->lastError = "[RAW QUERY FAILED] " . ($errorInfo[2] ?? 'Errore sconosciuto');
                    return false;
                }
            } else {
                // Usa exec() per INSERT, UPDATE, DELETE, CREATE, ALTER, DROP etc.
                // Restituisce il numero di righe affette o false
                $affected = $this->db->exec($query);

                if ($affected === false) {
                    // exec() ha fallito
                    $errorInfo = $this->db->errorInfo();
                    $this->lastError = "[RAW EXEC FAILED] " . ($errorInfo[2] ?? 'Errore sconosciuto');
                    return false;
                }
                // Successo
                $this->affectedRow = $affected;
                // Non si può ottenere lastInsertId in modo affidabile con exec()
                $this->lastInsertedId = false;
                return $affected; // Ritorna il numero di righe affette
            }
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Errore PDO Raw Query: " . $e->getMessage() . " | Raw Query: " . $this->query);
            return false; // Fallimento
        }
    }


    // --- TRANSACTIONS ---

    /**
     * Inizia una transazione o incrementa il livello di annidamento.
     * La vera transazione DB viene iniziata solo al primo livello.
     *
     * @return bool True se l'operazione logica è riuscita, False altrimenti.
     */
    public function beginTransaction(): bool
    {
        if (!$this->db) {
            $this->lastError = "Nessuna connessione per avviare la transazione.";
            return false;
        }

        // Se il livello è 0, inizia la vera transazione DB
        if ($this->transactionLevel === 0) {
            try {
                if (!$this->db->beginTransaction()) {
                    // Questo non dovrebbe accadere con ERRMODE_EXCEPTION, ma per sicurezza
                    $this->lastError = "Impossibile avviare la transazione a livello DB.";
                    return false;
                }
            } catch (PDOException $e) {
                $this->lastError = "Errore DB iniziando la transazione: " . $e->getMessage();
                error_log("Errore beginTransaction: " . $e->getMessage());
                return false;
            }
        }
        // Altrimenti (o se la beginTransaction DB è riuscita), incrementa il livello
        $this->transactionLevel++;
        return true;
    }

    /**
     * Esegue il commit della transazione se è il livello più esterno,
     * altrimenti decrementa solo il livello di annidamento.
     *
     * @return bool True se l'operazione logica è riuscita, False altrimenti.
     */
    public function commit(): bool
    {
        if (!$this->db) {
             $this->lastError = "Nessuna connessione per il commit.";
             return false;
        }

        // Non puoi committare se non sei in una transazione
        if ($this->transactionLevel <= 0) {
            $this->lastError = "Nessuna transazione attiva da committare.";
            $this->transactionLevel = 0; // Assicura che sia 0
            return false;
        }

        // Decrementa il livello
        $this->transactionLevel--;

        // Se il livello raggiunge 0, esegui il commit reale nel DB
        if ($this->transactionLevel === 0) {
            try {
                if (!$this->db->commit()) {
                     // Non dovrebbe accadere con ERRMODE_EXCEPTION
                     $this->lastError = "Impossibile eseguire il commit della transazione a livello DB.";
                     // Tenta un rollback come misura di sicurezza? Potrebbe essere rischioso
                     // $this->rollBack();
                     return false;
                }
            } catch (PDOException $e) {
                $this->lastError = "Errore DB durante il commit: " . $e->getMessage();
                error_log("Errore commit: " . $e->getMessage());
                // Tenta un rollback qui perché il commit è fallito
                try { $this->db->rollBack(); } catch (PDOException $re) { /* Ignora errore rollback */ }
                return false;
            }
        }
        // Se eravamo in una transazione annidata o il commit DB è riuscito
        return true;
    }

    /**
     * Esegue il rollback dell'intera transazione, indipendentemente dal livello di annidamento.
     * Resetta il livello di transazione a 0.
     *
     * @return bool True se il rollback è riuscito, False altrimenti (o se non c'era transazione attiva).
     */
    public function rollBack(): bool
    {
         if (!$this->db) {
             $this->lastError = "Nessuna connessione per il rollback.";
             return false;
         }

        // Non puoi fare rollback se non c'è una transazione attiva (a nessun livello)
        if ($this->transactionLevel <= 0) {
            $this->lastError = "Nessuna transazione attiva per il rollback.";
            $this->transactionLevel = 0; // Assicura che sia 0
            return false;
        }

        // Se il livello è > 0, significa che una transazione DB *dovrebbe* essere attiva
        $rollbackSuccess = false;
        if ($this->db->inTransaction()) { // Controlla se PDO pensa di essere in transazione
             try {
                 $rollbackSuccess = $this->db->rollBack();
                 if (!$rollbackSuccess) {
                     $this->lastError = "Rollback a livello DB fallito.";
                 }
             } catch (PDOException $e) {
                 $this->lastError = "Errore DB durante il rollback: " . $e->getMessage();
                 error_log("Errore rollBack: " . $e->getMessage());
                 $rollbackSuccess = false; // Assicura che sia false
             }
        } else {
             // Se level > 0 ma PDO dice !inTransaction(), c'è un'incongruenza.
             // Meglio resettare il livello comunque.
             $this->lastError = "Incongruenza: livello transazione > 0 ma PDO non è in transazione.";
             error_log($this->lastError);
             $rollbackSuccess = false; // Consideralo un fallimento logico
        }


        // Resetta sempre il livello a 0 dopo un tentativo di rollback
        $this->transactionLevel = 0;

        return $rollbackSuccess; // Ritorna l'esito del rollback effettivo
    }

    /**
     * Chiude la transazione con un commit o con un rollback
     * @param bool $commit True se eseguire il commit, False se eseguire il rollback
     * @return bool True se l'operazione è riuscita, False altrimenti (o se non c'era transazione attiva).
     */
    public function endTransaction(bool $commit): bool
    {
        if ($commit) {
            return $this->commit();
        } else {
            return $this->rollback();
        }
    }

    /**
     * Verifica se una transazione è attualmente attiva.
     * @return bool True se una transazione è attiva, False altrimenti.
     */
    public function inTransaction(): bool
    {
        return $this->transactionLevel > 0;
    }


    // --- GETTERS for results and status ---

    /**
     * Restituisce le righe ottenute dall'ultima query SELECT.
     * @return array Array di array associativi.
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    /**
     * Restituisce la prima riga dei risultati dell'ultima query SELECT.
     * @return array|null Array associativo della prima riga, o null se non ci sono risultati.
     */
    public function getFirstRow(): ?array
    {
        return $this->rows[0] ?? null;
    }

     /**
     * Restituisce il numero di righe affette dall'ultima query INSERT, UPDATE o DELETE.
     * @return int
     */
    public function getAffectedRow(): int
    {
        return $this->affectedRow;
    }

    /**
     * Restituisce l'ID dell'ultima riga inserita.
     * Potrebbe restituire 0, false o lanciare un errore a seconda del driver e della tabella.
     * @return string|false L'ID come stringa, o false se non disponibile/applicabile.
     */
    public function getLastInsertId(): string|false // Corretto tipo restituito
    {
        return $this->lastInsertedId;
    }

    /**
     * Restituisce il numero di righe trovate dall'ultima query SELECT.
     * @return int
     */
    public function getNumRows(): int
    {
        // Per SELECT, questo valore è derivato contando l'array $rows dopo fetchAll.
        return $this->numRows;
    }

    /**
     * Verifica se l'ultima query SELECT non ha restituito righe.
     * @return bool True se non ci sono righe, False altrimenti.
     */
    public function isEmpty(): bool
    {
        return empty($this->rows);
    }

    /**
     * Restituisce l'ultimo messaggio di errore registrato.
     * @return string|null
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Restituisce l'ultima query SQL eseguita o in costruzione.
     * Utile per il debug.
     * @return string
     */
    public function getLastQuery(): string
    {
        return $this->query;
    }

    /**
     * Restituisce i bindings utilizzati per l'ultima query eseguita.
     * Utile per il debug.
     * @return array
     */
    public function getLastBindings(): array
    {
        return $this->bindings;
    }


    // --- CONNECTION MANAGEMENT ---

    /**
     * Chiude la connessione al database impostando la proprietà $db a null.
     * PDO gestisce la chiusura effettiva quando l'oggetto viene distrutto o lo script termina.
     */
    public function close(): void
    {
        // Chiudi eventuali cursori aperti dallo statement precedente
        if ($this->statement) {
            $this->statement?->closeCursor();
            $this->statement = false;
        }
        // Rilascia il riferimento all'oggetto PDO
        $this->db = null;
        // Resetta lo stato per sicurezza
        $this->resetQuery();
    }

    /**
     * Restituisce l'oggetto PDO sottostante.
     * Utile se hai bisogno di accedere a funzionalità specifiche di PDO non esposte da questa classe.
     * Usare con cautela per non interferire con lo stato interno della classe Database.
     * @return PDO|null
     */
    public function getPdoInstance(): ?PDO
    {
        return $this->db;
    }


    // --- DEBUG ---

    /**
     * Stampa informazioni di debug sullo stato corrente della classe.
     */
    public function debug(): void
    {
        echo "<pre>";
        echo "--- Database Debug ---\n";
        echo "Last Query:\n" . htmlspecialchars($this->getLastQuery() ?: 'N/A') . "\n\n";
        echo "Last Bindings:\n" . htmlspecialchars(print_r($this->getLastBindings(), true)) . "\n";
        echo "Last Error: " . htmlspecialchars($this->getLastError() ?: 'None') . "\n";
        echo "Transaction Level: " . htmlspecialchars($this->transactionLevel) . "\n";
        echo "Affected Rows: " . $this->getAffectedRow() . "\n";
        echo "Last Insert ID: " . ($this->getLastInsertId() !== false ? htmlspecialchars($this->getLastInsertId()) : 'N/A') . "\n";
        echo "Fetched Rows Count: " . $this->getNumRows() . "\n";
        echo "Fetched Rows:\n" . htmlspecialchars(print_r($this->getRows(), true));
        echo "----------------------\n";
        echo "</pre>";
    }

    /**
     * Metodo distruttore per assicurarsi che la connessione venga chiusa.
     */
    public function __destruct()
    {
        $this->close();
    }
}
