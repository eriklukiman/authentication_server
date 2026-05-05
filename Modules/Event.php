<?php

namespace Lukiman\AuthServer\Modules;

use Lukiman\AuthServer\Libraries\BaseApiModule;
use Lukiman\AuthServer\Libraries\Logger;
use Lukiman\Cores\Exception\ServerErrorException;
use \Lukiman\Cores\Database\Query as Database_Query;
use Lukiman\AuthServer\Models\Event as EventModel;
use Lukiman\AuthServer\Models\ClientAllowedHost;
use Lukiman\AuthServer\Models\FileTagging;
use Lukiman\AuthServer\Models\Location;
use Lukiman\AuthServer\Models\TaggingText;

class Event extends BaseApiModule {

    /**
     * Apply CORS for gallery embed usage on Event endpoints only.
     */
    private function embedCorsHeaders(string $clientId): void {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if (empty($origin)) {
            return;
        }

        $uriScheme = $this->request->getUri()->getScheme();
        $uriHost = $this->request->getUri()->getHost();
        $uriPort = $this->request->getUri()->getPort();

        if ($uriPort === 80 || $uriPort === 443) {
            $uriPort = null;
        }

        $isSameOrigin = $origin === ($uriScheme . '://' . $uriHost . ($uriPort ? ':' . $uriPort : ''));

        if ($isSameOrigin) {
            return;
        }

        $allowedHostModel = new ClientAllowedHost();
        $originQuery = Database_Query::Select($allowedHostModel->getTable())
            ->limit(1)
            ->where('claoClientId', $clientId)
            ->where('claoOrigin', $origin)
            ->execute($allowedHostModel->getDb());

        if ($originQuery->count() === 0) {
            return;
        }

        $this->addHeaders([
            'Vary' => 'Origin',
            'Access-Control-Allow-Origin' => $origin,
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
            'Access-Control-Max-Age' => 600
        ]);
    }

    /**
     * Handle GET request for event resources by client.
     *
     * Behavior:
     * - List mode: when eventId is not provided, returns paginated events
     *   associated with the client.
     * - Detail mode: when eventId is provided, returns one event by ID or name
     *   and includes its associated locations.
     *
     * Path params:
     * - params[0]: clientId (required)
     * - params[1]: eventId (optional; numeric ID or URL-encoded event name)
     *
     * Response:
     * - List mode:
     *   [
     *     'data'       => array<int, array<string, mixed>>,
     *     'pagination' => array<string, mixed>
     *   ]
     * - Detail mode:
     *   [
     *     'data' => array<string, mixed>  // includes 'locations' key
     *   ]
     *
     * @param array<int, mixed> $params Route parameters.
     * @return array<string, mixed>
     *
     * @throws ServerErrorException If method is not GET (405),
     *                              if clientId is missing,
     *                              or if event is not found (404).
     */
    public function do_Index(array $params) {
        $method = strtolower($this->getRequest()->getMethod());

        if ($method != 'get' && $method != 'options') {
            throw new ServerErrorException('Method not allowed', 405);
        }
    
        $clientId = $params[0] ?? null;
        $eventId = $params[1] ?? null;

        if (empty($clientId)) {
            throw new ServerErrorException('Client ID is required');
        }

        $this->embedCorsHeaders($clientId);
        if ($method == 'options') {
            return [];
        }

        $eventModel = new EventModel();

        $eventQ = Database_Query::Grid($eventModel->getTable());

        $oneResult = false;

        if (!empty($eventId)) {
            $oneResult = true;
            $eventQ->limit(1);
            if (is_numeric($eventId)) {
                $eventQ->where('msevId', (int) $eventId);
            } else {
                $eventQ->where('msevName', urldecode($eventId));
            }
        }

        $eventQ->columns([
            'msevId',
            'msevName',
            'msevCreatedTime',
            'msevUpdatedTime'
        ]);
        $eventQ->join("event_client_association", "evcaMsevId=msevId", "LEFT");
        $eventQ->where('evcaClntId', $clientId);
        $eventQ->order('msevCreatedTime', 'DESC');

        $data = $eventQ->execute($eventModel->getDb());

        $returnData = [];

        $eventData = [];
        if ($oneResult) {
            $v = $data->next();
            if (empty($v)) {
                throw new ServerErrorException('Event not found', 404);
            }
            $v = (array) $v;

            // fetch associated locations in event
            $locationModel = new Location();
            $locationQ = Database_Query::Select($locationModel->getTable());
            $locationQ->where('mlocMsevId', $v['msevId']);
            $locationQ->select('mlocId, mlocName, mlocDescription, mlocCreatedTime, mlocUpdatedTime');
            $data = $locationQ->execute($locationModel->getDb());
            $locations = [];
            while ($loc = $data->next()) {
                $loc = (array) $loc;
                $locations[] = $loc;
            }
            $v['locations'] = $locations;
            $returnData['data'] = $v;
        } else {
            while ($v = $data->next()) {
                $v = (array) $v;
                $eventData[] = $v;
            }

            $events = array_map(fn($event) => $event['msevName'], $eventData);
            $photoEvents = $this->getTotalPhotosByEvent($events);

            $eventData = array_map(function ($event) use ($photoEvents) {
                $event['photoCount'] = $photoEvents[$event['msevName']] ?? 0;
                return $event;
            }, $eventData);

            $returnData['data'] = $eventData;
            $returnData['pagination'] = $eventQ->getGridInfo();
        }

        return $returnData;
    }

    private function getTotalPhotosByEvent(array $eventIds): array {
        if (empty($eventIds)) {
            return [];
        }

        $model = new FileTagging();
        $q = Database_Query::Select($model->getTable());
        $q->columns(['mftgEventName', 'COUNT(*) as photoCount']);
        $q->where('mftgEventName', $eventIds, 'IN');
        $q->group('mftgEventName');

        $data = $q->execute($model->getDb());
        $counts = [];
        while ($v = $data->next()) {
            $v = (array) $v;
            $counts[$v['mftgEventName']] = (int) $v['photoCount'];
        }
        return $counts;
    }

    public function do_Photo(array $params) {

        $method = strtolower($this->getRequest()->getMethod());

        if ($method != 'get' && $method != 'options') {
            throw new ServerErrorException('Method not allowed', 405);
        }

        Logger::info("Path: " . json_encode($params));
        $clientId = $params[0] ?? null;
        $eventId = $params[1] ?? null;

        if (empty($clientId)) {
            throw new ServerErrorException('Client ID is required');
        }

        $this->embedCorsHeaders($clientId);

        if ($method == 'options') {
            return [];
        }

        $query = $this->request->getGetVars();

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
            $search = trim(str_replace('*', '', $query['search']));
            $searchBoolean = $model->getDb()->quote($search . '*');
            $q->where("EXISTS(
                SELECT 1 FROM {$textModel->getTable()} AS tt
                WHERE tt.mftxId = {$model->getTable()}.mftgId
                AND MATCH(tt.mftxText) AGAINST ({$searchBoolean} IN BOOLEAN MODE)
            )");
        }

        // Join to event, event_client_association, and filter by clientId and eventId
        $q->join('master_events as ev', 'ev.msevName = mftgEventName', 'INNER');
        $q->join("event_client_association AS evca", "evca.evcaMsevId = ev.msevId", "INNER");
        $q->where("evca.evcaClntId", $clientId);
        if (!empty($eventId)) {
            if (is_numeric($eventId)) {
                $q->where("ev.msevId", (int) $eventId);
            } else {
                $q->where("ev.msevName", urldecode($eventId));
            }
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
}