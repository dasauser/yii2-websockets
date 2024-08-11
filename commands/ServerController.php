<?php

namespace app\commands;

use Psr\Log\LogLevel;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\MessageComponentInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use yii\console\Controller;

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
        $this->clients->attach($conn);
        $params = [];
        parse_str($conn->httpRequest->getUri()->getQuery(), $params);
        $token = $params['token'] ?? null;
        if ($token !== 'validToken') {
            $conn->send('Invalid token');
            $conn->close();
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);
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
                // The sender is not the receiver, send to each client connected
                $client->send($msg);
            }
        }
    }
}