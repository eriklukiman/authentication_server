<?php

namespace Lukiman\AuthServer\Modules;

use Lukiman\AuthServer\Libraries\BaseApiModule;
use Lukiman\AuthServer\Libraries\Logger;
use Lukiman\AuthServer\Models\FileTagging;
use Lukiman\AuthServer\Models\TaggingText;
use Lukiman\Cores\Exception\NotFoundException;
use \Lukiman\Cores\Database\Query as Database_Query;

class Images extends BaseApiModule
{
    private $filterParam = 'filters';
    private $orderParam = 'orders';

    public function do_Index(array $param)
    {
        Logger::info("Path: " . json_encode($param));
        if (empty($param)) {
            return $this->getImages($this->request->getGetVars());
        }
        // param is array with example input: ["Images","mias","miau","mau","RsGSXOYi0JL.png"]
        // clear the elements, convert to lowercase except the last one which is the filename
        $param = array_map(function($item) use ($param) {
            if ($item === end($param)) {
                return $item;
            }
            return strtolower($item);
        }, $param);

        // Base upload dir is on constant UPLOAD_FILE_DIR
        // Find the file in the upload dir with the filename from param
        $filePath = UPLOAD_FILE_DIR . '/' . implode('/', $param);
        if (!file_exists($filePath)) {
            Logger::info("Path: " . implode('/', $param));
            Logger::error('File not found: ' . $filePath);
            throw new NotFoundException('File not found', 404);
        }

        // Serve file with chunks 8KB to display in browser
        header('Content-Type: ' . mime_content_type($filePath));
        header('Content-Length: ' . filesize($filePath));
        header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
        readfile($filePath);
        exit;
    }

    public function getImages(array $query): array 
    {
        $model = new FileTagging();
        $textModel = new TaggingText();
        $q = Database_Query::Grid($model->getTable());
        $q->setRequest($this->request);
        
        $filters = $this->buildFindFilter($query);
		if (!empty($filters)) {
			foreach ($filters as $key => $value) {
				if ($value['operator'] == 'LIKE') {
					$q->where($value['field'] . ' ' . $value['operator'] . ' ' .  '"%' . $value['value'] . '%"');
				} else if ($value['operator'] == 'IN') {
					$q->where($value['field'], $value['value'], $value['operator']);
				} else {
					$q->where($value['field'], $value['value'], $value['operator']);
				}
			}
		}

		$orders = $this->buildSortFilter($query);
		if (!empty($orders)) {
			foreach ($orders as $key => $value) {
				$q->order($key, $value);
			}
		}

        if (isset($query['search']) && !empty($query['search'])) {
            $search = $query['search'];
            $q->where("EXISTS(
                SELECT 1 FROM {$textModel->getTable()} AS tt
                WHERE tt.mftxId = {$model->getTable()}.mftgId
                AND (tt.mftxText = '{$search}' OR tt.mftxNumber = '{$search}')
            )");
        }

        $data = $q->execute($model->getDb());
		$ret = array('data' => []);
		while ($v = $data->next()) {
			$v = (array) $v;
			$ret['data'][] = $v;
		}
		$ret['pagination'] = $q->getGridInfo();

		return $ret;

    }

    protected function buildFindFilter(array $get) : array {
		$retArray = [];

		if (!empty($get[$this->filterParam]) AND is_array($get[$this->filterParam])) {
			foreach ($get[$this->filterParam] as $key => $value) {
				$where = [
					'field'		=> $key,
					'operator'	=> 'LIKE',
					'value'		=> $value,
				];
				if (is_numeric($value)) {
					$where['operator'] = '=';
				} else if (is_array($value)) {
					$where['operator'] = 'IN';
				}
				$retArray[] = $where;
			}
		}

		return $retArray;
	}

	protected function buildSortFilter(array $get) : array {
		$retArray = [];

		if (!empty($get[$this->orderParam]) AND is_array($get[$this->orderParam])) {
			foreach ($get[$this->orderParam] as $key => $value) {
				$retArray[$key] = $value;
			}
		}

		return $retArray;
	}
}