<?php

namespace Lukiman\AuthServer\Libraries;

use Lukiman\Cores\Database;
use \Lukiman\Cores\Model;
use \Lukiman\Cores\Database\Query as Database_Query;
use \Lukiman\Cores\Exception\Base as ExceptionBase;

class Schema {

	private string $ddlSchema = "
		CREATE TABLE IF NOT EXISTS schema_sync (
			`schmId` SERIAL PRIMARY KEY,
			`schmVersion` VARCHAR(255) NOT NULL DEFAULT '',
			`schmLatestFile` VARCHAR(1000) NOT NULL DEFAULT '',
			`schmCreatedUserId` VARCHAR(150) NOT NULL DEFAULT '',
			`schmCreatedTime` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			UNIQUE (`schmVersion`)
		);
	";

	private Model $model;

	private String $folder = 'Migrations/';

	public function __construct() {
		$db = Database::getInstance();
		$db->exec($this->ddlSchema);
		$this->model = Model::load('Schema');
	}

	public function get_History($param) : array {
		$data = Database_Query::Grid($this->model->getTable())->execute($this->model->getDb());
		$ret = [];
		$cnt = 0;
		while ($v = $data->next()) {
			$v = (array) $v;
			$ret['data'][] = $v;
			$cnt++;
		}

		return $ret;
	}

	public function get_Update($param) : string {
		$dir = $this->getFileList();

		$latest = $this->getLatestVersion();

		if (empty($latest)) $latest = 0;

		if (isset($param[0]) && is_numeric($param[0])) {
			$latest = intval($param[0]);
		}
		$updates = $this->getEligibleUpdateList($dir, $latest);

		$success = 0;

		echo "Current version : {$latest}" . PHP_EOL;


		foreach ($updates as $version => $file) {
			if (!file_exists($this->folder . $file)) throw new ExceptionBase("File \"{$file}\" not found!", 404);
			
			//execute
			echo PHP_EOL . "Applying file {$file} ";

			$content = file_get_contents($this->folder . $file);
			$lines = $this->splitScriptSorted($content);
			foreach ($lines as $line) {
				$this->model->getDb()->exec($line);
				$success++;
				echo ' # ';

				$errorInfo = $this->model->getDb()->errorInfo();
				if (!empty($errorInfo[2])) {
					exit(PHP_EOL . 'Error: ' . $errorInfo[0] . ' - ' . $errorInfo[2]);
				}
			}
			echo ' OK' . PHP_EOL;

			//update schema table
			$data = [
				'schmVersion' 		=> $version,
				'schmLatestFile'	=> $file,
			];

			try {
				Database_Query::Insert($this->model->getTable())->data($data)->execute($this->model->getDb());
			} catch (\Exception $e) {
				var_dump($e);
			}
		}
		return "Executed: {$success}";
	}

	/**
	 * Splits script and sorts the statements for correct execution order:
	 * 1. CREATE TABLE
	 * 2. CREATE FUNCTION/PROCEDURE
	 * 3. CREATE TRIGGER
	 *
	 * @param string $sqlScript The entire SQL script string.
	 * @return array An array of individual SQL statements, sorted for execution.
	 */
	function splitScriptSorted(string $sqlScript): array
	{
		// Remove single-line comments
		$sqlScript = preg_replace('/^\s*--.*$/m', '', $sqlScript);
		$sqlScript = trim($sqlScript);

		$statements = [];

		// Match PostgreSQL functions using $$ delimiter
		$functionRegex = '/
			(CREATE\s+(?:OR\s+REPLACE\s+)?(?:FUNCTION|PROCEDURE)\s+
			.*?
			AS\s+\$\$
			.*?
			\$\$;)
		/imsx';

		if (preg_match_all($functionRegex, $sqlScript, $matches)) {
			foreach ($matches[1] as $fn) {
				$statements[] = ['type' => 'FUNCTION', 'sql' => trim($fn)];
				$sqlScript = str_replace($fn, '', $sqlScript);
			}
		}

		// Remaining DDL (tables, indexes, etc)
		$ddlParts = preg_split('/;\s*(\r?\n|$)/', trim($sqlScript), -1, PREG_SPLIT_NO_EMPTY);

		foreach ($ddlParts as $ddl) {
			$ddl = trim($ddl);
			if ($ddl !== '') {
				$type = preg_match('/^CREATE\s+TRIGGER/i', $ddl)
					? 'TRIGGER'
					: 'DDL';

				$statements[] = ['type' => $type, 'sql' => $ddl . ';'];
			}
		}

		// Sort execution order
		usort($statements, fn($a, $b) =>
			['DDL' => 1, 'FUNCTION' => 2, 'TRIGGER' => 3][$a['type']]
			<=>
			['DDL' => 1, 'FUNCTION' => 2, 'TRIGGER' => 3][$b['type']]
		);

		return array_column($statements, 'sql');
	}

	private function getLatestVersion() : String {
		$return = '';
		try {
			$q = Database_Query::Select($this->model->getTable())
				->order($this->model->getPrimaryKey(), 'DESC')
				->limit(1)
				->columns(['schmVersion'])
				->execute($this->model->getDb());
			if ($q->count() > 0) {
				$result = (array) $q->next();
				$return = $result['schmVersion'];
			} else {
				return '';
			}
		} catch (ExceptionBase $e) { }
		
		return $return;
	}

	private function getEligibleUpdateList(array $updates, String $latest) : array {
		$historyFiles = $this->getSchemaHistory();
		krsort($updates);
		$return = [];
		foreach ($updates as $version => $file) {
			$isVersionExists = isset($historyFiles['versionList'][$version]);
			$isAlreadyExecuted = isset($historyFiles['schemaHistory'][$file]);
			if (!$isAlreadyExecuted && $isVersionExists) {
				echo trim("Version $version is already exists in DB. "
				."Please make sure file $file has unique version.").PHP_EOL;
			}
			if (!strcasecmp($latest, $version)) {
				break;
			}
			if ($isAlreadyExecuted || $isVersionExists) {
				continue;
			}
			$return[$version] = $file;
		}
		ksort($return);
		return $return;
	}

	private function getFileList() : array {
		$dir = scandir(ROOT_PATH . $this->folder, SCANDIR_SORT_ASCENDING);
		array_shift($dir); // remove . entry
		array_shift($dir); // remove .. entry

		$return = [];
		foreach($dir as $entry) {
			$key = substr($entry, 0, 14);
			$return[$key] = $entry;
		}
		return $return;
	}

	private function getSchemaHistory() : array {
		$return = ['versionList' => array(), 'schemaHistory' => array()];
		try {
			$q = Database_Query::Select($this->model->getTable())
				->order($this->model->getPrimaryKey(), 'DESC')	
				->columns(['schmVersion', 'schmLatestFile'])
				->execute($this->model->getDb());
			$result = (array) $q->fetchAll('array');
			if (!empty($result)) {
				$return['versionList'] = array_column($result, 'schmVersion','schmVersion');
				$return['schemaHistory'] = array_column($result, 'schmLatestFile','schmLatestFile');
			}
		} catch (ExceptionBase $e) { }
		
		return $return;
	}
}