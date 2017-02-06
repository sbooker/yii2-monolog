<?php

namespace Mero\Monolog;

use Mero\Monolog\Exception\HandlerNotFoundException;
use Mero\Monolog\Exception\LoggerNotFoundException;
use Mero\Monolog\Handler\Strategy;
use Mero\Monolog\Processor\LogRecordProcessor;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractHandler;
use Monolog\Logger;
use yii\base\Component;

/**
 * MonologComponent is an component for the Monolog library.
 *
 * @author Rafael Mello <merorafael@gmail.com>
 */
class MonologComponent extends Component
{
    /**
     * @var array Logger channels
     */
    private $channels;

    /** @var  array - configuration array */
    private $handlers;

    /** @var \callable[]  */
    private $processors;
    /**
     * @var Strategy Handler strategy to create factory
     */
    protected $strategy;

    public function __construct(array $config = [])
    {
        if ( !isset($config['handlers'])) {
            $config['handlers']['main'] = [
                'type'      => 'rotating_file',
                'path'      => '@app/runtime/logs/log.log',
                'level'     => 'debug',
            ];
        }
        if ( !isset($config['processor'])) {
            $config['processor'] = [];
        }

        $this->handlers = $config['handlers'];
        $this->processors = $this->buildProcessorList($config['processor']);
        unset($config['handlers'], $config['processor']);
        $this->strategy = new Strategy();
        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        foreach ($this->handlers as $key => $handlerConfig) {
            if (!isset($handlerConfig['channels']) || empty($handlerConfig['channels'])) {
                $handlerConfig['channels'] = ['main'];
            }
            $channels = $handlerConfig['channels'];
            unset($handlerConfig['channels']);
            foreach ($channels as $channel) {
                $this->channels[$channel]['handlers'][] = $handlerConfig;
            }
        }

        foreach ($this->channels as $name => $config) {
            $this->createChannel($name, $config);
        }
        parent::init();
    }

    /**
     * Create a logger channel.
     *
     * @param string $name   Logger channel name
     * @param array  $config Logger channel configuration
     *
     * @throws \InvalidArgumentException When the channel already exists
     * @throws HandlerNotFoundException  When a handler configuration is invalid
     */
    public function createChannel($name, array $config)
    {
        $handlers = [];

        if (!empty($config['handler']) && is_array($config['handler'])) {
            foreach ($config['handler'] as $handler) {
                if (!is_array($handler) && !$handler instanceof AbstractHandler) {
                    throw new HandlerNotFoundException();
                }
                if (is_array($handler)) {
                    $handlerObject = $this->createHandlerInstance($handler);
                    if (array_key_exists('formatter', $handler)){
                        $handlerObject->setFormatter(
                            $this->buildFormatter($handler['formatter'])
                        );
                    }
                } else {
                    $handlerObject = $handler;
                }
                $handlers[] = $handlerObject;
            }
        }

        $this->openChannel($name, $handlers, $this->processors);

        return;
    }

    /**
     * Close a open logger channel.
     *
     * @param string $name Logger channel name
     */
    public function closeChannel($name)
    {
        if (isset($this->channels[$name])) {
            unset($this->channels[$name]);
        }

        return;
    }

    /**
     * Checks if the given logger exists.
     *
     * @param string $name Logger name
     *
     * @return bool
     */
    public function hasLogger($name)
    {
        return isset($this->channels[$name]) && ($this->channels[$name] instanceof Logger);
    }

    /**
     * Return logger object.
     *
     * @param string $name Logger name
     *
     * @return Logger Logger object
     *
     * @throws LoggerNotFoundException
     */
    public function getLogger($name = 'main')
    {
        if (!$this->hasLogger($name)) {
            throw new LoggerNotFoundException(sprintf("Logger instance '%s' not found", $name));
        }

        return $this->channels[$name];
    }

    /**
     * Open a new logger channel.
     *
     * @param string $name       Logger channel name
     * @param array  $handlers   Handlers collection
     * @param array  $processors Processors collection
     */
    protected function openChannel($name, array $handlers, array $processors)
    {
        if (isset($this->channels[$name]) && $this->channels[$name] instanceof Logger) {
            throw new \InvalidArgumentException("Channel '{$name}' already exists");
        }

        $this->channels[$name] = new Logger($name, $handlers, $processors);

        return;
    }

    /**
     * Create handler instance.
     *
     * @param array $config Configuration parameters
     *
     * @return AbstractHandler
     */
    protected function createHandlerInstance(array $config)
    {
        $factory = $this->strategy->createFactory($config);

        return $factory->createHandler();
    }

    /**
     * @param mixed[] $processorList
     *
     * @return callable[]
     */
    private function buildProcessorList(array $processorList)
    {
        $dstProcessorList = [];
        foreach ($processorList as $processor) {
            if (is_string($processor)) {
                $processor = \Yii::$app->get($processor);
            }
            if ($processor instanceof LogRecordProcessor) {
                $dstProcessorList[] = function (array $record) use ($processor) {
                    return $processor->process($record);
                };
                continue;
            }
            if (!is_callable($processor)) {
                throw new \RuntimeException('Processor must be callable');
            }
            $dstProcessorList[] = $processor;
        }

        return $dstProcessorList;
    }

    /**
     * @param string | FormatterInterface $formatter
     *
     * @return FormatterInterface
     */
    private function buildFormatter($formatter)
    {
        if (is_string($formatter)) {
            $formatter = \Yii::$app->get($formatter);
        }
        if ($formatter instanceof FormatterInterface) {
            return $formatter;
        }

        throw new \RuntimeException('Formatter must be instance of ' . FormatterInterface::class);
    }
}
