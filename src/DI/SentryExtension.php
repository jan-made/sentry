<?php declare(strict_types = 1);

namespace Trejjam\Sentry\DI;

use Nette\DI\CompilerExtension;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Sentry\ClientBuilder;
use Sentry\ClientInterface;
use Sentry\SentrySdk;
use Sentry\State\Hub;
use Sentry\State\HubInterface;
use Tracy\ILogger;
use Trejjam\Sentry\Logger\SentryLogger;

final class SentryExtension extends CompilerExtension
{

	// phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
	/** @var SentryExtensionConfiguration */
	protected $config;
	// phpcs:enable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint

	public function __construct()
	{
		$this->config = new SentryExtensionConfiguration();
	}

	public function getConfigSchema(): Schema
	{
		return Expect::from(
			$this->config,
			[
				'disabled' => Expect::bool()->required(false)->default(false),
			]
		);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('clientBuilder'))
			->setFactory(
				ClientBuilder::class . '::create',
				[
					[
						'dsn' => $this->config->dsn,
					],
				]
			);

		$builder->addDefinition($this->prefix('client'))
			->setType(ClientInterface::class)
			->setFactory($this->prefix('@clientBuilder') . '::getClient');

		$builder->addDefinition($this->prefix('sentryHub'))
			->setType(HubInterface::class)
			->setFactory(Hub::class);

		$builder->addDefinition($this->prefix('sentrySdk'))
			->setFactory(SentrySdk::class . '::setCurrentHub')
			->setAutowired(false);

		if (interface_exists(ILogger::class)) {
			$builder->addDefinition($this->prefix('sentryLogger'))
				->setType(ILogger::class)
				->setFactory(SentryLogger::class, [
					'disabled' => $this->config->disabled,
				])
				->setAutowired(false);
		}
	}

	public function afterCompile(ClassType $class): void
	{
		if ($this->config->disabled) {
			return;
		}

		$initialize = $class->getMethod('initialize');

		$initialize->addBody('$this->getService(?);', [$this->prefix('sentrySdk')]);
	}

}
