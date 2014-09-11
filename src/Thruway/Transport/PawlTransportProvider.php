<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/7/14
 * Time: 5:06 PM
 */

namespace Thruway\Transport;

use Thruway\Manager\ManagerDummy;
use Thruway\Manager\ManagerInterface;
use Thruway\Peer\AbstractPeer;
use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use Ratchet\Client\WebSocket;
use React\EventLoop\LoopInterface;
use Thruway\Serializer\JsonSerializer;

/**
 * Class WebsocketClient
 * @package Thruway\Transport
 */
class PawlTransportProvider extends AbstractTransportProvider implements EventEmitterInterface
{
    use EventEmitterTrait;

    /**
     * @var AbstractPeer
     */
    private $peer;

    /**
     * @var string
     */
    private $URL;

    /**
     * @var \React\EventLoop\ExtEventLoop|\React\EventLoop\LibEventLoop|\React\EventLoop\LibEvLoop|\React\EventLoop\StreamSelectLoop
     */
    private $loop;

    /**
     * @var \Ratchet\Client\Factory
     */
    private $connector;

    /**
     * @var ManagerInterface
     */
    private $manager;


    function __construct($URL = "ws://127.0.0.1:9090/")
    {

        $this->peer = null;

        $this->URL = $URL;

        $this->manager = new ManagerDummy();

    }

    /**
     *
     */
    public function startTransportProvider(AbstractPeer $peer, LoopInterface $loop)
    {
        $this->manager->info("Starting Transport\n");

        $this->peer = $peer;

        $this->loop = $loop;

        $this->connector = new \Ratchet\Client\Factory($this->loop);

        $this->connector->__invoke($this->URL, ['wamp.2.json'])->then(
            function (WebSocket $conn) {

                $this->manager->info("Pawl has connected\n");

                $transport = new PawlTransport($conn, $this->loop);

                $transport->setSerializer(new JsonSerializer());

                $this->peer->onOpen($transport);

                $conn->on(
                    'message',
                    function ($msg) use ($transport) {
                        $this->manager->info("Received: {$msg}\n");
                        $this->peer->onMessage($transport, $transport->getSerializer()->deserialize($msg));
                    }
                );

                $conn->on(
                    'close',
                    function ($conn) {
                        $this->manager->info("Pawl has closed\n");
                        $this->peer->onClose('close');
                    }
                );

                $conn->on(
                    'pong',
                    function ($frame, $ws) use ($transport) {
                        $transport->onPong($frame, $ws);
                    }
                );
            },
            function ($e) {
                $this->peer->onClose('unreachable');
                $this->manager->info("Could not connect: {$e->getMessage()}\n");
                // $this->loop->stop();
            }
        );

    }


    /**
     * @return AbstractPeer
     */
    public function getPeer()
    {
        return $this->peer;
    }


    /**
     * @param AbstractPeer $peer
     */
    public function setPeer(AbstractPeer $peer)
    {
        $this->peer = $peer;
    }

    /**
     * @param ManagerInterface $manager
     */
    public function setManager(ManagerInterface $manager)
    {
        $this->manager = $manager;

        $this->manager->info("Manager attached to PawlTransportProvider");
    }

    /**
     * @return ManagerInterface
     */
    public function getManager()
    {
        return $this->manager;
    }



}