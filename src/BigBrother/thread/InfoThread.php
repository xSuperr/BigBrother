<?php

declare(strict_types=1);

namespace BigBrother\thread;

use BigBrother\utils\InfoManager;
use ClassLoader;
use Exception;
use pocketmine\thread\Thread;
use ReflectionClass;
use Threaded;
use ThreadedLogger;

class InfoThread extends Thread
{
    public array $loadPaths;
    protected int $port;
    protected string $interface;
    protected ThreadedLogger $logger;
    protected ClassLoader $loader;
    protected string $data;
    protected bool $shutdown;

    protected Threaded $externalQueue;
    protected Threaded $internalQueue;

    /** @var resource */
    protected $externalSocket;
    /** @var resource */
    protected $internalSocket;

    public function __construct(ThreadedLogger $logger, ClassLoader $loader, int $port, string $interface = "0.0.0.0", string $motd = "Minecraft: PE server", string $icon = null)
    {
        $this->port = $port;
        if ($port < 1 or $port > 65536) {
            throw new Exception("Invalid port range");
        }

        $this->interface = $interface;
        $this->logger = $logger;
        $this->loader = $loader;

        $this->data = serialize([
            "motd" => $motd,
            "icon" => $icon
        ]);

        $loadPaths = [];
        $this->addDependency($loadPaths, new ReflectionClass($logger));
        $this->addDependency($loadPaths, new ReflectionClass($loader));
        $this->loadPaths = array_reverse($loadPaths);
        $this->shutdown = false;

        $this->externalQueue = new Threaded;
        $this->internalQueue = new Threaded;

        if (($sockets = stream_socket_pair((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? STREAM_PF_INET : STREAM_PF_UNIX), STREAM_SOCK_STREAM, STREAM_IPPROTO_IP)) === false) {
            throw new Exception("Could not create IPC streams. Reason: " . socket_strerror(socket_last_error()));
        }

        $this->internalSocket = $sockets[0];
        stream_set_blocking($this->internalSocket, false);
        $this->externalSocket = $sockets[1];
        stream_set_blocking($this->externalSocket, false);

        /*if($autoStart){
            $this->start();
        }*/
    }

    protected function addDependency(array &$loadPaths, ReflectionClass $dep)
    {
        if ($dep->getFileName() !== false) {
            $loadPaths[$dep->getName()] = $dep->getFileName();
        }

        if ($dep->getParentClass() instanceof ReflectionClass) {
            $this->addDependency($loadPaths, $dep->getParentClass());
        }

        foreach ($dep->getInterfaces() as $interface) {
            $this->addDependency($loadPaths, $interface);
        }
    }

    public function isShutdown(): bool
    {
        return $this->shutdown === true;
    }

    public function shutdown()
    {
        $this->shutdown = true;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getInterface(): string
    {
        return $this->interface;
    }
    public function getExternalQueue(): Threaded
    {
        return $this->externalQueue;
    }

    public function getInternalQueue(): Threaded
    {
        return $this->internalQueue;
    }

    public function getInternalSocket()
    {
        return $this->internalSocket;
    }

    public function pushMainToThreadPacket(string $str): void
    {
        $this->internalQueue[] = $str;
        @fwrite($this->externalSocket, "\xff", 1); //Notify
    }

    public function readMainToThreadPacket(): ?string
    {
        return $this->internalQueue->shift();
    }

    public function pushThreadToMainPacket(string $str): void
    {
        $this->externalQueue[] = $str;
    }

    public function readThreadToMainPacket(): ?string
    {
        return $this->externalQueue->shift();
    }

    public function shutdownHandler(): void
    {
        if ($this->shutdown !== true) {
            $this->getLogger()->emergency("[ServerThread #" . Thread::getCurrentThreadId() . "] ServerThread crashed!");
        }
    }

    public function getLogger(): ThreadedLogger
    {
        return $this->logger;
    }

    /**
     * @override
     */
    public function onRun(): void
    {
        //Load removed dependencies, can't use require_once()
        foreach ($this->loadPaths as $name => $path) {
            if (!class_exists($name, false) and !interface_exists($name, false)) {
                require $path;
            }
        }
        $this->loader->register();

        register_shutdown_function([$this, "shutdownHandler"]);

        $data = unserialize($this->data, ["allowed_classes" => false]);
        new InfoManager($this, $this->port, $this->interface, $data["motd"], $data["icon"]);
    }

    public function setGarbage()
    {
    }
}