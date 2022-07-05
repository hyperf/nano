<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf Nano.
 *
 * @link     https://www.hyperf.io
 * @document https://nano.hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/nano/blob/master/LICENSE
 */
namespace Hyperf\Nano;

use Closure;
use Hyperf\Command\Command;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Crontab\Crontab;
use Hyperf\Crontab\Process\CrontabDispatcherProcess;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\Nano\Factory\CommandFactory;
use Hyperf\Nano\Factory\CronFactory;
use Hyperf\Nano\Factory\ExceptionHandlerFactory;
use Hyperf\Nano\Factory\MiddlewareFactory;
use Hyperf\Nano\Factory\ProcessFactory;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * @method get($route, $handler, array $options = [])
 * @method post($route, $handler, array $options = [])
 * @method put($route, $handler, array $options = [])
 * @method delete($route, $handler, array $options = [])
 * @method patch($route, $handler, array $options = [])
 * @method head($route, $handler, array $options = [])
 */
class App
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var DispatcherFactory
     */
    protected $dispatcherFactory;

    /**
     * @var BoundInterface
     */
    protected $bound;

    private $serverName = 'http';

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->config = $this->container->get(ConfigInterface::class);
        $this->dispatcherFactory = $this->container->get(DispatcherFactory::class);
        $this->bound = $this->container->has(BoundInterface::class)
            ? $this->container->get(BoundInterface::class)
            : new ContainerProxy($this->container);
    }

    public function __call($name, $arguments)
    {
        $router = $this->dispatcherFactory->getRouter($this->serverName);
        if ($arguments[1] instanceof \Closure) {
            $arguments[1] = $arguments[1]->bindTo($this->bound, $this->bound);
        }
        return $router->{$name}(...$arguments);
    }

    /**
     * Run the application.
     */
    public function run()
    {
        $application = $this->container->get(\Hyperf\Contract\ApplicationInterface::class);
        $application->run();
    }

    /**
     * Config the application using arrays.
     */
    public function config(array $configs, int $flag = Constant::CONFIG_MERGE)
    {
        foreach ($configs as $key => $value) {
            $this->addConfig($key, $value, $flag);
        }
    }

    /**
     * Get the dependency injection container.
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Add a middleware globally.
     * @param callable|MiddlewareInterface|string $middleware
     */
    public function addMiddleware($middleware)
    {
        if ($middleware instanceof MiddlewareInterface || is_string($middleware)) {
            $this->appendConfig('middlewares.' . $this->serverName, $middleware);
            return;
        }

        $middleware = Closure::fromCallable($middleware);
        $middlewareFactory = $this->container->get(MiddlewareFactory::class);
        $this->appendConfig(
            'middlewares.' . $this->serverName,
            $middlewareFactory->create($middleware->bindTo($this->bound, $this->bound))
        );
    }

    /**
     * Add an exception handler globally.
     * @param callable|string $exceptionHandler
     */
    public function addExceptionHandler($exceptionHandler)
    {
        if (is_string($exceptionHandler)) {
            $this->appendConfig('exceptions.handler.' . $this->serverName, $exceptionHandler);
            return;
        }

        $exceptionHandler = Closure::fromCallable($exceptionHandler);
        $exceptionHandlerFactory = $this->container->get(ExceptionHandlerFactory::class);
        $handler = $exceptionHandlerFactory->create($exceptionHandler->bindTo($this->bound, $this->bound));
        $handlerId = spl_object_hash($handler);
        $this->container->set($handlerId, $handler);
        $this->appendConfig(
            'exceptions.handler.' . $this->serverName,
            $handlerId
        );
    }

    /**
     * Add an listener globally.
     * @param null|callable|string $listener
     */
    public function addListener(string $event, $listener = null, int $priority = 1)
    {
        if ($listener === null) {
            $listener = $event;
        }

        if (is_string($listener)) {
            $this->appendConfig('listeners', $listener);
            return;
        }

        $listener = Closure::fromCallable($listener);
        $listener = $listener->bindTo($this->bound, $this->bound);
        $provider = $this->container->get(ListenerProviderInterface::class);
        $provider->on($event, $listener, $priority);
    }

    /**
     * Add a route group.
     * @param array|string $prefix
     */
    public function addGroup($prefix, callable $callback, array $options = []): void
    {
        $router = $this->dispatcherFactory->getRouter($this->serverName);
        if (isset($options['middleware'])) {
            $this->convertClosureToMiddleware($options['middleware']);
        }
        $router->addGroup($prefix, $callback, $options);
    }

    /**
     * Add a new command.
     * @param null|callable|string $command
     */
    public function addCommand(string $name, $command = null): Command
    {
        if ($command === null) {
            $command = $name;
        }

        if (is_string($command)) {
            $this->appendConfig('commands', $command);
            return $this->container->get($command);
        }

        $command = Closure::fromCallable($command);
        /** @var CommandFactory $commandFactory */
        $commandFactory = $this->container->get(CommandFactory::class);
        $handler = $commandFactory->create($name, $command->bindTo($this->bound, $this->bound));

        return tap(
            $handler,
            function ($handler) {
                $handlerId = spl_object_hash($handler);
                $this->container->set($handlerId, $handler);
                $this->appendConfig(
                    'commands',
                    $handlerId
                );
            }
        );
    }

    /**
     * Add a new crontab.
     * @param callable|string $crontab
     */
    public function addCrontab(string $rule, $crontab): Crontab
    {
        $this->config->set('crontab.enable', true);
        $this->ensureConfigHasValue('processes', CrontabDispatcherProcess::class);

        if ($crontab instanceof Crontab) {
            $this->appendConfig('crontab.crontab', $crontab);
            return $crontab;
        }

        $callback = \Closure::fromCallable($crontab);
        $callback = $callback->bindTo($this->bound, $this->bound);
        $callbackId = spl_object_hash($callback);
        $this->container->set($callbackId, $callback);
        $this->ensureConfigHasValue('processes', CrontabDispatcherProcess::class);
        $this->config->set('crontab.enable', true);

        return tap(
            (new Crontab())
                ->setName(uniqid())
                ->setRule($rule)
                ->setCallback([CronFactory::class, 'execute', [$callbackId]]),
            function ($crontab) {
                $this->appendConfig(
                    'crontab.crontab',
                    $crontab
                );
            }
        );
    }

    /**
     * Add a new process.
     * @param callable|string $process
     */
    public function addProcess($process)
    {
        if (is_string($process)) {
            $this->appendConfig('processes', $process);
            return;
        }

        $callback = \Closure::fromCallable($process);
        $callback = $callback->bindTo($this->bound, $this->bound);
        $processFactory = $this->container->get(ProcessFactory::class);
        $process = $processFactory->create($callback);
        $processId = spl_object_hash($process);
        $this->container->set($processId, $process);
        $this->appendConfig(
            'processes',
            $processId
        );
    }

    /**
     * Add a new route.
     * @param mixed $httpMethod
     * @param mixed $handler
     */
    public function addRoute($httpMethod, string $route, $handler, array $options = []): void
    {
        $router = $this->dispatcherFactory->getRouter($this->serverName);
        if (isset($options['middleware'])) {
            $this->convertClosureToMiddleware($options['middleware']);
        }
        if ($handler instanceof \Closure) {
            $handler = $handler->bindTo($this->bound, $this->bound);
        }
        $router->addRoute($httpMethod, $route, $handler, $options);
    }

    /**
     * Add a server.
     */
    public function addServer(string $serverName, callable $callback)
    {
        $this->serverName = $serverName;
        call($callback, [$this]);
        $this->serverName = 'http';
    }

    private function appendConfig(string $key, $configValues)
    {
        $configs = $this->config->get($key, []);
        array_push($configs, $configValues);
        $this->config->set($key, $configs);
    }

    private function ensureConfigHasValue(string $key, $configValues)
    {
        $config = $this->config->get($key, []);
        if (! is_array($config)) {
            return;
        }

        if (in_array($configValues, $config)) {
            return;
        }

        array_push($config, $configValues);
        $this->config->set($key, $config);
    }

    private function addConfig(string $key, $configValues, $flag)
    {
        $config = $this->config->get($key);

        if (! is_array($config)) {
            $this->config->set($key, $configValues);
            return;
        }

        if ($flag === Constant::CONFIG_MERGE) {
            $this->config->set($key, array_merge_recursive($config, $configValues));
        } else {
            $this->config->set($key, array_merge($config, $configValues));
        }
    }

    private function convertClosureToMiddleware(array &$middlewares)
    {
        $middlewareFactory = $this->container->get(MiddlewareFactory::class);
        foreach ($middlewares as &$middleware) {
            if ($middleware instanceof \Closure) {
                $middleware = $middleware->bindTo($this->bound, $this->bound);
                $middleware = $middlewareFactory->create($middleware);
            }
        }
    }
}
