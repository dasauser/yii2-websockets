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
    const USERS = [
        'abcdefg' => 1,
        'hijklmn' => 2,
        'opqrstu' => 3,
        'vwxyz01' => 4,
        '2345678' => 5,
    ];
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
        $conn->send('connected');
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $decodedMsg = json_decode($msg, true);

        if ($this->isAuthMsg($decodedMsg)) {
            try {
                $this->clients->attach($from, $this->auth($from, $decodedMsg));
                $from->send('authenticated');
            } catch (\Throwable $exception) {
                $from->send($exception->getMessage());
                $from->close();
            }
            return;
        }

        if (!$this->isAuthenticated($from)) {
            $from->send('not authenticated');
            return;
        }

        foreach ($this->clients as $client) {
            if ($from === $client) {
                continue;
            }
            $client->send($msg);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $message = $e->getMessage();

        \Yii::getLogger()->log($message, LogLevel::ERROR);

        $conn->send('error: ' . $message);

        $conn->close();
    }

    public function onClose(ConnectionInterface $conn)
    {
        /** @var Connection $connection */
        $connection = $this->clients->offsetGet($conn);

        $connection->setScenario(COnnection::SCENARIO_CLOSE);

        if ($connection->update() === false) {
            $errors = $connection->getErrorSummary(true);
            $conn->send($errors);
            throw new ServerErrorHttpException($errors);
        }

        $this->clients->detach($conn);

        $conn->send('connection closed');
    }

    private function auth(ConnectionInterface $conn, array $msg): Connection
    {
        /** @var \Psr\Http\Message\RequestInterface $httpRequest */
        $httpRequest = $conn->httpRequest;

        $connection = new Connection([
            'scenario' => Connection::SCENARIO_OPEN,
            'token' => $msg['token'],
            'user_agent' => $httpRequest->getHeader('User-Agent')[0] ?? null,
            'user_id' => $this?->getUserId($msg['token']),
        ]);

        if (!$connection->save()) {
            throw new ServerErrorHttpException($connection->getErrorSummary(true));
        }

        return $connection;
    }

    /**
     * @return mixed
     */
    public function getUserId(string $token): ?int
    {
        // поиск юзера по токену.
        return self::USERS[$token] ?? null;
    }

    private function isAuthMsg(array $decodedMsg): bool
    {
        return count($decodedMsg) === 2
            && isset($decodedMsg['type'])
            && $decodedMsg['type'] === 'auth'
            && isset($decodedMsg['token'])
            && is_string($decodedMsg['token'])
            ;
    }

    private function isAuthenticated(ConnectionInterface $from): bool
    {
        return $this->clients->offsetExists($from);
    }
}