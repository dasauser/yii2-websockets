<?php

namespace app\commands;

use app\models\Connection;
use Psr\Log\LogLevel;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\MessageComponentInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use yii\console\Controller;
use yii\web\ServerErrorHttpException;

class ServerController extends Controller implements MessageComponentInterface
{
    private \SplObjectStorage $clients;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->clients = new \SplObjectStorage;
    }

    public function actionStart(int $port = 21080)
    {
        $server = IoServer::factory(new HttpServer(new WsServer($this)), $port);
        $server->run();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        /** @var \Psr\Http\Message\RequestInterface $httpRequest */
        $httpRequest = $conn->httpRequest;

        $params = [];
        parse_str($httpRequest->getUri()->getQuery(), $params);
        $token = $params['token'] ?? null;

        if ($token !== 'validToken') {
            $conn->send('Invalid token');
            $conn->close();
            return;
        }

        $userId = 1;

        $connection = new Connection([
            'scenario' => Connection::SCENARIO_OPEN,
            'token' => $token,
            'user_agent' => $httpRequest->getHeader('User-Agent'),
            'user_id' => $userId,
        ]);

        if (!$connection->save()) {
            $conn->send('can not open connection');
            $conn->close();
            throw new ServerErrorHttpException('can not open connection');
        }

        $conn->send('connected');
        $this->clients->attach($conn, $connection);
    }

    public function onClose(ConnectionInterface $conn)
    {
        /** @var Connection $connection */
        $connection = $this->clients->offsetGet($conn);
        $connection->setScenario(COnnection::SCENARIO_CLOSE);
        if ($connection->update() === false) {
            $conn->send('can not close connection');
            throw new ServerErrorHttpException('can not close connection');
        }
        $this->clients->detach($conn);
        $conn->send('connection closed');
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        \Yii::getLogger()->log($e->getMessage(), LogLevel::ERROR);
        $conn->close();
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $numRecv = count($this->clients) - 1;
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send($msg);
            }
        }
    }
}