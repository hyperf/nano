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
use Hyperf\Nano\Factory\ClosureProcess;
use Hyperf\Nano\Factory\CommandFactory;
use Hyperf\Nano\Factory\CronFactory;
use Hyperf\Nano\Factory\ExceptionHandlerFactory;
use Hyperf\Nano\Factory\MiddlewareFactory;
use Hyperf\Nano\Factory\ProcessFactory;
use Hyperf\Process\AbstractProcess;
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
    protected ConfigInterface $config;

    protected DispatcherFactory $dispatcherFactory;

    protected BoundInterface $bound;

    private string $serverName = 'http';

    public function __construct(protected ContainerInterface $container)
    {
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
    public function run(): void
    {
        $application = $this->container->get(\Hyperf\Contract\ApplicationInterface::class);
        $application->run();
    }

    /**
     * Config the application using arrays.
     */
    public function config(array $configs, int $flag = Constant::CONFIG_MERGE): void
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
     */
    public function addMiddleware(callable|MiddlewareInterface|string $middleware): void
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
     */
    public function addExceptionHandler(callable|string $exceptionHandler): void
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
    public function addListener(string $event, $listener = null, int $priority = 1): void
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
     */
    public function addGroup(array|string $prefix, callable $callback, array $options = []): void
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
    public function addCommand(string $signature, $command = null): Command
    {
        if ($command === null) {
            $command = $signature;
        }

        if (is_string($command)) {
            $this->appendConfig('commands', $command);
            return $this->container->get($command);
        }

        $command = Closure::fromCallable($command);
        /** @var CommandFactory $commandFactory */
        $commandFactory = $this->container->get(CommandFactory::class);
        $handler = $commandFactory->create($signature, $command->bindTo($this->bound, $this->bound));

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
     */
    public function addCrontab(string $rule, callable|string $crontab): Crontab
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
     */
    public function addProcess(callable|string $process): AbstractProcess|ClosureProcess
    {
        if (is_string($process)) {
            $this->appendConfig('processes', $process);
            return $this->container->get($process);
        }

        $callback = \Closure::fromCallable($process);
        $callback = $callback->bindTo($this->bound, $this->bound);
        $processFactory = $this->container->get(ProcessFactory::class);

        return tap($processFactory->create($callback), function ($process) {
            $processId = spl_object_hash($process);
            $this->container->set($processId, $process);
            $this->appendConfig(
                'processes',
                $processId
            );
        });
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
    public function addServer(string $serverName, callable $callback): void
    {
        $this->serverName = $serverName;
        call($callback, [$this]);
        $this->serverName = 'http';
    }

    private function appendConfig(string $key, $configValues): void
    {
        $configs = $this->config->get($key, []);
        array_push($configs, $configValues);
        $this->config->set($key, $configs);
    }

    private function ensureConfigHasValue(string $key, $configValues): void
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

    private function addConfig(string $key, $configValues, $flag): void
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

    private function convertClosureToMiddleware(array &$middlewares): void
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
